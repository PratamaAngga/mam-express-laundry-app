<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cashier\CustomerController;
use App\Http\Controllers\Cashier\OrderController;
use App\Http\Controllers\Cashier\ReceiptController;
use App\Http\Controllers\Owner\EmployeeController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\RestockController;
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
    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'owner_admin' => redirect()->route('owner.inventory.index'),
            default       => redirect()->route('cashier.orders.create'),
        };
    })->name('dashboard');
});

Route::middleware(['auth', 'role:owner_admin'])->prefix('owner')->name('owner.')->group(function () {
    // Raw Material Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    // Restock Supplier
    Route::get('/restock', [RestockController::class, 'index'])->name('restock.index');
    Route::get('/restock/create', [RestockController::class, 'create'])->name('restock.create');
    Route::post('/restock', [RestockController::class, 'store'])->name('restock.store');

    // Employee
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::patch('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::patch('/employees/{user}/status', [EmployeeController::class, 'toggleStatus'])->name('employees.status');
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
