<?php

namespace App\Providers;

use App\Providers\Interfaces\MarketplaceProvider;
use App\Providers\Processors\DivarProcessor;
use App\Providers\Processors\TorobProcessor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScrapingHelper::class, fn () => new ScrapingHelper);

        $this->app->bind(MarketplaceProvider::class, function ($app, $params) {
            $name = $params['provider'] ?? null;

            return match (strtolower((string) $name)) {
                'torob' => $app->make(TorobProcessor::class),
                'divar' => $app->make(DivarProcessor::class),
                default => null,
            };
        });

        $this->app->singleton(TorobProcessor::class, function ($app) {
            return new TorobProcessor(
                $app->make(ScrapingHelper::class),
                config('marketflow.torob')
            );
        });

        $this->app->singleton(DivarProcessor::class, function ($app) {
            return new DivarProcessor(
                $app->make(ScrapingHelper::class),
                config('marketflow.divar')
            );
        });

        $this->app->singleton(ProviderRegistry::class, function ($app) {
            return new ProviderRegistry(
                $app->make(TorobProcessor::class),
                $app->make(DivarProcessor::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
