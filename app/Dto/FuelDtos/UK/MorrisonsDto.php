<?php

namespace App\Dto\FuelDtos\UK;

use App\Dto\FuelDtos\UK\BaseFuelDto;

class MorrisonsDto extends BaseFuelDto
{
    public function __construct(array $data = [])
    {
        parent::__construct([], 'morrisons');

        $this->id = $data['site_id'] ?? null;
        $this->name = $data['address'] ?? null;
        $this->address = $data['address'] . $data['postcode'] ?? null;
        $this->latitude = $data['location']['latitude'] ?? null;
        $this->longitude = $data['location']['longitude'] ?? null;
        $this->prices = $data['prices'] ?? [];
    }
}
