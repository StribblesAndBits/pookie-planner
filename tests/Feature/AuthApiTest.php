<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'color_preference' => '#D6486B',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'email', 'color_preference'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'color_preference' => '#D6486B',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->postJson('/api/register', [
            'first_name' => 'Login',
            'last_name' => 'User',
            'email' => 'login@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'color_preference' => '#D6486B',
        ])->assertCreated();

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'first_name', 'last_name', 'email', 'color_preference'],
                'token',
            ]);
    }
}
