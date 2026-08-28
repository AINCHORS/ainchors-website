<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Admin\AuditService;
use App\Services\Products\ProductBillingRules;
use App\Services\Products\ProductImagePath;
use App\Services\Products\ProductReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ProductReadinessService $readiness,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $products = $this->safeProductQuery()
            ->withCount(['enrollments', 'orderItems'])
            ->with([
                ...$this->safeCourseContentRelation(),
                ...$this->safePackageRelations(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->value()))
            ->when(
                in_array($request->input('course_category'), array_keys(Product::COURSE_CATEGORIES), true),
                fn ($query) => $query->where('course_category', $request->string('course_category')->value()),
            )
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when(in_array($request->input('readiness'), ['ready', 'incomplete'], true), function ($query) use ($request): void {
                $this->applyReadinessFilter($query, $request->string('readiness')->value());
            })
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $readinessByProduct = $products->getCollection()->mapWithKeys(
            fn (Product $product): array => [$product->id => $this->readiness->inspect($product)],
        );

        return view('admin.products.index', compact('products', 'readinessByProduct'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProductData($request);
        $this->assertProductCanBeActivated($data);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        /** @var Product $product */
        $product = DB::transaction(function () use ($data, $admin): Product {
            $product = Product::query()->create($data);
            $this->audit->record($admin, 'PRODUCT_CREATED', $product, [], $this->auditData($product));

            return $product;
        });

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product created. Course products and packages must be fully configured before activation.');
    }

    public function show(Product $product): View
    {
        $product = $this->safeProductQuery()
            ->withCount(['enrollments', 'orderItems'])
            ->with([
                ...$this->safeCourseContentRelation(),
                ...$this->safePackageRelations(),
                'parentRelations.parentProduct:id,name,slug,type,status',
            ])
            ->findOrFail($product->getKey());
        $readiness = $this->readiness->inspect($product);

        return view('admin.products.show', compact('product', 'readiness'));
    }

    public function edit(Product $product): View
    {
        $product = $this->safeProductQuery()
            ->with($this->safeCourseContentRelation())
            ->findOrFail($product->getKey());
        $typeChange = $this->productTypeChangeState($product);

        return view('admin.products.edit', compact('product', 'typeChange'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedProductData($request, $product);
        $this->assertTypeAndSlugChangesAreSafe($product, $data);
        $this->assertCourseDeactivationIsSafe($product, $data['status']);
        $this->assertProductCanBeActivated($data, $product);

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

        $this->assertCourseDeactivationIsSafe($product, $data['status']);
        $this->assertProductCanBeActivated([
            ...$product->only(['type', 'sku', 'name', 'slug', 'price', 'currency', 'billing_type']),
            'status' => $data['status'],
        ], $product);

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
        $image = $request->input('image');

        $request->merge([
            'currency' => strtoupper((string) $request->input('currency', 'USD')),
            'image' => is_string($image) || $image === null
                ? ProductImagePath::normalize($image)
                : $image,
        ]);

        if ($product && ! $request->has('status')) {
            $request->merge(['status' => $product->status]);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['course', 'course_package', 'consulting', 'service'])],
            'course_category' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $request->input('type') === 'course'),
                Rule::in(array_keys(Product::COURSE_CATEGORIES)),
            ],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ProductImagePath::isSafe((string) $value) || ! ProductImagePath::exists((string) $value)) {
                        $fail('The catalogue image must be an existing approved image inside public/assets.');
                    }
                },
            ],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => [
                'required',
                'string',
                Rule::in(array_keys(config('commerce.supported_currencies', []))),
            ],
            'billing_type' => [
                'required',
                Rule::in(ProductBillingRules::supported()),
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $productType = (string) $request->input('type');

                    if (! ProductBillingRules::allows($productType, (string) $value)) {
                        $fail(ProductBillingRules::validationMessage($productType));
                    }
                },
            ],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ]);

        if ($data['type'] !== 'course') {
            $data['course_category'] = null;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function assertTypeAndSlugChangesAreSafe(Product $product, array $data): void
    {
        if ($product->type !== $data['type']) {
            $typeChange = $this->productTypeChangeState($product);

            if (! $typeChange['editable']) {
                throw ValidationException::withMessages([
                    'type' => $typeChange['message'],
                ]);
            }
        }

        if ($product->isCourse() && $product->slug !== $data['slug'] && $product->courseContent()->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'A course slug cannot change after protected course content is configured.',
            ]);
        }
    }

    private function assertCourseDeactivationIsSafe(Product $product, string $newStatus): void
    {
        if (! $product->isCourse() || $product->status !== 'active' || $newStatus === 'active') {
            return;
        }

        $activeParentPackageExists = $product->parentRelations()
            ->where('relation_type', 'bundle_item')
            ->whereHas('parentProduct', fn ($query) => $query
                ->where('type', 'course_package')
                ->where('status', 'active'))
            ->exists();

        if ($activeParentPackageExists) {
            throw ValidationException::withMessages([
                'status' => 'Deactivate the active package containing this course before deactivating the course.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function assertProductCanBeActivated(array $data, ?Product $product = null): void
    {
        if ($data['status'] !== 'active') {
            return;
        }

        $candidate = $product ? clone $product : new Product();
        $candidate->forceFill($data);
        $candidate->unsetRelations();
        $readiness = $this->readiness->inspect($candidate);

        if (! $readiness['ready']) {
            throw ValidationException::withMessages([
                'status' => $readiness['summary'],
            ]);
        }
    }

    /** @return array{editable: bool, blockers: array<int, string>, message: string} */
    private function productTypeChangeState(Product $product): array
    {
        $blockers = [];

        if ($product->status !== 'draft') {
            $blockers[] = 'the product status is not Draft';
        }

        if ($product->orderItems()->exists()) {
            $blockers[] = 'orders reference this product';
        }

        if ($product->enrollments()->exists()) {
            $blockers[] = 'enrollments reference this product';
        }

        if ($product->courseContent()->exists()) {
            $blockers[] = 'course content is configured';
        }

        if ($product->childRelations()->exists() || $product->parentRelations()->exists()) {
            $blockers[] = 'package or product relations exist';
        }

        if (Schema::hasTable('service_engagements') && $product->serviceEngagements()->exists()) {
            $blockers[] = 'service engagements reference this product';
        }

        $editable = $blockers === [];

        return [
            'editable' => $editable,
            'blockers' => $blockers,
            'message' => $editable
                ? 'Product type can be changed while this Draft product has no business dependencies.'
                : 'Product type cannot be changed because '.implode(', ', $blockers).'.',
        ];
    }

    /** @return array<string, int|float|string|null> */
    private function auditData(Product $product): array
    {
        return [
            'id' => $product->id,
            'type' => $product->type,
            'course_category' => $product->course_category,
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
            'id', 'type', 'course_category', 'sku', 'name', 'slug', 'short_description',
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
                'video_url', 'video_original_name', 'video_file_size', 'video_duration_seconds',
                'slide_name', 'slide_url', 'slide_original_name', 'slide_file_size', 'lesson_content',
                'created_at', 'updated_at',
            ]),
        ];
    }

    /** @return array<string, \Closure> */
    private function safePackageRelations(): array
    {
        return [
            'childRelations' => fn ($query) => $query
                ->select(['id', 'parent_product_id', 'child_product_id', 'relation_type', 'sort_order', 'created_at'])
                ->where('relation_type', 'bundle_item')
                ->orderBy('sort_order'),
            'childRelations.childProduct' => fn ($query) => $query->select([
                'id', 'name', 'slug', 'sku', 'type', 'status', 'price', 'currency', 'billing_type',
            ]),
            'childRelations.childProduct.courseContent' => fn ($query) => $query->select([
                'id', 'product_id', 'video_title', 'video_url', 'slide_url',
            ]),
        ];
    }

    private function applyReadinessFilter($query, string $readiness): void
    {
        if ($readiness === 'ready') {
            $query->where(function ($readyQuery): void {
                $readyQuery
                    ->where(function ($serviceQuery): void {
                        $serviceQuery->whereIn('type', ['consulting', 'service'])
                            ->whereIn('billing_type', ProductBillingRules::supported())
                            ->whereNotNull('price');
                    })
                    ->orWhere(function ($courseQuery): void {
                        $courseQuery->where('type', 'course')
                            ->where('billing_type', ProductBillingRules::ONE_TIME)
                            ->whereNotNull('price')
                            ->whereHas('courseContent', fn ($contentQuery) => $contentQuery->whereNotNull('video_url')->where('video_url', '!=', ''));
                    })
                    ->orWhere(function ($packageQuery): void {
                        $packageQuery->where('type', 'course_package')
                            ->where('billing_type', ProductBillingRules::ONE_TIME)
                            ->whereNotNull('price')
                            ->whereHas('childRelations', fn ($relationQuery) => $relationQuery->where('relation_type', 'bundle_item'))
                            ->whereDoesntHave('childRelations', function ($relationQuery): void {
                                $relationQuery->where('relation_type', 'bundle_item')
                                    ->whereHas('childProduct', function ($courseQuery): void {
                                        $courseQuery->where(function ($invalidQuery): void {
                                            $invalidQuery->where('type', '!=', 'course')
                                                ->orWhere('status', '!=', 'active')
                                                ->orWhereDoesntHave('courseContent', fn ($contentQuery) => $contentQuery->whereNotNull('video_url')->where('video_url', '!=', ''));
                                        });
                                    });
                            });
                    });
            });

            return;
        }

        $query->where(function ($incompleteQuery): void {
            $incompleteQuery
                ->where(function ($serviceQuery): void {
                    $serviceQuery->whereIn('type', ['consulting', 'service'])
                        ->where(function ($invalidServiceQuery): void {
                            $invalidServiceQuery
                                ->whereNotIn('billing_type', ProductBillingRules::supported())
                                ->orWhereNull('price');
                        });
                })
                ->orWhere(function ($courseQuery): void {
                    $courseQuery->where('type', 'course')
                        ->where(function ($invalidCourseQuery): void {
                            $invalidCourseQuery->where('billing_type', '!=', ProductBillingRules::ONE_TIME)
                                ->orWhereNull('price')
                                ->orWhereDoesntHave('courseContent', fn ($contentQuery) => $contentQuery->whereNotNull('video_url')->where('video_url', '!=', ''));
                        });
                })
                ->orWhere(function ($packageQuery): void {
                    $packageQuery->where('type', 'course_package')
                        ->where(function ($invalidPackageQuery): void {
                            $invalidPackageQuery
                                ->where('billing_type', '!=', ProductBillingRules::ONE_TIME)
                                ->orWhereNull('price')
                                ->orWhereDoesntHave('childRelations', fn ($relationQuery) => $relationQuery->where('relation_type', 'bundle_item'))
                                ->orWhereHas('childRelations', function ($relationQuery): void {
                                    $relationQuery->where('relation_type', 'bundle_item')
                                        ->whereHas('childProduct', function ($courseQuery): void {
                                            $courseQuery->where(function ($invalidCourseQuery): void {
                                                $invalidCourseQuery->where('type', '!=', 'course')
                                                    ->orWhere('status', '!=', 'active')
                                                    ->orWhereDoesntHave('courseContent', fn ($contentQuery) => $contentQuery->whereNotNull('video_url')->where('video_url', '!=', ''));
                                            });
                                        });
                                });
                        });
                });
        });
    }
}
