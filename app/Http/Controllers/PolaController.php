<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pola;
use App\Models\KategoriPola;
use App\Models\SpesifikasiUkuran;
use App\Models\Aksesoris;
use App\Models\DetailAksesorisPola;

class PolaController extends Controller
{
    public function index(Request $request)
    {
        $pola = Pola::with(['kategori', 'spesifikasiUkuran', 'aksesoris'])->get();
        $kategori = KategoriPola::all();
        $aksesoris = Aksesoris::all();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $pola]);
        }

        return view('bong.pola', compact('pola', 'kategori', 'aksesoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kategori' => 'required|integer|exists:Kategori_Pola,id_kategori',
            'kode_pola' => 'required|string|max:20|unique:Pola,kode_pola',
            'nama_pola' => 'required|string|max:100',
            'spesifikasi' => 'required|array|min:1',
            'spesifikasi.*.nama_ukuran' => 'required|string|max:20',
            'spesifikasi.*.diameter_leher' => 'nullable|numeric',
            'spesifikasi.*.panjang_pundak' => 'nullable|numeric',
            'spesifikasi.*.lingkar_lengan' => 'nullable|numeric',
            'spesifikasi.*.panjang_tangan' => 'nullable|numeric',
            'spesifikasi.*.lingkar_dada' => 'nullable|numeric',
            'spesifikasi.*.lingkar_pinggang' => 'nullable|numeric',
            'spesifikasi.*.lingkar_selangkang' => 'nullable|numeric',
            'spesifikasi.*.lingkar_paha' => 'nullable|numeric',
            'spesifikasi.*.lebar_kaki' => 'nullable|numeric',
            'spesifikasi.*.lebar_baju' => 'nullable|numeric',
            'spesifikasi.*.panjang_baju_celana' => 'nullable|numeric',
            'aksesoris' => 'nullable|array',
            'aksesoris.*.id_aksesoris' => 'required|integer|exists:Aksesoris,id_aksesoris',
            'aksesoris.*.jumlah_digunakan' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $pola = Pola::create([
                'id_kategori' => $validated['id_kategori'],
                'kode_pola' => $validated['kode_pola'],
                'nama_pola' => $validated['nama_pola'],
            ]);

            // Save 11-metric size specifications
            foreach ($validated['spesifikasi'] as $spec) {
                SpesifikasiUkuran::create([
                    'id_pola' => $pola->id_pola,
                    'nama_ukuran' => $spec['nama_ukuran'],
                    'diameter_leher' => $spec['diameter_leher'] ?? null,
                    'panjang_pundak' => $spec['panjang_pundak'] ?? null,
                    'lingkar_lengan' => $spec['lingkar_lengan'] ?? null,
                    'panjang_tangan' => $spec['panjang_tangan'] ?? null,
                    'lingkar_dada' => $spec['lingkar_dada'] ?? null,
                    'lingkar_pinggang' => $spec['lingkar_pinggang'] ?? null,
                    'lingkar_selangkang' => $spec['lingkar_selangkang'] ?? null,
                    'lingkar_paha' => $spec['lingkar_paha'] ?? null,
                    'lebar_kaki' => $spec['lebar_kaki'] ?? null,
                    'lebar_baju' => $spec['lebar_baju'] ?? null,
                    'panjang_baju_celana' => $spec['panjang_baju_celana'] ?? null,
                ]);
            }

            // Save accessory mappings
            if (!empty($validated['aksesoris'])) {
                foreach ($validated['aksesoris'] as $acc) {
                    DetailAksesorisPola::create([
                        'id_pola' => $pola->id_pola,
                        'id_aksesoris' => $acc['id_aksesoris'],
                        'jumlah_digunakan' => $acc['jumlah_digunakan'],
                    ]);
                }
            }

            $pola->load(['kategori', 'spesifikasiUkuran', 'aksesoris']);

            return response()->json([
                'success' => true,
                'message' => "Pola {$pola->kode_pola} berhasil disimpan dengan spesifikasi ukuran dan aksesoris.",
                'data' => $pola,
            ], 201);
        });
    }

    public function show($id)
    {
        $pola = Pola::with(['kategori', 'spesifikasiUkuran', 'aksesoris', 'detailAksesorisPola.aksesoris'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $pola]);
    }
}

