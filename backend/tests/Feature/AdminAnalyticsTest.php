<?php

namespace Tests\Feature;

use App\Models\DailyUserMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_analytics_endpoints(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $user = User::factory()->create();

        DailyUserMetric::query()->create([
            'metric_date' => now()->toDateString(),
            'user_id' => $user->id,
            'tier_name' => 'regular',
            'started_ads' => 10,
            'completed_ads' => 8,
            'rewarded_points' => 80,
            'referral_cost_points' => 10,
            'gross_revenue' => 0.24,
            'user_payout_cost' => 0.08,
            'referral_cost' => 0.01,
            'net_profit' => 0.15,
            'completion_rate' => 0.8,
            'risk_score_avg' => 20,
            'vpn_events' => false,
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/analytics/overview')
            ->assertOk()
            ->assertJsonStructure(['range', 'overview', 'fraud_buckets']);

        $this->actingAs($admin)
            ->getJson('/admin/analytics/top-users')
            ->assertOk()
            ->assertJsonStructure(['range', 'items']);

        $this->actingAs($admin)
            ->getJson('/admin/analytics/profit-trend')
            ->assertOk()
            ->assertJsonStructure(['range', 'items']);
    }

    public function test_non_admin_user_is_forbidden_from_admin_analytics(): void
    {
        $user = User::factory()->create(['admin_role' => 'viewer']);

        $this->actingAs($user)
            ->get('/admin/analytics/overview')
            ->assertForbidden();
    }
}
