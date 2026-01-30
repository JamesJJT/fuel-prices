<?php

namespace App\Services\Fuel\UK;

use Illuminate\Support\Facades\Http;
use App\Dto\FuelDtos\UK\AsconaGroupDto;
use App\Services\Fuel\FuelProviderInterface;

class AsconaGroupService implements FuelProviderInterface
{
    protected string $url = 'https://fuelprices.asconagroup.co.uk/newfuel.json';

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
            $dto = new AsconaGroupDto($rec);
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
