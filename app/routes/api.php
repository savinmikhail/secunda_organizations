<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\BuildingController;

// Secured by static API key via X-API-Key header
Route::middleware('apikey')->group(function () {
    // List buildings
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');

    Route::get('/buildings/{building}/organizations', [OrganizationController::class, 'indexByBuilding'])
        ->name('buildings.organizations.index');

    // List organizations by activity (includes descendants)
    Route::get('/activities/{activity}/organizations', [OrganizationController::class, 'indexByActivity'])
        ->name('activities.organizations.index');

    // List organizations by geo (radius or rectangle)
    Route::get('/organizations/geo', [OrganizationController::class, 'indexByGeo'])
        ->name('organizations.geo');
});
