<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cashier\OrderController;
use App\Http\Controllers\Cashier\CustomerController;
use App\Http\Controllers\Cashier\ReceiptController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Processing\ProcessingController;

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('cashier.orders.create');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:owner_admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
});

Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/success', [OrderController::class, 'success'])->name('orders.success');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/receipt', [ReceiptController::class, 'index'])->name('receipt.index');
});

Route::prefix('processing')->name('processing.')->group(function () {
    Route::get('/', [ProcessingController::class, 'home'])->name('home');
    Route::get('/work-session', [ProcessingController::class, 'workSession'])->name('work-session');
    Route::post('/shift/start', [ProcessingController::class, 'startShift'])->name('shift.start');
    Route::post('/shift/finish', [ProcessingController::class, 'finishShift'])->name('shift.finish');
});