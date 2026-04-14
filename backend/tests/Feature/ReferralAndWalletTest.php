<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAndWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_referral_code_then_fetch_referrals(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by' => null]);

        $apply = $this->withToken($user->api_token)->postJson('/api/apply-code', [
            'code' => $referrer->referral_code,
        ]);

        $apply->assertOk()->assertJsonPath('referred_by', $referrer->id);

        $index = $this->withToken($referrer->api_token)->getJson('/api/referrals');
        $index->assertOk()->assertJsonStructure(['referral_code', 'total_referral_earnings', 'items']);
    }

    public function test_withdraw_requires_balance_and_creates_pending_record(): void
    {
        $user = User::factory()->create(['points' => 2500]);

        $response = $this->withToken($user->api_token)->postJson('/api/withdraw', [
            'points' => 1000,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'points' => 1000,
            'status' => 'pending',
        ]);
    }
}
