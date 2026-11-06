<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\PemotonganSeri;
use App\Models\DetailPemotonganWarna;
use App\Models\DetailGelaranKain;
use App\Models\DetailRollBahan;
use App\Models\Qc;
use App\Models\StokBarangJadi;
use App\Models\SumberInisiasi;

class LaporanEksekutifController extends Controller
{
    public function index(Request $request)
    {
        $jenisLaporan = $request->input('jenis_laporan', 'KONSOLIDASI');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $idSumber = $request->input('id_sumber');

        $pesananQuery = Pesanan::with([
            'pola',
            'sumberInisiasi',
            'penerimaanBahan',
            'pemotonganSeri.detailPemotonganWarna.detailGelaranKain.detailRollBahan',
            'qc',
        ]);

        if (!empty($startDate)) {
            $pesananQuery->whereDate('tgl_order', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $pesananQuery->whereDate('tgl_order', '<=', $endDate);
        }
        if (!empty($idSumber)) {
            $pesananQuery->where('id_sumber', $idSumber);
        }

        $ordersList = $pesananQuery->orderBy('id_pesanan', 'asc')->get();

        $totalOrders = $ordersList->count();
        $completedOrders = $ordersList->where('status_pesanan', 'Selesai')->count();
        $inProgressOrders = $ordersList->whereIn('status_pesanan', ['Antrean', 'Pemotongan', 'Dalam QC', 'Jahit', 'Finishing'])->count();

        $qcQuery = Qc::query();
        if (!empty($startDate)) {
            $qcQuery->whereDate('tgl_pemeriksaan', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $qcQuery->whereDate('tgl_pemeriksaan', '<=', $endDate);
        }

        $totalChecked = (clone $qcQuery)->sum('jumlah_diperiksa') ?: 1;
        $totalPassed = (clone $qcQuery)->sum('jumlah_lolos');
        $totalDefective = (clone $qcQuery)->sum('jumlah_cacat');
        $passRate = round(($totalPassed / $totalChecked) * 100, 2);
        $defectRate = round(($totalDefective / $totalChecked) * 100, 2);

        $rawDefects = (clone $qcQuery)->selectRaw('jenis_cacat, SUM(jumlah_cacat) as total_cacat')
            ->where('jenis_cacat', '!=', 'Tidak Ada')
            ->groupBy('jenis_cacat')
            ->pluck('total_cacat', 'jenis_cacat');

        // Always guarantee all 3 defect categories are explicitly present
        $defectBreakdown = collect([
            (object) [
                'jenis_cacat' => 'Cacat Potong',
                'label_lengkap' => 'Cacat Potong (Stasiun Pemotongan / Pola)',
                'total_cacat' => (int) ($rawDefects['Cacat Potong'] ?? 0),
            ],
            (object) [
                'jenis_cacat' => 'Cacat Jahit',
                'label_lengkap' => 'Cacat Jahit (Mitra Penjahit CMT)',
                'total_cacat' => (int) ($rawDefects['Cacat Jahit'] ?? 0),
            ],
            (object) [
                'jenis_cacat' => 'Cacat Bahan',
                'label_lengkap' => 'Cacat Bahan / Fabric Stain (Klaim Retur Supplier)',
                'total_cacat' => (int) ($rawDefects['Cacat Bahan'] ?? 0),
            ],
        ]);

        $stageBreakdown = (clone $qcQuery)->selectRaw('tahap_qc, SUM(jumlah_diperiksa) as total_diperiksa, SUM(jumlah_lolos) as total_lolos, SUM(jumlah_cacat) as total_cacat')
            ->groupBy('tahap_qc')
            ->get();

        $totalCutSheets = PemotonganSeri::sum('total_lembar_hasil');
        $totalFinishedStock = StokBarangJadi::sum('jumlah_stok_pcs');

        // Roll consumption & yield metrics
        $distinctRollsConsumed = DetailGelaranKain::distinct('id_roll')->count('id_roll');
        if ($distinctRollsConsumed === 0) {
            $distinctRollsConsumed = DetailRollBahan::whereIn('status_roll', ['Dipotong', 'Habis'])->count();
        }
        $totalFabricConsumed = (float) DetailPemotonganWarna::sum('kain_terpakai');
        $avgYieldPerRoll = $distinctRollsConsumed > 0
            ? round($totalCutSheets / $distinctRollsConsumed, 1)
            : (float) $totalCutSheets;

        // Build consolidated operational rows per order
        $consolidatedOrders = $ordersList->map(function ($ord) {
            $rollIds = [];
            $actualYield = 0;
            $kainTerpakaiOrder = 0.0;

            foreach ($ord->pemotonganSeri ?? [] as $seri) {
                $actualYield += (int) $seri->total_lembar_hasil;
                foreach ($seri->detailPemotonganWarna ?? [] as $pw) {
                    $kainTerpakaiOrder += (float) $pw->kain_terpakai;
                    foreach ($pw->detailGelaranKain ?? [] as $gk) {
                        $rollIds[] = '#' . $gk->id_roll;
                    }
                }
            }

            $uniqueRolls = array_values(array_unique($rollIds));
            $qcLolos = $ord->qc->sum('jumlah_lolos');
            $qcCacat = $ord->qc->sum('jumlah_cacat');

            return (object) [
                'id_pesanan' => $ord->id_pesanan,
                'kode_pola' => $ord->pola->kode_pola ?? '-',
                'nama_pola' => $ord->pola->nama_pola ?? $ord->jenis_pesanan,
                'nama_mitra' => $ord->nama_mitra ?? 'Internal (Produk Abadi)',
                'nama_sumber' => $ord->sumberInisiasi->nama_sumber ?? '-',
                'tgl_order' => $ord->tgl_order,
                'deadline_terbaru' => $ord->deadline_terbaru ?? $ord->deadline_awal,
                'rolls_used' => count($uniqueRolls) > 0 ? implode(', ', $uniqueRolls) : 'Belum Gelar',
                'rolls_count' => count($uniqueRolls),
                'kain_terpakai' => $kainTerpakaiOrder,
                'actual_yield' => $actualYield,
                'qc_lolos' => $qcLolos,
                'qc_cacat' => $qcCacat,
                'status_pesanan' => $ord->status_pesanan,
            ];
        });

        $sumberList = SumberInisiasi::all();

        $reports = [
            'filters' => [
                'jenis_laporan' => $jenisLaporan,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'id_sumber' => $idSumber,
            ],
            'summary' => [
                'total_pesanan' => $totalOrders,
                'pesanan_selesai' => $completedOrders,
                'pesanan_proses' => $inProgressOrders,
                'total_roll_dikonsumsi' => $distinctRollsConsumed,
                'total_kain_terpakai' => $totalFabricConsumed,
                'avg_yield_per_roll' => $avgYieldPerRoll,
                'total_lembar_potong' => $totalCutSheets,
                'total_stok_jadi' => $totalFinishedStock,
                'pass_rate_pct' => $passRate,
                'defect_rate_pct' => $defectRate,
            ],
            'defectBreakdown' => $defectBreakdown,
            'stageBreakdown' => $stageBreakdown,
            'consolidatedOrders' => $consolidatedOrders,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $reports]);
        }

        return view('bong.laporan', compact('reports', 'sumberList'));
    }
}
