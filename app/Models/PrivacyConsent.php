<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id', 'user_id', 'consent_type', 'consent_version', 'granted',
        'granted_at', 'revoked_at', 'source',
    ];

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
