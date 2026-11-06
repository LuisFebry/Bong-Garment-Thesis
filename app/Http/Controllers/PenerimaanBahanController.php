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
        $penerimaan = PenerimaanBahan::with(['supplier', 'detailBahanWarna.detailRollBahan'])
            ->orderBy('id_penerimaan', 'desc')
            ->get();
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

    public function update(Request $request, $id)
    {
        $penerimaan = PenerimaanBahan::findOrFail($id);

        $validated = $request->validate([
            'id_supplier' => 'required|integer|exists:Supplier,id_supplier',
            'no_nota' => 'required|string|max:50',
            'tgl_nota' => 'required|date',
            'nama_kain' => 'required|string|max:100',
            'lebar_gramasi' => 'nullable|string|max:50',
            'no_kendaraan' => 'nullable|string|max:20',
            'penerima' => 'nullable|string|max:100',
        ]);

        $penerimaan->update($validated);
        $penerimaan->load(['supplier', 'detailBahanWarna.detailRollBahan']);

        return response()->json([
            'success' => true,
            'message' => "Data nota penerimaan {$penerimaan->no_nota} berhasil diperbarui.",
            'data' => $penerimaan,
        ]);
    }

    public function updateWarna(Request $request, $id)
    {
        $warna = DetailBahanWarna::findOrFail($id);

        $validated = $request->validate([
            'nama_warna' => 'required|string|max:50',
            'kode_warna' => 'nullable|string|max:30',
            'satuan_ukur' => 'required|in:Kg,Yard,Meter',
        ]);

        DB::transaction(function () use ($warna, $validated) {
            $warna->update($validated);
            DetailRollBahan::where('id_detail_warna', $warna->id_detail_warna)
                ->update(['satuan' => $validated['satuan_ukur']]);
        });

        return response()->json([
            'success' => true,
            'message' => "Kelompok warna '{$warna->nama_warna}' berhasil diperbarui.",
            'data' => $warna,
        ]);
    }

    public function updateRoll(Request $request, $id)
    {
        $roll = DetailRollBahan::findOrFail($id);

        $validated = $request->validate([
            'no_roll_ke' => 'required|integer|min:1',
            'ukuran_awal' => 'required|numeric|min:0.1',
            'ukuran_sisa' => 'required|numeric|min:0',
            'satuan' => 'required|in:Kg,Yard,Meter',
            'status_roll' => 'required|in:Tersedia,Dipotong,Habis',
        ]);

        if ((float)$validated['ukuran_sisa'] <= 0.0) {
            $validated['status_roll'] = 'Habis';
            $validated['ukuran_sisa'] = 0.0;
        }

        DB::transaction(function () use ($roll, $validated) {
            $roll->update($validated);

            // Recalculate parent Detail_Bahan_Warna totals
            $warna = DetailBahanWarna::find($roll->id_detail_warna);
            if ($warna) {
                $rolls = DetailRollBahan::where('id_detail_warna', $warna->id_detail_warna)->get();
                $warna->update([
                    'total_roll' => $rolls->count(),
                    'total_ukuran' => $rolls->sum('ukuran_awal'),
                    'satuan_ukur' => $validated['satuan'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Roll #{$roll->no_roll_ke} (#id_roll: {$roll->id_roll}) berhasil diperbarui.",
            'data' => $roll,
        ]);
    }

    public function addRollToWarna(Request $request, $id)
    {
        $warna = DetailBahanWarna::findOrFail($id);

        $validated = $request->validate([
            'ukuran_awal' => 'required|numeric|min:0.1',
            'ukuran_sisa' => 'nullable|numeric|min:0',
            'status_roll' => 'nullable|in:Tersedia,Dipotong,Habis',
        ]);

        $roll = DB::transaction(function () use ($warna, $validated) {
            $nextNo = (DetailRollBahan::where('id_detail_warna', $warna->id_detail_warna)->max('no_roll_ke') ?? 0) + 1;
            $ukuranAwal = (float) $validated['ukuran_awal'];
            $ukuranSisa = isset($validated['ukuran_sisa']) ? (float) $validated['ukuran_sisa'] : $ukuranAwal;
            $status = $validated['status_roll'] ?? ($ukuranSisa <= 0 ? 'Habis' : 'Tersedia');

            $created = DetailRollBahan::create([
                'id_detail_warna' => $warna->id_detail_warna,
                'no_roll_ke' => $nextNo,
                'ukuran_awal' => $ukuranAwal,
                'ukuran_sisa' => $ukuranSisa,
                'satuan' => $warna->satuan_ukur,
                'status_roll' => $status,
            ]);

            $rolls = DetailRollBahan::where('id_detail_warna', $warna->id_detail_warna)->get();
            $warna->update([
                'total_roll' => $rolls->count(),
                'total_ukuran' => $rolls->sum('ukuran_awal'),
            ]);

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => "Roll baru (#id_roll: {$roll->id_roll}) berhasil ditambahkan ke warna {$warna->nama_warna}.",
            'data' => $roll,
        ], 201);
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
