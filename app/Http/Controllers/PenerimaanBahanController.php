<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PenerimaanBahan;
use App\Models\DetailBahanWarna;
use App\Models\DetailRollBahan;
use App\Models\Supplier;

class PenerimaanBahanController extends Controller
{
    public function index(Request $request)
    {
        $penerimaan = PenerimaanBahan::with(['supplier', 'detailBahanWarna.detailRollBahan'])->get();
        $suppliers = Supplier::all();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $penerimaan]);
        }

        return view('bong.penerimaan', compact('penerimaan', 'suppliers'));
    }

    public function store(Request $request)
    {
        // Flat-Form Fallback for direct form submissions
        if (!$request->has('warna') && $request->filled('nama_warna')) {
            $satuan = $request->input('satuan_ukur', 'Kg');
            $ukuranAwal = (float) $request->input('ukuran_awal', 25.0);
            $request->merge([
                'warna' => [
                    [
                        'nama_warna' => $request->input('nama_warna'),
                        'kode_warna' => null,
                        'satuan_ukur' => $satuan,
                        'rolls' => [
                            ['ukuran_awal' => $ukuranAwal]
                        ]
                    ]
                ]
            ]);
        }

        $validated = $request->validate([
            'id_supplier' => 'required|integer|exists:Supplier,id_supplier',
            'no_nota' => 'required|string|max:50',
            'tgl_nota' => 'required|date',
            'nama_kain' => 'required|string|max:100',
            'lebar_gramasi' => 'nullable|string|max:50',
            'no_kendaraan' => 'nullable|string|max:20',
            'penerima' => 'nullable|string|max:100',
            'warna' => 'required|array|min:1',
            'warna.*.nama_warna' => 'required|string|max:50',
            'warna.*.kode_warna' => 'nullable|string|max:30',
            'warna.*.satuan_ukur' => 'required|in:Kg,Yard,Meter',
            'warna.*.rolls' => 'required|array|min:1',
            'warna.*.rolls.*.ukuran_awal' => 'required|numeric|min:0.1',
        ]);

        return DB::transaction(function () use ($validated) {
            $penerimaan = PenerimaanBahan::create([
                'id_supplier' => $validated['id_supplier'],
                'no_nota' => $validated['no_nota'],
                'tgl_nota' => $validated['tgl_nota'],
                'nama_kain' => $validated['nama_kain'],
                'lebar_gramasi' => $validated['lebar_gramasi'] ?? null,
                'no_kendaraan' => $validated['no_kendaraan'] ?? null,
                'penerima' => $validated['penerima'] ?? null,
            ]);

            foreach ($validated['warna'] as $w) {
                $totalUkuran = 0;
                $totalRoll = count($w['rolls']);
                foreach ($w['rolls'] as $r) {
                    $totalUkuran += (float)$r['ukuran_awal'];
                }

                $detailWarna = DetailBahanWarna::create([
                    'id_penerimaan' => $penerimaan->id_penerimaan,
                    'nama_warna' => $w['nama_warna'],
                    'kode_warna' => $w['kode_warna'] ?? null,
                    'total_roll' => $totalRoll,
                    'total_ukuran' => $totalUkuran,
                    'satuan_ukur' => $w['satuan_ukur'],
                ]);

                foreach ($w['rolls'] as $idx => $r) {
                    DetailRollBahan::create([
                        'id_detail_warna' => $detailWarna->id_detail_warna,
                        'no_roll_ke' => $idx + 1,
                        'ukuran_awal' => $r['ukuran_awal'],
                        'ukuran_sisa' => $r['ukuran_awal'],
                        'satuan' => $w['satuan_ukur'],
                        'status_roll' => 'Tersedia',
                    ]);
                }
            }

            $penerimaan->load(['supplier', 'detailBahanWarna.detailRollBahan']);

            return response()->json([
                'success' => true,
                'message' => "Penerimaan nota {$penerimaan->no_nota} berhasil disimpan beserta roll bahan.",
                'data' => $penerimaan,
            ], 201);
        });
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:100',
            'kontak' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Supplier {$supplier->nama_supplier} berhasil ditambahkan.",
            'data' => $supplier,
        ], 201);
    }
}

