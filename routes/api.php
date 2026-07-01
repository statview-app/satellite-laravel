<?php

use Illuminate\Support\Facades\Route;
use Statview\Satellite\Http\Controllers\AboutController;
use Statview\Satellite\Http\Controllers\MaintenanceController;
use Statview\Satellite\Http\Controllers\PackagesController;
use Statview\Satellite\Http\Controllers\StatsController;

Route::get('about', AboutController::class);

Route::get('packages', PackagesController::class);

Route::get('stats', StatsController::class);

Route::post('toggle-maintenance', MaintenanceController::class);
