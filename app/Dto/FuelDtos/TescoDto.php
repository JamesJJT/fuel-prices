<?php

namespace App\Dto\FuelDtos;

use App\Dto\FuelDtos\BaseFuelDto;

class TescoDto extends BaseFuelDto
{
    public function __construct(array $data = [])
    {
        parent::__construct([], 'tesco');

        $this->id = $data['id'] ?? $data['site_id'] ?? $data['SiteId'] ?? null;
        $this->name = $data['site_name'] ?? $data['name'] ?? null;

        if (!empty($data['address'])) {
            $this->address = is_array($data['address']) ? implode(', ', $data['address']) : (string) $data['address'];
        } else {
            $parts = [];
            foreach (['street', 'addr1', 'addr_line1'] as $k) {
                if (!empty($data[$k])) {
                    $parts[] = $data[$k];
                }
            }
            $this->address = $parts ? implode(', ', $parts) : null;
        }

        $this->latitude = isset($data['latitude']) ? (is_numeric($data['latitude']) ? (float) $data['latitude'] : null) : ($data['lat'] ?? null);
        $this->longitude = isset($data['longitude']) ? (is_numeric($data['longitude']) ? (float) $data['longitude'] : null) : ($data['lng'] ?? $data['lon'] ?? null);

        $prices = [];
        if (!empty($data['fuels']) && is_array($data['fuels'])) {
            foreach ($data['fuels'] as $fuel) {
                if (is_array($fuel)) {
                    $type = $fuel['type'] ?? $fuel['fuel'] ?? null;
                    $price = $fuel['price'] ?? $fuel['pence'] ?? null;
                    if ($type && is_numeric($price)) {
                        $prices[strtolower($type)] = (float) $price;
                    }
                }
            }
        }

        // fallback: direct price keys
        foreach ($data as $k => $v) {
            $lk = strtolower($k);
            if (in_array($lk, ['unleaded', 'diesel', 'e10', 'e5', 'super'])) {
                if (is_numeric($v)) {
                    $prices[$lk] = (float) $v;
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
