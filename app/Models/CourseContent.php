<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseContent extends Model
{
    protected $fillable = [
        'product_id', 'video_title', 'video_provider', 'video_url', 'video_original_name', 'video_file_size',
        'video_duration_seconds', 'slide_name', 'slide_url', 'slide_original_name', 'slide_file_size', 'lesson_content',
    ];

    protected function casts(): array
    {
        return ['video_duration_seconds' => 'integer', 'video_file_size' => 'integer', 'slide_file_size' => 'integer', 'lesson_content' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
