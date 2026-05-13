<?php

use App\API\Controllers\AuthController;
use App\Http\Controllers\API\Controllers\ClientController;
use App\Http\Controllers\API\Controllers\ContractController;
use App\Http\Controllers\API\Controllers\EmailHistoryController;
use App\Http\Controllers\API\Controllers\ExpenseController;
use App\Http\Controllers\API\Controllers\InvoiceController;
use App\Http\Controllers\API\Controllers\PublicContractController;
use App\Http\Controllers\API\Controllers\RecurringInvoiceController;
use App\Http\Controllers\API\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['success' => true, 'message' => 'Xpendee API is running', 'version' => '1.0']);
});

// ─── Auth (no middleware) ────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ─── Public contract routes (no auth) ────────────────────────────────────────
Route::prefix('public')->group(function () {
    Route::get('/contract/{token}',                  [PublicContractController::class, 'show']);
    Route::post('/contract/{token}/request-otp',     [PublicContractController::class, 'requestOtp']);
    Route::post('/contract/{token}/sign',            [PublicContractController::class, 'sign']);
});

// ─── Protected routes ─────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'tenant.active'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me',           [AuthController::class, 'me']);

    // Settings
    Route::get('/settings',  [SettingsController::class, 'show']);
    Route::post('/settings', [SettingsController::class, 'update']);

    // Clients
    Route::get('/clients',         [ClientController::class, 'index']);
    Route::post('/clients',        [ClientController::class, 'store']);
    Route::get('/clients/{id}',    [ClientController::class, 'show']);
    Route::put('/clients/{id}',    [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

    // Invoices
    Route::get('/invoices',               [InvoiceController::class, 'index']);
    Route::post('/invoices',              [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}',          [InvoiceController::class, 'show']);
    Route::put('/invoices/{id}',          [InvoiceController::class, 'update']);
    Route::delete('/invoices/{id}',       [InvoiceController::class, 'destroy']);
    Route::post('/invoices/{id}/send',    [InvoiceController::class, 'send']);
    Route::post('/invoices/{id}/payment', [InvoiceController::class, 'payment']);

    // Expenses
    Route::get('/expenses/summary',  [ExpenseController::class, 'summary']);
    Route::get('/expenses',          [ExpenseController::class, 'index']);
    Route::post('/expenses',         [ExpenseController::class, 'store']);
    Route::get('/expenses/{id}',     [ExpenseController::class, 'show']);
    Route::put('/expenses/{id}',     [ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}',  [ExpenseController::class, 'destroy']);

    // Recurring Invoices
    Route::get('/recurring',              [RecurringInvoiceController::class, 'index']);
    Route::post('/recurring',             [RecurringInvoiceController::class, 'store']);
    Route::get('/recurring/{id}',         [RecurringInvoiceController::class, 'show']);
    Route::put('/recurring/{id}',         [RecurringInvoiceController::class, 'update']);
    Route::delete('/recurring/{id}',      [RecurringInvoiceController::class, 'destroy']);
    Route::post('/recurring/{id}/toggle', [RecurringInvoiceController::class, 'toggle']);
    Route::post('/recurring/{id}/run-now',[RecurringInvoiceController::class, 'runNow']);

    // Contracts
    Route::get('/contracts',           [ContractController::class, 'index']);
    Route::post('/contracts',          [ContractController::class, 'store']);
    Route::get('/contracts/{id}',      [ContractController::class, 'show']);
    Route::put('/contracts/{id}',      [ContractController::class, 'update']);
    Route::delete('/contracts/{id}',   [ContractController::class, 'destroy']);
    Route::post('/contracts/{id}/send',[ContractController::class, 'send']);

    // Email History
    Route::get('/email-history',      [EmailHistoryController::class, 'index']);
    Route::delete('/email-history/{id}', [EmailHistoryController::class, 'destroy']);
});
