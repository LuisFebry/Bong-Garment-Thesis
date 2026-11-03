<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aksesoris;
use App\Models\Supplier;

class AksesorisController extends Controller
{
    public function index(Request $request)
    {
        $aksesoris = Aksesoris::with('supplier')->get();
        $suppliers = Supplier::all();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $aksesoris]);
        }

        return view('bong.gudang', compact('aksesoris', 'suppliers'));
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
}

