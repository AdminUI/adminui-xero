<?php

use Inertia\Inertia;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Facades\Route;
use AdminUI\AdminUIXero\Controllers\XeroController;

// Route::prefix('xero')
//     ->as('admin.xero.')
//     ->middleware(['adminui', 'auth:admin'])
//     ->group(function () {
//         Route::get('/', [XeroController::class, 'index'])->name('index');
//     });

Route::prefix(config('adminui.prefix'))->middleware(['adminui', 'auth:admin'])->group(function () {

    // // Xero routes requiring authentication
    // Route::middleware(['XeroAuthenticated'])->group(function() {
    //     Route::get('xero', function () {
    //         return Xero::getTenantName();
    //     });
    //     Route::get('xero/test', function() {
    //         return Inertia::render('xero::Xero');
    //     });
    // });

    Route::get('xero/connect', function () {
        return Xero::connect();
    })->name('admin.setup.xero.connect');

    Route::get('setup/xero', [XeroController::class, 'index'])->name('admin.setup.xero.index');
    Route::delete('setup/xero', [XeroController::class, 'disconnect'])->name('admin.setup.xero.disconnect');
    Route::get('setup/xero/sync', [XeroController::class, 'sync'])->name('admin.setup.xero.sync.contacts');

    Route::get('xero/manual/order/{id}', [XeroController::class, 'manual']);
});
