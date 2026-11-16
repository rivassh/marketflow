<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'purchase_intent_id',
        'status',
        'seller',
        'total_amount',
        'currency',
        'approved_at',
        'approved_by',
        'placed_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'placed_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft',
        'awaiting_approval',
        'approved',
        'placed',
        'failed',
        'cancelled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseIntent(): BelongsTo
    {
        return $this->belongsTo(PurchaseIntent::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            'draft' => ['awaiting_approval', 'cancelled'],
            'awaiting_approval' => ['approved', 'cancelled'],
            'approved' => ['placed', 'failed', 'cancelled'],
            'placed' => [],
            'failed' => [],
            'cancelled' => [],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }
}
