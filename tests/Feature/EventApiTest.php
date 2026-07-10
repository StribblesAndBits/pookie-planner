<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_all_day_custom_recurring_event(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'Team Standup',
            'start' => '2026-07-10',
            'end' => '2026-07-10',
            'all_day' => true,
            'description' => 'Recurring planning sync',
            'recurrence_type' => 'custom',
            'recurrence_interval' => 1,
            'recurrence_unit' => 'week',
            'recurrence_days_of_week' => [1, 3, 5],
            'recurrence_end_type' => 'after',
            'recurrence_occurrences' => 12,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('all_day', true)
            ->assertJsonPath('recurrence_type', 'custom')
            ->assertJsonPath('recurrence_end_type', 'after')
            ->assertJsonPath('recurrence_occurrences', 12);

        $event = Event::query()->firstOrFail();

        $this->assertSame('00:00', $event->start_time);
        $this->assertSame('23:59', $event->end_time);
        $this->assertSame([1, 3, 5], $event->recurrence_days_of_week);
    }

    public function test_non_all_day_event_requires_start_and_end_times(): void
    {
        $user = $this->createUser('notime@example.com');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/events', [
            'title' => 'No time event',
            'start' => '2026-07-10',
            'end' => '2026-07-10',
            'all_day' => false,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    private function createUser(string $email = 'event-test@example.com'): User
    {
        return User::query()->create([
            'first_name' => 'Event',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => Hash::make('password123'),
            'color_preference' => null,
        ]);
    }
}
