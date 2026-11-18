<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pesanan;
use App\Models\PemotonganSeri;
use App\Models\DetailPemotonganWarna;
use App\Models\DetailGelaranKain;
use App\Models\DetailRollBahan;
use App\Models\PenerimaanBahan;
use App\Models\Qc;
use App\Models\StokBarangJadi;
use App\Models\RiwayatStokBarangJadi;
use App\Models\Aksesoris;
use App\Models\SumberInisiasi;
use App\Models\User;

class LaporanEksekutifController extends Controller
{
    public function index(Request $request)
    {
        $jenisLaporan = strtoupper((string) $request->input('jenis_laporan', 'KONSOLIDASI'));
        $allowedJenis = ['KONSOLIDASI', 'STOK', 'BAHAN_MASUK', 'MUTASI_KELUAR', 'QC'];
        if (!in_array($jenisLaporan, $allowedJenis, true)) {
            $jenisLaporan = 'KONSOLIDASI';
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $idSumber = $request->input('id_sumber');

        // Resolve authenticated operator info for Kop Surat & Blok Pengesahan
        $authUserId = $_COOKIE['bong_garment_user_id'] ?? $request->cookie('bong_garment_user_id');
        $authRoleCookie = $_COOKIE['bong_garment_auth_role'] ?? $request->cookie('bong_garment_auth_role');
        $loggedUser = $authUserId ? User::find($authUserId) : null;
        $printerName = $loggedUser->nama_user ?? 'Administrator';
        if (stripos($printerName, 'Luis Febry') !== false) {
            $printerName = 'Administrator';
        }
        $printerUsername = $loggedUser->username ?? 'admin';
        $printerRole = $loggedUser->role ?? ($authRoleCookie ?: 'Admin');

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

        $rawChecked = (int) (clone $qcQuery)->sum('jumlah_diperiksa');
        $totalCheckedDivisor = $rawChecked > 0 ? $rawChecked : 1;
        $totalPassed = (int) (clone $qcQuery)->sum('jumlah_lolos');
        $totalDefective = (int) (clone $qcQuery)->sum('jumlah_cacat');
        $totalReworkPending = (clone $qcQuery)->where('status_qc', 'Need Rework')->count();
        $passRate = round(($totalPassed / $totalCheckedDivisor) * 100, 2);
        $defectRate = round(($totalDefective / $totalCheckedDivisor) * 100, 2);

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

        $qcList = (clone $qcQuery)
            ->with(['pesanan.pola', 'pesanan.sumberInisiasi', 'userQc'])
            ->orderBy('tgl_pemeriksaan', 'desc')
            ->orderBy('id_qc', 'desc')
            ->get();

        $totalCutSheets = (int) PemotonganSeri::sum('total_lembar_hasil');
        $totalBatchPotong = PemotonganSeri::count();
        $totalFinishedStock = (int) StokBarangJadi::sum('jumlah_stok_pcs');

        // Roll consumption & yield metrics
        $distinctRollsConsumed = DetailGelaranKain::distinct('id_roll')->count('id_roll');
        if ($distinctRollsConsumed === 0) {
            $distinctRollsConsumed = DetailRollBahan::whereIn('status_roll', ['Dipotong', 'Habis'])->count();
        }
        $totalFabricConsumed = (float) DetailPemotonganWarna::sum('kain_terpakai');
        $avgYieldPerRoll = $distinctRollsConsumed > 0
            ? round($totalCutSheets / $distinctRollsConsumed, 1)
            : (float) $totalCutSheets;

        // Global low-stock thresholds
        $thresholds = StokBarangJadiController::getThresholds();
        $minStokJadi = (int) ($thresholds['min_stok_jadi'] ?? 20);
        $minStokAksesoris = (int) ($thresholds['min_stok_aksesoris'] ?? 500);

        // Finished goods stock list
        $stokBarangJadiList = StokBarangJadi::with(['pola.kategori', 'spesifikasiUkuran'])
            ->orderBy('id_stok', 'asc')
            ->get();
        $lowStockJadiCount = $stokBarangJadiList->filter(function ($item) use ($minStokJadi) {
            return (int) $item->jumlah_stok_pcs <= $minStokJadi;
        })->count();

        // Accessories inventory list
        $aksesorisList = Aksesoris::with('supplier')
            ->orderBy('id_aksesoris', 'asc')
            ->get();
        $totalAksesorisUnit = (int) $aksesorisList->sum('stok');
        $lowStockAksesorisCount = $aksesorisList->filter(function ($item) use ($minStokAksesoris) {
            return (int) $item->stok < $minStokAksesoris;
        })->count();

        // Physical fabric rolls & status breakdown
        $rollList = DetailRollBahan::with(['detailBahanWarna.penerimaanBahan.supplier'])
            ->orderBy('id_roll', 'asc')
            ->get();
        $rollsTersedia = $rollList->where('status_roll', 'Tersedia');
        $rollsDipotong = $rollList->where('status_roll', 'Dipotong');
        $rollsHabis = $rollList->where('status_roll', 'Habis');

        // Inbound fabric receipts filtered by start_date/end_date on tgl_nota
        $penerimaanQuery = PenerimaanBahan::with(['supplier', 'detailBahanWarna.detailRollBahan']);
        if (!empty($startDate)) {
            $penerimaanQuery->whereDate('tgl_nota', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $penerimaanQuery->whereDate('tgl_nota', '<=', $endDate);
        }
        $penerimaanList = $penerimaanQuery
            ->orderBy('tgl_nota', 'desc')
            ->orderBy('id_penerimaan', 'desc')
            ->get();

        // Finished goods stock mutation ledger filtered by start_date/end_date on tgl_transaksi
        $riwayatQuery = RiwayatStokBarangJadi::with(['pola', 'spesifikasiUkuran', 'user']);
        if (!empty($startDate)) {
            $riwayatQuery->whereDate('tgl_transaksi', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $riwayatQuery->whereDate('tgl_transaksi', '<=', $endDate);
        }
        $riwayatStokList = $riwayatQuery
            ->orderBy('tgl_transaksi', 'desc')
            ->orderBy('id_riwayat_stok', 'desc')
            ->get();

        $totalOutboundTrx = $riwayatStokList->where('jenis_transaksi', 'KELUAR')->count();
        $totalOutboundPcs = (int) $riwayatStokList->where('jenis_transaksi', 'KELUAR')->sum('jumlah_pcs');
        $totalInboundPcs = (int) $riwayatStokList->where('jenis_transaksi', 'MASUK')->sum('jumlah_pcs');

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
            'meta' => [
                'printer_name' => $printerName,
                'printer_username' => $printerUsername,
                'printer_role' => $printerRole,
                'printed_at' => Carbon::now('Asia/Jakarta'),
            ],
            'thresholds' => $thresholds,
            'summary' => [
                'total_pesanan' => $totalOrders,
                'pesanan_selesai' => $completedOrders,
                'pesanan_proses' => $inProgressOrders,
                'total_roll_dikonsumsi' => $distinctRollsConsumed,
                'total_kain_terpakai' => $totalFabricConsumed,
                'avg_yield_per_roll' => $avgYieldPerRoll,
                'total_lembar_potong' => $totalCutSheets,
                'total_batch_potong' => $totalBatchPotong,
                'total_stok_jadi' => $totalFinishedStock,
                'total_sku_stok_jadi' => $stokBarangJadiList->count(),
                'low_stock_jadi_count' => $lowStockJadiCount,
                'total_aksesoris_item' => $aksesorisList->count(),
                'total_aksesoris_unit' => $totalAksesorisUnit,
                'low_stock_aksesoris_count' => $lowStockAksesorisCount,
                'total_roll_terdaftar' => $rollList->count(),
                'roll_tersedia_count' => $rollsTersedia->count(),
                'roll_tersedia_qty' => (float) $rollsTersedia->sum('ukuran_sisa'),
                'roll_dipotong_count' => $rollsDipotong->count(),
                'roll_dipotong_qty' => (float) $rollsDipotong->sum('ukuran_sisa'),
                'roll_habis_count' => $rollsHabis->count(),
                'total_ukuran_awal_roll' => (float) $rollList->sum('ukuran_awal'),
                'total_ukuran_sisa_roll' => (float) $rollList->sum('ukuran_sisa'),
                'total_penerimaan_nota' => $penerimaanList->count(),
                'total_outbound_trx' => $totalOutboundTrx,
                'total_outbound_pcs' => $totalOutboundPcs,
                'total_inbound_pcs' => $totalInboundPcs,
                'total_sampel_diperiksa' => $rawChecked,
                'total_lolos_qc' => $totalPassed,
                'total_cacat_qc' => $totalDefective,
                'total_rework_pending' => $totalReworkPending,
                'pass_rate_pct' => $passRate,
                'defect_rate_pct' => $defectRate,
            ],
            'defectBreakdown' => $defectBreakdown,
            'stageBreakdown' => $stageBreakdown,
            'consolidatedOrders' => $consolidatedOrders,
            'stokBarangJadiList' => $stokBarangJadiList,
            'aksesorisList' => $aksesorisList,
            'rollList' => $rollList,
            'penerimaanList' => $penerimaanList,
            'riwayatStokList' => $riwayatStokList,
            'qcList' => $qcList,
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $reports]);
        }

        return view('bong.laporan', compact('reports', 'sumberList'));
    }
}
