<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }

    public function test_user_can_create_watch(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/watches', [
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [
                ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
            ],
            'frequency_minutes' => 15,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('watches', [
            'user_id' => $this->user->id,
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
        ]);
    }

    public function test_user_can_list_their_watches(): void
    {
        Watch::factory()->count(3)->create(['user_id' => $this->user->id]);
        Watch::factory()->count(2)->create(); // Other user's watches

        $response = $this->actingAs($this->user)->getJson('/api/watches');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_watch(): void
    {
        $watch = Watch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/watches/{$watch->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $watch->id);
    }

    public function test_user_can_update_watch(): void
    {
        $watch = Watch::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/watches/{$watch->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('watches', [
            'id' => $watch->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_delete_watch(): void
    {
        $watch = Watch::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/watches/{$watch->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('watches', ['id' => $watch->id]);
    }

    public function test_user_cannot_view_other_users_watch(): void
    {
        $otherUser = User::factory()->create();
        $watch = Watch::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson("/api/watches/{$watch->id}");

        $response->assertStatus(404);
    }

    public function test_watch_requires_valid_torob_url(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/watches', [
            'product_url' => 'https://example.com/product',
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [],
            'frequency_minutes' => 15,
        ]);

        $response->assertStatus(422);
    }

    public function test_watch_requires_authentication(): void
    {
        $response = $this->getJson('/api/watches');
        $response->assertStatus(401);
    }

    public function test_watch_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/watches', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_url', 'workflow_type', 'frequency_minutes']);
    }

    public function test_watch_with_multiple_conditions_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/watches', [
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'workflow_type' => 'COMPOSITE_ALERT',
            'conditions' => [
                ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
                ['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available'],
            ],
            'frequency_minutes' => 30,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('watches', [
            'user_id' => $this->user->id,
            'workflow_type' => 'COMPOSITE_ALERT',
        ]);
    }
}
