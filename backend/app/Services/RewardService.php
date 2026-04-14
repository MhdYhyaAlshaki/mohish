<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\AdsLog;
use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardService
{
    public function __construct(
        private readonly FraudSignalService $fraudSignalService,
        private readonly SettingsService $settingsService,
    ) {
    }

    public function startAdSession(User $user, array $context): array
    {
        $dailyCap = $this->dailyCap();
        $cooldownSeconds = $this->cooldownSeconds();

        $completedToday = AdsLog::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->whereDate('completed_at', Carbon::today())
            ->count();

        if ($completedToday >= $dailyCap) {
            throw new ApiException('daily_limit_reached', 'Daily limit reached. Come back tomorrow.', 429);
        }

        $lastCompleted = AdsLog::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->latest('completed_at')
            ->first();

        if ($lastCompleted?->completed_at) {
            $nextAllowed = $lastCompleted->completed_at->copy()->addSeconds($cooldownSeconds);
            if ($nextAllowed->isFuture()) {
                throw new ApiException(
                    'cooldown_active',
                    'Please wait before watching another ad.',
                    429,
                    Carbon::now()->diffInSeconds($nextAllowed),
                );
            }
        }

        $sessionId = (string) Str::uuid();
        $vpnFlag = (bool) ($context['vpn_flag'] ?? false);
        $fingerprint = $context['device_fingerprint'] ?? null;

        AdsLog::query()->create([
            'user_id' => $user->id,
            'ad_type' => $context['ad_type'] ?? 'rewarded',
            'session_id' => $sessionId,
            'started_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMinutes($this->sessionExpiryMinutes()),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'device_fingerprint' => $fingerprint,
            'vpn_flag' => $vpnFlag,
            'risk_score' => $this->fraudSignalService->riskScore($fingerprint, $vpnFlag),
        ]);

        return [
            'session_id' => $sessionId,
            'expires_at' => Carbon::now()->addMinutes($this->sessionExpiryMinutes())->toIso8601String(),
            'cooldown_seconds' => $cooldownSeconds,
            'remaining_today' => max(0, $dailyCap - $completedToday),
        ];
    }

    public function completeAdSession(User $user, string $sessionId): array
    {
        return DB::transaction(function () use ($user, $sessionId): array {
            /** @var AdsLog|null $session */
            $session = AdsLog::query()
                ->where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw new ApiException('session_not_found', 'Ad session not found.', 404);
            }

            if ($session->completed) {
                $dailyCount = AdsLog::query()
                    ->where('user_id', $user->id)
                    ->where('completed', true)
                    ->whereDate('completed_at', Carbon::today())
                    ->count();

                return [
                    'awarded_points' => $session->reward_given,
                    'new_balance' => (int) $user->fresh()->points,
                    'daily_count' => $dailyCount,
                    'next_available_at' => $session->completed_at
                        ? $session->completed_at->copy()->addSeconds($this->cooldownSeconds())->toIso8601String()
                        : Carbon::now()->toIso8601String(),
                ];
            }

            if ($session->expires_at && $session->expires_at->isPast()) {
                throw new ApiException('session_expired', 'Ad session expired. Start a new one.', 422);
            }

            /** @var User $lockedUser */
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            $awardedPoints = $this->pointsPerAdForUser($lockedUser);
            $lockedUser->increment('points', $awardedPoints);

            $completedAt = Carbon::now();
            $session->update([
                'completed' => true,
                'reward_given' => $awardedPoints,
                'completed_at' => $completedAt,
            ]);

            Transaction::query()->create([
                'user_id' => $lockedUser->id,
                'type' => 'ad',
                'points' => $awardedPoints,
                'status' => 'completed',
                'meta' => ['session_id' => $sessionId],
            ]);

            $this->applyReferralEarnings($lockedUser, $awardedPoints);

            $dailyCount = AdsLog::query()
                ->where('user_id', $lockedUser->id)
                ->where('completed', true)
                ->whereDate('completed_at', Carbon::today())
                ->count();

            return [
                'awarded_points' => $awardedPoints,
                'new_balance' => (int) $lockedUser->fresh()->points,
                'daily_count' => $dailyCount,
                'next_available_at' => $completedAt
                    ->copy()
                    ->addSeconds($this->cooldownSeconds())
                    ->toIso8601String(),
            ];
        });
    }

    public function claimReward(User $user): array
    {
        $alreadyClaimed = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'claim_reward')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($alreadyClaimed) {
            throw new ApiException('reward_already_claimed', 'Daily claim already used.', 429);
        }

        $points = $this->dailyClaimPoints();

        DB::transaction(function () use ($user, $points): void {
            User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail()->increment('points', $points);

            Transaction::query()->create([
                'user_id' => $user->id,
                'type' => 'claim_reward',
                'points' => $points,
                'status' => 'completed',
            ]);
        });

        return [
            'claimed_points' => $points,
            'new_balance' => (int) $user->fresh()->points,
        ];
    }

    public function requestWithdraw(User $user, int $points): array
    {
        $minWithdrawal = $this->minWithdrawPoints();
        if ($points < $minWithdrawal) {
            throw new ApiException('below_minimum_withdraw', "Minimum withdrawal is {$minWithdrawal} points.", 422);
        }

        return DB::transaction(function () use ($user, $points): array {
            /** @var User $lockedUser */
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->points < $points) {
                throw new ApiException('insufficient_balance', 'Not enough points for withdrawal.', 422);
            }

            $lockedUser->decrement('points', $points);

            $withdrawal = Withdrawal::query()->create([
                'user_id' => $lockedUser->id,
                'points' => $points,
                'status' => 'pending',
            ]);

            Transaction::query()->create([
                'user_id' => $lockedUser->id,
                'type' => 'withdrawal',
                'points' => -$points,
                'status' => 'pending',
                'meta' => ['withdrawal_id' => $withdrawal->id],
            ]);

            return [
                'withdrawal_id' => $withdrawal->id,
                'status' => $withdrawal->status,
                'new_balance' => (int) $lockedUser->fresh()->points,
            ];
        });
    }

    private function applyReferralEarnings(User $user, int $earnedPoints): void
    {
        if (! $user->referred_by) {
            return;
        }

        $referralPercent = $this->referralPercent();
        $bonus = (int) floor(($earnedPoints * $referralPercent) / 100);
        if ($bonus <= 0) {
            return;
        }

        $referrer = User::query()->find($user->referred_by);
        if (! $referrer) {
            return;
        }

        $referrer->increment('points', $bonus);

        $referral = Referral::query()->firstOrCreate(
            [
                'user_id' => $referrer->id,
                'referred_user_id' => $user->id,
            ],
            ['earnings' => 0]
        );

        $referral->increment('earnings', $bonus);

        Transaction::query()->create([
            'user_id' => $referrer->id,
            'type' => 'referral',
            'points' => $bonus,
            'status' => 'completed',
            'meta' => ['from_user_id' => $user->id],
        ]);
    }

    private function pointsPerAdForUser(User $user): int
    {
        $base = $this->settingsService->getFloat('base_user_payout_points_per_ad', (float) config('reward.points_per_ad'));
        $multiplier = (float) ($user->payoutTier?->payout_multiplier ?? 1.0);
        return max(1, (int) round($base * $multiplier));
    }

    private function dailyCap(): int
    {
        return $this->settingsService->getInt('daily_cap', (int) config('reward.daily_cap'));
    }

    private function cooldownSeconds(): int
    {
        return $this->settingsService->getInt('cooldown_seconds', (int) config('reward.cooldown_seconds'));
    }

    private function sessionExpiryMinutes(): int
    {
        return $this->settingsService->getInt('session_expiry_minutes', (int) config('reward.session_expiry_minutes'));
    }

    private function referralPercent(): int
    {
        return $this->settingsService->getInt('referral_percent', (int) config('reward.referral_percent'));
    }

    private function dailyClaimPoints(): int
    {
        return $this->settingsService->getInt('daily_claim_points', (int) config('reward.daily_claim_points'));
    }

    private function minWithdrawPoints(): int
    {
        return $this->settingsService->getInt('min_withdraw_points', (int) config('reward.min_withdraw_points'));
    }
}
