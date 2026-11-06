<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use App\Models\Pola;
use App\Models\KategoriPola;
use App\Models\SpesifikasiUkuran;
use App\Models\DetailAksesorisPola;
use App\Models\Aksesoris;
use App\Models\SumberInisiasi;
use App\Models\PenerimaanBahan;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $pesanan = Pesanan::with(['pola.kategori', 'sumberInisiasi', 'penerimaanBahan', 'userAdmin'])
            ->orderByRaw('COALESCE(deadline_terbaru, deadline_awal) ASC')
            ->get();

        $pola = Pola::with(['kategori', 'spesifikasiUkuran', 'detailAksesorisPola.aksesoris'])->get();
        $kategori = KategoriPola::all();
        $aksesoris = Aksesoris::all();
        $sumber = SumberInisiasi::all();
        $penerimaan = PenerimaanBahan::all();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $pesanan]);
        }

        return view('bong.pesanan', compact('pesanan', 'pola', 'kategori', 'aksesoris', 'sumber', 'penerimaan'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('id_user_admin')) {
            $request->merge(['id_user_admin' => 1]);
        }
        if (!$request->filled('tgl_order')) {
            $request->merge(['tgl_order' => now()->toDateString()]);
        }

        return DB::transaction(function () use ($request) {
            // Support inline New Pattern (Daftarkan Pola Baru) creation during order initiation
            if ($request->input('pola_mode') === 'new' && $request->filled('new_pola')) {
                $newPolaData = $request->input('new_pola');
                $kodePola = strtoupper(trim($newPolaData['kode_pola'] ?? ('POLA-' . rand(100, 999))));
                $namaPola = trim($newPolaData['nama_pola'] ?? 'Master Pola Baru');
                $idKategori = (int) ($newPolaData['id_kategori'] ?? 1);

                $createdPola = Pola::create([
                    'id_kategori' => $idKategori,
                    'kode_pola' => $kodePola,
                    'nama_pola' => $namaPola,
                ]);

                $specs = $newPolaData['spesifikasi'] ?? [];
                foreach ($specs as $spec) {
                    if (empty($spec['nama_ukuran'])) continue;
                    SpesifikasiUkuran::create([
                        'id_pola' => $createdPola->id_pola,
                        'nama_ukuran' => $spec['nama_ukuran'],
                        'diameter_leher' => !empty($spec['diameter_leher']) ? $spec['diameter_leher'] : null,
                        'panjang_pundak' => !empty($spec['panjang_pundak']) ? $spec['panjang_pundak'] : null,
                        'lingkar_lengan' => !empty($spec['lingkar_lengan']) ? $spec['lingkar_lengan'] : null,
                        'panjang_tangan' => !empty($spec['panjang_tangan']) ? $spec['panjang_tangan'] : null,
                        'lingkar_dada' => !empty($spec['lingkar_dada']) ? $spec['lingkar_dada'] : null,
                        'lingkar_pinggang' => !empty($spec['lingkar_pinggang']) ? $spec['lingkar_pinggang'] : null,
                        'lingkar_selangkang' => !empty($spec['lingkar_selangkang']) ? $spec['lingkar_selangkang'] : null,
                        'lingkar_paha' => !empty($spec['lingkar_paha']) ? $spec['lingkar_paha'] : null,
                        'lebar_kaki' => !empty($spec['lebar_kaki']) ? $spec['lebar_kaki'] : null,
                        'lebar_baju' => !empty($spec['lebar_baju']) ? $spec['lebar_baju'] : null,
                        'panjang_baju_celana' => !empty($spec['panjang_baju_celana']) ? $spec['panjang_baju_celana'] : null,
                    ]);
                }

                $accs = $newPolaData['aksesoris'] ?? [];
                foreach ($accs as $acc) {
                    if (empty($acc['id_aksesoris']) || empty($acc['jumlah_digunakan'])) continue;
                    DetailAksesorisPola::updateOrCreate(
                        [
                            'id_pola' => $createdPola->id_pola,
                            'id_aksesoris' => (int) $acc['id_aksesoris'],
                        ],
                        [
                            'jumlah_digunakan' => (int) $acc['jumlah_digunakan'],
                        ]
                    );
                }

                $request->merge(['id_pola' => $createdPola->id_pola]);
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
        });
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
            'tgl_order' => 'nullable|date',
            'deadline_awal' => 'required|date',
            'deadline_terbaru' => 'nullable|date',
            'status_pesanan' => 'required|string|max:50',
            'alasan_perubahan_deadline' => 'nullable|string|max:255',
        ]);

        if ((int)$validated['id_sumber'] !== 1) {
            $validated['nama_mitra'] = null;
        }

        if (empty($validated['tgl_order'])) {
            unset($validated['tgl_order']);
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
