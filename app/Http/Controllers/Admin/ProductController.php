<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $products = $this->safeProductQuery()
            ->withCount(['enrollments', 'orderItems'])
            ->with($this->safeCourseContentRelation())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->value()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProductData($request);
        $this->assertCourseCanBeActivated($data['type'], $data['status']);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        /** @var Product $product */
        $product = DB::transaction(function () use ($data, $admin): Product {
            $product = Product::query()->create($data);
            $this->audit->record($admin, 'PRODUCT_CREATED', $product, [], $this->auditData($product));

            return $product;
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product created. Course products remain drafts until protected course metadata is configured.');
    }

    public function show(Product $product): View
    {
        $product = $this->safeProductQuery()
            ->withCount(['enrollments', 'orderItems'])
            ->with([
                ...$this->safeCourseContentRelation(),
                'childRelations.childProduct:id,name,slug,type,status',
                'parentRelations.parentProduct:id,name,slug,type,status',
            ])
            ->findOrFail($product->getKey());

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product = $this->safeProductQuery()
            ->with($this->safeCourseContentRelation())
            ->findOrFail($product->getKey());

        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedProductData($request, $product);
        $this->assertTypeAndSlugChangesAreSafe($product, $data);
        $this->assertCourseCanBeActivated($data['type'], $data['status'], $product);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $data): void {
            $before = $this->auditData($product);
            $wasDeactivated = $product->status === 'active' && $data['status'] !== 'active';

            $product->fill($data);

            if (! $product->isDirty()) {
                return;
            }

            $product->save();
            $this->audit->record(
                $admin,
                $wasDeactivated ? 'PRODUCT_DISABLED' : 'PRODUCT_UPDATED',
                $product,
                $before,
                $this->auditData($product),
            );
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated.');
    }

    public function updateStatus(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ]);
        $this->assertCourseCanBeActivated($product->type, $data['status'], $product);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $data): void {
            $before = $this->auditData($product);

            if ($product->status === $data['status']) {
                return;
            }

            $product->forceFill(['status' => $data['status']])->save();
            $action = $data['status'] === 'inactive' ? 'PRODUCT_DISABLED' : 'PRODUCT_STATUS_CHANGED';
            $this->audit->record($admin, $action, $product, $before, $this->auditData($product));
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product status updated.');
    }

    /** @return array<string, mixed> */
    private function validatedProductData(Request $request, ?Product $product = null): array
    {
        $request->merge(['currency' => strtoupper((string) $request->input('currency', 'USD'))]);

        // Status is intentionally controlled through the dedicated status
        // action on an existing product; preserve it when editing catalogue
        // copy and pricing through the normal edit form.
        if ($product && ! $request->has('status')) {
            $request->merge(['status' => $product->status]);
        }

        return $request->validate([
            'type' => ['required', Rule::in(['course', 'course_package', 'consulting', 'service'])],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'billing_type' => ['required', Rule::in(['one_time', 'monthly', 'custom'])],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function assertTypeAndSlugChangesAreSafe(Product $product, array $data): void
    {
        if ($product->type !== $data['type'] && (
            $product->courseContent()->exists()
            || $product->orderItems()->exists()
            || $product->enrollments()->exists()
        )) {
            throw ValidationException::withMessages([
                'type' => 'A product with course content, orders, or enrollments cannot change type.',
            ]);
        }

        if ($product->isCourse() && $product->slug !== $data['slug'] && $product->courseContent()->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'A course slug cannot change after protected course content is configured.',
            ]);
        }
    }

    private function assertCourseCanBeActivated(string $type, string $status, ?Product $product = null): void
    {
        if ($type !== 'course' || $status !== 'active') {
            return;
        }

        $hasConfiguredVideo = $product
            && $product->courseContent()->whereNotNull('video_url')->where('video_url', '!=', '')->exists();

        if (! $hasConfiguredVideo) {
            throw ValidationException::withMessages([
                'status' => 'A course can only be active after protected course video metadata has been configured.',
            ]);
        }
    }

    /** @return array<string, int|float|string|null> */
    private function auditData(Product $product): array
    {
        return [
            'id' => $product->id,
            'type' => $product->type,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'price' => $product->price,
            'currency' => $product->currency,
            'billing_type' => $product->billing_type,
            'status' => $product->status,
        ];
    }

    private function safeProductQuery()
    {
        return Product::query()->select([
            'id', 'type', 'sku', 'name', 'slug', 'short_description',
            'description', 'image', 'price', 'currency', 'billing_type',
            'status', 'created_at', 'updated_at',
        ]);
    }

    /** @return array<string, \Closure> */
    private function safeCourseContentRelation(): array
    {
        return [
            'courseContent' => fn ($query) => $query->select([
                'id', 'product_id', 'video_title', 'video_provider',
                'video_duration_seconds', 'slide_name', 'lesson_content',
                'created_at', 'updated_at',
            ]),
        ];
    }
}
