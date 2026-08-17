<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id', 'visitor_session_id', 'user_id', 'event_type', 'event_name',
        'page_url', 'related_type', 'related_id', 'active_seconds', 'metadata',
    ];

    protected function casts(): array
    {
        return ['active_seconds' => 'integer', 'metadata' => 'array'];
    }

    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }
    public function visitorSession(): BelongsTo { return $this->belongsTo(VisitorSession::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
