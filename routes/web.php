<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PolaController;
use App\Http\Controllers\AksesorisController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PenerimaanBahanController;
use App\Http\Controllers\PemotonganSeriController;
use App\Http\Controllers\QcController;
use App\Http\Controllers\StokBarangJadiController;
use App\Http\Controllers\LaporanEksekutifController;
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Web & AJAX Routes for Bong Garment CMT Production System
|--------------------------------------------------------------------------
| Undergraduate Thesis: Luis Febry (NIM: 825220088)
| Universitas Tarumanagara 2026
*/

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Pola & 11-Metric Specifications
Route::prefix('pola')->group(function () {
    Route::get('/', [PolaController::class, 'index'])->name('pola.index');
    Route::get('/{id}', [PolaController::class, 'show'])->name('pola.show');
    Route::post('/', [PolaController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin')->name('pola.store');
});

// Aksesoris & Inventory
Route::prefix('aksesoris')->group(function () {
    Route::get('/', [AksesorisController::class, 'index'])->name('aksesoris.index');
    Route::post('/', [AksesorisController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin,Gudang')->name('aksesoris.store');
    Route::post('/{id}/stock', [AksesorisController::class, 'updateStock'])->middleware(RoleMiddleware::class . ':Admin,Gudang')->name('aksesoris.stock');
});

// Pesanan & EDF Deadline Management
Route::prefix('pesanan')->group(function () {
    Route::get('/', [PesananController::class, 'index'])->name('pesanan.index');
    Route::post('/', [PesananController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin')->name('pesanan.store');
    Route::post('/{id}/revise-deadline', [PesananController::class, 'reviseDeadline'])->middleware(RoleMiddleware::class . ':Admin')->name('pesanan.revise_deadline');
    Route::post('/{id}/status', [PesananController::class, 'updateStatus'])->middleware(RoleMiddleware::class . ':Admin')->name('pesanan.status');
});

// Penerimaan Bahan & Multi-Unit Roll Registration
Route::prefix('penerimaan')->group(function () {
    Route::get('/', [PenerimaanBahanController::class, 'index'])->name('penerimaan.index');
    Route::post('/', [PenerimaanBahanController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin,Gudang')->name('penerimaan.store');
});

// Pemotongan Seri & Fabric Spreading / Roll Deduction
Route::prefix('pemotongan')->group(function () {
    Route::get('/', [PemotonganSeriController::class, 'index'])->name('pemotongan.index');
    Route::post('/', [PemotonganSeriController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin,Pemotong')->name('pemotongan.store');
});

// Multi-Stage Quality Control & Perpetual Stock Integration
Route::prefix('qc')->group(function () {
    Route::get('/', [QcController::class, 'index'])->name('qc.index');
    Route::post('/', [QcController::class, 'store'])->middleware(RoleMiddleware::class . ':Admin,QC')->name('qc.store');
    Route::post('/{id}/complete-rework', [QcController::class, 'completeRework'])->middleware(RoleMiddleware::class . ':Admin,QC')->name('qc.complete_rework');
});

// Stok Barang Jadi
Route::prefix('stok')->group(function () {
    Route::get('/', [StokBarangJadiController::class, 'index'])->name('stok.index');
    Route::post('/adjust', [StokBarangJadiController::class, 'adjustStock'])->middleware(RoleMiddleware::class . ':Admin,Gudang')->name('stok.adjust');
});

// Laporan Eksekutif
Route::prefix('laporan')->group(function () {
    Route::get('/', [LaporanEksekutifController::class, 'index'])->middleware(RoleMiddleware::class . ':Admin,Pemilik')->name('laporan.index');
});

// Web-triggered database migration & seeding helper
Route::get('/migrate-db', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--force' => true,
            '--seed' => true,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response("<div style='font-family:sans-serif;padding:30px;background:#f8fafc;color:#1e293b;max-width:700px;margin:40px auto;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);'>"
            . "<h2 style='color:#16a34a;margin-top:0;'>&#10004; 17 Tables Migrated and Seeded Successfully</h2>"
            . "<p>Your MySQL database has been provisioned with all canonical tables and demonstration records.</p>"
            . "<pre style='background:#0f172a;color:#38bdf8;padding:16px;border-radius:8px;font-size:12px;overflow-x:auto;'>" . htmlspecialchars($output) . "</pre>"
            . "<p style='margin-top:20px;'><a href='/' style='background:#4f46e5;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;display:inline-block;'>Go to Dashboard &rarr;</a></p>"
            . "</div>");
    } catch (\Throwable $e) {
        return response("<div style='font-family:sans-serif;padding:30px;background:#fff1f2;color:#9f1239;max-width:700px;margin:40px auto;border-radius:12px;border:1px solid #fecdd3;'>"
            . "<h2 style='margin-top:0;'>&#10006; Migration Error</h2>"
            . "<pre style='background:#881337;color:#ffe4e6;padding:16px;border-radius:8px;font-size:12px;overflow-x:auto;'>" . htmlspecialchars($e->getMessage()) . "</pre>"
            . "</div>", 500);
    }
})->name('migrate.db');
