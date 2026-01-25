<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Fuel\FuelAggregatorService;

class FetchFuelPrices extends Command
{
    protected $signature = 'fuel:fetch {--pretty}';

    protected $description = 'Fetch fuel prices from configured providers and print JSON.';

    public function handle(FuelAggregatorService $aggregator)
    {
        $this->info('Fetching fuel prices...');

        $data = $aggregator->fetchAll();

        if ($this->option('pretty')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $this->line(json_encode($data));
        }

        return 0;
    }
}
