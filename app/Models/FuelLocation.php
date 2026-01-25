<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelLocation extends Model
{
    protected $table = 'fuel_locations';

    protected $fillable = [
        'source',
        'provider_site_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'country_code',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(FuelPriceHistory::class, 'location_id');
    }

    /**
     * Get the latest price record for each fuel type for this location.
     * Returns a Collection of FuelPriceHistory models keyed by fuel_type.
     *
     * @return \Illuminate\Support\Collection
     */
    public function latestPrices()
    {
        $rows = $this->prices()->orderByDesc('recorded_at')->get();

        // unique by fuel_type keeps the first (most recent) entry per type
        return $rows->unique('fuel_type')->values();
    }

    /**
     * Get the latest price record for a single fuel type.
     * Returns null if not found.
     *
     * @param string $fuelType
     * @return \App\Models\FuelPriceHistory|null
     */
    public function latestPrice(string $fuelType)
    {
        return $this->prices()
            ->where('fuel_type', strtolower($fuelType))
            ->orderByDesc('recorded_at')
            ->first();
    }
}
