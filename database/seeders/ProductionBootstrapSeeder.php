<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductionBootstrapSeeder extends Seeder
{
    /**
     * Seed only the essential lookup tables and default role accounts
     * for a clean production installation without sample operational data.
     */
    public function run(): void
    {
        // 1. Sumber_Inisiasi (Order Initiation Sources)
        $sources = [
            ['id_sumber' => 1, 'nama_sumber' => 'Request Mitra'],
            ['id_sumber' => 2, 'nama_sumber' => 'Produk Abadi'],
            ['id_sumber' => 3, 'nama_sumber' => 'Inisiatif Tren Pasar'],
        ];
        foreach ($sources as $s) {
            DB::table('Sumber_Inisiasi')->updateOrInsert(['id_sumber' => $s['id_sumber']], $s);
        }

        // 2. Kategori_Pola (Default Pattern Categories)
        $categories = [
            ['id_kategori' => 1, 'nama_kategori' => 'Kaos & Polo'],
            ['id_kategori' => 2, 'nama_kategori' => 'Kemeja & Formal'],
            ['id_kategori' => 3, 'nama_kategori' => 'Celana & Chino'],
            ['id_kategori' => 4, 'nama_kategori' => 'Jaket & Outerwear'],
        ];
        foreach ($categories as $c) {
            DB::table('Kategori_Pola')->updateOrInsert(['id_kategori' => $c['id_kategori']], $c);
        }

        // 3. Default User Accounts (only created if no users exist yet)
        if (DB::table('User')->count() === 0) {
            DB::table('User')->insert([
                [
                    'id_user' => 1, 'username' => 'admin', 'password' => Hash::make('admin123'),
                    'nama_user' => 'Administrator', 'role' => 'Admin',
                ],
                [
                    'id_user' => 2, 'username' => 'pemilik', 'password' => Hash::make('pemilik123'),
                    'nama_user' => 'Pemilik Konveksi', 'role' => 'Pemilik',
                ],
                [
                    'id_user' => 3, 'username' => 'pemotong', 'password' => Hash::make('potong123'),
                    'nama_user' => 'Kepala Pemotong', 'role' => 'Pemotong',
                ],
                [
                    'id_user' => 4, 'username' => 'qc', 'password' => Hash::make('qc123'),
                    'nama_user' => 'Petugas Quality Control', 'role' => 'QC',
                ],
                [
                    'id_user' => 5, 'username' => 'gudang', 'password' => Hash::make('gudang123'),
                    'nama_user' => 'Kepala Gudang & Inventori', 'role' => 'Gudang',
                ],
            ]);
        }
    }
}
