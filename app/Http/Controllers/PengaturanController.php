<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\KategoriPola;
use App\Models\SumberInisiasi;
use App\Models\Supplier;

class PengaturanController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('id_user', 'asc')->get();
        $kategori = KategoriPola::orderBy('id_kategori', 'asc')->get();
        $sumber = SumberInisiasi::orderBy('id_sumber', 'asc')->get();
        $suppliers = Supplier::orderBy('id_supplier', 'asc')->get();

        $counts = [
            'pesanan' => DB::table('Pesanan')->count(),
            'penerimaan' => DB::table('Penerimaan_Bahan')->count(),
            'roll' => DB::table('Detail_Roll_Bahan')->count(),
            'pemotongan' => DB::table('Pemotongan_Seri')->count(),
            'qc' => DB::table('QC')->count(),
            'stok_jadi' => DB::table('Stok_Barang_Jadi')->count(),
            'pola' => DB::table('Pola')->count(),
            'aksesoris' => DB::table('Aksesoris')->count(),
        ];

        return view('bong.pengaturan', compact('users', 'kategori', 'sumber', 'suppliers', 'counts'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'nama_user' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:User,username',
            'password' => 'required|string|min:4|max:100',
            'role' => 'required|in:Admin,Pemilik,Pemotong,QC,Gudang',
        ]);

        $user = User::create([
            'nama_user' => $validated['nama_user'],
            'username' => trim($validated['username']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Akun pengguna {$user->nama_user} (@{$user->username}) dengan peran {$user->role} berhasil ditambahkan.",
            'data' => $user,
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama_user' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:User,username,' . $user->id_user . ',id_user',
            'password' => 'nullable|string|min:4|max:100',
            'role' => 'required|in:Admin,Pemilik,Pemotong,QC,Gudang',
        ]);

        $user->nama_user = $validated['nama_user'];
        $user->username = trim($validated['username']);
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $response = response()->json([
            'success' => true,
            'message' => "Akun pengguna {$user->nama_user} (@{$user->username}) berhasil diperbarui.",
            'data' => $user,
        ]);

        $activeUserId = $_COOKIE['bong_garment_user_id'] ?? $request->cookie('bong_garment_user_id');
        if ((int) $activeUserId === (int) $user->id_user) {
            $response->withCookie(cookie('bong_garment_user_name', $user->nama_user, 60 * 24 * 365, '/', null, false, false));
        }

        return $response;
    }

    public function destroyUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $activeUserId = $_COOKIE['bong_garment_user_id'] ?? $request->cookie('bong_garment_user_id');

        if ((int) $activeUserId === (int) $user->id_user) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun yang sedang Anda gunakan untuk login saat ini.',
            ], 422);
        }

        if ($user->role === 'Admin' && User::where('role', 'Admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus satu-satunya akun Admin yang tersisa di sistem.',
            ], 422);
        }

        try {
            $nama = $user->nama_user;
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => "Akun pengguna {$nama} berhasil dihapus.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak dapat dihapus karena masih terhubung dengan riwayat pesanan/pemotongan/QC.',
            ], 422);
        }
    }

    public function storeKategori(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:50',
        ]);
        $kat = KategoriPola::create($validated);
        return response()->json([
            'success' => true,
            'message' => "Kategori pola '{$kat->nama_kategori}' berhasil ditambahkan.",
            'data' => $kat,
        ], 201);
    }

    public function updateKategori(Request $request, $id)
    {
        $kat = KategoriPola::findOrFail($id);
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:50',
        ]);
        $kat->update($validated);
        return response()->json([
            'success' => true,
            'message' => "Kategori pola berhasil diperbarui menjadi '{$kat->nama_kategori}'.",
            'data' => $kat,
        ]);
    }

    public function destroyKategori($id)
    {
        $kat = KategoriPola::findOrFail($id);
        try {
            $kat->delete();
            return response()->json(['success' => true, 'message' => 'Kategori pola berhasil dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Kategori ini sedang digunakan oleh Master Pola dan tidak dapat dihapus.'], 422);
        }
    }

    public function storeSumber(Request $request)
    {
        $validated = $request->validate([
            'nama_sumber' => 'required|string|max:50',
        ]);
        $sumber = SumberInisiasi::create($validated);
        return response()->json([
            'success' => true,
            'message' => "Sumber inisiasi '{$sumber->nama_sumber}' berhasil ditambahkan.",
            'data' => $sumber,
        ], 201);
    }

    public function updateSumber(Request $request, $id)
    {
        $sumber = SumberInisiasi::findOrFail($id);
        $validated = $request->validate([
            'nama_sumber' => 'required|string|max:50',
        ]);
        $sumber->update($validated);
        return response()->json([
            'success' => true,
            'message' => "Sumber inisiasi berhasil diperbarui menjadi '{$sumber->nama_sumber}'.",
            'data' => $sumber,
        ]);
    }

    public function destroySumber($id)
    {
        $sumber = SumberInisiasi::findOrFail($id);
        try {
            $sumber->delete();
            return response()->json(['success' => true, 'message' => 'Sumber inisiasi berhasil dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sumber inisiasi ini sedang digunakan pada Pesanan dan tidak dapat dihapus.'], 422);
        }
    }

    public function updateSupplier(Request $request, $id)
    {
        $sup = Supplier::findOrFail($id);
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:100',
            'kontak' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);
        $sup->update($validated);
        return response()->json([
            'success' => true,
            'message' => "Data supplier '{$sup->nama_supplier}' berhasil diperbarui.",
            'data' => $sup,
        ]);
    }

    public function destroySupplier($id)
    {
        $sup = Supplier::findOrFail($id);
        try {
            $sup->delete();
            return response()->json(['success' => true, 'message' => 'Supplier berhasil dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Supplier ini masih terhubung dengan nota penerimaan atau aksesoris.'], 422);
        }
    }

    /**
     * Wipe operational/sample data so the system starts completely clean for real factory production,
     * while preserving User accounts and base lookup categories.
     */
    public function resetOperationalData(Request $request)
    {
        $includeMaster = (bool) $request->input('include_master', false);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('QC')->truncate();
        DB::table('Detail_Gelaran_Kain')->truncate();
        DB::table('Detail_Pemotongan_Warna')->truncate();
        DB::table('Pemotongan_Seri')->truncate();
        DB::table('Stok_Barang_Jadi')->truncate();
        DB::table('Pesanan')->truncate();
        DB::table('Detail_Roll_Bahan')->truncate();
        DB::table('Detail_Bahan_Warna')->truncate();
        DB::table('Penerimaan_Bahan')->truncate();

        if ($includeMaster) {
            DB::table('Detail_Aksesoris_Pola')->truncate();
            DB::table('Aksesoris')->truncate();
            DB::table('Supplier')->truncate();
            DB::table('Spesifikasi_Ukuran')->truncate();
            DB::table('Pola')->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $msg = $includeMaster
            ? 'Seluruh data transaksi operasional DAN katalog master (Pola, Aksesoris, Supplier) berhasil dikosongkan. Akun Pengguna tetap tersimpan.'
            : 'Seluruh data transaksi operasional (Pesanan, Nota & Roll Bahan, Pemotongan, QC, Stok Barang Jadi) berhasil dikosongkan. Akun Pengguna dan Master Pola/Aksesoris tetap tersimpan.';

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    /**
     * Populate sample data for testing/training while preserving custom User accounts.
     */
    public function seedSampleData()
    {
        $existingUsers = DB::table('User')->get()->map(fn($u) => (array) $u)->toArray();

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\BongGarment17TableSeeder::class,
            '--force' => true,
        ]);

        if (!empty($existingUsers)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('User')->truncate();
            DB::table('User')->insert($existingUsers);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return response()->json([
            'success' => true,
            'message' => 'Data contoh produksi berhasil dimuat ulang tanpa mengubah akun pengguna Anda.',
        ]);
    }
}
