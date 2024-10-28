<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PinjamController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Peminjam\PinjamController as PeminjamPinjamController;
use App\Http\Controllers\Penyetuju\PinjamController as PenyetujuPinjamController;

Route::get('/', [DashboardController::class, 'dashboard']);
Route::get('/filter', [DashboardController::class, 'filterItems'])->name('filter');
Route::get('/get-available-items', [DashboardController::class, 'getAvailableItems'])->name('getAvailableItems');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');



Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});


Route::middleware(['auth', 'check.status'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'dashboardAdmin'])->middleware('role:admin');
    Route::get('/peminjam/dashboard', [DashboardController::class, 'dashboardPeminjam'])->middleware('role:peminjam');
    Route::get('/penyetuju/dashboard', [DashboardController::class, 'dashboardPenyetuju'])->middleware('role:penyetuju');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('items', ItemController::class);
    Route::resource('users', UserController::class);
    Route::put('users/{user}/toggleStatus', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
    Route::resource('pinjams', PinjamController::class)->only(['index', 'create', 'store', 'destroy','edit','update']);
    Route::get('pinjams/get-available-items', [DashboardController::class, 'getAvailableItems'])->name('pinjams.getAvailableItems');
    Route::put('pinjams/{id}/status/{status}', [PinjamController::class, 'updateStatus'])->name('pinjams.updateStatus');
    Route::get('reports', [ReportController::class, 'reports']);
    Route::get('reports/filter', [ReportController::class, 'filterReports'])->name('reports.filter');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('pinjams/{id}/print', [PinjamController::class, 'print'])->name('pinjams.print');
});

Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    Route::resource('pinjams', PeminjamPinjamController::class)->only(['index', 'create', 'store', 'destroy','edit','update']);
    Route::get('pinjams/cek/{id}', [PeminjamPinjamController::class, 'cek'])->name('pinjams.cek');
    Route::get('pinjams/get-available-items', [DashboardController::class, 'getAvailableItems'])->name('pinjams.getAvailableItems');
    Route::put('pinjams/{id}/status/{status}', [PeminjamPinjamController::class, 'updateStatus'])->name('pinjams.updateStatus');
    Route::get('reports', [ReportController::class, 'reports']);
    Route::get('reports/filter', [ReportController::class, 'filterReports'])->name('reports.filter');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('pinjams/{id}/print', [PinjamController::class, 'print'])->name('pinjams.print');
});

Route::middleware(['auth', 'role:penyetuju'])->prefix('penyetuju')->name('penyetuju.')->group(function () {
    Route::resource('pinjams', PenyetujuPinjamController::class)->only(['index', 'create', 'store', 'destroy','edit','update']);
    Route::put('pinjams/{id}/update-status', [PenyetujuPinjamController::class, 'updateStatus'])->name('pinjams.update-status');
    Route::get('pinjams/cek/{id}', [PenyetujuPinjamController::class, 'cek'])->name('pinjams.cek');
    Route::get('pinjams/get-available-items', [DashboardController::class, 'getAvailableItems'])->name('pinjams.getAvailableItems');
    Route::put('pinjams/{id}/status/{status}', [PenyetujuPinjamController::class, 'updateStatus'])->name('pinjams.updateStatus');
    Route::get('reports', [ReportController::class, 'reports']);
    Route::get('reports/filter', [ReportController::class, 'filterReports'])->name('reports.filter');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('pinjams/{id}/print', [PinjamController::class, 'print'])->name('pinjams.print');
});