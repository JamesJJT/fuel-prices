<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Fuel\FuelProviderInterface;
use App\Services\Fuel\SainsburysService;
use App\Services\Fuel\TescoService;
use App\Services\Fuel\AsdaService;
use App\Services\Fuel\FuelAggregatorService;

class FuelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SainsburysService::class, function ($app) {
            return new SainsburysService();
        });

        $this->app->singleton(TescoService::class, function ($app) {
            return new TescoService();
        });

        $this->app->singleton(AsdaService::class, function ($app) {
            return new AsdaService();
        });

        // Bind the aggregator with all providers
        $this->app->singleton(FuelAggregatorService::class, function ($app) {
            $providers = [
                $app->make(SainsburysService::class),
                $app->make(TescoService::class),
                $app->make(AsdaService::class),
            ];

            return new FuelAggregatorService($providers);
        });
    }

    public function boot()
    {
        // nothing for now
    }
}
