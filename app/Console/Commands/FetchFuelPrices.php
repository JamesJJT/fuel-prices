<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Fuel\FuelAggregatorService;
use App\Services\Fuel\SainsburysService;
use App\Services\Fuel\TescoService;

class FetchFuelPrices extends Command
{
    protected $signature = 'fuel:fetch {--force} {--country=GB} {--provider=}';

    protected $description = 'Fetch fuel prices from configured providers, insert into history, and print JSON.';

    public function handle(FuelAggregatorService $aggregator)
    {
        $force = $this->option('force');
        $country = strtoupper($this->option('country') ?? 'GB');

        // Enforce throttle: only update once per hour unless forced
        $last = DB::table('fuel_price_history')->orderByDesc('created_at')->value('created_at');
        if ($last && !$force) {
            $lastTs = Carbon::parse($last);

            // If last timestamp is in the future, treat it as "just now" (0 minutes ago)
            if (Carbon::now()->lessThan($lastTs)) {
                $minutesSince = 0;
            } else {
                $minutesSince = Carbon::now()->diffInMinutes($lastTs);
            }

            if ($minutesSince < 60) {
                if ($minutesSince <= 0) {
                    $readable = 'less than a minute';
                } elseif ($minutesSince === 1) {
                    $readable = '1 minute';
                } else {
                    $readable = "{$minutesSince} minutes";
                }

                $this->info("Last update was {$readable} ago. Use --force to override.");
                return 0;
            }
        }

        $providerOpt = $this->option('provider');

        if ($providerOpt) {
            $map = [
                'sainsburys' => SainsburysService::class,
                'tesco' => TescoService::class,
            ];

            $key = strtolower($providerOpt);
            if (!isset($map[$key])) {
                $this->error("Unknown provider: {$providerOpt}. Valid: " . implode(', ', array_keys($map)));
                return 1;
            }

            $providerInstance = app()->make($map[$key]);
            $data = $providerInstance->fetch();
            $target = $key;
        } else {
            $data = $aggregator->fetchAll();
            $target = 'all';
        }

        $this->line("Updating {$target}");

        $now = Carbon::now();
        $insertedPriceRows = 0;

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $source = $item['source'] ?? null;
            $providerSiteId = $item['id'] ?? null;

            // Build name/address: name = address, address = address + postcode (if available)
            $addr = $item['address'] ?? $item['addr'] ?? null;
            $postcode = $item['postcode'] ?? $item['post_code'] ?? $item['postal_code'] ?? $item['zip'] ?? null;

            $nameValue = $addr;
            $addressValue = $addr;
            if ($addr && $postcode) {
                $addressValue = $addr . ', ' . $postcode;
            } elseif (!$addr && $postcode) {
                $addressValue = $postcode;
            }

            $locationPayload = [
                'name' => $nameValue,
                'address' => $addressValue,
                'latitude' => isset($item['latitude']) ? $item['latitude'] : null,
                'longitude' => isset($item['longitude']) ? $item['longitude'] : null,
                'country_code' => $country,
                'updated_at' => $now,
            ];

            if (!empty($source)) {
                $locationWhere = ['source' => $source, 'provider_site_id' => $providerSiteId];

                DB::transaction(function () use ($locationWhere, $locationPayload, $now, $item, &$insertedPriceRows) {
                    $insertPayload = $locationPayload;
                    $insertPayload['created_at'] = $now;

                    DB::table('fuel_locations')->updateOrInsert($locationWhere, $insertPayload);

                    $locationId = DB::table('fuel_locations')->where($locationWhere)->value('id');

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

                        DB::table('fuel_price_history')->insert([
                            'location_id' => $locationId,
                            'fuel_type' => $ft,
                            'price' => $price !== null ? (float) $price : null,
                            'currency' => 'GBP',
                            'recorded_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $insertedPriceRows++;
                    }
                });
            }
        }

        $this->line('Imported '.$insertedPriceRows);

        return 0;
    }
}
