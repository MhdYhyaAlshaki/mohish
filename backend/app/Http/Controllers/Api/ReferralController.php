<?php

namespace App\Http\Controllers\Api;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;

class ReferralController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $entries = Referral::query()
            ->where('user_id', $user->id)
            ->with('referredUser:id,name,email')
            ->latest()
            ->get()
            ->map(static fn (Referral $referral): array => [
                'id' => $referral->id,
                'earnings' => (int) $referral->earnings,
                'referred_user' => $referral->referredUser
                    ? [
                        'id' => $referral->referredUser->id,
                        'name' => $referral->referredUser->name,
                        'email' => $referral->referredUser->email,
                    ]
                    : null,
            ])
            ->values();

        return $this->success([
            'referral_code' => $user->referral_code,
            'total_referral_earnings' => (int) $entries->sum('earnings'),
            'items' => $entries,
        ]);
    }

    public function applyCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $user = $request->user();
        if ($user->referred_by) {
            return $this->error('referral_locked', 'Referral code already applied.', 422);
        }

        $referrer = User::query()->where('referral_code', $validated['code'])->first();
        if (! $referrer) {
            return $this->error('invalid_referral_code', 'Referral code is invalid.', 422);
        }
        if ($referrer->id === $user->id) {
            return $this->error('invalid_referral_code', 'You cannot apply your own code.', 422);
        }

        $user->update(['referred_by' => $referrer->id]);

        return $this->success([
            'message' => 'Referral code applied successfully.',
            'referred_by' => $referrer->id,
        ]);
    }
}
