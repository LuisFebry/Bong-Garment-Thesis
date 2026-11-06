<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Aksesoris;
use App\Models\Supplier;
use App\Models\StokBarangJadi;
use App\Models\Pola;
use App\Models\DetailAksesorisPola;

class AksesorisController extends Controller
{
    public function index(Request $request)
    {
        $aksesoris = Aksesoris::with('supplier')->get();
        $suppliers = Supplier::all();
        $stok = StokBarangJadi::with(['pola', 'spesifikasiUkuran'])
            ->orderBy('tgl_update_terakhir', 'desc')
            ->get();
        $pola = Pola::with(['spesifikasiUkuran', 'detailAksesorisPola.aksesoris'])->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => compact('aksesoris', 'stok', 'pola')]);
        }

        return view('bong.gudang', compact('aksesoris', 'suppliers', 'stok', 'pola'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_supplier' => 'required|integer|exists:Supplier,id_supplier',
            'nama_aksesoris' => 'required|string|max:100',
            'ukuran' => 'nullable|string|max:30',
            'stok' => 'required|integer|min:0',
            'satuan' => 'required|string|max:20',
        ]);

        $item = Aksesoris::create($validated);
        $item->load('supplier');

        return response()->json([
            'success' => true,
            'message' => "Aksesoris '{$item->nama_aksesoris}' berhasil didaftarkan.",
            'data' => $item,
        ], 201);
    }

    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'delta_stok' => 'required|integer',
        ]);

        $item = Aksesoris::findOrFail($id);
        $newStock = $item->stok + $validated['delta_stok'];
        if ($newStock < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi untuk pengurangan ini.',
            ], 422);
        }

        $item->update(['stok' => $newStock]);

        return response()->json([
            'success' => true,
            'message' => "Stok {$item->nama_aksesoris} diperbarui menjadi {$item->stok} {$item->satuan}.",
            'data' => $item,
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = Aksesoris::findOrFail($id);

        $validated = $request->validate([
            'id_supplier' => 'required|integer|exists:Supplier,id_supplier',
            'nama_aksesoris' => 'required|string|max:100',
            'ukuran' => 'nullable|string|max:30',
            'stok' => 'required|integer|min:0',
            'satuan' => 'required|string|max:20',
        ]);

        $item->update($validated);
        $item->load('supplier');

        return response()->json([
            'success' => true,
            'message' => "Data aksesoris '{$item->nama_aksesoris}' berhasil diperbarui.",
            'data' => $item,
        ]);
    }

    public function destroy($id)
    {
        $item = Aksesoris::findOrFail($id);
        $nama = $item->nama_aksesoris;

        DB::transaction(function () use ($item) {
            DetailAksesorisPola::where('id_aksesoris', $item->id_aksesoris)->delete();
            $item->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Aksesoris '{$nama}' berhasil dihapus.",
        ]);
    }

    public function storePolaMapping(Request $request)
    {
        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_aksesoris' => 'required|integer|exists:Aksesoris,id_aksesoris',
            'jumlah_digunakan' => 'required|integer|min:1',
        ]);

        DB::table('Detail_Aksesoris_Pola')->updateOrInsert(
            [
                'id_pola' => $validated['id_pola'],
                'id_aksesoris' => $validated['id_aksesoris'],
            ],
            [
                'jumlah_digunakan' => $validated['jumlah_digunakan'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Kebutuhan aksesoris per pola berhasil disimpan ke Detail_Aksesoris_Pola.',
            'data' => $validated,
        ]);
    }

    public function destroyPolaMapping(Request $request)
    {
        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_aksesoris' => 'required|integer|exists:Aksesoris,id_aksesoris',
        ]);

        DetailAksesorisPola::where('id_pola', $validated['id_pola'])
            ->where('id_aksesoris', $validated['id_aksesoris'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pemetaan aksesoris pola berhasil dihapus.',
        ]);
    }
}
