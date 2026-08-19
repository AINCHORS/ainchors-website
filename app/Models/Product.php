<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'type', 'sku', 'name', 'slug', 'short_description', 'description',
        'image', 'price', 'currency', 'billing_type', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'metadata' => 'array'];
    }

    public function courseContent(): HasOne
    {
        return $this->hasOne(CourseContent::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function serviceEngagements(): HasMany
    {
        return $this->hasMany(ServiceEngagement::class);
    }

    public function childRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'parent_product_id');
    }

    public function parentRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'child_product_id');
    }

    public function bundleProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'parent_product_id', 'child_product_id')
            ->wherePivot('relation_type', 'bundle_item')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isCourse(): bool
    {
        return $this->type === 'course';
    }

    public function isPackage(): bool
    {
        return $this->type === 'course_package';
    }

    public function listPrice(): float
    {
        return (float) (data_get($this->metadata, 'pricing.original_price')
            ?? data_get($this->metadata, 'original_price')
            ?? $this->price);
    }
}
