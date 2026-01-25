<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Fuel\FuelProviderInterface;
use App\Services\Fuel\SainsburysService;
use App\Services\Fuel\TescoService;
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

        // Bind the aggregator with the two providers by default
        $this->app->singleton(FuelAggregatorService::class, function ($app) {
            $providers = [
                $app->make(SainsburysService::class),
                $app->make(TescoService::class),
            ];

            return new FuelAggregatorService($providers);
        });
    }

    public function boot()
    {
        // nothing for now
    }
}
