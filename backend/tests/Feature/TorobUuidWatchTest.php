<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use App\Models\Watch;
use App\Providers\Processors\TorobProcessor;
use App\Services\WorkflowDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TorobUuidWatchTest extends TestCase
{
    use RefreshDatabase;

    private const UUID_URL = 'https://torob.com/p/49216e75-b6f3-4266-985d-8b034b8a9716/هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22/';

    private function fixtureHtml(): string
    {
        return file_get_contents(base_path('tests/Fixtures/torob_uuid_product.html'));
    }

    private function makeWatch(User $user): Watch
    {
        return Watch::create([
            'user_id' => $user->id,
            'product_url' => self::UUID_URL,
            'product_ref' => [
                'provider' => 'torob',
                'externalId' => '49216e75-b6f3-4266-985d-8b034b8a9716',
                'canonicalUrl' => 'هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22',
            ],
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [
                ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
            ],
            'frequency_minutes' => 15,
            'is_active' => true,
            'is_tracked' => true,
            'last_checked_at' => now()->subMinutes(20),
        ]);
    }

    public function test_it_supports_uuid_and_persian_slug_urls(): void
    {
        $processor = app(TorobProcessor::class);
        $this->assertTrue($processor->supports(self::UUID_URL));
    }

    public function test_watch_creation_accepts_uuid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/watches', [
            'product_url' => self::UUID_URL,
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [
                ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
            ],
            'frequency_minutes' => 15,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('watches', [
            'user_id' => $user->id,
            'product_url' => self::UUID_URL,
        ]);

        $watch = Watch::where('product_url', self::UUID_URL)->first();
        $this->assertNotNull($watch);
        $this->assertSame('49216e75-b6f3-4266-985d-8b034b8a9716', $watch->product_ref['externalId']);
        $this->assertSame('هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22', $watch->product_ref['canonicalUrl']);
    }

    public function test_non_torob_url_is_still_rejected_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/watches', [
            'product_url' => 'https://example.com/product',
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [],
            'frequency_minutes' => 15,
        ]);

        $response->assertStatus(422);
    }

    public function test_forced_check_records_cheapest_price(): void
    {
        Http::fake([
            'torob.com/*' => Http::response($this->fixtureHtml(), 200),
        ]);

        $user = User::factory()->create();
        $watch = $this->makeWatch($user);

        $dispatcher = app(WorkflowDispatcher::class);
        $dispatcher->dispatch($watch);

        $observation = Observation::where('watch_id', $watch->id)->first();
        $this->assertNotNull($observation, 'an observation must be recorded');
        $this->assertSame('success', $observation->status);

        $normalized = $observation->normalized_data;
        $this->assertIsArray($normalized);
        $this->assertSame(4400000, $normalized['price']['amount']);
        $this->assertStringContainsString('۴٫۴۰۰٫۰۰۰', $normalized['price']['display'] ?? '');
    }

    public function test_get_observations_returns_recorded_price(): void
    {
        Http::fake([
            'torob.com/*' => Http::response($this->fixtureHtml(), 200),
        ]);

        $user = User::factory()->create();
        $watch = $this->makeWatch($user);

        $dispatcher = app(WorkflowDispatcher::class);
        $dispatcher->dispatch($watch);

        $response = $this->actingAs($user)->getJson("/api/watches/{$watch->id}/observations");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.normalized_data.price.amount', 4400000);
    }

    public function test_check_endpoint_triggers_dispatch_and_records_observation(): void
    {
        Http::fake([
            'torob.com/*' => Http::response($this->fixtureHtml(), 200),
        ]);

        $user = User::factory()->create();
        $watch = $this->makeWatch($user);

        $response = $this->actingAs($user)
            ->postJson("/api/watches/{$watch->id}/check");

        $response->assertStatus(200);
        $this->assertNotNull(
            Observation::where('watch_id', $watch->id)->first(),
            'check endpoint must record an observation'
        );
    }

    public function test_check_endpoint_returns_404_for_other_users_watch(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $watch = $this->makeWatch($other);

        $response = $this->actingAs($user)
            ->postJson("/api/watches/{$watch->id}/check");

        $response->assertStatus(404);
    }

    public function test_build_url_preserves_persian_slug(): void
    {
        $processor = app(TorobProcessor::class);
        $reference = $processor->parseProductReference(self::UUID_URL);

        $built = $this->invokeBuildUrl($processor, $reference);

        $this->assertSame(
            'https://torob.com/p/49216e75-b6f3-4266-985d-8b034b8a9716/هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22/',
            $built
        );
    }

    private function invokeBuildUrl(TorobProcessor $processor, $reference): string
    {
        $method = new \ReflectionMethod($processor, 'buildUrl');
        $method->setAccessible(true);

        return $method->invoke($processor, $reference);
    }
}
