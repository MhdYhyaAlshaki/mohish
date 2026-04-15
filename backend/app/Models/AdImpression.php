<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

 
 
 
// ── AdImpression ───────────────────────────────────────────────────────────────

class AdImpression extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'placement_key',
        'platform',
        'country_code',
        'is_vpn',
        'estimated_revenue',
        'ip_address',
    ];

    protected $casts = [
        'is_vpn'             => 'boolean',
        'estimated_revenue'  => 'decimal:6',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function click(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AdClick::class);
    }
}