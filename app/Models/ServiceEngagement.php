<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEngagement extends Model
{
    protected $fillable = [
        'user_id', 'lead_id', 'workflow_audit_id', 'order_item_id', 'product_id',
        'engagement_type', 'status', 'start_date', 'end_date', 'assigned_admin_id', 'notes',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function workflowAudit(): BelongsTo { return $this->belongsTo(WorkflowAudit::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function assignedAdmin(): BelongsTo { return $this->belongsTo(User::class, 'assigned_admin_id'); }
}
