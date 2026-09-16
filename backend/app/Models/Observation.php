<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Observation extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_id',
        'provider',
        'observed_at',
        'normalized_data',
        'raw_data',
        'content_hash',
        'status',
        'error_code',
        'error_message',
        'response_time_ms',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'normalized_data' => 'array',
        'raw_data' => 'array',
        'content_hash' => 'string',
        'status' => 'string',
        'error_code' => 'string',
        'error_message' => 'string',
        'response_time_ms' => 'integer',
    ];

    public function watch()
    {
        return $this->belongsTo(Watch::class);
    }
}