<?php

namespace App\Services\Fuel\UK;

use Illuminate\Support\Facades\Http;
use App\Dto\FuelDtos\UK\BPDto;
use App\Services\Fuel\FuelProviderInterface;

class BPService implements FuelProviderInterface
{
    protected string $url = 'https://www.bp.com/en_gb/united-kingdom/home/fuelprices/fuel_prices_data.json';

    public function fetch(): array
    {
        $response = Http::timeout(10)->get($this->url);

        if (!$response->ok()) {
            return [];
        }

        $json = $response->json();

        $records = $this->extractRecords($json);

        $out = [];
        foreach ($records as $rec) {
            $dto = new BPDto($rec);
            $out[] = $dto->toArray();
        }

        return $out;
    }

    protected function extractRecords($json): array
    {
        if (empty($json)) {
            return [];
        }

        if (is_array($json) && array_values($json) === $json) {
            return $json;
        }

        foreach (['stations', 'data', 'features', 'items', 'sites'] as $k) {
            if (!empty($json[$k]) && is_array($json[$k])) {
                return $json[$k];
            }
        }

        return [];
    }
}
