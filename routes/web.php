<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Api\CheckOrderStatusController;
use App\Http\Controllers\Api\ConvertSendController;
use App\Http\Controllers\Api\DriverCostController;
use App\Http\Controllers\Api\ExportDataCsvController;
use App\Http\Controllers\Api\ImportLokasiController;
use App\Http\Controllers\Api\LogViewerController;
use App\Http\Controllers\Api\OrderLocationController;
use App\Http\Controllers\Api\ProductToRouteController;
use App\Http\Controllers\Api\QtyUnloadingController;
use App\Http\Controllers\Api\ReconciliationController;
use App\Http\Controllers\Api\SyncManagerController;
use App\Http\Controllers\Api\UbahOrderDataController;
use App\Http\Controllers\Api\UpdateOrderOnRouteController;
use App\Http\Controllers\Api\WmsIntegrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest / Auth (PRD §3)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| App pages + JSON API — session auth (PRD §4–5)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/env/select', [\App\Http\Controllers\EnvSelectorController::class, 'select'])->name('env.select');

    Route::resource('users', \App\Http\Controllers\UserManagementController::class)
        ->except(['show']);

    Route::get('/env-configuration', [\App\Http\Controllers\EnvConfigurationController::class, 'index'])->name('env-config.index');
    Route::put('/env-configuration', [\App\Http\Controllers\EnvConfigurationController::class, 'update'])->name('env-config.update');
    Route::post('/env-configuration/test-connection', [\App\Http\Controllers\EnvConfigurationController::class, 'testConnection'])->name('env-config.test');

    foreach (config('armos_menus.items', []) as $item) {
        Route::get($item['path'], [MenuController::class, 'show'])
            ->defaults('key', $item['key'])
            ->name($item['route']);
    }

    Route::prefix('api')->middleware('armos.env')->group(function () {
        // Menu 1 — Update Lokasi Customer (query_function.md §1)
        Route::get('/warehouses', [OrderLocationController::class, 'warehouses']);
        Route::get('/orders', [OrderLocationController::class, 'orders']);
        Route::get('/locations', [OrderLocationController::class, 'locations']);
        Route::post('/orders/update-location', [OrderLocationController::class, 'updateLocation']);

        // Menu 2 — Uncheck Reconciliation (§2)
        Route::get('/reconciliation', [ReconciliationController::class, 'index']);
        Route::post('/reconciliation/uncheck', [ReconciliationController::class, 'uncheck']);

        // Menu 3 — Log Viewer (monitoring index + production detail)
        Route::get('/logs/events', [LogViewerController::class, 'events']);
        Route::get('/logs', [LogViewerController::class, 'index']);
        Route::get('/logs/sync-status', [LogViewerController::class, 'syncStatus']);
        Route::post('/logs/sync', [LogViewerController::class, 'sync']);
        Route::get('/logs/{apiRequestLogId}', [LogViewerController::class, 'show'])
            ->whereNumber('apiRequestLogId');

        // Backward-compatible aliases
        Route::get('/log/events', [LogViewerController::class, 'events']);
        Route::get('/log/search', [LogViewerController::class, 'index']);

        // Menu 4 — Product To Route (§4)
        Route::get('/product-to-route', [ProductToRouteController::class, 'index']);

        // Menu 5 — WMS Integrasi (§5)
        Route::get('/wms-integration', [WmsIntegrationController::class, 'index']);
        Route::post('/wms-integration/update', [WmsIntegrationController::class, 'update']);

        // Menu 6 — Convert & Send (§6)
        Route::post('/convert-send', [ConvertSendController::class, 'store']);

        // Menu 7 — Sync Manager (§7)
        Route::post('/sync/run', [SyncManagerController::class, 'run']);
        Route::get('/sync/job/{jobId}', [SyncManagerController::class, 'job']);
        Route::get('/sync/status', [SyncManagerController::class, 'status']);

        // Menu 8 — Qty Unloading (§8)
        Route::get('/qty-unloading/warehouses', [QtyUnloadingController::class, 'warehouses']);
        Route::get('/qty-unloading/find', [QtyUnloadingController::class, 'find']);
        Route::post('/qty-unloading/update', [QtyUnloadingController::class, 'update']);

        // Menu 9 — Driver Cost (§9)
        Route::get('/driver-cost/list', [DriverCostController::class, 'list']);
        Route::post('/driver-cost/delete', [DriverCostController::class, 'delete']);

        // Menu 10 — Import Lokasi (§10)
        Route::post('/import-lokasi', [ImportLokasiController::class, 'import']);
        Route::get('/import-lokasi/download-log', [ImportLokasiController::class, 'downloadLog']);

        // Menu 11 — Check Order Status (§11)
        Route::get('/check-order-status/orders', [CheckOrderStatusController::class, 'orders']);
        Route::get('/check-order-status/order-details', [CheckOrderStatusController::class, 'orderDetails']);
        Route::get('/check-order-status/product-vs-inventory', [CheckOrderStatusController::class, 'productVsInventory']);

        // Menu 12 — Ubah Order Data (§12)
        Route::get('/ubah-order-status/search', [UbahOrderDataController::class, 'search']);
        Route::post('/ubah-order-data/update', [UbahOrderDataController::class, 'update']);

        // Menu 13 — Export Data CSV (§13)
        Route::post('/export-data-csv/generate', [ExportDataCsvController::class, 'generate']);
        Route::get('/export-data-csv/download', [ExportDataCsvController::class, 'download']);

        // Menu 14 — Update Order On Route (§14)
        Route::get('/update-order-on-route/search', [UpdateOrderOnRouteController::class, 'search']);
        Route::post('/update-order-on-route/update', [UpdateOrderOnRouteController::class, 'update']);
    });
});
