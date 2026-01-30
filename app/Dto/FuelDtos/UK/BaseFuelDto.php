<?php

namespace App\Dto\FuelDtos\UK;

class BaseFuelDto
{
    public string $source = '';
    public ?string $id = null;
    public ?string $name = null;
    public ?string $address = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public array $prices = [];

    public function __construct(array $data = [], string $source = '')
    {
        $this->source = $source;

        if (isset($data['id'])) {
            $this->id = (string) $data['id'];
        }

        if (isset($data['name'])) {
            $this->name = (string) $data['name'];
        }

        if (isset($data['address'])) {
            $this->address = (string) $data['address'];
        }

        if (isset($data['latitude'])) {
            $this->latitude = is_numeric($data['latitude']) ? (float) $data['latitude'] : null;
        }

        if (isset($data['longitude'])) {
            $this->longitude = is_numeric($data['longitude']) ? (float) $data['longitude'] : null;
        }

        if (isset($data['prices']) && is_array($data['prices'])) {
            $this->prices = $data['prices'];
        }
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'prices' => $this->prices,
        ];
    }
}