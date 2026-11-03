<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PemotonganSeri;
use App\Models\DetailPemotonganWarna;
use App\Models\DetailGelaranKain;
use App\Models\DetailRollBahan;
use App\Models\Pesanan;
use App\Models\User;

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

        $users = User::where('role', 'Pemotong')->get();
        $orders = Pesanan::with('pola')->whereIn('status_pesanan', ['Antrean', 'Pemotongan'])->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => compact('pemotongan', 'orders', 'users')]);
        }

        return view('bong.pemotongan', compact('pemotongan', 'orders', 'users'));
    }

    public function store(Request $request)
    {
        // 1. Flat-Form Fallback for standard HTML form submissions
        if (!$request->has('potong_warna') && $request->filled('id_pesanan')) {
            $order = Pesanan::find($request->input('id_pesanan'));
            $roll = null;

            if ($order && $order->id_penerimaan) {
                $roll = DetailRollBahan::whereHas('detailBahanWarna', function ($q) use ($order) {
                    $q->where('id_penerimaan', $order->id_penerimaan);
                })->where('status_roll', '!=', 'Habis')->first();
            }

            if (!$roll) {
                $roll = DetailRollBahan::where('status_roll', '!=', 'Habis')->first();
            }

            $totalHasil = (int) $request->input('total_lembar_hasil', 100);
            $userId = $request->input('id_user_pemotong')
                ?? User::where('role', 'Pemotong')->value('id_user')
                ?? 1;

            if ($roll) {
                $potongWarna = [
                    [
                        'id_detail_warna' => $roll->id_detail_warna,
                        'total_lembar_direncanakan' => $totalHasil,
                        'kain_terpakai' => round($totalHasil * 0.15, 2),
                        'gelaran' => [
                            [
                                'id_roll' => $roll->id_roll,
                                'panjang_gelar' => 2.5,
                                'jumlah_tumpuk' => max(1, (int) ceil($totalHasil / 2)),
                                'total_lembar_gelar' => $totalHasil,
                            ]
                        ]
                    ]
                ];

                $request->merge([
                    'id_user_pemotong' => $userId,
                    'potong_warna' => $potongWarna,
                ]);
            }
        }

        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_pemotong' => 'required|integer|exists:User,id_user',
            'kode_seri' => 'required|string|max:50',
            'jenis_variasi' => 'nullable|string|max:50',
            'total_lembar_hasil' => 'required|integer|min:1',
            'potong_warna' => 'required|array|min:1',
            'potong_warna.*.id_detail_warna' => 'required|integer|exists:Detail_Bahan_Warna,id_detail_warna',
            'potong_warna.*.total_lembar_direncanakan' => 'required|integer|min:1',
            'potong_warna.*.kain_terpakai' => 'required|numeric|min:0.01|max:99999999.99',
            'potong_warna.*.gelaran' => 'required|array|min:1',
            'potong_warna.*.gelaran.*.id_roll' => 'required|integer|exists:Detail_Roll_Bahan,id_roll',
            'potong_warna.*.gelaran.*.panjang_gelar' => 'required|numeric|min:0.1',
            'potong_warna.*.gelaran.*.jumlah_tumpuk' => 'required|integer|min:1',
            'potong_warna.*.gelaran.*.total_lembar_gelar' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
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

                foreach ($pw['gelaran'] as $g) {
                    DetailGelaranKain::create([
                        'id_potong_warna' => $detailPw->id_potong_warna,
                        'id_roll' => $g['id_roll'],
                        'panjang_gelar' => $g['panjang_gelar'],
                        'jumlah_tumpuk' => $g['jumlah_tumpuk'],
                        'total_lembar_gelar' => $g['total_lembar_gelar'],
                    ]);

                    // Roll deduction specifically per roll based on (panjang_gelar * jumlah_tumpuk * 0.15)
                    $roll = DetailRollBahan::where('id_roll', $g['id_roll'])->lockForUpdate()->first();
                    if ($roll) {
                        $rollDeduction = round((float)$g['panjang_gelar'] * (int)$g['jumlah_tumpuk'] * 0.15, 2);
                        if ($rollDeduction <= 0) {
                            $rollDeduction = min((float)$roll->ukuran_sisa, (float)$pw['kain_terpakai']);
                        }

                        $newSisa = max(0.0, (float)$roll->ukuran_sisa - $rollDeduction);
                        $roll->ukuran_sisa = $newSisa;
                        $roll->status_roll = ($newSisa <= 0.05) ? 'Habis' : 'Dipotong';
                        $roll->save();
                    }
                }
            }

            // Update order status to Pemotongan
            $order = Pesanan::find($validated['id_pesanan']);
            if ($order && in_array(strtolower($order->status_pesanan), ['antrean', 'pending'])) {
                $order->update(['status_pesanan' => 'Pemotongan']);
            }

            $potong->load([
                'pesanan',
                'userPemotong',
                'detailPemotonganWarna.detailGelaranKain.detailRollBahan',
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pemotongan seri {$potong->kode_seri} berhasil diproses. Stok roll bahan otomatis dipotong.",
                    'data' => $potong,
                ], 201);
            }

            return redirect()->route('pemotongan.index')->with('success', "Pemotongan seri {$potong->kode_seri} berhasil diproses.");
        });
    }
}

