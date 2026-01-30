<?php

namespace App\Console\Commands;

use App\Models\FuelLocation;
use App\Models\FuelPriceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Services\Fuel\FuelAggregatorService;

class FetchFuelPrices extends Command
{
    protected $signature = 'fuel:fetch {--force} {--country=GB}';

    protected $description = 'Fetch fuel prices from configured providers, insert into history, and print JSON.';

    public function handle(FuelAggregatorService $aggregator)
    {
        $force = $this->option('force');
        $country = strtoupper($this->option('country') ?? 'GB');

        // Enforce throttle: only update once per hour unless forced
        $last = FuelPriceHistory::orderByDesc('created_at')->value('created_at');
        if ($last && !$force) {
            $lastTs = Carbon::parse($last);

            // If the last record is within the past hour, skip unless forced.
            if ($lastTs->greaterThanOrEqualTo(Carbon::now()->subHour())) {
                $this->info('Last update was within the past hour. Use --force to override.');
                return 0;
            }
        }

        $data = $aggregator->fetchAll();

        $now = Carbon::now();
        $insertedPriceRows = 0;

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $source = $item['source'] ?? null;
            $providerSiteId = $item['id'] ?? null;

            if (empty($source)) {
                continue;
            }

            $addr = $item['address'] ?? $item['addr'] ?? null;
            $postcode = $item['postcode'] ?? $item['post_code'] ?? $item['postal_code'] ?? $item['zip'] ?? null;

            $nameValue = $addr;
            $addressValue = $addr;
            if ($addr && $postcode) {
                $addressValue = $addr . ', ' . $postcode;
            } elseif (!$addr && $postcode) {
                $addressValue = $postcode;
            }

            $locationAttrs = [
                'name' => $nameValue,
                'address' => $addressValue,
                'latitude' => isset($item['latitude']) ? $item['latitude'] : null,
                'longitude' => isset($item['longitude']) ? $item['longitude'] : null,
                'country_code' => $country,
            ];

            $location = FuelLocation::updateOrCreate([
                'source' => $source,
                'provider_site_id' => $providerSiteId,
            ], $locationAttrs);

            $prices = [];
            if (isset($item['prices'])) {
                if (is_string($item['prices'])) {
                    $decoded = json_decode($item['prices'], true);
                    if (is_array($decoded)) {
                        $prices = $decoded;
                    }
                } elseif (is_array($item['prices'])) {
                    $prices = $item['prices'];
                }
            }

            foreach ($prices as $fuelType => $price) {
                $ft = is_string($fuelType) ? strtolower(trim($fuelType)) : null;
                if ($ft === null) {
                    continue;
                }

                if (!is_numeric($price)) {
                    $price = floatval(preg_replace('/[^0-9\.]/', '', (string) $price));
                }

                $location->prices()->create([
                    'fuel_type' => $ft,
                    'price' => $price !== null ? (float) $price : null,
                    'currency' => 'GBP',
                    'recorded_at' => $now,
                ]);

                $insertedPriceRows++;
            }
        }

        $this->line('Imported '.$insertedPriceRows);

        return 0;
    }
}
