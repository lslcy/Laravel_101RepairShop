<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Middleware\AuthenticateSupabaseCustomer;

// All customer routes require a Supabase Auth access token: "Authorization: Bearer <token>".
Route::prefix('customer')
    ->middleware([AuthenticateSupabaseCustomer::class, 'throttle:60,1'])
    ->group(function () {
        Route::get('/profile', [CustomerApiController::class, 'getProfile']);
        Route::get('/appliances', [CustomerApiController::class, 'getAppliances']);
        Route::get('/service-reports', [CustomerApiController::class, 'getServiceReports']);
        Route::get('/transactions', [CustomerApiController::class, 'getTransactions']);
        Route::get('/appointments', [CustomerApiController::class, 'getAppointments']);
        Route::post('/appointments', [CustomerApiController::class, 'createAppointment']);
        Route::patch('/appointments/{appointment}/cancel', [CustomerApiController::class, 'cancelAppointment'])->whereNumber('appointment');
    });
