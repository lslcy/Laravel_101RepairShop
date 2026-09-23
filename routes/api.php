<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerApiController;

Route::prefix('customer')->group(function () {
    Route::get('/profile', [CustomerApiController::class, 'getProfile']);
    Route::get('/appliances', [CustomerApiController::class, 'getAppliances']);
    Route::get('/service-reports', [CustomerApiController::class, 'getServiceReports']);
    Route::get('/appointments', [CustomerApiController::class, 'getAppointments']);
    Route::post('/appointments', [CustomerApiController::class, 'createAppointment']);
});