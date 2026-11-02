<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\PemotonganSeri;
use App\Models\Qc;
use App\Models\StokBarangJadi;

class LaporanEksekutifController extends Controller
{
    public function index(Request $request)
    {
        $totalOrders = Pesanan::count();
        $completedOrders = Pesanan::where('status_pesanan', 'Selesai')->count();
        $inProgressOrders = Pesanan::whereIn('status_pesanan', ['Antrean', 'Pemotongan', 'Jahit', 'Finishing'])->count();

        $totalChecked = Qc::sum('jumlah_diperiksa') ?: 1;
        $totalPassed = Qc::sum('jumlah_lolos');
        $totalDefective = Qc::sum('jumlah_cacat');
        $passRate = round(($totalPassed / $totalChecked) * 100, 2);
        $defectRate = round(($totalDefective / $totalChecked) * 100, 2);

        $defectBreakdown = Qc::selectRaw('jenis_cacat, SUM(jumlah_cacat) as total_cacat')
            ->where('jenis_cacat', '!=', 'Tidak Ada')
            ->groupBy('jenis_cacat')
            ->get();

        $stageBreakdown = Qc::selectRaw('tahap_qc, SUM(jumlah_diperiksa) as total_diperiksa, SUM(jumlah_lolos) as total_lolos, SUM(jumlah_cacat) as total_cacat')
            ->groupBy('tahap_qc')
            ->get();

        $totalCutSheets = PemotonganSeri::sum('total_lembar_hasil');
        $totalFinishedStock = StokBarangJadi::sum('jumlah_stok_pcs');

        $reports = [
            'summary' => [
                'total_pesanan' => $totalOrders,
                'pesanan_selesai' => $completedOrders,
                'pesanan_proses' => $inProgressOrders,
                'total_lembar_potong' => $totalCutSheets,
                'total_stok_jadi' => $totalFinishedStock,
                'pass_rate_pct' => $passRate,
                'defect_rate_pct' => $defectRate,
            ],
            'defectBreakdown' => $defectBreakdown,
            'stageBreakdown' => $stageBreakdown,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $reports]);
        }

        return view('bong.laporan', compact('reports'));
    }
}

