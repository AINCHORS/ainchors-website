<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\User;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageMembershipController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->assertPackage($product);
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $course = Product::query()->findOrFail((int) $data['course_id']);
        $this->assertCourseCanJoinPackage($product, $course);

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $course): void {
            $exists = ProductRelation::query()
                ->where('parent_product_id', $product->id)
                ->where('child_product_id', $course->id)
                ->where('relation_type', 'bundle_item')
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'course_id' => 'This course is already included in the package.',
                ]);
            }

            $before = $this->membershipSnapshot($product);
            $nextSortOrder = (int) ProductRelation::query()
                ->where('parent_product_id', $product->id)
                ->where('relation_type', 'bundle_item')
                ->max('sort_order') + 1;

            ProductRelation::query()->create([
                'parent_product_id' => $product->id,
                'child_product_id' => $course->id,
                'relation_type' => 'bundle_item',
                'sort_order' => $nextSortOrder,
            ]);

            $this->audit->record(
                $admin,
                'PACKAGE_COURSE_ADDED',
                $product,
                $before,
                $this->membershipSnapshot($product),
            );
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Course added to package.');
    }

    public function reorder(Request $request, Product $product): RedirectResponse
    {
        $this->assertPackage($product);
        $data = $request->validate([
            'positions' => ['required', 'array', 'min:1'],
            'positions.*' => ['required', 'integer', 'min:1', 'max:9999', 'distinct'],
        ]);

        $relations = ProductRelation::query()
            ->where('parent_product_id', $product->id)
            ->where('relation_type', 'bundle_item')
            ->get(['id', 'child_product_id', 'sort_order']);

        $submittedIds = collect(array_keys($data['positions']))->map(fn ($id) => (int) $id)->sort()->values();
        $existingIds = $relations->pluck('child_product_id')->map(fn ($id) => (int) $id)->sort()->values();

        if ($submittedIds->all() !== $existingIds->all()) {
            throw ValidationException::withMessages([
                'positions' => 'Reorder data must contain every course currently in the package exactly once.',
            ]);
        }

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $relations, $data): void {
            $before = $this->membershipSnapshot($product);

            $ordered = $relations->sort(function ($left, $right) use ($data): int {
                $leftPosition = (int) $data['positions'][(string) $left->child_product_id];
                $rightPosition = (int) $data['positions'][(string) $right->child_product_id];

                return $leftPosition <=> $rightPosition ?: $left->child_product_id <=> $right->child_product_id;
            })->values();

            foreach ($ordered as $index => $relation) {
                ProductRelation::query()->whereKey($relation->id)->update(['sort_order' => $index + 1]);
            }

            $this->audit->record(
                $admin,
                'PACKAGE_COURSES_REORDERED',
                $product,
                $before,
                $this->membershipSnapshot($product),
            );
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Package course order updated.');
    }

    public function destroy(Request $request, Product $product, Product $course): RedirectResponse
    {
        $this->assertPackage($product);

        if (! $course->isCourse()) {
            abort(404);
        }

        $relation = ProductRelation::query()
            ->where('parent_product_id', $product->id)
            ->where('child_product_id', $course->id)
            ->where('relation_type', 'bundle_item')
            ->firstOrFail();

        $memberCount = ProductRelation::query()
            ->where('parent_product_id', $product->id)
            ->where('relation_type', 'bundle_item')
            ->count();

        if ($product->status === 'active' && $memberCount <= 1) {
            throw ValidationException::withMessages([
                'package' => 'An active package must contain at least one course. Add another course or deactivate the package first.',
            ]);
        }

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $relation): void {
            $before = $this->membershipSnapshot($product);
            $relation->delete();
            $this->normalizeSortOrder($product);

            $this->audit->record(
                $admin,
                'PACKAGE_COURSE_REMOVED',
                $product,
                $before,
                $this->membershipSnapshot($product),
            );
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Course removed from package.');
    }

    private function assertPackage(Product $product): void
    {
        if (! $product->isPackage()) {
            abort(404);
        }
    }

    private function assertCourseCanJoinPackage(Product $package, Product $course): void
    {
        if ($course->id === $package->id || ! $course->isCourse()) {
            throw ValidationException::withMessages([
                'course_id' => 'Only course products can be added to a course package.',
            ]);
        }

        if ($package->status !== 'active') {
            return;
        }

        $configured = $course->courseContent()
            ->whereNotNull('video_url')
            ->where('video_url', '!=', '')
            ->exists();

        if ($course->status !== 'active' || ! $configured) {
            throw ValidationException::withMessages([
                'course_id' => 'An active package can only include active courses with protected course content configured.',
            ]);
        }
    }

    private function normalizeSortOrder(Product $package): void
    {
        $relations = ProductRelation::query()
            ->where('parent_product_id', $package->id)
            ->where('relation_type', 'bundle_item')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($relations as $index => $relation) {
            ProductRelation::query()->whereKey($relation->id)->update(['sort_order' => $index + 1]);
        }
    }

    /** @return array<string, mixed> */
    private function membershipSnapshot(Product $package): array
    {
        return [
            'package_id' => $package->id,
            'course_ids' => ProductRelation::query()
                ->where('parent_product_id', $package->id)
                ->where('relation_type', 'bundle_item')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('child_product_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ];
    }
}
