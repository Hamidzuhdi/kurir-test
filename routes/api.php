<?php

use App\Http\Controllers\Api\V1\CourierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::apiResource('couriers', CourierController::class);
});
