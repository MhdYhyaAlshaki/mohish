<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\PayoutTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'referral_code' => ['nullable', 'string', 'max:12'],
        ]);

        $referrerId = null;
        if (! empty($validated['referral_code'])) {
            $referrer = User::query()->where('referral_code', $validated['referral_code'])->first();
            if (! $referrer) {
                throw ValidationException::withMessages(['referral_code' => 'Invalid referral code.']);
            }
            $referrerId = $referrer->id;
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'api_token' => Str::random(60),
            'referral_code' => $this->generateReferralCode(),
            'referred_by' => $referrerId,
            'admin_role' => 'analyst',
            'payout_tier_id' => PayoutTier::query()->where('name', 'regular')->value('id'),
        ]);

        return $this->success([
            'token' => $user->api_token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points' => $user->points,
                'referral_code' => $user->referral_code,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('invalid_credentials', 'Invalid email or password.', 401);
        }

        $user->forceFill(['api_token' => Str::random(60)])->save();

        return $this->success([
            'token' => $user->api_token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points' => $user->points,
                'referral_code' => $user->referral_code,
            ],
        ]);
    }

    private function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
