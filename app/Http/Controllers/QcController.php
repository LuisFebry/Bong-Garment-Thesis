<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Qc;
use App\Models\Pesanan;
use App\Models\StokBarangJadi;
use App\Models\RiwayatStokBarangJadi;
use App\Models\SpesifikasiUkuran;
use App\Models\User;

class QcController extends Controller
{
    public function index(Request $request)
    {
        $qcRecords = Qc::with(['pesanan.pola', 'userQc'])
            ->orderBy('tgl_pemeriksaan', 'desc')
            ->get();

        $orders = Pesanan::with([
            'pola.spesifikasiUkuran',
            'penerimaanBahan.detailBahanWarna',
        ])->orderBy('id_pesanan', 'desc')->get();

        $qcUsers = User::whereIn('role', ['QC', 'Admin'])->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('qcRecords', 'orders', 'qcUsers'),
            ]);
        }

        return view('bong.qc', compact('qcRecords', 'orders', 'qcUsers'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('id_user_qc')) {
            $defaultQcId = $request->cookie('bong_garment_user_id')
                ?: User::where('role', 'QC')->value('id_user')
                ?: 1;
            $request->merge(['id_user_qc' => (int) $defaultQcId]);
        }

        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_qc' => 'required|integer|exists:User,id_user',
            'tahap_qc' => 'required|in:Prototipe,Potong,Jahit Akhir',
            'tgl_pemeriksaan' => 'nullable|date',
            'jumlah_diperiksa' => 'required|integer|min:1',
            'jumlah_lolos' => 'required|integer|min:0',
            'jumlah_cacat' => 'required|integer|min:0',
            'jenis_cacat' => 'required|in:Tidak Ada,Cacat Potong,Cacat Jahit,Cacat Bahan',
            'pic_rework' => 'nullable|string|max:100',
            'catatan_qc' => 'nullable|string',
            'status_qc' => 'required|in:Lolos QC,Need Rework,Rework Selesai,Scrapped',
            'tgl_target_perbaikan' => 'nullable|date',
            'tgl_selesai_perbaikan' => 'nullable|date',
            'id_spesifikasi' => 'nullable|integer|exists:Spesifikasi_Ukuran,id_spesifikasi',
            'nama_warna' => 'nullable|string|max:50',
        ]);

        $diperiksa = (int) $validated['jumlah_diperiksa'];
        $lolos = (int) $validated['jumlah_lolos'];
        $cacat = (int) $validated['jumlah_cacat'];

        if (($lolos + $cacat) !== $diperiksa) {
            return response()->json([
                'success' => false,
                'message' => "Validasi Mutu Gagal: Jumlah Lolos ($lolos) + Jumlah Cacat ($cacat) harus sama dengan Jumlah Diperiksa ($diperiksa).",
            ], 422);
        }

        return DB::transaction(function () use ($validated, $request) {
            $tglPemeriksaan = !empty($validated['tgl_pemeriksaan'])
                ? date('Y-m-d H:i:s', strtotime($validated['tgl_pemeriksaan']))
                : now();

            $catatanFinal = $validated['catatan_qc'] ?? '';
            if (!empty($validated['pic_rework'])) {
                $catatanFinal = trim("[PIC: {$validated['pic_rework']}] " . $catatanFinal);
            }

            $qc = Qc::create([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_qc' => $validated['id_user_qc'],
                'tahap_qc' => $validated['tahap_qc'],
                'tgl_pemeriksaan' => $tglPemeriksaan,
                'jumlah_diperiksa' => $validated['jumlah_diperiksa'],
                'jumlah_lolos' => $validated['jumlah_lolos'],
                'jumlah_cacat' => $validated['jumlah_cacat'],
                'jenis_cacat' => $validated['jenis_cacat'],
                'catatan_qc' => $catatanFinal ?: null,
                'status_qc' => $validated['status_qc'],
                'tgl_target_perbaikan' => $validated['tgl_target_perbaikan'] ?? null,
                'tgl_selesai_perbaikan' => $validated['tgl_selesai_perbaikan'] ?? null,
            ]);

            // Perpetual Inventory Integration:
            // When Jahit Akhir has passed units (jumlah_lolos > 0), increment finished goods inventory
            $order = Pesanan::find($validated['id_pesanan']);
            $allocatedStockInfo = null;
            if ($validated['tahap_qc'] === 'Jahit Akhir' && $validated['jumlah_lolos'] > 0) {
                if ($order) {
                    $idPola = $order->id_pola;
                    $idSpec = $validated['id_spesifikasi'] ?? SpesifikasiUkuran::where('id_pola', $idPola)->value('id_spesifikasi');
                    $warna = !empty($validated['nama_warna']) ? $validated['nama_warna'] : 'Hitam Reaktif';

                    if ($idSpec) {
                        $stock = StokBarangJadi::firstOrNew([
                            'id_pola' => $idPola,
                            'id_spesifikasi' => $idSpec,
                            'nama_warna' => $warna,
                        ]);

                        $stokSebelum = (int) ($stock->jumlah_stok_pcs ?? 0);
                        $stokSesudah = $stokSebelum + (int) $validated['jumlah_lolos'];
                        $stock->jumlah_stok_pcs = $stokSesudah;
                        $stock->tgl_update_terakhir = now();
                        $stock->save();

                        if (Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
                            RiwayatStokBarangJadi::create([
                                'id_stok' => $stock->id_stok,
                                'id_pola' => $idPola,
                                'id_spesifikasi' => $idSpec,
                                'nama_warna' => $warna,
                                'jenis_transaksi' => 'MASUK',
                                'jumlah_pcs' => (int) $validated['jumlah_lolos'],
                                'stok_sebelum' => $stokSebelum,
                                'stok_sesudah' => $stokSesudah,
                                'kategori_alasan' => 'Lolos QC Jahit Akhir (Pesanan #' . $order->id_pesanan . ')',
                                'nama_mitra' => $order->nama_mitra ?: null,
                                'keterangan' => 'Masuk otomatis dari hasil inspeksi QC #' . $qc->id_qc,
                                'id_user' => $validated['id_user_qc'],
                                'tgl_transaksi' => now(),
                            ]);
                        }

                        $specModel = SpesifikasiUkuran::find($idSpec);
                        $allocatedStockInfo = "+{$validated['jumlah_lolos']} Pcs ke Stok_Barang_Jadi (Ukuran " . ($specModel->nama_ukuran ?? 'S/M/L/XL') . ", Warna {$warna})";
                    }
                }
            }

            // Order Lifecycle State Transitions
            if ($order) {
                if ($validated['status_qc'] === 'Need Rework') {
                    $order->update(['status_pesanan' => 'Dalam QC']);
                } elseif ($validated['tahap_qc'] === 'Jahit Akhir' && $validated['status_qc'] === 'Lolos QC') {
                    $order->update(['status_pesanan' => 'Selesai']);
                }
            }

            $qc->load(['pesanan.pola', 'userQc']);

            $msg = "Inspeksi QC ({$qc->tahap_qc}) berhasil dicatat. Status: {$qc->status_qc}.";
            if ($allocatedStockInfo) {
                $msg .= " Dialokasikan {$allocatedStockInfo}.";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'data' => $qc,
                ], 201);
            }

            return redirect()->route('qc.index')->with('success', $msg);
        });
    }

    public function completeRework(Request $request, $id)
    {
        $qc = Qc::findOrFail($id);
        $tglSelesai = $request->input('tgl_selesai_perbaikan')
            ? date('Y-m-d H:i:s', strtotime($request->input('tgl_selesai_perbaikan')))
            : now();

        $qc->update([
            'status_qc' => 'Rework Selesai',
            'tgl_selesai_perbaikan' => $tglSelesai,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rework untuk QC #{$qc->id_qc} telah diselesaikan pada {$qc->tgl_selesai_perbaikan}.",
            'data' => $qc,
        ]);
    }

    public function update(Request $request, $id)
    {
        $qc = Qc::findOrFail($id);

        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_qc' => 'required|integer|exists:User,id_user',
            'tahap_qc' => 'required|in:Prototipe,Potong,Jahit Akhir',
            'tgl_pemeriksaan' => 'required|date',
            'jumlah_diperiksa' => 'required|integer|min:1',
            'jumlah_lolos' => 'required|integer|min:0',
            'jumlah_cacat' => 'required|integer|min:0',
            'jenis_cacat' => 'required|in:Tidak Ada,Cacat Potong,Cacat Jahit,Cacat Bahan',
            'catatan_qc' => 'nullable|string',
            'status_qc' => 'required|in:Lolos QC,Need Rework,Rework Selesai,Scrapped',
            'tgl_target_perbaikan' => 'nullable|date',
            'tgl_selesai_perbaikan' => 'nullable|date',
        ]);

        $diperiksa = (int) $validated['jumlah_diperiksa'];
        $lolos = (int) $validated['jumlah_lolos'];
        $cacat = (int) $validated['jumlah_cacat'];

        if (($lolos + $cacat) !== $diperiksa) {
            return response()->json([
                'success' => false,
                'message' => "Validasi Mutu Gagal: Jumlah Lolos ($lolos) + Jumlah Cacat ($cacat) harus sama dengan Jumlah Diperiksa ($diperiksa).",
            ], 422);
        }

        return DB::transaction(function () use ($qc, $validated) {
            $oldLolosInStok = ($qc->tahap_qc === 'Jahit Akhir') ? (int) $qc->jumlah_lolos : 0;
            $newLolosInStok = ($validated['tahap_qc'] === 'Jahit Akhir') ? (int) $validated['jumlah_lolos'] : 0;
            $deltaStok = $newLolosInStok - $oldLolosInStok;

            $tglPemeriksaan = date('Y-m-d H:i:s', strtotime($validated['tgl_pemeriksaan']));
            $tglSelesai = !empty($validated['tgl_selesai_perbaikan'])
                ? date('Y-m-d H:i:s', strtotime($validated['tgl_selesai_perbaikan']))
                : null;

            $qc->update([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_qc' => $validated['id_user_qc'],
                'tahap_qc' => $validated['tahap_qc'],
                'tgl_pemeriksaan' => $tglPemeriksaan,
                'jumlah_diperiksa' => $diperiksa,
                'jumlah_lolos' => $lolos,
                'jumlah_cacat' => $cacat,
                'jenis_cacat' => $validated['jenis_cacat'],
                'catatan_qc' => $validated['catatan_qc'] ?: null,
                'status_qc' => $validated['status_qc'],
                'tgl_target_perbaikan' => $validated['tgl_target_perbaikan'] ?: null,
                'tgl_selesai_perbaikan' => $tglSelesai,
            ]);

            if ($deltaStok !== 0) {
                $order = Pesanan::find($validated['id_pesanan']);
                if ($order) {
                    $stock = StokBarangJadi::where('id_pola', $order->id_pola)->first();
                    if ($stock) {
                        $stock->jumlah_stok_pcs = max(0, (int) $stock->jumlah_stok_pcs + $deltaStok);
                        $stock->tgl_update_terakhir = now();
                        $stock->save();
                    } elseif ($deltaStok > 0) {
                        $idSpec = SpesifikasiUkuran::where('id_pola', $order->id_pola)->value('id_spesifikasi');
                        if ($idSpec) {
                            StokBarangJadi::create([
                                'id_pola' => $order->id_pola,
                                'id_spesifikasi' => $idSpec,
                                'nama_warna' => 'Hitam Reaktif',
                                'jumlah_stok_pcs' => $deltaStok,
                                'tgl_update_terakhir' => now(),
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Catatan QC #{$qc->id_qc} berhasil diperbarui.",
                'data' => $qc->fresh(['pesanan.pola', 'userQc']),
            ]);
        });
    }
}
