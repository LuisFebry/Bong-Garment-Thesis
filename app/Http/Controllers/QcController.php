<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Qc;
use App\Models\Pesanan;
use App\Models\StokBarangJadi;
use App\Models\SpesifikasiUkuran;

class QcController extends Controller
{
    public function index(Request $request)
    {
        $qcRecords = Qc::with(['pesanan.pola', 'userQc'])
            ->orderBy('tgl_pemeriksaan', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $qcRecords]);
        }

        return view('bong.qc', compact('qcRecords'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('id_user_qc')) {
            $request->merge(['id_user_qc' => 1]);
        }

        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_qc' => 'required|integer|exists:User,id_user',
            'tahap_qc' => 'required|in:Prototipe,Potong,Jahit Akhir',
            'jumlah_diperiksa' => 'required|integer|min:1',
            'jumlah_lolos' => 'required|integer|min:0',
            'jumlah_cacat' => 'required|integer|min:0',
            'jenis_cacat' => 'required|in:Tidak Ada,Cacat Potong,Cacat Jahit,Cacat Bahan',
            'catatan_qc' => 'nullable|string',
            'status_qc' => 'required|in:Lolos QC,Need Rework,Rework Selesai,Scrapped',
            'tgl_target_perbaikan' => 'nullable|date',
            'tgl_selesai_perbaikan' => 'nullable|date',
            // Optional parameters for perpetual inventory attribution when tahap_qc is 'Jahit Akhir'
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
            $qc = Qc::create([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_qc' => $validated['id_user_qc'],
                'tahap_qc' => $validated['tahap_qc'],
                'tgl_pemeriksaan' => now(),
                'jumlah_diperiksa' => $validated['jumlah_diperiksa'],
                'jumlah_lolos' => $validated['jumlah_lolos'],
                'jumlah_cacat' => $validated['jumlah_cacat'],
                'jenis_cacat' => $validated['jenis_cacat'],
                'catatan_qc' => $validated['catatan_qc'] ?? null,
                'status_qc' => $validated['status_qc'],
                'tgl_target_perbaikan' => $validated['tgl_target_perbaikan'] ?? null,
                'tgl_selesai_perbaikan' => $validated['tgl_selesai_perbaikan'] ?? null,
            ]);

            // Perpetual Inventory Integration:
            // When Jahit Akhir has passed units (jumlah_lolos > 0), increment finished goods inventory
            $order = Pesanan::find($validated['id_pesanan']);
            if ($validated['tahap_qc'] === 'Jahit Akhir' && $validated['jumlah_lolos'] > 0) {
                if ($order) {
                    $idPola = $order->id_pola;
                    $idSpec = $validated['id_spesifikasi'] ?? SpesifikasiUkuran::where('id_pola', $idPola)->value('id_spesifikasi');
                    $warna = $validated['nama_warna'] ?? 'Hitam Reaktif';

                    if ($idSpec) {
                        $stock = StokBarangJadi::firstOrNew([
                            'id_pola' => $idPola,
                            'id_spesifikasi' => $idSpec,
                            'nama_warna' => $warna,
                        ]);

                        $stock->jumlah_stok_pcs = ($stock->jumlah_stok_pcs ?? 0) + $validated['jumlah_lolos'];
                        $stock->tgl_update_terakhir = now();
                        $stock->save();
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

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Inspeksi QC ({$qc->tahap_qc}) berhasil dicatat. Status: {$qc->status_qc}.",
                    'data' => $qc,
                ], 201);
            }

            return redirect()->route('qc.index')->with('success', "Inspeksi QC ({$qc->tahap_qc}) berhasil dicatat. Status: {$qc->status_qc}.");
        });
    }

    public function completeRework(Request $request, $id)
    {
        $qc = Qc::findOrFail($id);
        $qc->update([
            'status_qc' => 'Rework Selesai',
            'tgl_selesai_perbaikan' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rework untuk QC #{$qc->id_qc} telah diselesaikan.",
            'data' => $qc,
        ]);
    }
}