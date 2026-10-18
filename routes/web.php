<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cashier\CustomerController;
use App\Http\Controllers\Cashier\OrderController;
use App\Http\Controllers\Cashier\ReceiptController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\RestockController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\ServicePricingController;
use App\Http\Controllers\Supplier\RestockOrderController;
use App\Http\Controllers\Supplier\ShipmentController;
use App\Http\Controllers\Processing\ProcessingController;
use App\Http\Controllers\Processing\ProcessingOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'owner_admin'        => redirect()->route('owner.dashboard'),
            'kasir'              => redirect()->route('cashier.orders.create'),
            'petugas_processing' => redirect()->route('processing.home'),
            'supplier'           => redirect()->route('supplier.restock.index'),
            default              => abort(403, 'Halaman untuk role ini belum tersedia.'),
        };
    })->name('dashboard');
});

Route::middleware(['auth', 'role:owner_admin'])->prefix('owner')->name('owner.')->group(function () {
    // Dasboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');

    // Raw Material Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    // Restock Supplier
    Route::get('/restock', [RestockController::class, 'index'])->name('restock.index');
    Route::get('/restock/create', [RestockController::class, 'create'])->name('restock.create');
    Route::post('/restock', [RestockController::class, 'store'])->name('restock.store');

    // Report
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/print', [ReportController::class, 'printView'])->name('reports.print');

    // Employee
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::patch('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::patch('/employees/{user}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.status');

    // Service Pricing
    Route::get('/pricing', [ServicePricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing', [ServicePricingController::class, 'store'])->name('pricing.store');
    Route::patch('/pricing/additional', [ServicePricingController::class, 'updateAdditional'])->name('pricing.additional');
    Route::patch('/pricing/{service}', [ServicePricingController::class, 'update'])->name('pricing.update');
});

Route::middleware(['auth', 'role:kasir'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/success', [OrderController::class, 'success'])->name('orders.success');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/receipt', [ReceiptController::class, 'index'])->name('receipt.index');
});

Route::middleware(['auth', 'role:petugas_processing'])->prefix('processing')->name('processing.')->group(function () {
    Route::get('/', [ProcessingController::class, 'home'])->name('home');
    Route::get('/work-session', [ProcessingController::class, 'workSession'])->name('work-session');
    Route::post('/shift/start', [ProcessingController::class, 'startShift'])->name('shift.start');
    Route::post('/shift/finish', [ProcessingController::class, 'finishShift'])->name('shift.finish');
    Route::post('/work-session/update-status', [ProcessingController::class, 'updateStatus'])->name('work-session.update');

    // Scan QR / kode manual -> konfirmasi -> ubah status
    Route::post('/scan', [ProcessingOrderController::class, 'find'])->name('scan');
    Route::get('/orders/{code}', [ProcessingOrderController::class, 'show'])->where('code', '[A-Za-z0-9-]+')->name('order.show');
    Route::post('/orders/{code}/advance', [ProcessingOrderController::class, 'advance'])->where('code', '[A-Za-z0-9-]+')->name('order.advance');
});

Route::middleware(['auth', 'role:supplier'])->prefix('supplier')->name('supplier.')->group(function () {
    // Restock orders
    Route::get('/restock', [RestockOrderController::class, 'index'])->name('restock.index');
    Route::get('/restock/{order}', [RestockOrderController::class, 'show'])->name('restock.show');
    Route::patch('/restock/{order}/confirm', [RestockOrderController::class, 'confirm'])->name('restock.confirm');

    // Shipments
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{order}', [ShipmentController::class, 'edit'])->name('shipments.edit');
    Route::patch('/shipments/{order}', [ShipmentController::class, 'update'])->name('shipments.update');
});