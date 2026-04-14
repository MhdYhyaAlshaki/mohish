<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'points',
    'api_token',
    'referral_code',
    'referred_by',
    'admin_role',
    'is_flagged',
    'payout_tier_id',
])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->referral_code)) {
                do {
                    $code = Str::upper(Str::random(8));
                } while (self::query()->where('referral_code', $code)->exists());
                $user->referral_code = $code;
            }

            if (blank($user->api_token)) {
                $user->api_token = Str::random(60);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_flagged' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function adLogs(): HasMany
    {
        return $this->hasMany(AdsLog::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function payoutTier(): BelongsTo
    {
        return $this->belongsTo(PayoutTier::class);
    }

    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(DailyUserMetric::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->admin_role, ['admin', 'analyst'], true);
    }
}
