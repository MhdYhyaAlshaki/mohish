<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdNetwork extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'credentials',
        'base_priority',
        'is_active',
        'android_cpm_estimate',
        'ios_cpm_estimate',
    ];

    protected $casts = [
        'credentials'          => 'encrypted:array',
        'is_active'            => 'boolean',
        'android_cpm_estimate' => 'decimal:4',
        'ios_cpm_estimate'     => 'decimal:4',
    ];

    protected $hidden = ['credentials'];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    // ── Business Logic ─────────────────────────────────────────────────────────

    /**
     * Return the estimated CPM (cost per 1 000 impressions) for a given platform.
     *
     * iOS advertisers typically pay 1.5–2× more than Android because:
     *  - Higher average iOS user income / purchasing power
     *  - Smaller, more premium iOS audience
     *  - Apple's privacy controls push up bid prices
     */
    public function getCpmForPlatform(string $platform): float
    {
        return match ($platform) {
            'ios'   => (float) $this->ios_cpm_estimate,
            default => (float) $this->android_cpm_estimate,
        };
    }

    /**
     * A simple "profitability rank" string – handy for admin dashboards.
     */
    public function getPlatformProfitabilityLabel(): string
    {
        $ratio = $this->android_cpm_estimate > 0
            ? round($this->ios_cpm_estimate / $this->android_cpm_estimate, 1)
            : 0;

        return "iOS is {$ratio}× more profitable than Android for {$this->name}";
    }
}
