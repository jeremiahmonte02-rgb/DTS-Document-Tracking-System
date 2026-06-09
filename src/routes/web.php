<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Public Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Real POST Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Application Routes (Phase 2 & 3 Pages)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/upload', [App\Http\Controllers\DocumentController::class, 'create'])->name('documents.create');
    Route::post('/upload', [App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
    Route::get('/scan', [App\Http\Controllers\DocumentController::class, 'showScanPage'])->name('scan');
    Route::get('/scan/lookup', [App\Http\Controllers\DocumentController::class, 'lookupDocument'])->name('scan.lookup');
    Route::post('/scan/receive', [App\Http\Controllers\DocumentController::class, 'receiveDocument'])->name('scan.receive');
    Route::get('/inbox', fn () => view('inbox'))->name('inbox');
    Route::get('/outbox', fn () => view('outbox'))->name('outbox');
    Route::get('/document-details/{document_number}', [App\Http\Controllers\DocumentController::class, 'showDocumentDetails'])->name('document-details.show');
    Route::post('/documents/confirm-receipt', [App\Http\Controllers\DocumentController::class, 'confirmReceipt'])->name('documents.confirm-receipt');
});

// Admin-only routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/users', fn () => view('users'))->name('users');
});