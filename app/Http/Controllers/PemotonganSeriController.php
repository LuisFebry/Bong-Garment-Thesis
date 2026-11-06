<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PemotonganSeri;
use App\Models\DetailPemotonganWarna;
use App\Models\DetailGelaranKain;
use App\Models\DetailBahanWarna;
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

        $users = User::whereIn('role', ['Pemotong', 'Admin'])->get();

        // EDF Scheduling Queue: sorted by COALESCE(deadline_terbaru, deadline_awal) ASC
        $orders = Pesanan::with([
            'pola',
            'sumberInisiasi',
            'penerimaanBahan.detailBahanWarna.detailRollBahan',
        ])->orderByRaw('COALESCE(deadline_terbaru, deadline_awal) ASC')->get();

        $warnaList = DetailBahanWarna::with('penerimaanBahan')->get();
        $rolls = DetailRollBahan::with('detailBahanWarna.penerimaanBahan')
            ->where('status_roll', '!=', 'Habis')
            ->orderBy('id_roll', 'asc')
            ->get();
        $allRolls = DetailRollBahan::with('detailBahanWarna.penerimaanBahan')
            ->orderBy('id_roll', 'asc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('pemotongan', 'orders', 'users', 'warnaList', 'rolls', 'allRolls'),
            ]);
        }

        return view('bong.pemotongan', compact('pemotongan', 'orders', 'users', 'warnaList', 'rolls', 'allRolls'));
    }

    public function store(Request $request)
    {
        // Flat-Form Fallback if potong_warna array is not directly sent
        if (!$request->has('potong_warna') && $request->filled('id_pesanan')) {
            $order = Pesanan::find($request->input('id_pesanan'));
            $idRoll = $request->input('id_roll');
            $roll = $idRoll ? DetailRollBahan::find($idRoll) : null;

            if (!$roll && $order && $order->id_penerimaan) {
                $roll = DetailRollBahan::whereHas('detailBahanWarna', function ($q) use ($order) {
                    $q->where('id_penerimaan', $order->id_penerimaan);
                })->where('status_roll', '!=', 'Habis')->first();
            }

            if (!$roll) {
                $roll = DetailRollBahan::where('status_roll', '!=', 'Habis')->first();
            }

            if (!$roll) {
                $firstWarna = DetailBahanWarna::first();
                if ($firstWarna) {
                    $nextRollNo = (DetailRollBahan::where('id_detail_warna', $firstWarna->id_detail_warna)->max('no_roll_ke') ?? 0) + 1;
                    $roll = DetailRollBahan::create([
                        'id_detail_warna' => $firstWarna->id_detail_warna,
                        'no_roll_ke' => $nextRollNo,
                        'ukuran_awal' => 100.0,
                        'ukuran_sisa' => 100.0,
                        'satuan' => $firstWarna->satuan_ukur ?? 'Kg',
                        'status_roll' => 'Tersedia',
                    ]);
                }
            }

            $totalHasil = (int) $request->input('total_lembar_hasil', 100);
            $targetLembar = (int) $request->input('total_lembar_direncanakan', $totalHasil);
            $kainTerpakai = (float) $request->input('kain_terpakai', round($totalHasil * 0.15, 2));
            $panjangGelar = (float) $request->input('panjang_gelar', 2.5);
            $jumlahTumpuk = (int) $request->input('jumlah_tumpuk', max(1, (int) ceil($totalHasil / 2)));
            $totalLembarGelar = (int) $request->input('total_lembar_gelar', $totalHasil);
            $statusRollBaru = $request->input('status_roll_baru', 'Dipotong');

            $userId = $request->input('id_user_pemotong')
                ?? User::where('role', 'Pemotong')->value('id_user')
                ?? 1;

            if ($roll) {
                $idDetailWarna = $request->input('id_detail_warna') ?: $roll->id_detail_warna;
                $potongWarna = [
                    [
                        'id_detail_warna' => (int) $idDetailWarna,
                        'total_lembar_direncanakan' => $targetLembar,
                        'kain_terpakai' => $kainTerpakai,
                        'gelaran' => [
                            [
                                'id_roll' => $roll->id_roll,
                                'panjang_gelar' => $panjangGelar,
                                'jumlah_tumpuk' => $jumlahTumpuk,
                                'total_lembar_gelar' => $totalLembarGelar,
                                'status_roll_baru' => $statusRollBaru,
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
            'tgl_potong' => 'nullable|date',
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
            'potong_warna.*.gelaran.*.status_roll_baru' => 'nullable|in:Dipotong,Habis',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $tglPotong = !empty($validated['tgl_potong'])
                ? date('Y-m-d H:i:s', strtotime($validated['tgl_potong']))
                : now();

            $potong = PemotonganSeri::create([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_pemotong' => $validated['id_user_pemotong'],
                'kode_seri' => $validated['kode_seri'],
                'jenis_variasi' => $validated['jenis_variasi'] ?? null,
                'total_lembar_hasil' => $validated['total_lembar_hasil'],
                'tgl_potong' => $tglPotong,
            ]);

            foreach ($validated['potong_warna'] as $pw) {
                $detailPw = DetailPemotonganWarna::create([
                    'id_potong' => $potong->id_potong,
                    'id_detail_warna' => $pw['id_detail_warna'],
                    'total_lembar_direncanakan' => $pw['total_lembar_direncanakan'],
                    'kain_terpakai' => $pw['kain_terpakai'],
                ]);

                $gelaranCount = max(1, count($pw['gelaran']));
                $perRollKain = round(((float)$pw['kain_terpakai']) / $gelaranCount, 2);

                foreach ($pw['gelaran'] as $g) {
                    DetailGelaranKain::create([
                        'id_potong_warna' => $detailPw->id_potong_warna,
                        'id_roll' => $g['id_roll'],
                        'panjang_gelar' => $g['panjang_gelar'],
                        'jumlah_tumpuk' => $g['jumlah_tumpuk'],
                        'total_lembar_gelar' => $g['total_lembar_gelar'],
                    ]);

                    $roll = DetailRollBahan::where('id_roll', $g['id_roll'])->lockForUpdate()->first();
                    if ($roll) {
                        $statusRequested = $g['status_roll_baru'] ?? null;
                        $rollDeduction = $perRollKain > 0
                            ? $perRollKain
                            : round((float)$g['panjang_gelar'] * (int)$g['jumlah_tumpuk'] * 0.15, 2);

                        $newSisa = max(0.0, (float)$roll->ukuran_sisa - $rollDeduction);

                        if ($statusRequested === 'Habis' || $newSisa <= 0.05) {
                            $roll->ukuran_sisa = 0.0;
                            $roll->status_roll = 'Habis';
                        } else {
                            $roll->ukuran_sisa = $newSisa;
                            $roll->status_roll = 'Dipotong';
                        }
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
                    'message' => "Pemotongan seri {$potong->kode_seri} berhasil diproses. Stok & status roll bahan telah diperbarui.",
                    'data' => $potong,
                ], 201);
            }

            return redirect()->route('pemotongan.index')->with('success', "Pemotongan seri {$potong->kode_seri} berhasil diproses.");
        });
    }

    public function update(Request $request, $id)
    {
        $potong = PemotonganSeri::with('detailPemotonganWarna.detailGelaranKain')->findOrFail($id);

        $validated = $request->validate([
            'id_pesanan' => 'required|integer|exists:Pesanan,id_pesanan',
            'id_user_pemotong' => 'required|integer|exists:User,id_user',
            'kode_seri' => 'required|string|max:50',
            'jenis_variasi' => 'nullable|string|max:50',
            'tgl_potong' => 'required|date',
            'total_lembar_hasil' => 'required|integer|min:1',
            'id_detail_warna' => 'nullable|integer|exists:Detail_Bahan_Warna,id_detail_warna',
            'total_lembar_direncanakan' => 'nullable|integer|min:1',
            'kain_terpakai' => 'nullable|numeric|min:0.01|max:99999999.99',
            'id_roll' => 'nullable|integer|exists:Detail_Roll_Bahan,id_roll',
            'panjang_gelar' => 'nullable|numeric|min:0.1',
            'jumlah_tumpuk' => 'nullable|integer|min:1',
            'total_lembar_gelar' => 'nullable|integer|min:1',
            'status_roll_baru' => 'nullable|in:Tersedia,Dipotong,Habis',
        ]);

        return DB::transaction(function () use ($potong, $validated, $request) {
            $tglPotong = date('Y-m-d H:i:s', strtotime($validated['tgl_potong']));

            $potong->update([
                'id_pesanan' => $validated['id_pesanan'],
                'id_user_pemotong' => $validated['id_user_pemotong'],
                'kode_seri' => $validated['kode_seri'],
                'jenis_variasi' => $validated['jenis_variasi'] ?? null,
                'tgl_potong' => $tglPotong,
                'total_lembar_hasil' => $validated['total_lembar_hasil'],
            ]);

            $detailPw = $potong->detailPemotonganWarna->first();
            $oldKain = $detailPw ? (float) $detailPw->kain_terpakai : 0.0;
            $newKain = isset($validated['kain_terpakai']) ? (float) $validated['kain_terpakai'] : $oldKain;

            if ($detailPw) {
                $detailPw->update([
                    'id_detail_warna' => $validated['id_detail_warna'] ?? $detailPw->id_detail_warna,
                    'total_lembar_direncanakan' => $validated['total_lembar_direncanakan'] ?? $detailPw->total_lembar_direncanakan,
                    'kain_terpakai' => $newKain,
                ]);
            } elseif (!empty($validated['id_detail_warna'])) {
                $detailPw = DetailPemotonganWarna::create([
                    'id_potong' => $potong->id_potong,
                    'id_detail_warna' => $validated['id_detail_warna'],
                    'total_lembar_direncanakan' => $validated['total_lembar_direncanakan'] ?? $validated['total_lembar_hasil'],
                    'kain_terpakai' => $newKain,
                ]);
            }

            if ($detailPw) {
                $gelaran = $detailPw->detailGelaranKain()->first();
                $oldRollId = $gelaran ? $gelaran->id_roll : null;
                $newRollId = $validated['id_roll'] ?? $oldRollId;

                if ($gelaran) {
                    $gelaran->update([
                        'id_roll' => $newRollId,
                        'panjang_gelar' => $validated['panjang_gelar'] ?? $gelaran->panjang_gelar,
                        'jumlah_tumpuk' => $validated['jumlah_tumpuk'] ?? $gelaran->jumlah_tumpuk,
                        'total_lembar_gelar' => $validated['total_lembar_gelar'] ?? $gelaran->total_lembar_gelar,
                    ]);
                } elseif ($newRollId) {
                    DetailGelaranKain::create([
                        'id_potong_warna' => $detailPw->id_potong_warna,
                        'id_roll' => $newRollId,
                        'panjang_gelar' => $validated['panjang_gelar'] ?? 2.5,
                        'jumlah_tumpuk' => $validated['jumlah_tumpuk'] ?? 50,
                        'total_lembar_gelar' => $validated['total_lembar_gelar'] ?? $validated['total_lembar_hasil'],
                    ]);
                }

                // Adjust roll stock when kain_terpakai or id_roll changes
                if ($oldRollId && $newRollId && $oldRollId != $newRollId) {
                    $oldRoll = DetailRollBahan::where('id_roll', $oldRollId)->lockForUpdate()->first();
                    if ($oldRoll) {
                        $restored = min((float) $oldRoll->ukuran_awal, (float) $oldRoll->ukuran_sisa + $oldKain);
                        $oldRoll->ukuran_sisa = $restored;
                        $oldRoll->status_roll = ($restored >= (float) $oldRoll->ukuran_awal - 0.01) ? 'Tersedia' : 'Dipotong';
                        $oldRoll->save();
                    }
                    $newRoll = DetailRollBahan::where('id_roll', $newRollId)->lockForUpdate()->first();
                    if ($newRoll) {
                        $statusReq = $validated['status_roll_baru'] ?? 'Dipotong';
                        $sisa = max(0.0, (float) $newRoll->ukuran_sisa - $newKain);
                        if ($statusReq === 'Habis' || $sisa <= 0.05) {
                            $newRoll->ukuran_sisa = 0.0;
                            $newRoll->status_roll = 'Habis';
                        } else {
                            $newRoll->ukuran_sisa = $sisa;
                            $newRoll->status_roll = $statusReq;
                        }
                        $newRoll->save();
                    }
                } elseif ($newRollId) {
                    $roll = DetailRollBahan::where('id_roll', $newRollId)->lockForUpdate()->first();
                    if ($roll) {
                        $diff = $newKain - $oldKain;
                        $adjustedSisa = max(0.0, min((float) $roll->ukuran_awal, (float) $roll->ukuran_sisa - $diff));
                        $statusReq = $validated['status_roll_baru'] ?? $roll->status_roll;
                        if ($statusReq === 'Habis') {
                            $roll->ukuran_sisa = 0.0;
                            $roll->status_roll = 'Habis';
                        } else {
                            $roll->ukuran_sisa = $adjustedSisa;
                            $roll->status_roll = $statusReq;
                        }
                        $roll->save();
                    }
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Data pemotongan seri {$potong->kode_seri} berhasil diperbarui.",
                    'data' => $potong->fresh(['pesanan', 'userPemotong', 'detailPemotonganWarna.detailGelaranKain.detailRollBahan']),
                ]);
            }

            return redirect()->route('pemotongan.index')->with('success', "Data pemotongan seri {$potong->kode_seri} berhasil diperbarui.");
        });
    }
}
