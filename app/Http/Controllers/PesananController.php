<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use App\Models\Pola;
use App\Models\SumberInisiasi;
use App\Models\PenerimaanBahan;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $pesanan = Pesanan::with(['pola', 'sumberInisiasi', 'penerimaanBahan', 'userAdmin'])
            ->orderByRaw('COALESCE(deadline_terbaru, deadline_awal) ASC')
            ->get();

        $pola = Pola::all();
        $sumber = SumberInisiasi::all();
        $penerimaan = PenerimaanBahan::all();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $pesanan]);
        }

        return view('bong.pesanan', compact('pesanan', 'pola', 'sumber', 'penerimaan'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('id_user_admin')) {
            $request->merge(['id_user_admin' => 1]);
        }
        if (!$request->filled('tgl_order')) {
            $request->merge(['tgl_order' => now()->toDateString()]);
        }

        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_sumber' => 'required|integer|exists:Sumber_Inisiasi,id_sumber',
            'id_penerimaan' => 'nullable|integer|exists:Penerimaan_Bahan,id_penerimaan',
            'id_user_admin' => 'required|integer|exists:User,id_user',
            'jenis_pesanan' => 'required|string|max:50',
            'nama_mitra' => 'nullable|string|max:100',
            'tgl_order' => 'required|date',
            'deadline_awal' => 'required|date',
        ]);

        // If source is not Mitra (id_sumber != 1), nama_mitra is strictly null
        if ((int)$validated['id_sumber'] !== 1) {
            $validated['nama_mitra'] = null;
        }

        // Initialize deadline_terbaru equal to deadline_awal (EDF algorithm invariant)
        $validated['deadline_terbaru'] = $validated['deadline_awal'];
        $validated['status_pesanan'] = 'Antrean';

        $order = Pesanan::create($validated);
        $order->load(['pola', 'sumberInisiasi', 'penerimaanBahan']);

        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$order->id_pesanan} berhasil diinisiasi.",
            'data' => $order,
        ], 201);
    }

    /**
     * Update order details (including assigning or switching Nota Bahan / Penerimaan)
     */
    public function update(Request $request, $id)
    {
        $order = Pesanan::findOrFail($id);

        $validated = $request->validate([
            'id_pola' => 'required|integer|exists:Pola,id_pola',
            'id_sumber' => 'required|integer|exists:Sumber_Inisiasi,id_sumber',
            'id_penerimaan' => 'nullable|integer|exists:Penerimaan_Bahan,id_penerimaan',
            'jenis_pesanan' => 'required|string|max:50',
            'nama_mitra' => 'nullable|string|max:100',
            'deadline_awal' => 'required|date',
            'deadline_terbaru' => 'nullable|date',
            'status_pesanan' => 'required|string|max:50',
            'alasan_perubahan_deadline' => 'nullable|string|max:255',
        ]);

        if ((int)$validated['id_sumber'] !== 1) {
            $validated['nama_mitra'] = null;
        }

        if (empty($validated['deadline_terbaru'])) {
            $validated['deadline_terbaru'] = $order->deadline_terbaru ?? $validated['deadline_awal'];
        }

        if ($order->deadline_terbaru != $validated['deadline_terbaru']) {
            $validated['tgl_perubahan_deadline'] = now();
        }

        $order->update($validated);
        $order->load(['pola', 'sumberInisiasi', 'penerimaanBahan']);

        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$order->id_pesanan} berhasil diperbarui.",
            'data' => $order,
        ]);
    }

    /**
     * Audit deadline revision with EDF sorting update
     */
    public function reviseDeadline(Request $request, $id)
    {
        $validated = $request->validate([
            'deadline_terbaru' => 'required|date',
            'alasan_perubahan_deadline' => 'required|string|max:255',
        ]);

        $order = Pesanan::findOrFail($id);

        $order->update([
            'deadline_terbaru' => $validated['deadline_terbaru'],
            'alasan_perubahan_deadline' => $validated['alasan_perubahan_deadline'],
            'tgl_perubahan_deadline' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Deadline pesanan #{$order->id_pesanan} berhasil direvisi menjadi {$order->deadline_terbaru}. Antrean EDF diperbarui.",
            'data' => $order,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status_pesanan' => 'required|string|max:50',
        ]);

        $order = Pesanan::findOrFail($id);
        $order->update(['status_pesanan' => $validated['status_pesanan']]);

        return response()->json([
            'success' => true,
            'message' => "Status pesanan #{$order->id_pesanan} diubah menjadi {$order->status_pesanan}.",
            'data' => $order,
        ]);
    }
}

