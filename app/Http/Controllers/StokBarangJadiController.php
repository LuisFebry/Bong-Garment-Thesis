<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\StokBarangJadi;
use App\Models\RiwayatStokBarangJadi;
use App\Models\Pesanan;
use App\Models\Pola;
use App\Models\SpesifikasiUkuran;
use App\Models\Aksesoris;
use App\Models\Supplier;

class StokBarangJadiController extends Controller
{
    public function index(Request $request)
    {
        $stok = StokBarangJadi::with(['pola', 'spesifikasiUkuran'])
            ->orderBy('tgl_update_terakhir', 'desc')
            ->get();

        $pola = Pola::with(['spesifikasiUkuran', 'detailAksesorisPola.aksesoris'])->get();
        $aksesoris = Aksesoris::with('supplier')->get();
        $suppliers = Supplier::all();

        $riwayatStok = collect();
        if (Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
            $riwayatStok = RiwayatStokBarangJadi::with(['pola', 'spesifikasiUkuran', 'user'])
                ->orderBy('tgl_transaksi', 'desc')
                ->orderBy('id_riwayat_stok', 'desc')
                ->limit(100)
                ->get();
        }

        $mitraFromPesanan = Pesanan::whereNotNull('nama_mitra')
            ->where('nama_mitra', '!=', '')
            ->distinct()
            ->pluck('nama_mitra');
        $mitraFromRiwayat = $riwayatStok->pluck('nama_mitra')->filter()->unique();
        $mitraList = $mitraFromPesanan->merge($mitraFromRiwayat)->unique()->values();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('stok', 'aksesoris', 'pola', 'riwayatStok'),
            ]);
        }

        return view('bong.gudang', compact('stok', 'pola', 'aksesoris', 'suppliers', 'riwayatStok', 'mitraList'));
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_spesifikasi' => 'required|integer|exists:Spesifikasi_Ukuran,id_spesifikasi',
            'nama_warna' => 'required|string|max:50',
            'delta_pcs' => 'required|integer',
            'alasan_penyesuaian' => 'nullable|string|max:150',
            'nama_mitra' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $alasan = $validated['alasan_penyesuaian'] ?? 'Transaksi Gudang';
        $namaMitra = !empty($validated['nama_mitra']) ? trim($validated['nama_mitra']) : null;
        $keterangan = !empty($validated['keterangan']) ? trim($validated['keterangan']) : null;

        if (stripos($alasan, 'Mitra') !== false && empty($namaMitra)) {
            return response()->json([
                'success' => false,
                'message' => 'Nama Mitra wajib diisi ketika mencatat pengambilan barang oleh Mitra.',
            ], 422);
        }

        $stock = StokBarangJadi::firstOrNew([
            'id_pola' => $validated['id_pola'],
            'id_spesifikasi' => $validated['id_spesifikasi'],
            'nama_warna' => $validated['nama_warna'],
        ]);

        $stokSebelum = (int) ($stock->jumlah_stok_pcs ?? 0);
        $newPcs = $stokSebelum + (int) $validated['delta_pcs'];
        if ($newPcs < 0) {
            return response()->json([
                'success' => false,
                'message' => "Stok barang jadi tidak mencukupi (tersedia {$stokSebelum} pcs, tidak boleh bernilai negatif).",
            ], 422);
        }

        $stock->jumlah_stok_pcs = $newPcs;
        $stock->tgl_update_terakhir = now();
        $stock->save();

        $userId = session('auth_user_id') ?: ($request->cookie('bong_user_id') ? (int) $request->cookie('bong_user_id') : 1);

        if (Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
            RiwayatStokBarangJadi::create([
                'id_stok' => $stock->id_stok,
                'id_pola' => $stock->id_pola,
                'id_spesifikasi' => $stock->id_spesifikasi,
                'nama_warna' => $stock->nama_warna,
                'jenis_transaksi' => $validated['delta_pcs'] < 0 ? 'KELUAR' : 'MASUK',
                'jumlah_pcs' => abs((int) $validated['delta_pcs']),
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $newPcs,
                'kategori_alasan' => $alasan,
                'nama_mitra' => $namaMitra,
                'keterangan' => $keterangan,
                'id_user' => $userId,
                'tgl_transaksi' => now(),
            ]);
        }

        $stock->load(['pola', 'spesifikasiUkuran']);

        $mitraInfo = $namaMitra ? " [Mitra: {$namaMitra}]" : '';
        $alasanText = !empty($alasan) ? " ({$alasan}{$mitraInfo})" : '';
        $jenisText = $validated['delta_pcs'] < 0
            ? 'Pengeluaran stok ' . abs($validated['delta_pcs']) . ' pcs'
            : 'Penambahan stok +' . $validated['delta_pcs'] . ' pcs';

        return response()->json([
            'success' => true,
            'message' => "{$jenisText}{$alasanText} berhasil disimpan ke Riwayat_Stok_Barang_Jadi. Sisa stok: {$stock->jumlah_stok_pcs} pcs.",
            'data' => $stock,
        ]);
    }

    public function update(Request $request, $id)
    {
        $stock = StokBarangJadi::findOrFail($id);

        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_spesifikasi' => 'required|integer|exists:Spesifikasi_Ukuran,id_spesifikasi',
            'nama_warna' => 'required|string|max:50',
            'jumlah_stok_pcs' => 'required|integer|min:0',
        ]);

        $stokSebelum = (int) $stock->jumlah_stok_pcs;
        $stokSesudah = (int) $validated['jumlah_stok_pcs'];
        $delta = $stokSesudah - $stokSebelum;

        $stock->update([
            'id_pola' => $validated['id_pola'],
            'id_spesifikasi' => $validated['id_spesifikasi'],
            'nama_warna' => $validated['nama_warna'],
            'jumlah_stok_pcs' => $stokSesudah,
            'tgl_update_terakhir' => now(),
        ]);

        if ($delta !== 0 && Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
            $userId = session('auth_user_id') ?: ($request->cookie('bong_user_id') ? (int) $request->cookie('bong_user_id') : 1);
            RiwayatStokBarangJadi::create([
                'id_stok' => $stock->id_stok,
                'id_pola' => $stock->id_pola,
                'id_spesifikasi' => $stock->id_spesifikasi,
                'nama_warna' => $stock->nama_warna,
                'jenis_transaksi' => $delta < 0 ? 'KELUAR' : 'MASUK',
                'jumlah_pcs' => abs($delta),
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'kategori_alasan' => 'Revisi / Koreksi Data Stok Gudang',
                'nama_mitra' => null,
                'keterangan' => 'Edit langsung pada tabel Stok Barang Jadi',
                'id_user' => $userId,
                'tgl_transaksi' => now(),
            ]);
        }

        $stock->load(['pola', 'spesifikasiUkuran']);

        return response()->json([
            'success' => true,
            'message' => "Data Stok Barang Jadi #{$stock->id_stok} ({$stock->nama_warna}) berhasil diperbarui menjadi {$stock->jumlah_stok_pcs} Pcs.",
            'data' => $stock,
        ]);
    }
}
