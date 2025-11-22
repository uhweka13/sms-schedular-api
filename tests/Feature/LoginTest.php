<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_verified_user_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now()
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ],
                    'token',
                    'token_type'
                ],
                'message',
                'code'
            ]);


    }

    public function test_unverified_user_login(): void
    {

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => null
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(400);

    }
}
