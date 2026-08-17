<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    protected $fillable = ['visitor_uuid', 'linked_user_id', 'first_seen_at', 'last_seen_at'];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function linkedUser(): BelongsTo { return $this->belongsTo(User::class, 'linked_user_id'); }
    public function sessions(): HasMany { return $this->hasMany(VisitorSession::class); }
    public function activityEvents(): HasMany { return $this->hasMany(ActivityEvent::class); }
    public function privacyConsents(): HasMany { return $this->hasMany(PrivacyConsent::class); }
    public function workflowAudits(): HasMany { return $this->hasMany(WorkflowAudit::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
}
