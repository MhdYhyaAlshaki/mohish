<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUserMetric extends Model
{
    protected $fillable = [
        'metric_date',
        'user_id',
        'tier_name',
        'started_ads',
        'completed_ads',
        'rewarded_points',
        'referral_cost_points',
        'gross_revenue',
        'user_payout_cost',
        'referral_cost',
        'net_profit',
        'completion_rate',
        'risk_score_avg',
        'vpn_events',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'vpn_events' => 'boolean',
            'completion_rate' => 'float',
            'gross_revenue' => 'float',
            'user_payout_cost' => 'float',
            'referral_cost' => 'float',
            'net_profit' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
