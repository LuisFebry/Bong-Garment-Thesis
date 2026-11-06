<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BongGarment18TableSeeder extends Seeder
{
    /**
     * Run the database seeds for all 18 canonical tables.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        if (\Illuminate\Support\Facades\Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
            DB::table('Riwayat_Stok_Barang_Jadi')->truncate();
        }
        DB::table('QC')->truncate();
        DB::table('Detail_Gelaran_Kain')->truncate();
        DB::table('Detail_Pemotongan_Warna')->truncate();
        DB::table('Pemotongan_Seri')->truncate();
        DB::table('Stok_Barang_Jadi')->truncate();
        DB::table('Pesanan')->truncate();
        DB::table('Detail_Roll_Bahan')->truncate();
        DB::table('Detail_Bahan_Warna')->truncate();
        DB::table('Penerimaan_Bahan')->truncate();
        DB::table('Detail_Aksesoris_Pola')->truncate();
        DB::table('Aksesoris')->truncate();
        DB::table('Supplier')->truncate();
        DB::table('User')->truncate();
        DB::table('Spesifikasi_Ukuran')->truncate();
        DB::table('Pola')->truncate();
        DB::table('Kategori_Pola')->truncate();
        DB::table('Sumber_Inisiasi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Sumber_Inisiasi
        DB::table('Sumber_Inisiasi')->insert([
            ['id_sumber' => 1, 'nama_sumber' => 'Request Mitra'],
            ['id_sumber' => 2, 'nama_sumber' => 'Produk Abadi'],
            ['id_sumber' => 3, 'nama_sumber' => 'Inisiatif Tren Pasar'],
        ]);

        // 2. Kategori_Pola
        DB::table('Kategori_Pola')->insert([
            ['id_kategori' => 1, 'nama_kategori' => 'Kaos & Polo'],
            ['id_kategori' => 2, 'nama_kategori' => 'Kemeja & Formal'],
            ['id_kategori' => 3, 'nama_kategori' => 'Celana & Chino'],
            ['id_kategori' => 4, 'nama_kategori' => 'Jaket & Outerwear'],
        ]);

        // 3. Pola
        DB::table('Pola')->insert([
            ['id_pola' => 1, 'id_kategori' => 1, 'kode_pola' => 'POLA-KOS-01', 'nama_pola' => 'Kaos Polos Cotton Combed 30s Crewneck'],
            ['id_pola' => 2, 'id_kategori' => 2, 'kode_pola' => 'POLA-KMJ-02', 'nama_pola' => 'Kemeja Slim Fit Oxford Lengan Panjang'],
            ['id_pola' => 3, 'id_kategori' => 3, 'kode_pola' => 'POLA-CHN-03', 'nama_pola' => 'Celana Chino Stretch Kasual Pria'],
            ['id_pola' => 4, 'id_kategori' => 1, 'kode_pola' => 'POLA-PLO-04', 'nama_pola' => 'Polo Shirt Lacoste CVC Rib Collar'],
        ]);

        // 4. Spesifikasi_Ukuran (11 metric columns)
        DB::table('Spesifikasi_Ukuran')->insert([
            // Kaos Pola 1
            [
                'id_spesifikasi' => 1, 'id_pola' => 1, 'nama_ukuran' => 'S',
                'diameter_leher' => 17.50, 'panjang_pundak' => 13.50, 'lingkar_lengan' => 42.00, 'panjang_tangan' => 21.00,
                'lingkar_dada' => 96.00, 'lingkar_pinggang' => 94.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 48.00, 'panjang_baju_celana' => 68.00,
            ],
            [
                'id_spesifikasi' => 2, 'id_pola' => 1, 'nama_ukuran' => 'M',
                'diameter_leher' => 18.00, 'panjang_pundak' => 14.50, 'lingkar_lengan' => 44.00, 'panjang_tangan' => 22.00,
                'lingkar_dada' => 102.00, 'lingkar_pinggang' => 100.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 51.00, 'panjang_baju_celana' => 70.00,
            ],
            [
                'id_spesifikasi' => 3, 'id_pola' => 1, 'nama_ukuran' => 'L',
                'diameter_leher' => 18.50, 'panjang_pundak' => 15.50, 'lingkar_lengan' => 46.00, 'panjang_tangan' => 23.50,
                'lingkar_dada' => 108.00, 'lingkar_pinggang' => 106.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 54.00, 'panjang_baju_celana' => 72.00,
            ],
            [
                'id_spesifikasi' => 4, 'id_pola' => 1, 'nama_ukuran' => 'XL',
                'diameter_leher' => 19.00, 'panjang_pundak' => 16.50, 'lingkar_lengan' => 48.00, 'panjang_tangan' => 25.00,
                'lingkar_dada' => 114.00, 'lingkar_pinggang' => 112.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 57.00, 'panjang_baju_celana' => 74.00,
            ],
            // Kemeja Pola 2
            [
                'id_spesifikasi' => 5, 'id_pola' => 2, 'nama_ukuran' => 'S',
                'diameter_leher' => 38.00, 'panjang_pundak' => 43.00, 'lingkar_lengan' => 46.00, 'panjang_tangan' => 58.00,
                'lingkar_dada' => 98.00, 'lingkar_pinggang' => 90.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 49.00, 'panjang_baju_celana' => 71.00,
            ],
            [
                'id_spesifikasi' => 6, 'id_pola' => 2, 'nama_ukuran' => 'M',
                'diameter_leher' => 39.50, 'panjang_pundak' => 45.00, 'lingkar_lengan' => 48.00, 'panjang_tangan' => 60.00,
                'lingkar_dada' => 104.00, 'lingkar_pinggang' => 96.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 52.00, 'panjang_baju_celana' => 73.00,
            ],
            [
                'id_spesifikasi' => 7, 'id_pola' => 2, 'nama_ukuran' => 'L',
                'diameter_leher' => 41.00, 'panjang_pundak' => 47.00, 'lingkar_lengan' => 50.00, 'panjang_tangan' => 62.00,
                'lingkar_dada' => 110.00, 'lingkar_pinggang' => 102.00, 'lingkar_selangkang' => null, 'lingkar_paha' => null,
                'lebar_kaki' => null, 'lebar_baju' => 55.00, 'panjang_baju_celana' => 75.00,
            ],
            // Chino Pola 3
            [
                'id_spesifikasi' => 8, 'id_pola' => 3, 'nama_ukuran' => 'M',
                'diameter_leher' => null, 'panjang_pundak' => null, 'lingkar_lengan' => null, 'panjang_tangan' => null,
                'lingkar_dada' => null, 'lingkar_pinggang' => 82.00, 'lingkar_selangkang' => 28.00, 'lingkar_paha' => 58.00,
                'lebar_kaki' => 34.00, 'lebar_baju' => null, 'panjang_baju_celana' => 98.00,
            ],
            [
                'id_spesifikasi' => 9, 'id_pola' => 3, 'nama_ukuran' => 'L',
                'diameter_leher' => null, 'panjang_pundak' => null, 'lingkar_lengan' => null, 'panjang_tangan' => null,
                'lingkar_dada' => null, 'lingkar_pinggang' => 88.00, 'lingkar_selangkang' => 30.00, 'lingkar_paha' => 62.00,
                'lebar_kaki' => 36.00, 'lebar_baju' => null, 'panjang_baju_celana' => 100.00,
            ],
        ]);

        // 5. User (5 Roles)
        DB::table('User')->insert([
            [
                'id_user' => 1, 'username' => 'admin', 'password' => Hash::make('admin123'),
                'nama_user' => 'Administrator', 'role' => 'Admin',
            ],
            [
                'id_user' => 2, 'username' => 'pemilik', 'password' => Hash::make('pemilik123'),
                'nama_user' => 'Bong (Pemilik CMT)', 'role' => 'Pemilik',
            ],
            [
                'id_user' => 3, 'username' => 'pemotong', 'password' => Hash::make('potong123'),
                'nama_user' => 'Budi Santoso (Head Cutter)', 'role' => 'Pemotong',
            ],
            [
                'id_user' => 4, 'username' => 'qc', 'password' => Hash::make('qc123'),
                'nama_user' => 'Siti Rahma (Lead Quality Control)', 'role' => 'QC',
            ],
            [
                'id_user' => 5, 'username' => 'gudang', 'password' => Hash::make('gudang123'),
                'nama_user' => 'Agus Hermawan (Kepala Gudang & Inventori)', 'role' => 'Gudang',
            ],
        ]);

        // 6. Supplier
        DB::table('Supplier')->insert([
            [
                'id_supplier' => 1, 'nama_supplier' => 'PT Kahatex Tekstil Prima',
                'kontak' => '022-7798888', 'alamat' => 'Jl. Raya Rancaekek No.KM 23, Sumedang, Jawa Barat',
            ],
            [
                'id_supplier' => 2, 'nama_supplier' => 'CV Aksesoris Garment Harapan',
                'kontak' => '021-6901234', 'alamat' => 'Pasar Pagi Mangga Dua Lt. 2 Blok B No. 45, Jakarta Barat',
            ],
            [
                'id_supplier' => 3, 'nama_supplier' => 'PT Dan Liris Tekstil Sukses',
                'kontak' => '0271-714400', 'alamat' => 'Banaran, Grogol, Sukoharjo, Jawa Tengah',
            ],
        ]);

        // 7. Aksesoris
        DB::table('Aksesoris')->insert([
            ['id_aksesoris' => 1, 'id_supplier' => 2, 'nama_aksesoris' => 'Kancing Jamur Resin Smoke Grey 18L', 'ukuran' => '18L', 'stok' => 15000, 'satuan' => 'pcs'],
            ['id_aksesoris' => 2, 'id_supplier' => 2, 'nama_aksesoris' => 'Ritsleting Metal YKK Antique Brass 7 Inch', 'ukuran' => '7 Inch', 'stok' => 2400, 'satuan' => 'pcs'],
            ['id_aksesoris' => 3, 'id_supplier' => 2, 'nama_aksesoris' => 'Benang Jahit Katun Astra 40/2 Hitam', 'ukuran' => '5000 Yds', 'stok' => 180, 'satuan' => 'pcs'],
            ['id_aksesoris' => 4, 'id_supplier' => 2, 'nama_aksesoris' => 'Label Woven Tag Bong Garment Premium', 'ukuran' => '5x2.5 cm', 'stok' => 12000, 'satuan' => 'pcs'],
            ['id_aksesoris' => 5, 'id_supplier' => 2, 'nama_aksesoris' => 'Karet Pinggang Stretch Elastic 3.8cm', 'ukuran' => '3.8 cm', 'stok' => 45, 'satuan' => 'gross'],
        ]);

        // 8. Detail_Aksesoris_Pola
        DB::table('Detail_Aksesoris_Pola')->insert([
            // Kaos Pola 1: Label Woven 1 pcs, Benang Astra 1 spool/unit
            ['id_pola' => 1, 'id_aksesoris' => 4, 'jumlah_digunakan' => 1],
            ['id_pola' => 1, 'id_aksesoris' => 3, 'jumlah_digunakan' => 1],
            // Kemeja Pola 2: Kancing 8 pcs, Label 1 pcs, Benang 1
            ['id_pola' => 2, 'id_aksesoris' => 1, 'jumlah_digunakan' => 8],
            ['id_pola' => 2, 'id_aksesoris' => 4, 'jumlah_digunakan' => 1],
            ['id_pola' => 2, 'id_aksesoris' => 3, 'jumlah_digunakan' => 1],
            // Chino Pola 3: Ritsleting 1 pcs, Kancing 1 pcs, Karet 1, Label 1
            ['id_pola' => 3, 'id_aksesoris' => 2, 'jumlah_digunakan' => 1],
            ['id_pola' => 3, 'id_aksesoris' => 1, 'jumlah_digunakan' => 1],
            ['id_pola' => 3, 'id_aksesoris' => 4, 'jumlah_digunakan' => 1],
        ]);

        // 9. Penerimaan_Bahan
        DB::table('Penerimaan_Bahan')->insert([
            [
                'id_penerimaan' => 1, 'id_supplier' => 1, 'no_nota' => 'NOTA-2026-1001',
                'tgl_nota' => '2026-10-15', 'nama_kain' => 'Cotton Combed 30s Super Soft',
                'lebar_gramasi' => 'Gramasi 145 / Lebar 42" Tubular', 'no_kendaraan' => 'B 9821 TAA', 'penerima' => 'Agus Hermawan',
            ],
            [
                'id_penerimaan' => 2, 'id_supplier' => 1, 'no_nota' => 'NOTA-2026-1014',
                'tgl_nota' => '2026-10-22', 'nama_kain' => 'Katun Oxford Solid Premium',
                'lebar_gramasi' => 'Gramasi 180 / Lebar 58"', 'no_kendaraan' => 'B 9112 PQU', 'penerima' => 'Agus Hermawan',
            ],
            [
                'id_penerimaan' => 3, 'id_supplier' => 3, 'no_nota' => 'NOTA-2026-1028',
                'tgl_nota' => '2026-10-28', 'nama_kain' => 'Twill Chino Stretch Spandex',
                'lebar_gramasi' => 'Gramasi 260 / Lebar 60"', 'no_kendaraan' => 'B 9345 SXZ', 'penerima' => 'Agus Hermawan',
            ],
        ]);

        // 10. Detail_Bahan_Warna
        DB::table('Detail_Bahan_Warna')->insert([
            ['id_detail_warna' => 1, 'id_penerimaan' => 1, 'nama_warna' => 'Hitam Reaktif', 'kode_warna' => 'CLR-BLK-01', 'total_roll' => 4, 'total_ukuran' => 100.00, 'satuan_ukur' => 'Kg'],
            ['id_detail_warna' => 2, 'id_penerimaan' => 1, 'nama_warna' => 'Navy Blue', 'kode_warna' => 'CLR-NVY-02', 'total_roll' => 3, 'total_ukuran' => 75.00, 'satuan_ukur' => 'Kg'],
            ['id_detail_warna' => 3, 'id_penerimaan' => 2, 'nama_warna' => 'Putih Bersih', 'kode_warna' => 'CLR-WHT-03', 'total_roll' => 2, 'total_ukuran' => 240.00, 'satuan_ukur' => 'Yard'],
            ['id_detail_warna' => 4, 'id_penerimaan' => 2, 'nama_warna' => 'Sky Blue', 'kode_warna' => 'CLR-SKY-04', 'total_roll' => 2, 'total_ukuran' => 240.00, 'satuan_ukur' => 'Yard'],
            ['id_detail_warna' => 5, 'id_penerimaan' => 3, 'nama_warna' => 'Khaki Beige', 'kode_warna' => 'CLR-KHK-05', 'total_roll' => 3, 'total_ukuran' => 360.00, 'satuan_ukur' => 'Meter'],
        ]);

        // 11. Detail_Roll_Bahan
        DB::table('Detail_Roll_Bahan')->insert([
            ['id_roll' => 1, 'id_detail_warna' => 1, 'no_roll_ke' => 1, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 5.00, 'satuan' => 'Kg', 'status_roll' => 'Dipotong'],
            ['id_roll' => 2, 'id_detail_warna' => 1, 'no_roll_ke' => 2, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 25.00, 'satuan' => 'Kg', 'status_roll' => 'Tersedia'],
            ['id_roll' => 3, 'id_detail_warna' => 1, 'no_roll_ke' => 3, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 25.00, 'satuan' => 'Kg', 'status_roll' => 'Tersedia'],
            ['id_roll' => 4, 'id_detail_warna' => 1, 'no_roll_ke' => 4, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 0.00, 'satuan' => 'Kg', 'status_roll' => 'Habis'],
            ['id_roll' => 5, 'id_detail_warna' => 2, 'no_roll_ke' => 1, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 25.00, 'satuan' => 'Kg', 'status_roll' => 'Tersedia'],
            ['id_roll' => 6, 'id_detail_warna' => 2, 'no_roll_ke' => 2, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 25.00, 'satuan' => 'Kg', 'status_roll' => 'Tersedia'],
            ['id_roll' => 7, 'id_detail_warna' => 2, 'no_roll_ke' => 3, 'ukuran_awal' => 25.00, 'ukuran_sisa' => 25.00, 'satuan' => 'Kg', 'status_roll' => 'Tersedia'],
            ['id_roll' => 8, 'id_detail_warna' => 3, 'no_roll_ke' => 1, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Yard', 'status_roll' => 'Tersedia'],
            ['id_roll' => 9, 'id_detail_warna' => 3, 'no_roll_ke' => 2, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Yard', 'status_roll' => 'Tersedia'],
            ['id_roll' => 10, 'id_detail_warna' => 4, 'no_roll_ke' => 1, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Yard', 'status_roll' => 'Tersedia'],
            ['id_roll' => 11, 'id_detail_warna' => 4, 'no_roll_ke' => 2, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Yard', 'status_roll' => 'Tersedia'],
            ['id_roll' => 12, 'id_detail_warna' => 5, 'no_roll_ke' => 1, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Meter', 'status_roll' => 'Tersedia'],
            ['id_roll' => 13, 'id_detail_warna' => 5, 'no_roll_ke' => 2, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Meter', 'status_roll' => 'Tersedia'],
            ['id_roll' => 14, 'id_detail_warna' => 5, 'no_roll_ke' => 3, 'ukuran_awal' => 120.00, 'ukuran_sisa' => 120.00, 'satuan' => 'Meter', 'status_roll' => 'Tersedia'],
        ]);

        // 12. Pesanan (with EDF deadlines)
        DB::table('Pesanan')->insert([
            [
                'id_pesanan' => 1, 'id_pola' => 1, 'id_sumber' => 1, 'id_penerimaan' => 1, 'id_user_admin' => 1,
                'jenis_pesanan' => 'Kaos Combed 30s Custom Sablon', 'nama_mitra' => 'PT Busana Mandiri Kreasi',
                'tgl_order' => '2026-10-20', 'deadline_awal' => '2026-11-10', 'deadline_terbaru' => '2026-11-08',
                'tgl_perubahan_deadline' => '2026-10-26 14:00:00',
                'alasan_perubahan_deadline' => 'Permintaan percepatan jadwal distribusi oleh mitra untuk event peluncuran produk',
                'status_pesanan' => 'Pemotongan',
            ],
            [
                'id_pesanan' => 2, 'id_pola' => 2, 'id_sumber' => 1, 'id_penerimaan' => 2, 'id_user_admin' => 1,
                'jenis_pesanan' => 'Kemeja Formal Oxford Putih & Sky Blue', 'nama_mitra' => 'CV Sinar Apparel Distro',
                'tgl_order' => '2026-10-22', 'deadline_awal' => '2026-11-15', 'deadline_terbaru' => '2026-11-15',
                'tgl_perubahan_deadline' => null,
                'alasan_perubahan_deadline' => null,
                'status_pesanan' => 'Antrean',
            ],
            [
                'id_pesanan' => 3, 'id_pola' => 1, 'id_sumber' => 2, 'id_penerimaan' => 1, 'id_user_admin' => 1,
                'jenis_pesanan' => 'Kaos Polos Standar Bong Garment', 'nama_mitra' => null,
                'tgl_order' => '2026-10-25', 'deadline_awal' => '2026-11-20', 'deadline_terbaru' => '2026-11-20',
                'tgl_perubahan_deadline' => null,
                'alasan_perubahan_deadline' => null,
                'status_pesanan' => 'Antrean',
            ],
            [
                'id_pesanan' => 4, 'id_pola' => 3, 'id_sumber' => 3, 'id_penerimaan' => 3, 'id_user_admin' => 1,
                'jenis_pesanan' => 'Celana Chino Slim Fit Tren Musim Hujan', 'nama_mitra' => null,
                'tgl_order' => '2026-10-28', 'deadline_awal' => '2026-11-25', 'deadline_terbaru' => '2026-11-28',
                'tgl_perubahan_deadline' => '2026-10-30 09:30:00',
                'alasan_perubahan_deadline' => 'Penyesuaian jadwal kedatangan kancing saku belakang dari vendor aksesoris',
                'status_pesanan' => 'Antrean',
            ],
        ]);

        // 13. Stok_Barang_Jadi
        DB::table('Stok_Barang_Jadi')->insert([
            ['id_stok' => 1, 'id_pola' => 1, 'id_spesifikasi' => 1, 'nama_warna' => 'Hitam Reaktif', 'jumlah_stok_pcs' => 80, 'tgl_update_terakhir' => '2026-10-30 16:30:00'],
            ['id_stok' => 2, 'id_pola' => 1, 'id_spesifikasi' => 2, 'nama_warna' => 'Hitam Reaktif', 'jumlah_stok_pcs' => 140, 'tgl_update_terakhir' => '2026-10-30 16:30:00'],
            ['id_stok' => 3, 'id_pola' => 1, 'id_spesifikasi' => 3, 'nama_warna' => 'Hitam Reaktif', 'jumlah_stok_pcs' => 110, 'tgl_update_terakhir' => '2026-10-30 16:30:00'],
            ['id_stok' => 4, 'id_pola' => 2, 'id_spesifikasi' => 6, 'nama_warna' => 'Putih Bersih', 'jumlah_stok_pcs' => 65, 'tgl_update_terakhir' => '2026-10-29 11:15:00'],
        ]);

        // 14. Pemotongan_Seri
        DB::table('Pemotongan_Seri')->insert([
            [
                'id_potong' => 1, 'id_pesanan' => 1, 'id_user_pemotong' => 3,
                'kode_seri' => 'SERI-2026-001', 'jenis_variasi' => 'Kaos Combed Hitam All Size',
                'total_lembar_hasil' => 300, 'tgl_potong' => '2026-10-28 10:00:00',
            ],
        ]);

        // 15. Detail_Pemotongan_Warna
        DB::table('Detail_Pemotongan_Warna')->insert([
            [
                'id_potong_warna' => 1, 'id_potong' => 1, 'id_detail_warna' => 1,
                'total_lembar_direncanakan' => 300, 'kain_terpakai' => 45.00,
            ],
        ]);

        // 16. Detail_Gelaran_Kain
        DB::table('Detail_Gelaran_Kain')->insert([
            ['id_gelar' => 1, 'id_potong_warna' => 1, 'id_roll' => 4, 'panjang_gelar' => 2.50, 'jumlah_tumpuk' => 60, 'total_lembar_gelar' => 150],
            ['id_gelar' => 2, 'id_potong_warna' => 1, 'id_roll' => 1, 'panjang_gelar' => 2.50, 'jumlah_tumpuk' => 60, 'total_lembar_gelar' => 150],
        ]);

        // 17. QC (Multi-stage inspection)
        DB::table('QC')->insert([
            [
                'id_qc' => 1, 'id_pesanan' => 1, 'id_user_qc' => 4,
                'tahap_qc' => 'Potong', 'tgl_pemeriksaan' => '2026-10-28 15:30:00',
                'jumlah_diperiksa' => 300, 'jumlah_lolos' => 294, 'jumlah_cacat' => 6,
                'jenis_cacat' => 'Cacat Potong',
                'catatan_qc' => '6 lembar kain terpotong miring pada bagian kerung lengan (armhole)',
                'status_qc' => 'Need Rework',
                'tgl_target_perbaikan' => '2026-10-29',
                'tgl_selesai_perbaikan' => '2026-10-29 11:00:00',
            ],
            [
                'id_qc' => 2, 'id_pesanan' => 1, 'id_user_qc' => 4,
                'tahap_qc' => 'Jahit Akhir', 'tgl_pemeriksaan' => '2026-10-30 16:00:00',
                'jumlah_diperiksa' => 294, 'jumlah_lolos' => 290, 'jumlah_cacat' => 4,
                'jenis_cacat' => 'Cacat Jahit',
                'catatan_qc' => '[PIC: Mitra Penjahit CMT] Obras kerah terlepas pada 4 pcs kaos, segera diperbaiki operator jahit',
                'status_qc' => 'Rework Selesai',
                'tgl_target_perbaikan' => '2026-10-31',
                'tgl_selesai_perbaikan' => '2026-10-31 09:00:00',
            ],
            [
                'id_qc' => 3, 'id_pesanan' => 2, 'id_user_qc' => 4,
                'tahap_qc' => 'Prototipe', 'tgl_pemeriksaan' => '2026-10-31 11:00:00',
                'jumlah_diperiksa' => 20, 'jumlah_lolos' => 18, 'jumlah_cacat' => 2,
                'jenis_cacat' => 'Cacat Bahan',
                'catatan_qc' => '[PIC: Gudang Bahan (Klaim Retur Supplier)] Ditemukan noda oli tenun (fabric stain) pada 2 pcs sampel kain, diajukan klaim retur ke supplier',
                'status_qc' => 'Need Rework',
                'tgl_target_perbaikan' => '2026-11-05',
                'tgl_selesai_perbaikan' => null,
            ],
        ]);

        // 18. Riwayat_Stok_Barang_Jadi (Immutable Transaction Ledger with Mitra tracking)
        if (\Illuminate\Support\Facades\Schema::hasTable('Riwayat_Stok_Barang_Jadi')) {
            DB::table('Riwayat_Stok_Barang_Jadi')->insert([
                [
                    'id_riwayat_stok' => 1,
                    'id_stok' => 1,
                    'id_pola' => 1,
                    'id_spesifikasi' => 1,
                    'nama_warna' => 'Hitam Reaktif',
                    'jenis_transaksi' => 'KELUAR',
                    'jumlah_pcs' => 20,
                    'stok_sebelum' => 100,
                    'stok_sesudah' => 80,
                    'kategori_alasan' => 'Diambil Mitra (Serah Terima Request Mitra)',
                    'nama_mitra' => 'CV Sinar Apparel Distro',
                    'keterangan' => 'Surat Jalan No. SJ-2026-1030 (Batch 1 Pengiriman)',
                    'id_user' => 1,
                    'tgl_transaksi' => '2026-10-30 16:30:00',
                ],
            ]);
        }
    }
}

