<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\Pesanan;
use App\Models\PenerimaanBahan;
use App\Models\DetailRollBahan;
use App\Models\PemotonganSeri;
use App\Models\Qc;
use App\Models\StokBarangJadi;
use App\Models\Aksesoris;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $kpi = [
            'total_pesanan_aktif' => Pesanan::whereNotIn('status_pesanan', ['Selesai', 'Dibatalkan'])->count(),
            'total_roll_tersedia' => DetailRollBahan::where('status_roll', 'Tersedia')->count(),
            'total_potong_seri' => PemotonganSeri::count(),
            'total_stok_jadi_pcs' => (int) StokBarangJadi::sum('jumlah_stok_pcs'),
            'total_qc_need_rework' => Qc::where('status_qc', 'Need Rework')->count(),
            'total_aksesoris_low' => Aksesoris::where('stok', '<', 500)->count(),
        ];

        // EDF Cutter Queue
        $edfQueue = Pesanan::with(['pola', 'sumberInisiasi'])
            ->whereIn('status_pesanan', ['Antrean', 'Pemotongan'])
            ->orderByRaw('COALESCE(deadline_terbaru, deadline_awal) ASC')
            ->take(5)
            ->get();

        $recentQc = Qc::with('pesanan')
            ->orderBy('tgl_pemeriksaan', 'desc')
            ->take(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('kpi', 'edfQueue', 'recentQc'),
            ]);
        }

        return view('bong.dashboard', compact('kpi', 'edfQueue', 'recentQc'));
    }
}
