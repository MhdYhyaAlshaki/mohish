<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

 


// ── AdClick ────────────────────────────────────────────────────────────────────

class AdClick extends Model
{
    protected $fillable = ['ad_impression_id', 'ip_address'];

    public function impression(): BelongsTo
    {
        return $this->belongsTo(AdImpression::class, 'ad_impression_id');
    }
}
