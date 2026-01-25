<?php

namespace App\Dto\FuelDtos;

use App\Dto\FuelDtos\BaseFuelDto;

class SainsburysDto extends BaseFuelDto
{
    public function __construct(array $data = [])
    {
        parent::__construct([], 'sainsburys');

        // map common fields using heuristics
        $this->id = $data['id'] ?? $data['site_id'] ?? $data['SiteId'] ?? null;
        $this->name = $data['trading_name'] ?? $data['site_name'] ?? $data['name'] ?? null;

        // address assembly
        if (isset($data['address']) && is_string($data['address'])) {
            $this->address = $data['address'];
        } else {
            $lines = [];
            foreach (['address', 'street', 'addr_line1', 'addr1'] as $k) {
                if (!empty($data[$k])) {
                    $lines[] = $data[$k];
                }
            }
            $this->address = $lines ? implode(', ', $lines) : null;
        }

        // latitude / longitude
        $this->latitude = isset($data['location']['latitude']) ? (is_numeric($data['location']['latitude']) ? (float) $data['location']['latitude'] : null) : ($data['lat'] ?? null);
        $this->longitude = isset($data['location']['longitude']) ? (is_numeric($data['location']['longitude']) ? (float) $data['location']['longitude'] : null) : ($data['lng'] ?? $data['lon'] ?? null);

        // prices: look for common fuel keys
        $prices = [];
        $possibleKeys = ['unleaded', 'petrol', 'diesel', 'super_unleaded', 'e10', 'e5'];
        foreach ($data as $k => $v) {
            $lk = strtolower($k);
            foreach ($possibleKeys as $pk) {
                if (strpos($lk, $pk) !== false && is_numeric($v)) {
                    $prices[$pk] = (float) $v;
                }
            }
        }

        // some feeds nest prices under 'prices' or 'fuels'
        if (empty($prices) && !empty($data['prices']) && is_array($data['prices'])) {
            foreach ($data['prices'] as $fuelK => $fuelV) {
                if (is_string($fuelK)) {
                    $prices[strtolower($fuelK)] = is_numeric($fuelV) ? (float) $fuelV : $fuelV;
                } elseif (is_array($fuelV) && isset($fuelV['type']) && isset($fuelV['price'])) {
                    $prices[strtolower($fuelV['type'])] = (float) $fuelV['price'];
                }
            }
        }

        $this->prices = $prices;
    }

    public function build(): array
    {
        return $this->toArray();
    }
}