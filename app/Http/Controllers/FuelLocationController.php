<?php

namespace App\Http\Controllers;

use App\Models\FuelLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FuelLocationController extends Controller
{
    public function index(Request $request)
    {
        $userLat = $request->input('lat');
        $userLon = $request->input('lon');

        $query = FuelLocation::with(['prices' => function ($query) {
            $query->orderByDesc('recorded_at');
        }]);

        $locations = $query->get()->map(function ($location) use ($userLat, $userLon) {
            // Calculate distance in PHP for SQLite compatibility
            $distance = null;
            if ($userLat && $userLon && $location->latitude && $location->longitude) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLon,
                    $location->latitude,
                    $location->longitude
                );
            }

            // Get latest prices grouped by fuel type
            $latestPrices = $location->prices
                ->groupBy('fuel_type')
                ->map(fn($priceGroup) => $priceGroup->first());

            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'source' => $location->source,
                'distance' => $distance,
                'prices' => $latestPrices->map(fn($price) => [
                    'fuel_type' => $price->fuel_type,
                    'price' => $price->price,
                    'currency' => $price->currency,
                    'recorded_at' => $price->recorded_at->format('Y-m-d H:i:s'),
                ])->values(),
            ];
        });

        // Sort by distance if user location is provided
        if ($userLat && $userLon) {
            $locations = $locations->sortBy('distance')->values();
        }

        return Inertia::render('FuelLocations', [
            'locations' => $locations,
            'userLocation' => [
                'lat' => $userLat,
                'lon' => $userLon,
            ],
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula
     * Returns distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    public function map(Request $request)
    {
        $userLat = $request->input('lat');
        $userLon = $request->input('lon');

        $locations = FuelLocation::with(['prices' => function ($query) {
            $query->orderByDesc('recorded_at');
        }])->get()->map(function ($location) use ($userLat, $userLon) {
            // Calculate distance in PHP for SQLite compatibility
            $distance = null;
            if ($userLat && $userLon && $location->latitude && $location->longitude) {
                $distance = $this->calculateDistance(
                    $userLat,
                    $userLon,
                    $location->latitude,
                    $location->longitude
                );
            }

            // Get latest prices grouped by fuel type
            $latestPrices = $location->prices
                ->groupBy('fuel_type')
                ->map(fn($priceGroup) => $priceGroup->first());

            return [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'source' => $location->source,
                'distance' => $distance,
                'prices' => $latestPrices->map(fn($price) => [
                    'fuel_type' => $price->fuel_type,
                    'price' => $price->price,
                    'currency' => $price->currency,
                    'recorded_at' => $price->recorded_at->format('Y-m-d H:i:s'),
                ])->values(),
            ];
        });

        return Inertia::render('FuelMap', [
            'locations' => $locations,
            'userLocation' => [
                'lat' => $userLat,
                'lon' => $userLon,
            ],
        ]);
    }
}
