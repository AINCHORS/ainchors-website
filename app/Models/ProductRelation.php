<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRelation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['parent_product_id', 'child_product_id', 'relation_type', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function parentProduct(): BelongsTo { return $this->belongsTo(Product::class, 'parent_product_id'); }
    public function childProduct(): BelongsTo { return $this->belongsTo(Product::class, 'child_product_id'); }
}
