<?php

namespace Tests\Feature;

use App\Models\AdsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_and_complete_rewarded_ad(): void
    {
        $user = User::factory()->create(['points' => 0]);

        $start = $this->withToken($user->api_token)->postJson('/api/ad/start', [
            'ad_type' => 'rewarded',
            'device_fingerprint' => 'device-1',
            'vpn_flag' => false,
        ]);

        $start->assertOk()->assertJsonStructure([
            'session_id',
            'expires_at',
            'cooldown_seconds',
            'remaining_today',
        ]);

        $sessionId = $start->json('session_id');
        $complete = $this->withToken($user->api_token)->postJson('/api/ad/complete', [
            'session_id' => $sessionId,
        ]);

        $complete->assertOk()->assertJsonStructure([
            'awarded_points',
            'new_balance',
            'daily_count',
            'next_available_at',
        ]);

        $this->assertGreaterThan(0, $complete->json('awarded_points'));
        $this->assertSame($complete->json('awarded_points'), $complete->json('new_balance'));
        $this->assertDatabaseHas('ads_logs', [
            'session_id' => $sessionId,
            'completed' => 1,
        ]);
    }

    public function test_completion_is_idempotent_for_same_session(): void
    {
        $user = User::factory()->create(['points' => 0]);
        $sessionId = (string) AdsLog::query()->create([
            'user_id' => $user->id,
            'ad_type' => 'rewarded',
            'session_id' => (string) Str::uuid(),
            'completed' => false,
            'reward_given' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'ip_address' => '127.0.0.1',
            'vpn_flag' => false,
            'risk_score' => 0,
        ])->session_id;

        $first = $this->withToken($user->api_token)->postJson('/api/ad/complete', ['session_id' => $sessionId]);
        $second = $this->withToken($user->api_token)->postJson('/api/ad/complete', ['session_id' => $sessionId]);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('new_balance'), $second->json('new_balance'));
        $this->assertDatabaseCount('transactions', 1);
    }
}
