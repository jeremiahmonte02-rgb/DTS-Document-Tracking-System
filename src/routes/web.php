<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;

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
    Route::get('/inbox', [App\Http\Controllers\DocumentController::class, 'inbox'])->name('inbox');
    Route::get('/api/inbox/data', [App\Http\Controllers\DocumentController::class, 'getInboxData'])->name('api.inbox.data');
    Route::get('/outbox', [App\Http\Controllers\DocumentController::class, 'outbox'])->name('outbox');
    Route::get('/api/outbox/data', [App\Http\Controllers\DocumentController::class, 'getOutboxData'])->name('api.outbox.data');
    Route::get('/document-details/{document_number}', [App\Http\Controllers\DocumentController::class, 'showDocumentDetails'])->name('document-details.show');
    Route::post('/documents/confirm-receipt', [App\Http\Controllers\DocumentController::class, 'confirmReceipt'])->name('documents.confirm-receipt');
    Route::post('/documents/{document_number}/receive', [App\Http\Controllers\DocumentController::class, 'receiveDocument'])->name('documents.receive');
    Route::post('/documents/{document_number}/complete', [App\Http\Controllers\DocumentController::class, 'completeDocument'])->name('documents.complete');
});

// Admin-only routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/manage-users', [UserController::class, 'index'])->name('users');
    Route::get('/api/users/data', [UserController::class, 'getUsersData'])->name('api.users.data');
    Route::get('/api/users/stats', [UserController::class, 'stats'])->name('api.users.stats');
    Route::post('/api/users', [UserController::class, 'store'])->name('api.users.store');
    Route::put('/api/users/{id}', [UserController::class, 'update'])->name('api.users.update');
    Route::patch('/api/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('api.users.toggle-status');
});