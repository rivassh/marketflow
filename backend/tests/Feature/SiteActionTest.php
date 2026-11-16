<?php

namespace Tests\Feature;

use App\Models\User;
use App\SiteActions\TorobAddToCartAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_plan_endpoint_returns_steps_and_warnings(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/site-actions/plan', [
            'action' => 'torob_add_to_cart',
            'url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'quantity' => 2,
            'dry_run' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.action', 'torob_add_to_cart');
        $response->assertJsonPath('data.dry_run', true);
        $this->assertNotEmpty($response->json('data.steps'));
        $this->assertTrue($response->json('data.requires_approval'));
    }

    public function test_plan_stops_when_captcha_expected(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/site-actions/plan', [
            'action' => 'torob_add_to_cart',
            'url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'captcha_expected' => true,
        ]);

        $response->assertStatus(200);
        $warnings = $response->json('data.warnings');
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('captcha', $warnings[0]);
    }

    public function test_execute_dry_run_succeeds(): void
    {
        $planResponse = $this->actingAs($this->user)->postJson('/api/site-actions/plan', [
            'action' => 'torob_add_to_cart',
            'url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'dry_run' => true,
        ]);

        $plan = $planResponse->json('data');

        $response = $this->actingAs($this->user)->postJson('/api/site-actions/execute', [
            'action' => 'torob_add_to_cart',
            'plan' => $plan,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'dry_run');
        $response->assertJsonPath('data.success', true);
    }

    public function test_execute_stops_on_captcha_warning(): void
    {
        $planResponse = $this->actingAs($this->user)->postJson('/api/site-actions/plan', [
            'action' => 'torob_add_to_cart',
            'url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'captcha_expected' => true,
            'dry_run' => false,
        ]);

        $plan = $planResponse->json('data');

        $response = $this->actingAs($this->user)->postJson('/api/site-actions/execute', [
            'action' => 'torob_add_to_cart',
            'plan' => $plan,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('data.status', 'stopped');
    }

    public function test_plan_rejects_unsupported_url(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/site-actions/plan', [
            'action' => 'torob_add_to_cart',
            'url' => 'https://example.com/product/123',
        ]);

        $response->assertStatus(422);
    }

    public function test_torob_action_supports_torob_urls(): void
    {
        $action = app(TorobAddToCartAction::class);
        $this->assertTrue($action->supports('https://torob.com/p/12345/iphone-14-pro/'));
        $this->assertFalse($action->supports('https://divar.ir/v/slug/token'));
    }
}
