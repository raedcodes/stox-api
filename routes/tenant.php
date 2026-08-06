<?php

declare(strict_types=1);

use App\Http\Controllers\PurchaseOrderPdfController;
use App\Http\Controllers\SaleInvoiceController;
use App\Http\Middleware\AuthenticateBusinessUser;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware([AuthenticateBusinessUser::class])->group(function () {
        Route::get('/sales/{sale}/invoice', [SaleInvoiceController::class, 'show'])
            ->name('business.sales.invoice');
        Route::get('/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderPdfController::class, 'show'])
            ->name('business.purchase-orders.pdf');
    });
});
