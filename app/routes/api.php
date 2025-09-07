<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationController;

// Secured by static API key via X-API-Key header
Route::middleware('apikey')->group(function () {
    Route::get('/buildings/{building}/organizations', [OrganizationController::class, 'indexByBuilding'])
        ->name('buildings.organizations.index');

    // List organizations by activity (includes descendants)
    Route::get('/activities/{activity}/organizations', [OrganizationController::class, 'indexByActivity'])
        ->name('activities.organizations.index');
});
