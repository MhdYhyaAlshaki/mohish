<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mohish',
            'email' => 'mohish@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'points', 'referral_code'],
            ]);
    }

    public function test_user_can_login_and_receive_new_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mohish@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $oldToken = $user->api_token;
        $response = $this->postJson('/api/login', [
            'email' => 'mohish@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
        $this->assertNotSame($oldToken, $response->json('token'));
    }
}
