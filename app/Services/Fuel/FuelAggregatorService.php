<?php

namespace App\Services\Fuel;

class FuelAggregatorService
{
    /** @var FuelProviderInterface[] */
    protected array $providers = [];

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Fetches data from all configured providers and returns combined array.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        $out = [];

        foreach ($this->providers as $provider) {
            try {
                $data = $provider->fetch();
                if (is_array($data)) {
                    $out = array_merge($out, $data);
                }
            } catch (\Throwable $e) {
                dump($e->getMessage());
                // swallow provider errors to avoid total failure
                continue;
            }
        }

        return $out;
    }
}
