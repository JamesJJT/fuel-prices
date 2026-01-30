<?php

use App\Http\Controllers\FuelLocationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('fuel-locations', [FuelLocationController::class, 'index'])->name('fuel-locations');

Route::get('fuel-map', [FuelLocationController::class, 'map'])->name('fuel-map');

require __DIR__.'/settings.php';
