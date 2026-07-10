<?php

namespace Tests\Feature;

use App\Models\Utility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UtilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_utility(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/utilities', [
            'name' => 'Power',
            'tag' => 'essential',
            'due_date' => '2026-07-20',
            'amount' => 145.23,
            'recurs_monthly' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Power')
            ->assertJsonPath('tag', 'essential')
            ->assertJsonPath('status', 'unpaid');

        $this->assertDatabaseHas('utilities', [
            'user_id' => $user->id,
            'name' => 'Power',
            'tag' => 'essential',
            'status' => 'unpaid',
        ]);
    }

    public function test_user_can_mark_utility_as_paid(): void
    {
        $user = $this->createUser('utilities-paid@example.com');
        Sanctum::actingAs($user);

        $utility = Utility::query()->create([
            'user_id' => $user->id,
            'name' => 'Internet',
            'tag' => 'essential',
            'due_date' => '2026-07-12',
            'amount' => 79.99,
            'status' => 'unpaid',
            'recurs_monthly' => false,
        ]);

        $response = $this->putJson("/api/utilities/{$utility->id}", [
            'status' => 'paid',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertDatabaseHas('utilities', [
            'id' => $utility->id,
            'status' => 'paid',
        ]);
    }

    private function createUser(string $email = 'utility-test@example.com'): User
    {
        return User::query()->create([
            'first_name' => 'Utility',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => Hash::make('password123'),
            'color_preference' => null,
        ]);
    }
}
