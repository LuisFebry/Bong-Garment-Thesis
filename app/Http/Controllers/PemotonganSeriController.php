<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PemotonganSeri;
use App\Models\DetailPemotonganWarna;
use App\Models\DetailGelaranKain;
use App\Models\DetailRollBahan;
use App\Models\Pesanan;

class PemotonganSeriController extends Controller
{
    public function index(Request $request)
    {
        $pemotongan = PemotonganSeri::with([
            'pesanan.pola',
            'userPemotong',
            'detailPemotonganWarna.detailGelaranKain.detailRollBahan',
            'detailPemotonganWarna.detailBahanWarna',
        ])->orderBy('tgl_potong', 'desc')->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $pemotongan]);
        }

        return view('bong.pemotongan', compact('pemotongan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_pemotong' => 'required|integer|exists:User,id_user',
            'kode_seri' => 'required|string|max:50',
            'jenis_variasi' => 'nullable|string|max:50',
            'total_lembar_hasil' => 'required|integer|min:1',
            'potong_warna' => 'required|array|min:1',
            'potong_warna.*.id_detail_warna' => 'required|integer|exists:Detail_Bahan_Warna,id_detail_warna',
            'potong_warna.*.total_lembar_direncanakan' => 'required|integer|min:1',
            'potong_warna.*.kain_terpakai' => 'required|numeric|min:0.01',
            'potong_warna.*.gelaran' => 'required|array|min:1',
            'potong_warna.*.gelaran.*.id_roll' => 'required|integer|exists:Detail_Roll_Bahan,id_roll',
            'potong_warna.*.gelaran.*.panjang_gelar' => 'required|numeric|min:0.1',
            'potong_warna.*.gelaran.*.jumlah_tumpuk' => 'required|integer|min:1',
            'potong_warna.*.gelaran.*.total_lembar_gelar' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $potong = PemotonganSeri::create([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_pemotong' => $validated['id_user_pemotong'],
                'kode_seri' => $validated['kode_seri'],
                'jenis_variasi' => $validated['jenis_variasi'] ?? null,
                'total_lembar_hasil' => $validated['total_lembar_hasil'],
                'tgl_potong' => now(),
            ]);

            foreach ($validated['potong_warna'] as $pw) {
                $detailPw = DetailPemotonganWarna::create([
                    'id_potong' => $potong->id_potong,
                    'id_detail_warna' => $pw['id_detail_warna'],
                    'total_lembar_direncanakan' => $pw['total_lembar_direncanakan'],
                    'kain_terpakai' => $pw['kain_terpakai'],
                ]);

                // Fabric roll deduction with row locking
                $remainingFabricToDeduct = (float)$pw['kain_terpakai'];

                foreach ($pw['gelaran'] as $g) {
                    DetailGelaranKain::create([
                        'id_potong_warna' => $detailPw->id_potong_warna,
                        'id_roll' => $g['id_roll'],
                        'panjang_gelar' => $g['panjang_gelar'],
                        'jumlah_tumpuk' => $g['jumlah_tumpuk'],
                        'total_lembar_gelar' => $g['total_lembar_gelar'],
                    ]);

                    // Lock and deduct roll stock
                    $roll = DetailRollBahan::where('id_roll', $g['id_roll'])->lockForUpdate()->first();
                    if ($roll) {
                        $deduction = min((float)$roll->ukuran_sisa, $remainingFabricToDeduct);
                        $newSisa = max(0.0, (float)$roll->ukuran_sisa - $deduction);
                        $remainingFabricToDeduct = max(0.0, $remainingFabricToDeduct - $deduction);

                        $roll->ukuran_sisa = $newSisa;
                        $roll->status_roll = ($newSisa <= 0.05) ? 'Habis' : 'Dipotong';
                        $roll->save();
                    }
                }
            }

            // Update order status to Pemotongan or Jahit
            $order = Pesanan::find($validated['id_pesanan']);
            if ($order && $order->status_pesanan === 'Antrean') {
                $order->update(['status_pesanan' => 'Pemotongan']);
            }

            $potong->load([
                'pesanan',
                'userPemotong',
                'detailPemotonganWarna.detailGelaranKain.detailRollBahan',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pemotongan seri {$potong->kode_seri} berhasil diproses. Stok roll bahan otomatis dipotong.",
                'data' => $potong,
            ], 201);
        });
    }
}

