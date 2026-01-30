<?php

namespace App\Services\Fuel\UK;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Dto\FuelDtos\UK\TescoDto;
use App\Services\Fuel\FuelProviderInterface;

class TescoService implements FuelProviderInterface
{
    protected string $url = 'https://www.tesco.com/fuel_prices/fuel_prices_data.json';

    public function fetch(): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language' => 'en-GB,en;q=0.9',
            'Referer' => 'https://www.tesco.com/',
            'Origin' => 'https://www.tesco.com',
        ];

        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->timeout(10)
            ->get($this->url);

        if ($response->status() === 403) {
            Log::warning('TescoService received 403', ['url' => $this->url, 'status' => $response->status(), 'body' => substr($response->body(), 0, 1000)]);
            return [];
        }

        if (!$response->ok()) {
            return [];
        }

        $json = $response->json();

        $records = $this->extractRecords($json);

        $out = [];
        foreach ($records as $rec) {
            $dto = new TescoDto($rec);
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

        foreach (['stations', 'data', 'features', 'items', 'sites', 'fuel_prices'] as $k) {
            if (!empty($json[$k]) && is_array($json[$k])) {
                return $json[$k];
            }
        }

        return [];
    }
}
