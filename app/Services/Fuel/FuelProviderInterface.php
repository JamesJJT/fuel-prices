<?php

namespace App\Services\Fuel;

use App\Dto\FuelDtos\BaseFuelDto;

interface FuelProviderInterface
{
    /**
     * Fetch raw and mapped fuel data from the provider.
     * Should return an array of BaseFuelDto instances or arrays.
     *
     * @return array
     */
    public function fetch(): array;
}
