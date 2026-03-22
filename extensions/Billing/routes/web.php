<?php

declare(strict_types=1);

use Extensions\Billing\App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/seats', [BillingController::class, 'updateSeats'])->name('billing.seats');
    Route::post('/billing/swap', [BillingController::class, 'swap'])->name('billing.swap');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
});
