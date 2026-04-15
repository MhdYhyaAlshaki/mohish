<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

 

// ── AdPlacement ────────────────────────────────────────────────────────────────

class AdPlacement extends Model
{
    protected $fillable = ['name', 'key', 'ad_type_id', 'is_active', 'fallback_config'];

    protected $casts = [
        'is_active'       => 'boolean',
        'fallback_config' => 'array',
    ];

    public function adType(): BelongsTo
    {
        return $this->belongsTo(AdType::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
