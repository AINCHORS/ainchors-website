<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    protected $fillable = [
        'job_position_id', 'full_name', 'email', 'phone', 'interview_available_on',
        'interview_availability_notes', 'resume_disk', 'resume_path',
        'resume_original_name', 'resume_mime', 'resume_size', 'short_note',
        'recruitment_consent_at', 'status', 'reviewed_by', 'reviewed_at',
        'status_changed_at', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'interview_available_on' => 'date',
            'recruitment_consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'resume_size' => 'integer',
        ];
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobApplicationStatusHistory::class);
    }
}
