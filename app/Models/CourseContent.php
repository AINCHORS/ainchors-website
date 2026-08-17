<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseContent extends Model
{
    protected $fillable = [
        'product_id', 'video_title', 'video_provider', 'video_url',
        'video_duration_seconds', 'slide_name', 'slide_url',
    ];

    protected function casts(): array
    {
        return ['video_duration_seconds' => 'integer'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
