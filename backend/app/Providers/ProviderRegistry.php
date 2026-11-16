<?php

namespace App\Providers;

use App\Providers\Interfaces\MarketplaceProvider;
use App\Providers\Processors\DivarProcessor;
use App\Providers\Processors\TorobProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderRegistry
{
    protected array $processors = [];

    protected bool $loaded = false;

    public function __construct(
        protected ?TorobProcessor $torob = null,
        protected ?DivarProcessor $divar = null
    ) {
        $this->torob = $torob;
        $this->divar = $divar;
    }

    /**
     * Resolve a processor for the given URL.
     */
    public function resolve(string $url): ?MarketplaceProvider
    {
        $this->ensureLoaded();

        foreach ($this->processors as $processor) {
            if ($processor->supports($url)) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Return all enabled processors.
     *
     * @return MarketplaceProvider[]
     */
    public function all(): array
    {
        $this->ensureLoaded();

        return array_values($this->processors);
    }

    /**
     * Get a processor by name.
     */
    public function get(string $name): ?MarketplaceProvider
    {
        $this->ensureLoaded();

        return $this->processors[$name] ?? null;
    }

    /**
     * Load processors from the database (provider_registry table),
     * falling back to in-memory defaults when the table is empty or
     * the migration hasn't run yet.
     */
    protected function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $dbProviders = $this->loadFromDatabase();
        if (! empty($dbProviders)) {
            $this->processors = $dbProviders;

            return;
        }

        $this->processors = $this->defaults();
    }

    /**
     * @return MarketplaceProvider[]
     */
    protected function defaults(): array
    {
        $processors = [];

        if ($this->torob && config('marketflow.torob.enabled', true)) {
            $processors['torob'] = $this->torob;
        }

        if ($this->divar && config('marketflow.divar.enabled', true)) {
            $processors['divar'] = $this->divar;
        }

        return $processors;
    }

    /**
     * Read enabled providers from the provider_registry table.
     *
     * @return MarketplaceProvider[]
     */
    protected function loadFromDatabase(): array
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('provider_registry')) {
                return [];
            }

            $rows = Cache::remember('marketflow.providers', 300, function () {
                return DB::table('provider_registry')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('name')
                    ->all();
            });

            $processors = [];
            foreach ($rows as $row) {
                $config = is_string($row->configuration)
                    ? json_decode($row->configuration, true)
                    : (array) ($row->configuration ?? []);

                $processor = $this->makeFromConfig($row->name, $config);
                if ($processor) {
                    $processors[$row->name] = $processor;
                }
            }

            return $processors;
        } catch (\Throwable $e) {
            Log::warning('ProviderRegistry: failed to load from database', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function makeFromConfig(string $name, array $config): ?MarketplaceProvider
    {
        return match (strtolower($name)) {
            'torob' => $this->torob ? clone $this->torob : null,
            'divar' => $this->divar ? clone $this->divar : null,
            default => null,
        };
    }
}
