<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseDomainTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_full_purchase_order_lifecycle(): void
    {
        // Create order
        $response = $this->actingAs($this->user)->postJson('/api/purchase-orders', [
            'seller' => 'فروشگاه تست',
            'total_amount' => 1000000,
            'currency' => 'IRR',
            'items' => [
                ['title' => 'آیفون ۱۴ پرو', 'price' => 950000, 'quantity' => 1],
            ],
        ]);
        $response->assertStatus(201);
        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('purchase_orders', ['id' => $orderId, 'status' => 'draft']);

        // Submit for approval
        $response = $this->actingAs($this->user)->postJson("/api/purchase-orders/{$orderId}/submit");
        $response->assertStatus(200);
        $this->assertDatabaseHas('purchase_orders', ['id' => $orderId, 'status' => 'awaiting_approval']);

        // Approve
        $response = $this->actingAs($this->user)->postJson("/api/purchase-orders/{$orderId}/approve");
        $response->assertStatus(200);
        $this->assertDatabaseHas('purchase_orders', ['id' => $orderId, 'status' => 'approved']);
        $this->assertDatabaseHas('purchase_audit_logs', [
            'auditable_type' => PurchaseOrder::class,
            'auditable_id' => $orderId,
            'action' => 'approved',
            'old_status' => 'awaiting_approval',
            'new_status' => 'approved',
        ]);
    }

    public function test_approve_without_permission_fails(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/purchase-orders', [
            'seller' => 'فروشگاه تست',
            'total_amount' => 1000000,
        ]);
        $orderId = $response->json('data.id');

        $this->actingAs($this->user)->postJson("/api/purchase-orders/{$orderId}/submit");

        // Other user cannot approve
        $response = $this->actingAs($this->otherUser)->postJson("/api/purchase-orders/{$orderId}/approve");
        $response->assertStatus(404);
    }

    public function test_approve_from_draft_fails(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/purchase-orders', [
            'seller' => 'فروشگاه تست',
            'total_amount' => 1000000,
        ]);
        $orderId = $response->json('data.id');

        $response = $this->actingAs($this->user)->postJson("/api/purchase-orders/{$orderId}/approve");
        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_view_others_order(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/purchase-orders', [
            'seller' => 'فروشگاه تست',
            'total_amount' => 1000000,
        ]);
        $orderId = $response->json('data.id');

        $response = $this->actingAs($this->otherUser)->getJson("/api/purchase-orders/{$orderId}");
        $response->assertStatus(404);
    }

    public function test_purchase_intent_crud(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/purchase-intents', [
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'title' => 'iPhone 14 Pro',
            'target_price' => 9500000,
            'currency' => 'IRR',
            'seller' => 'فروشگاه تکنولوژی',
        ]);
        $response->assertStatus(201);
        $intentId = $response->json('data.id');

        $response = $this->actingAs($this->user)->getJson("/api/purchase-intents/{$intentId}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'iPhone 14 Pro');

        $response = $this->actingAs($this->user)->putJson("/api/purchase-intents/{$intentId}", [
            'target_price' => 9000000,
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.target_price', '9000000.00');

        $response = $this->actingAs($this->user)->deleteJson("/api/purchase-intents/{$intentId}");
        $response->assertStatus(204);
    }

    public function test_submit_purchase_intent(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/purchase-intents', [
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'title' => 'iPhone 14 Pro',
            'target_price' => 9500000,
        ]);
        $intentId = $response->json('data.id');

        $response = $this->actingAs($this->user)->postJson("/api/purchase-intents/{$intentId}/submit");
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'awaiting_approval');
    }
}
