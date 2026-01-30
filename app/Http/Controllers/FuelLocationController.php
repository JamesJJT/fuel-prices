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

        // If user location is provided, calculate distance and sort by it
        if ($userLat && $userLon) {
            // Haversine formula for distance calculation in SQL
            $query->selectRaw("
                *,
                (
                    6371 * acos(
                        cos(radians(?)) 
                        * cos(radians(latitude)) 
                        * cos(radians(longitude) - radians(?)) 
                        + sin(radians(?)) 
                        * sin(radians(latitude))
                    )
                ) AS distance
            ", [$userLat, $userLon, $userLat])
            ->orderBy('distance');
        }

        $locations = $query->get()->map(function ($location) {
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
                'distance' => isset($location->distance) ? round($location->distance, 2) : null,
                'prices' => $latestPrices->map(fn($price) => [
                    'fuel_type' => $price->fuel_type,
                    'price' => $price->price,
                    'currency' => $price->currency,
                    'recorded_at' => $price->recorded_at->format('Y-m-d H:i:s'),
                ])->values(),
            ];
        });

        return Inertia::render('FuelLocations', [
            'locations' => $locations,
            'userLocation' => [
                'lat' => $userLat,
                'lon' => $userLon,
            ],
        ]);
    }
}
