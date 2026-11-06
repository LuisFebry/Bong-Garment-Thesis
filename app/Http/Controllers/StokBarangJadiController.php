<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StokBarangJadi;
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

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => compact('stok', 'aksesoris', 'pola')]);
        }

        return view('bong.gudang', compact('stok', 'pola', 'aksesoris', 'suppliers'));
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_spesifikasi' => 'required|integer|exists:Spesifikasi_Ukuran,id_spesifikasi',
            'nama_warna' => 'required|string|max:50',
            'delta_pcs' => 'required|integer',
            'alasan_penyesuaian' => 'nullable|string|max:150',
        ]);

        $stock = StokBarangJadi::firstOrNew([
            'id_pola' => $validated['id_pola'],
            'id_spesifikasi' => $validated['id_spesifikasi'],
            'nama_warna' => $validated['nama_warna'],
        ]);

        $newPcs = ($stock->jumlah_stok_pcs ?? 0) + $validated['delta_pcs'];
        if ($newPcs < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stok barang jadi tidak mencukupi (tidak boleh bernilai negatif).',
            ], 422);
        }

        $stock->jumlah_stok_pcs = $newPcs;
        $stock->tgl_update_terakhir = now();
        $stock->save();

        $stock->load(['pola', 'spesifikasiUkuran']);

        $alasanText = !empty($validated['alasan_penyesuaian']) ? " ({$validated['alasan_penyesuaian']})" : '';
        $jenisText = $validated['delta_pcs'] < 0
            ? 'Pengeluaran stok ' . abs($validated['delta_pcs']) . ' pcs'
            : 'Penambahan stok +' . $validated['delta_pcs'] . ' pcs';

        return response()->json([
            'success' => true,
            'message' => "{$jenisText}{$alasanText} berhasil dicatat. Sisa stok barang jadi: {$stock->jumlah_stok_pcs} pcs.",
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

        $stock->update([
            'id_pola' => $validated['id_pola'],
            'id_spesifikasi' => $validated['id_spesifikasi'],
            'nama_warna' => $validated['nama_warna'],
            'jumlah_stok_pcs' => $validated['jumlah_stok_pcs'],
            'tgl_update_terakhir' => now(),
        ]);

        $stock->load(['pola', 'spesifikasiUkuran']);

        return response()->json([
            'success' => true,
            'message' => "Data Stok Barang Jadi #{$stock->id_stok} ({$stock->nama_warna}) berhasil diperbarui menjadi {$stock->jumlah_stok_pcs} Pcs.",
            'data' => $stock,
        ]);
    }
}
