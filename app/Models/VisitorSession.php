<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id', 'user_id', 'session_uuid', 'started_at', 'ended_at',
        'landing_url', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_content', 'utm_term', 'device_type', 'browser', 'operating_system',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function activityEvents(): HasMany { return $this->hasMany(ActivityEvent::class); }
}
