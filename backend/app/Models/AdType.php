<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

// ── AdType ─────────────────────────────────────────────────────────────────────

class AdType extends Model
{
    protected $fillable = ['name', 'slug'];

    public function placements(): HasMany
    {
        return $this->hasMany(AdPlacement::class);
    }
}
