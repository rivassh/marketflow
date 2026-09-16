<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Workflow\WorkflowType;

class Watch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_url',
        'product_ref',
        'workflow_type',
        'conditions',
        'frequency_minutes',
        'user_id',
        'is_active',
        'last_checked_at',
        'is_tracked',
        'alert_count',
        'price_history',
    ];

    protected $casts = [
        'conditions' => 'array',
        'frequency_minutes' => 'integer',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'is_tracked' => 'boolean',
        'alert_count' => 'integer',
        'price_history' => 'array',
        'product_ref' => 'array',
        'workflow_type' => WorkflowType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function observations()
    {
        return $this->hasMany(Observation::class);
    }

    public function shouldCheck(): bool
    {
        if (!$this->is_active || !$this->is_tracked) {
            return false;
        }

        return !$this->last_checked_at || 
               $this->last_checked_at->addMinutes($this->frequency_minutes)->isPast();
    }

    public function getConfig(): array
    {
        return $this->conditions ?? [];
    }
}