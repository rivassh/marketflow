<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseIntent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'watch_id',
        'product_url',
        'product_ref',
        'title',
        'target_price',
        'currency',
        'seller',
        'status',
        'notes',
    ];

    protected $casts = [
        'product_ref' => 'array',
        'target_price' => 'decimal:2',
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

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
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
