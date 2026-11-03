<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 17 Canonical Tables for Bong Garment CMT Production & Inventory Management System
     */
    public function up(): void
    {
        // 1. Sumber_Inisiasi
        Schema::create('Sumber_Inisiasi', function (Blueprint $table) {
            $table->id('id_sumber');
            $table->string('nama_sumber', 50);
        });

        // 2. Kategori_Pola
        Schema::create('Kategori_Pola', function (Blueprint $table) {
            $table->id('id_kategori');
            $table->string('nama_kategori', 50);
        });

        // 3. Pola
        Schema::create('Pola', function (Blueprint $table) {
            $table->id('id_pola');
            $table->unsignedBigInteger('id_kategori');
            $table->string('kode_pola', 20)->unique();
            $table->string('nama_pola', 100);

            $table->foreign('id_kategori')->references('id_kategori')->on('Kategori_Pola')->onDelete('restrict')->onUpdate('cascade');
        });

        // 4. Spesifikasi_Ukuran
        Schema::create('Spesifikasi_Ukuran', function (Blueprint $table) {
            $table->id('id_spesifikasi');
            $table->unsignedBigInteger('id_pola');
            $table->string('nama_ukuran', 20)->comment('S, M, L, XL');
            $table->decimal('diameter_leher', 5, 2)->nullable()->comment('Lebar kerah atasan');
            $table->decimal('panjang_pundak', 5, 2)->nullable()->comment('Lebar bahu atasan');
            $table->decimal('lingkar_lengan', 5, 2)->nullable()->comment('Kerung lengan (armhole)');
            $table->decimal('panjang_tangan', 5, 2)->nullable()->comment('Panjang lengan baju');
            $table->decimal('lingkar_dada', 5, 2)->nullable()->comment('LD untuk baju/gamis');
            $table->decimal('lingkar_pinggang', 5, 2)->nullable()->comment('LP untuk baju/celana/rok');
            $table->decimal('lingkar_selangkang', 5, 2)->nullable()->comment('Tinggi duduk / pesak celana');
            $table->decimal('lingkar_paha', 5, 2)->nullable()->comment('Lebar paha celana');
            $table->decimal('lebar_kaki', 5, 2)->nullable()->comment('Bukaan bawah celana/rok');
            $table->decimal('lebar_baju', 5, 2)->nullable()->comment('Lebar badan pakaian horizontal');
            $table->decimal('panjang_baju_celana', 5, 2)->nullable()->comment('Panjang total vertikal pakaian');

            $table->foreign('id_pola')->references('id_pola')->on('Pola')->onDelete('restrict')->onUpdate('cascade');
        });

        // 5. User
        Schema::create('User', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('username', 50)->unique();
            $table->string('password', 255);
            $table->string('nama_user', 100);
            $table->enum('role', ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang']);
        });

        // 6. Supplier
        Schema::create('Supplier', function (Blueprint $table) {
            $table->id('id_supplier');
            $table->string('nama_supplier', 100);
            $table->string('kontak', 50)->nullable();
            $table->text('alamat')->nullable();
        });

        // 7. Aksesoris
        Schema::create('Aksesoris', function (Blueprint $table) {
            $table->id('id_aksesoris');
            $table->unsignedBigInteger('id_supplier');
            $table->string('nama_aksesoris', 100);
            $table->string('ukuran', 30)->nullable();
            $table->integer('stok')->default(0);
            $table->string('satuan', 20)->comment('pcs, lusin, gross');

            $table->foreign('id_supplier')->references('id_supplier')->on('Supplier')->onDelete('restrict')->onUpdate('cascade');
        });

        // 8. Detail_Aksesoris_Pola
        Schema::create('Detail_Aksesoris_Pola', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pola');
            $table->unsignedBigInteger('id_aksesoris');
            $table->integer('jumlah_digunakan')->comment('Kebutuhan aksesoris per 1 pakaian');

            $table->primary(['id_pola', 'id_aksesoris']);
            $table->foreign('id_pola')->references('id_pola')->on('Pola')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_aksesoris')->references('id_aksesoris')->on('Aksesoris')->onDelete('restrict')->onUpdate('cascade');
        });

        // 9. Penerimaan_Bahan
        Schema::create('Penerimaan_Bahan', function (Blueprint $table) {
            $table->id('id_penerimaan');
            $table->unsignedBigInteger('id_supplier');
            $table->string('no_nota', 50);
            $table->date('tgl_nota');
            $table->string('nama_kain', 100);
            $table->string('lebar_gramasi', 50)->nullable();
            $table->string('no_kendaraan', 20)->nullable();
            $table->string('penerima', 100)->nullable();

            $table->foreign('id_supplier')->references('id_supplier')->on('Supplier')->onDelete('restrict')->onUpdate('cascade');
        });

        // 10. Detail_Bahan_Warna
        Schema::create('Detail_Bahan_Warna', function (Blueprint $table) {
            $table->id('id_detail_warna');
            $table->unsignedBigInteger('id_penerimaan');
            $table->string('nama_warna', 50);
            $table->string('kode_warna', 30)->nullable();
            $table->integer('total_roll')->default(0);
            $table->decimal('total_ukuran', 10, 2)->default(0.00);
            $table->enum('satuan_ukur', ['Kg', 'Yard', 'Meter'])->default('Kg');

            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('Penerimaan_Bahan')->onDelete('restrict')->onUpdate('cascade');
        });

        // 11. Detail_Roll_Bahan
        Schema::create('Detail_Roll_Bahan', function (Blueprint $table) {
            $table->id('id_roll');
            $table->unsignedBigInteger('id_detail_warna');
            $table->integer('no_roll_ke');
            $table->decimal('ukuran_awal', 10, 2);
            $table->decimal('ukuran_sisa', 10, 2);
            $table->enum('satuan', ['Kg', 'Yard', 'Meter']);
            $table->enum('status_roll', ['Tersedia', 'Dipotong', 'Habis'])->default('Tersedia');

            $table->foreign('id_detail_warna')->references('id_detail_warna')->on('Detail_Bahan_Warna')->onDelete('restrict')->onUpdate('cascade');
            $table->index('status_roll');
        });

        // 12. Pesanan
        Schema::create('Pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->unsignedBigInteger('id_pola');
            $table->unsignedBigInteger('id_sumber');
            $table->unsignedBigInteger('id_penerimaan')->nullable();
            $table->unsignedBigInteger('id_user_admin');
            $table->string('jenis_pesanan', 50);
            $table->string('nama_mitra', 100)->nullable();
            $table->date('tgl_order');
            $table->date('deadline_awal')->nullable()->comment('Target asli awal');
            $table->date('deadline_terbaru')->nullable()->comment('Target revisi terbaru untuk EDF');
            $table->dateTime('tgl_perubahan_deadline')->nullable()->comment('Kapan deadline terakhir diubah');
            $table->string('alasan_perubahan_deadline', 255)->nullable();
            $table->string('status_pesanan', 50)->default('Antrean');

            $table->foreign('id_pola')->references('id_pola')->on('Pola')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_sumber')->references('id_sumber')->on('Sumber_Inisiasi')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('Penerimaan_Bahan')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_user_admin')->references('id_user')->on('User')->onDelete('restrict')->onUpdate('cascade');
            $table->index(['deadline_terbaru', 'deadline_awal']);
            $table->index('status_pesanan');
        });

        // 13. Stok_Barang_Jadi
        Schema::create('Stok_Barang_Jadi', function (Blueprint $table) {
            $table->id('id_stok');
            $table->unsignedBigInteger('id_pola');
            $table->unsignedBigInteger('id_spesifikasi');
            $table->string('nama_warna', 50);
            $table->integer('jumlah_stok_pcs')->default(0);
            $table->dateTime('tgl_update_terakhir');

            $table->foreign('id_pola')->references('id_pola')->on('Pola')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_spesifikasi')->references('id_spesifikasi')->on('Spesifikasi_Ukuran')->onDelete('restrict')->onUpdate('cascade');
            $table->index(['id_pola', 'id_spesifikasi']);
        });

        // 14. Pemotongan_Seri
        Schema::create('Pemotongan_Seri', function (Blueprint $table) {
            $table->id('id_potong');
            $table->unsignedBigInteger('id_pesanan');
            $table->unsignedBigInteger('id_user_pemotong');
            $table->string('kode_seri', 50);
            $table->string('jenis_variasi', 50)->nullable();
            $table->integer('total_lembar_hasil')->default(0);
            $table->dateTime('tgl_potong');

            $table->foreign('id_pesanan')->references('id_pesanan')->on('Pesanan')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_user_pemotong')->references('id_user')->on('User')->onDelete('restrict')->onUpdate('cascade');
        });

        // 15. Detail_Pemotongan_Warna
        Schema::create('Detail_Pemotongan_Warna', function (Blueprint $table) {
            $table->id('id_potong_warna');
            $table->unsignedBigInteger('id_potong');
            $table->unsignedBigInteger('id_detail_warna');
            $table->integer('total_lembar_direncanakan');
            $table->decimal('kain_terpakai', 10, 2)->nullable()->comment('Kuantitas material yang dihabiskan');

            $table->foreign('id_potong')->references('id_potong')->on('Pemotongan_Seri')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_detail_warna')->references('id_detail_warna')->on('Detail_Bahan_Warna')->onDelete('restrict')->onUpdate('cascade');
        });

        // 16. Detail_Gelaran_Kain
        Schema::create('Detail_Gelaran_Kain', function (Blueprint $table) {
            $table->id('id_gelar');
            $table->unsignedBigInteger('id_potong_warna');
            $table->unsignedBigInteger('id_roll');
            $table->decimal('panjang_gelar', 8, 2);
            $table->integer('jumlah_tumpuk');
            $table->integer('total_lembar_gelar');

            $table->foreign('id_potong_warna')->references('id_potong_warna')->on('Detail_Pemotongan_Warna')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_roll')->references('id_roll')->on('Detail_Roll_Bahan')->onDelete('restrict')->onUpdate('cascade');
        });

        // 17. QC
        Schema::create('QC', function (Blueprint $table) {
            $table->id('id_qc');
            $table->unsignedBigInteger('id_pesanan');
            $table->unsignedBigInteger('id_user_qc');
            $table->enum('tahap_qc', ['Prototipe', 'Potong', 'Jahit Akhir']);
            $table->dateTime('tgl_pemeriksaan');
            $table->integer('jumlah_diperiksa');
            $table->integer('jumlah_lolos')->default(0);
            $table->integer('jumlah_cacat')->default(0);
            $table->enum('jenis_cacat', ['Tidak Ada', 'Cacat Potong', 'Cacat Jahit', 'Cacat Bahan'])->default('Tidak Ada');
            $table->text('catatan_qc')->nullable();
            $table->enum('status_qc', ['Lolos QC', 'Need Rework', 'Rework Selesai', 'Scrapped']);
            $table->date('tgl_target_perbaikan')->nullable()->comment('Tenggat perbaikan jika cacat');
            $table->dateTime('tgl_selesai_perbaikan')->nullable()->comment('Kapan rework selesai');

            $table->foreign('id_pesanan')->references('id_pesanan')->on('Pesanan')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_user_qc')->references('id_user')->on('User')->onDelete('restrict')->onUpdate('cascade');
            $table->index(['tahap_qc', 'status_qc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('QC');
        Schema::dropIfExists('Detail_Gelaran_Kain');
        Schema::dropIfExists('Detail_Pemotongan_Warna');
        Schema::dropIfExists('Pemotongan_Seri');
        Schema::dropIfExists('Stok_Barang_Jadi');
        Schema::dropIfExists('Pesanan');
        Schema::dropIfExists('Detail_Roll_Bahan');
        Schema::dropIfExists('Detail_Bahan_Warna');
        Schema::dropIfExists('Penerimaan_Bahan');
        Schema::dropIfExists('Detail_Aksesoris_Pola');
        Schema::dropIfExists('Aksesoris');
        Schema::dropIfExists('Supplier');
        Schema::dropIfExists('User');
        Schema::dropIfExists('Spesifikasi_Ukuran');
        Schema::dropIfExists('Pola');
        Schema::dropIfExists('Kategori_Pola');
        Schema::dropIfExists('Sumber_Inisiasi');
    }
};

