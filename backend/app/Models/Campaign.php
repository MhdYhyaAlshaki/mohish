<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

 
 
// ── Campaign ───────────────────────────────────────────────────────────────────

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'ad_network_id',
        'ad_placement_id',
        'budget',
        'spent',
        'priority',
        'target_platforms',
        'target_countries',
        'ad_config',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'target_platforms' => 'array',
        'target_countries' => 'array',
        'ad_config'        => 'array',
        'is_active'        => 'boolean',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'budget'           => 'decimal:2',
        'spent'            => 'decimal:6',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function adNetwork(): BelongsTo
    {
        return $this->belongsTo(AdNetwork::class);
    }

    public function adPlacement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class);
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    // ── Eligibility Guards ─────────────────────────────────────────────────────

    public function isActiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $today = Carbon::today();
        if ($this->start_date && $this->start_date->gt($today)) {
            return false;
        }
        if ($this->end_date && $this->end_date->lt($today)) {
            return false;
        }
        return true;
    }

    public function isWithinBudget(): bool
    {
        return $this->budget === null || (float) $this->spent < (float) $this->budget;
    }

    public function supportsPlatform(string $platform): bool
    {
        return empty($this->target_platforms)
            || in_array($platform, $this->target_platforms, true);
    }

    public function supportsCountry(?string $countryCode): bool
    {
        if (empty($this->target_countries)) {
            return true;
        }
        return $countryCode !== null
            && in_array(strtoupper($countryCode), $this->target_countries, true);
    }

    /**
     * Return the platform-specific ad unit ID stored in ad_config.
     * Keys expected: "android_ad_unit_id", "ios_ad_unit_id", "ad_unit_id".
     */
    public function getAdUnitId(string $platform): ?string
    {
        $config = $this->ad_config ?? [];
        return $config[$platform . '_ad_unit_id']
            ?? $config['ad_unit_id']
            ?? null;
    }
}
