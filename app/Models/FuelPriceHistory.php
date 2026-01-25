<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPriceHistory extends Model
{
    protected $table = 'fuel_price_history';

    protected $fillable = [
        'location_id',
        'fuel_type',
        'price',
        'currency',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(FuelLocation::class, 'location_id');
    }
}
