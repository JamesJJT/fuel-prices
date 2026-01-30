<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Fuel\FuelProviderInterface;
use App\Services\Fuel\UK\SainsburysService;
use App\Services\Fuel\UK\TescoService;
use App\Services\Fuel\UK\AsdaService;
use App\Services\Fuel\UK\BPService;
use App\Services\Fuel\UK\EssoService;
use App\Services\Fuel\UK\AsconaGroupService;
use App\Services\Fuel\UK\JetService;
use App\Services\Fuel\UK\KaranService;
use App\Services\Fuel\UK\MorrisonsService;
use App\Services\Fuel\UK\MotoService;
use App\Services\Fuel\UK\MotorFuelGroupService;
use App\Services\Fuel\UK\RontecService;
use App\Services\Fuel\UK\SgnService;
use App\Services\Fuel\UK\ShellService;
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

        $this->app->singleton(BPService::class, function ($app) {
            return new BPService();
        });

        $this->app->singleton(EssoService::class, function ($app) {
            return new EssoService();
        });

        $this->app->singleton(AsconaGroupService::class, function ($app) {
            return new AsconaGroupService();
        });

        $this->app->singleton(JetService::class, function ($app) {
            return new JetService();
        });

        $this->app->singleton(KaranService::class, function ($app) {
            return new KaranService();
        });

        $this->app->singleton(MorrisonsService::class, function ($app) {
            return new MorrisonsService();
        });

        $this->app->singleton(MotoService::class, function ($app) {
            return new MotoService();
        });

        $this->app->singleton(MotorFuelGroupService::class, function ($app) {
            return new MotorFuelGroupService();
        });

        $this->app->singleton(RontecService::class, function ($app) {
            return new RontecService();
        });

        $this->app->singleton(SgnService::class, function ($app) {
            return new SgnService();
        });

        $this->app->singleton(ShellService::class, function ($app) {
            return new ShellService();
        });

        // Bind the aggregator with all providers
        $this->app->singleton(FuelAggregatorService::class, function ($app) {
            $providers = [
                $app->make(SainsburysService::class),
                $app->make(TescoService::class),
                $app->make(AsdaService::class),
                $app->make(BPService::class),
                $app->make(EssoService::class),
                $app->make(AsconaGroupService::class),
                $app->make(JetService::class),
                $app->make(KaranService::class),
                $app->make(MorrisonsService::class),
                $app->make(MotoService::class),
                $app->make(MotorFuelGroupService::class),
                $app->make(RontecService::class),
                $app->make(SgnService::class),
                $app->make(ShellService::class),
            ];

            return new FuelAggregatorService($providers);
        });
    }

    public function boot()
    {
        // nothing for now
    }
}
