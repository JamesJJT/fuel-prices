<?php

namespace App\Dto\FuelDtos;

use App\Dto\FuelDtos\BaseFuelDto;

class AsdaDto extends BaseFuelDto
{
    public function __construct(array $data = [])
    {
        parent::__construct([], 'asda');

        $this->id = $data['site_id'] ?? null;
        $this->name = $data['address'] ?? null;
        $this->address = $data['address'] . $data['postcode'] ?? null;
        $this->latitude = $data['location']['latitude'] ?? null;
        $this->longitude = $data['location']['longitude'] ?? null;
        $this->prices = $data['prices'] ?? [];
    }
}
