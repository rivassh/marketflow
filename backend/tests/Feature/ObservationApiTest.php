<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_observations_for_watch(): void
    {
        $user = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $user->id]);

        Observation::factory()->count(5)->create(['watch_id' => $watch->id]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_observations_include_normalized_data(): void
    {
        $user = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $user->id]);

        Observation::factory()->create([
            'watch_id' => $watch->id,
            'normalized_data' => [
                'title' => 'iPhone 14 Pro',
                'price' => ['amount' => 9500000],
                'availability' => 'available',
            ],
            'status' => 'success',
        ]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.normalized_data.title', 'iPhone 14 Pro');
    }

    public function test_observations_include_error_details_on_failure(): void
    {
        $user = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $user->id]);

        Observation::factory()->create([
            'watch_id' => $watch->id,
            'status' => 'error',
            'error_code' => 'Exception',
            'error_message' => 'Connection timeout',
        ]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.status', 'error');
        $response->assertJsonPath('data.0.error_code', 'Exception');
    }

    public function test_user_cannot_view_observations_for_other_users_watch(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $otherUser->id]);

        Observation::factory()->count(3)->create(['watch_id' => $watch->id]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(404);
    }

    public function test_observations_are_ordered_by_most_recent_first(): void
    {
        $user = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $user->id]);

        $oldest = Observation::factory()->create([
            'watch_id' => $watch->id,
            'observed_at' => now()->subHours(2),
        ]);
        $newest = Observation::factory()->create([
            'watch_id' => $watch->id,
            'observed_at' => now(),
        ]);
        $middle = Observation::factory()->create([
            'watch_id' => $watch->id,
            'observed_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertEquals($newest->id, $ids[0]);
        $this->assertEquals($middle->id, $ids[1]);
        $this->assertEquals($oldest->id, $ids[2]);
    }

    public function test_pagination_for_observations(): void
    {
        $user = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $user->id]);

        Observation::factory()->count(25)->create(['watch_id' => $watch->id]);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations?per_page=10&page=1");

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.per_page', 10);
    }
}
