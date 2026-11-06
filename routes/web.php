<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PolaController;
use App\Http\Controllers\AksesorisController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PenerimaanBahanController;
use App\Http\Controllers\PemotonganSeriController;
use App\Http\Controllers\QcController;
use App\Http\Controllers\StokBarangJadiController;
use App\Http\Controllers\LaporanEksekutifController;
use App\Http\Controllers\PengaturanController;
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Web & AJAX Routes for Bong Garment CMT Production System
|--------------------------------------------------------------------------
*/

// Authentication Routes (Login / Logout)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout.post');

Route::middleware(RoleMiddleware::class . ':Admin,Pemilik,Pemotong,QC,Gudang')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Pola & 11-Metric Specifications: Admin
Route::prefix('pola')->middleware(RoleMiddleware::class . ':Admin')->group(function () {
    Route::get('/', [PolaController::class, 'index'])->name('pola.index');
    Route::get('/{id}', [PolaController::class, 'show'])->name('pola.show');
    Route::post('/', [PolaController::class, 'store'])->name('pola.store');
    Route::post('/{id}/update', [PolaController::class, 'update'])->name('pola.update');
});

// Pesanan & EDF Deadline Management: Admin
Route::prefix('pesanan')->middleware(RoleMiddleware::class . ':Admin')->group(function () {
    Route::get('/', [PesananController::class, 'index'])->name('pesanan.index');
    Route::post('/', [PesananController::class, 'store'])->name('pesanan.store');
    Route::post('/{id}/update', [PesananController::class, 'update'])->name('pesanan.update');
    Route::put('/{id}', [PesananController::class, 'update'])->name('pesanan.update_put');
    Route::post('/{id}/revise-deadline', [PesananController::class, 'reviseDeadline'])->name('pesanan.revise_deadline');
    Route::post('/{id}/status', [PesananController::class, 'updateStatus'])->name('pesanan.status');
});

// Penerimaan Bahan & Supplier Registration: Admin, Gudang
Route::prefix('penerimaan')->middleware(RoleMiddleware::class . ':Admin,Gudang')->group(function () {
    Route::get('/', [PenerimaanBahanController::class, 'index'])->name('penerimaan.index');
    Route::post('/', [PenerimaanBahanController::class, 'store'])->name('penerimaan.store');
    Route::post('/{id}/update', [PenerimaanBahanController::class, 'update'])->name('penerimaan.update');
    Route::post('/warna/{id}/update', [PenerimaanBahanController::class, 'updateWarna'])->name('penerimaan.warna.update');
    Route::post('/warna/{id}/add-roll', [PenerimaanBahanController::class, 'addRollToWarna'])->name('penerimaan.warna.add_roll');
    Route::post('/roll/{id}/update', [PenerimaanBahanController::class, 'updateRoll'])->name('penerimaan.roll.update');
});

Route::post('/supplier', [PenerimaanBahanController::class, 'storeSupplier'])
    ->middleware(RoleMiddleware::class . ':Admin,Gudang')
    ->name('supplier.store');

// Pemotongan Seri: Admin, Pemotong
Route::prefix('pemotongan')->middleware(RoleMiddleware::class . ':Admin,Pemotong')->group(function () {
    Route::get('/', [PemotonganSeriController::class, 'index'])->name('pemotongan.index');
    Route::post('/', [PemotonganSeriController::class, 'store'])->name('pemotongan.store');
    Route::post('/{id}/update', [PemotonganSeriController::class, 'update'])->name('pemotongan.update');
});

// Multi-Stage Quality Control: Admin, QC
Route::prefix('qc')->middleware(RoleMiddleware::class . ':Admin,QC')->group(function () {
    Route::get('/', [QcController::class, 'index'])->name('qc.index');
    Route::post('/', [QcController::class, 'store'])->name('qc.store');
    Route::post('/{id}/update', [QcController::class, 'update'])->name('qc.update');
    Route::post('/{id}/complete-rework', [QcController::class, 'completeRework'])->name('qc.complete_rework');
});

// Stok Barang Jadi: Admin, Gudang, Pemilik (Pemilik can view, POST mutations blocked by RoleMiddleware)
Route::prefix('stok')->middleware(RoleMiddleware::class . ':Admin,Gudang,Pemilik')->group(function () {
    Route::get('/', [StokBarangJadiController::class, 'index'])->name('stok.index');
    Route::post('/adjust', [StokBarangJadiController::class, 'adjustStock'])->name('stok.adjust');
    Route::post('/{id}/update', [StokBarangJadiController::class, 'update'])->name('stok.update');
});

// Aksesoris & Inventory: Admin, Gudang
Route::prefix('aksesoris')->middleware(RoleMiddleware::class . ':Admin,Gudang')->group(function () {
    Route::get('/', [AksesorisController::class, 'index'])->name('aksesoris.index');
    Route::post('/', [AksesorisController::class, 'store'])->name('aksesoris.store');
    Route::post('/pola-mapping', [AksesorisController::class, 'storePolaMapping'])->name('aksesoris.pola_mapping.store');
    Route::post('/pola-mapping/delete', [AksesorisController::class, 'destroyPolaMapping'])->name('aksesoris.pola_mapping.delete');
    Route::post('/{id}/stock', [AksesorisController::class, 'updateStock'])->name('aksesoris.stock');
    Route::post('/{id}/update', [AksesorisController::class, 'update'])->name('aksesoris.update');
    Route::post('/{id}/delete', [AksesorisController::class, 'destroy'])->name('aksesoris.delete');
});

// Laporan Eksekutif: Admin, Pemilik
Route::prefix('laporan')->middleware(RoleMiddleware::class . ':Admin,Pemilik')->group(function () {
    Route::get('/', [LaporanEksekutifController::class, 'index'])->name('laporan.index');
});

// Pengaturan Sistem, Manajemen Pengguna & Reset Produksi: Admin
Route::prefix('pengaturan')->middleware(RoleMiddleware::class . ':Admin')->group(function () {
    Route::get('/', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/user', [PengaturanController::class, 'storeUser'])->name('pengaturan.user.store');
    Route::post('/user/{id}/update', [PengaturanController::class, 'updateUser'])->name('pengaturan.user.update');
    Route::post('/user/{id}/delete', [PengaturanController::class, 'destroyUser'])->name('pengaturan.user.delete');
    Route::post('/kategori', [PengaturanController::class, 'storeKategori'])->name('pengaturan.kategori.store');
    Route::post('/kategori/{id}/update', [PengaturanController::class, 'updateKategori'])->name('pengaturan.kategori.update');
    Route::post('/kategori/{id}/delete', [PengaturanController::class, 'destroyKategori'])->name('pengaturan.kategori.delete');
    Route::post('/sumber', [PengaturanController::class, 'storeSumber'])->name('pengaturan.sumber.store');
    Route::post('/sumber/{id}/update', [PengaturanController::class, 'updateSumber'])->name('pengaturan.sumber.update');
    Route::post('/sumber/{id}/delete', [PengaturanController::class, 'destroySumber'])->name('pengaturan.sumber.delete');
    Route::post('/supplier/{id}/update', [PengaturanController::class, 'updateSupplier'])->name('pengaturan.supplier.update');
    Route::post('/supplier/{id}/delete', [PengaturanController::class, 'destroySupplier'])->name('pengaturan.supplier.delete');
    Route::post('/reset-operational', [PengaturanController::class, 'resetOperationalData'])->name('pengaturan.reset');
    Route::post('/seed-sample', [PengaturanController::class, 'seedSampleData'])->name('pengaturan.seed_sample');
});
