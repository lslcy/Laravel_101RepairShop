<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ServicePriceController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ApplianceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CustomerPaymentReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Acknowledge PayMongo callbacks; staff confirm payments on the existing transaction.
Route::post('/webhooks/paymongo', [TransactionController::class , 'paymongoWebhook'])->name('webhooks.paymongo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/appliances', [ApplianceController::class , 'store'])->name('customers.appliances.store');
    Route::put('/appliances/{appliance}', [ApplianceController::class , 'update'])->name('appliances.update');
    Route::delete('/appliances/{appliance}', [ApplianceController::class , 'destroy'])->name('appliances.destroy');

    Route::resource('services', ServiceReportController::class);
    Route::post('/services/{service}/comments', [ServiceReportController::class , 'storeComment'])->name('services.comments.store');
    Route::get('/services/{service}/print', [ServiceReportController::class , 'print'])->name('services.print');

    Route::resource('inventory', InventoryController::class);
    Route::resource('transactions', TransactionController::class);

    // Appointments booked from the Flutter mobile app
    Route::get('/appointments', [AppointmentController::class , 'index'])->name('appointments.index');
    Route::patch('/appointments/{appointment}', [AppointmentController::class , 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/convert', [AppointmentController::class , 'convert'])->name('appointments.convert');

    Route::middleware(['can:admin-only'])->group(function () {
            // Receipt evidence and QR settings are available only to administrators.
            Route::get('/customer-payments', [CustomerPaymentReviewController::class, 'index'])->name('customer-payments.index');
            Route::get('/customer-payments/settings', [CustomerPaymentReviewController::class, 'editSettings'])->name('customer-payments.settings.edit');
            Route::put('/customer-payments/settings', [CustomerPaymentReviewController::class, 'updateSettings'])->name('customer-payments.settings.update');
            Route::get('/customer-payments/{submission}', [CustomerPaymentReviewController::class, 'show'])->whereUuid('submission')->name('customer-payments.show');
            Route::get('/customer-payments/{submission}/receipt', [CustomerPaymentReviewController::class, 'receipt'])->whereUuid('submission')->name('customer-payments.receipt');
            Route::post('/customer-payments/{submission}/review', [CustomerPaymentReviewController::class, 'review'])->whereUuid('submission')->name('customer-payments.review');
            Route::resource('prices', ServicePriceController::class);
            // Archive Routes
            Route::get('/archive', [ArchiveController::class , 'index'])->name('archive.index');
            Route::post('/archive/{type}/{id}/restore', [ArchiveController::class , 'restore'])->name('archive.restore');
            Route::delete('/archive/{type}/{id}/force-delete', [ArchiveController::class , 'destroy'])->name('archive.destroy');
            Route::resource('staff', StaffController::class);
        }
        );    });

Route::middleware('auth')->group(function () {
    Route::get('/profile/view', [ProfileController::class , 'show'])->name('profile.show');
    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');

    // Notifications Route
    Route::get('/notifications', [NotificationController::class , 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class , 'markAllAsRead'])->name('notifications.markRead');
});

require __DIR__ . '/auth.php';
