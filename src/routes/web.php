<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Api\DocumentTypePolicyController;

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
    //Route::post('/documents/confirm-receipt', [App\Http\Controllers\DocumentController::class, 'confirmReceipt'])->name('documents.confirm-receipt');
    Route::post('/documents/{document_number}/receive', [App\Http\Controllers\DocumentController::class, 'receiveDocument'])->name('documents.receive');
    Route::post('/documents/{document_number}/complete', [App\Http\Controllers\DocumentController::class, 'completeDocument'])->name('documents.complete');
    Route::post('/documents/{document_number}/reject', [App\Http\Controllers\DocumentController::class, 'rejectDocument'])->name('documents.reject');
    Route::post('/documents/{document_number}/cancel', [App\Http\Controllers\DocumentController::class, 'cancelDocument'])->name('documents.cancel');
    Route::post('/documents/{document_number}/update-routing', [App\Http\Controllers\DocumentController::class, 'updateRoutingPath'])->name('documents.update-routing');
    Route::post('/api/issues', [App\Http\Controllers\DocumentController::class, 'reportIssue'])->name('api.issues.report');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
    Route::get('/api/document-types/{id}/policy', [DocumentTypePolicyController::class, 'show'])->name('api.document-types.policy');
});

// Admin-only routes (gated via UserPolicy)
Route::middleware(['auth'])->group(function () {
    Route::get('/manage-users', [UserController::class, 'index'])->name('users')->can('viewAny', App\Models\User::class);
    Route::get('/api/users/data', [UserController::class, 'getUsersData'])->name('api.users.data')->can('viewAny', App\Models\User::class);
    Route::get('/api/users/stats', [UserController::class, 'stats'])->name('api.users.stats')->can('viewAny', App\Models\User::class);
    Route::post('/api/users', [UserController::class, 'store'])->name('api.users.store')->can('create', App\Models\User::class);
    Route::put('/api/users/{user}', [UserController::class, 'update'])->name('api.users.update')->can('update', 'user');
    Route::patch('/api/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('api.users.toggle-status')->can('toggleStatus', 'user');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/audit', [AuditorController::class, 'dashboard'])->name('audit.dashboard');
    Route::get('/audit/documents', [AuditorController::class, 'documents'])->name('audit.documents');
    Route::get('/api/audit/documents/data', [AuditorController::class, 'getDocumentData'])->name('api.audit.documents.data');

    // Department Management
    Route::get('/audit/departments', [AuditorController::class, 'departments'])->name('audit.departments');
    Route::get('/api/audit/departments/data', [AuditorController::class, 'getDepartmentsData'])->name('api.audit.departments.data');
    Route::post('/api/audit/departments', [AuditorController::class, 'storeDepartment'])->name('api.audit.departments.store');
    Route::put('/api/audit/departments/{id}', [AuditorController::class, 'updateDepartment'])->name('api.audit.departments.update');
    Route::patch('/api/audit/departments/{id}/toggle', [AuditorController::class, 'toggleDepartment'])->name('api.audit.departments.toggle');

    // Department Details & Analytics
    Route::get('/audit/departments/{id}', [AuditorController::class, 'departmentDetails'])->name('audit.departments.details');
    Route::get('/api/audit/departments/{id}/data', [AuditorController::class, 'getDepartmentDetailsData'])->name('api.audit.departments.details.data');
    Route::post('/audit/departments/{id}/slas', [AuditorController::class, 'updateDepartmentSlas'])->name('audit.departments.update-slas');

    // Document Type Routing Policies
    Route::get('/audit/policies', [AuditorController::class, 'indexPolicies'])->name('audit.policies');
    Route::post('/audit/policies', [AuditorController::class, 'storePolicy'])->name('audit.policies.store');

    // Reported Issues
    Route::get('/audit/issues', [AuditorController::class, 'issues'])->name('audit.issues');
});