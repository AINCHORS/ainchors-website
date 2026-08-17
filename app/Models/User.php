<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'role', 'full_name', 'email', 'password', 'phone', 'country',
        'profile_picture', 'status', 'email_verified_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function workflowAudits(): HasMany { return $this->hasMany(WorkflowAudit::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
    public function consultationRequests(): HasMany { return $this->hasMany(ConsultationRequest::class); }
    public function assignedConsultationRequests(): HasMany { return $this->hasMany(ConsultationRequest::class, 'assigned_admin_id'); }
    public function serviceEngagements(): HasMany { return $this->hasMany(ServiceEngagement::class); }
    public function assignedServiceEngagements(): HasMany { return $this->hasMany(ServiceEngagement::class, 'assigned_admin_id'); }
    public function visitorSessions(): HasMany { return $this->hasMany(VisitorSession::class); }
    public function activityEvents(): HasMany { return $this->hasMany(ActivityEvent::class); }
    public function privacyConsents(): HasMany { return $this->hasMany(PrivacyConsent::class); }
    public function linkedVisitors(): HasMany { return $this->hasMany(Visitor::class, 'linked_user_id'); }
}
