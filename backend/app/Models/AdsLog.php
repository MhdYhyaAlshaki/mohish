<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsLog extends Model
{
    protected $table = 'ads_logs';

    protected $fillable = [
        'user_id',
        'ad_type',
        'session_id',
        'completed',
        'reward_given',
        'started_at',
        'completed_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'vpn_flag',
        'risk_score',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'vpn_flag' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
