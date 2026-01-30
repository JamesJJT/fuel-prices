<?php

namespace App\Services\Fuel\UK;

use Illuminate\Support\Facades\Http;
use App\Dto\FuelDtos\UK\JetDto;
use App\Services\Fuel\FuelProviderInterface;

class JetService implements FuelProviderInterface
{
    protected string $url = 'https://jetlocal.co.uk/fuel_prices_data.json';

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
            $dto = new JetDto($rec);
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
