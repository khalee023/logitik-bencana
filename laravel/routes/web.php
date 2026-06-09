<?php

use App\Http\Controllers\AdminDaerahController;
use App\Http\Controllers\AdminPusatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KoordinatorController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — RBAC Protected
|--------------------------------------------------------------------------
*/

// === Halaman Publik (No Auth) ===
Route::get('/', [PublicController::class, 'index'])->name('public.map');

// === Auth Routes ===
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// === Admin Pusat (role: Pusat) ===
Route::prefix('admin-pusat')->middleware(['auth', 'role:Pusat'])->group(function () {
    Route::get('/', [AdminPusatController::class, 'dashboard'])->name('admin-pusat.dashboard');

    // Pusat Distribusi
    Route::get('/pusat-distribusi', [AdminPusatController::class, 'pusatDistribusiIndex'])->name('admin-pusat.pusat-distribusi.index');
    Route::post('/pusat-distribusi', [AdminPusatController::class, 'pusatDistribusiStore'])->name('admin-pusat.pusat-distribusi.store');
    Route::put('/pusat-distribusi/{pusat}', [AdminPusatController::class, 'pusatDistribusiUpdate'])->name('admin-pusat.pusat-distribusi.update');

    // Master Bantuan
    Route::get('/master-bantuan', [AdminPusatController::class, 'masterBantuanIndex'])->name('admin-pusat.master-bantuan.index');
    Route::post('/master-bantuan', [AdminPusatController::class, 'masterBantuanStore'])->name('admin-pusat.master-bantuan.store');

    // Stok
    Route::get('/stok', [AdminPusatController::class, 'stokIndex'])->name('admin-pusat.stok.index');
    Route::post('/stok', [AdminPusatController::class, 'stokUpdate'])->name('admin-pusat.stok.update');

    // Armada
    Route::get('/armada', [AdminPusatController::class, 'armadaIndex'])->name('admin-pusat.armada.index');
    Route::post('/armada', [AdminPusatController::class, 'armadaStore'])->name('admin-pusat.armada.store');
});

// === Admin Daerah (role: Daerah) ===
Route::prefix('admin-daerah')->middleware(['auth', 'role:Daerah'])->group(function () {
    Route::get('/', [AdminDaerahController::class, 'dashboard'])->name('admin-daerah.dashboard');
    Route::get('/demand', [AdminDaerahController::class, 'demandIndex'])->name('admin-daerah.demand.index');
    Route::post('/demand', [AdminDaerahController::class, 'demandStore'])->name('admin-daerah.demand.store');
    Route::put('/demand/{demand}', [AdminDaerahController::class, 'demandUpdate'])->name('admin-daerah.demand.update');
    Route::post('/demand/{demand}/queue', [AdminDaerahController::class, 'demandQueue'])->name('admin-daerah.demand.queue');
});

// === Tim SAR (role: SAR) ===
Route::prefix('sar')->middleware(['auth', 'role:SAR'])->group(function () {
    Route::get('/', [SarController::class, 'dashboard'])->name('sar.dashboard');
    Route::post('/rute/toggle-access', [SarController::class, 'toggleRouteAccess'])->name('api.rute.toggle');
    Route::post('/desa/update-status', [SarController::class, 'updateDesaStatus'])->name('api.desa.status');
});

// === Koordinator (role: Koor) ===
Route::prefix('koordinator')->middleware(['auth', 'role:Koor'])->group(function () {
    Route::get('/', [KoordinatorController::class, 'dashboard'])->name('koordinator.dashboard');
    Route::post('/optimize', [KoordinatorController::class, 'triggerOptimization'])->name('koordinator.optimize');
    Route::get('/review', [KoordinatorController::class, 'reviewSimulation'])->name('koordinator.review');
    Route::post('/approve', [KoordinatorController::class, 'approveSimulation'])->name('koordinator.approve');
    Route::post('/reject', [KoordinatorController::class, 'rejectSimulation'])->name('koordinator.reject');
    Route::post('/manifest/{manifest}/complete', [KoordinatorController::class, 'completeManifest'])->name('koordinator.manifest.complete');
    Route::get('/manifest', [KoordinatorController::class, 'manifestHistory'])->name('koordinator.manifest');
});
