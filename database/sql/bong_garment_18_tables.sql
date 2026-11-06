-- ========================================================
-- SISTEM INFORMASI MANAJEMEN PRODUKSI & INVENTORI CMT
-- BONG GARMENT - SKEMA BASIS DATA 18 TABEL
-- DBMS: MySQL 8.0+ | Engine: InnoDB
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Riwayat_Stok_Barang_Jadi;
DROP TABLE IF EXISTS QC;
DROP TABLE IF EXISTS Detail_Gelaran_Kain;
DROP TABLE IF EXISTS Detail_Pemotongan_Warna;
DROP TABLE IF EXISTS Pemotongan_Seri;
DROP TABLE IF EXISTS Stok_Barang_Jadi;
DROP TABLE IF EXISTS Pesanan;
DROP TABLE IF EXISTS Detail_Roll_Bahan;
DROP TABLE IF EXISTS Detail_Bahan_Warna;
DROP TABLE IF EXISTS Penerimaan_Bahan;
DROP TABLE IF EXISTS Detail_Aksesoris_Pola;
DROP TABLE IF EXISTS Aksesoris;
DROP TABLE IF EXISTS Supplier;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Spesifikasi_Ukuran;
DROP TABLE IF EXISTS Pola;
DROP TABLE IF EXISTS Kategori_Pola;
DROP TABLE IF EXISTS Sumber_Inisiasi;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Sumber Inisiasi
CREATE TABLE Sumber_Inisiasi (
    id_sumber INT NOT NULL AUTO_INCREMENT,
    nama_sumber VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_sumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Kategori Pola
CREATE TABLE Kategori_Pola (
    id_kategori INT NOT NULL AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Pola
CREATE TABLE Pola (
    id_pola INT NOT NULL AUTO_INCREMENT,
    id_kategori INT NOT NULL,
    kode_pola VARCHAR(20) NOT NULL UNIQUE,
    nama_pola VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_pola),
    FOREIGN KEY (id_kategori) REFERENCES Kategori_Pola(id_kategori) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Spesifikasi Ukuran (Detail komentar kegunaan per ukuran)
CREATE TABLE Spesifikasi_Ukuran (
    id_spesifikasi INT NOT NULL AUTO_INCREMENT,
    id_pola INT NOT NULL,
    nama_ukuran VARCHAR(20) NOT NULL COMMENT 'S, M, L, XL',
    diameter_leher DECIMAL(5,2) NULL COMMENT 'Lebar kerah atasan',
    panjang_pundak DECIMAL(5,2) NULL COMMENT 'Lebar bahu atasan',
    lingkar_lengan DECIMAL(5,2) NULL COMMENT 'Kerung lengan (armhole)',
    panjang_tangan DECIMAL(5,2) NULL COMMENT 'Panjang lengan baju',
    lingkar_dada DECIMAL(5,2) NULL COMMENT 'LD untuk baju/gamis',
    lingkar_pinggang DECIMAL(5,2) NULL COMMENT 'LP untuk baju/celana/rok',
    lingkar_selangkang DECIMAL(5,2) NULL COMMENT 'Tinggi duduk / pesak celana',
    lingkar_paha DECIMAL(5,2) NULL COMMENT 'Lebar paha celana',
    lebar_kaki DECIMAL(5,2) NULL COMMENT 'Bukaan bawah celana/rok',
    lebar_baju DECIMAL(5,2) NULL COMMENT 'Lebar badan pakaian horizontal',
    panjang_baju_celana DECIMAL(5,2) NULL COMMENT 'Panjang total vertikal pakaian',
    PRIMARY KEY (id_spesifikasi),
    FOREIGN KEY (id_pola) REFERENCES Pola(id_pola) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel User (Role RBAC diperbaiki)
CREATE TABLE User (
    id_user INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_user VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang') NOT NULL,
    PRIMARY KEY (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Supplier
CREATE TABLE Supplier (
    id_supplier INT NOT NULL AUTO_INCREMENT,
    nama_supplier VARCHAR(100) NOT NULL,
    kontak VARCHAR(50) NULL,
    alamat TEXT NULL,
    PRIMARY KEY (id_supplier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel Aksesoris (Entitas Fisik Baru)
CREATE TABLE Aksesoris (
    id_aksesoris INT NOT NULL AUTO_INCREMENT,
    id_supplier INT NOT NULL,
    nama_aksesoris VARCHAR(100) NOT NULL,
    ukuran VARCHAR(30) NULL,
    stok INT NOT NULL DEFAULT 0,
    satuan VARCHAR(20) NOT NULL COMMENT 'pcs, lusin, gross',
    PRIMARY KEY (id_aksesoris),
    FOREIGN KEY (id_supplier) REFERENCES Supplier(id_supplier) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel Detail Aksesoris Pola (Jembatan Pola ke Aksesoris)
CREATE TABLE Detail_Aksesoris_Pola (
    id_pola INT NOT NULL,
    id_aksesoris INT NOT NULL,
    jumlah_digunakan INT NOT NULL COMMENT 'Kebutuhan aksesoris per 1 pakaian',
    PRIMARY KEY (id_pola, id_aksesoris),
    FOREIGN KEY (id_pola) REFERENCES Pola(id_pola) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_aksesoris) REFERENCES Aksesoris(id_aksesoris) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabel Penerimaan Bahan
CREATE TABLE Penerimaan_Bahan (
    id_penerimaan INT NOT NULL AUTO_INCREMENT,
    id_supplier INT NOT NULL,
    no_nota VARCHAR(50) NOT NULL,
    tgl_nota DATE NOT NULL,
    nama_kain VARCHAR(100) NOT NULL,
    lebar_gramasi VARCHAR(50) NULL,
    no_kendaraan VARCHAR(20) NULL,
    penerima VARCHAR(100) NULL,
    PRIMARY KEY (id_penerimaan),
    FOREIGN KEY (id_supplier) REFERENCES Supplier(id_supplier) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabel Detail Bahan Warna (Ditambah satuan ukur global)
CREATE TABLE Detail_Bahan_Warna (
    id_detail_warna INT NOT NULL AUTO_INCREMENT,
    id_penerimaan INT NOT NULL,
    nama_warna VARCHAR(50) NOT NULL,
    kode_warna VARCHAR(30) NULL,
    total_roll INT NOT NULL DEFAULT 0,
    total_ukuran DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    satuan_ukur ENUM('Kg', 'Yard', 'Meter') NOT NULL DEFAULT 'Kg',
    PRIMARY KEY (id_detail_warna),
    FOREIGN KEY (id_penerimaan) REFERENCES Penerimaan_Bahan(id_penerimaan) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tabel Detail Roll Bahan (Ditambah ukuran awal, sisa, dan satuan)
CREATE TABLE Detail_Roll_Bahan (
    id_roll INT NOT NULL AUTO_INCREMENT,
    id_detail_warna INT NOT NULL,
    no_roll_ke INT NOT NULL,
    ukuran_awal DECIMAL(6,2) NOT NULL,
    ukuran_sisa DECIMAL(6,2) NOT NULL,
    satuan ENUM('Kg', 'Yard', 'Meter') NOT NULL,
    status_roll ENUM('Tersedia', 'Dipotong', 'Habis') NOT NULL DEFAULT 'Tersedia',
    PRIMARY KEY (id_roll),
    FOREIGN KEY (id_detail_warna) REFERENCES Detail_Bahan_Warna(id_detail_warna) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Tabel Pesanan (Ditambah fitur histori Deadline & Nullable untuk Non-Mitra)
CREATE TABLE Pesanan (
    id_pesanan INT NOT NULL AUTO_INCREMENT,
    id_pola INT NOT NULL,
    id_sumber INT NOT NULL,
    id_penerimaan INT NULL,
    id_user_admin INT NOT NULL,
    jenis_pesanan VARCHAR(50) NOT NULL,
    nama_mitra VARCHAR(100) NULL,
    tgl_order DATE NOT NULL,
    deadline_awal DATE NULL COMMENT 'Target asli awal',
    deadline_terbaru DATE NULL COMMENT 'Target revisi terbaru untuk EDF',
    tgl_perubahan_deadline DATETIME NULL COMMENT 'Kapan deadline terakhir diubah',
    alasan_perubahan_deadline VARCHAR(255) NULL,
    status_pesanan VARCHAR(50) NOT NULL DEFAULT 'Antrean',
    PRIMARY KEY (id_pesanan),
    FOREIGN KEY (id_pola) REFERENCES Pola(id_pola) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_sumber) REFERENCES Sumber_Inisiasi(id_sumber) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_penerimaan) REFERENCES Penerimaan_Bahan(id_penerimaan) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_user_admin) REFERENCES User(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Tabel Stok Barang Jadi (Pemisahan Stok vs Pesanan untuk Produk Abadi)
CREATE TABLE Stok_Barang_Jadi (
    id_stok INT NOT NULL AUTO_INCREMENT,
    id_pola INT NOT NULL,
    id_spesifikasi INT NOT NULL,
    nama_warna VARCHAR(50) NOT NULL,
    jumlah_stok_pcs INT NOT NULL DEFAULT 0,
    tgl_update_terakhir DATETIME NOT NULL,
    PRIMARY KEY (id_stok),
    FOREIGN KEY (id_pola) REFERENCES Pola(id_pola) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_spesifikasi) REFERENCES Spesifikasi_Ukuran(id_spesifikasi) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Tabel Pemotongan Seri
CREATE TABLE Pemotongan_Seri (
    id_potong INT NOT NULL AUTO_INCREMENT,
    id_pesanan INT NOT NULL,
    id_user_pemotong INT NOT NULL,
    kode_seri VARCHAR(50) NOT NULL,
    jenis_variasi VARCHAR(50) NULL,
    total_lembar_hasil INT NOT NULL DEFAULT 0,
    tgl_potong DATETIME NOT NULL,
    PRIMARY KEY (id_potong),
    FOREIGN KEY (id_pesanan) REFERENCES Pesanan(id_pesanan) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_user_pemotong) REFERENCES User(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Tabel Detail Pemotongan Warna
CREATE TABLE Detail_Pemotongan_Warna (
    id_potong_warna INT NOT NULL AUTO_INCREMENT,
    id_potong INT NOT NULL,
    id_detail_warna INT NOT NULL,
    total_lembar_direncanakan INT NOT NULL,
    kain_terpakai DECIMAL(6,2) NULL COMMENT 'Kuantitas material yang dihabiskan',
    PRIMARY KEY (id_potong_warna),
    FOREIGN KEY (id_potong) REFERENCES Pemotongan_Seri(id_potong) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_detail_warna) REFERENCES Detail_Bahan_Warna(id_detail_warna) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Tabel Detail Gelaran Kain
CREATE TABLE Detail_Gelaran_Kain (
    id_gelar INT NOT NULL AUTO_INCREMENT,
    id_potong_warna INT NOT NULL,
    id_roll INT NOT NULL,
    panjang_gelar DECIMAL(5,2) NOT NULL,
    jumlah_tumpuk INT NOT NULL,
    total_lembar_gelar INT NOT NULL,
    PRIMARY KEY (id_gelar),
    FOREIGN KEY (id_potong_warna) REFERENCES Detail_Pemotongan_Warna(id_potong_warna) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_roll) REFERENCES Detail_Roll_Bahan(id_roll) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Tabel QC (Merekam Lolos QC maupun Cacat)
CREATE TABLE QC (
    id_qc INT NOT NULL AUTO_INCREMENT,
    id_pesanan INT NOT NULL,
    id_user_qc INT NOT NULL,
    tahap_qc ENUM('Prototipe', 'Potong', 'Jahit Akhir') NOT NULL,
    tgl_pemeriksaan DATETIME NOT NULL,
    jumlah_diperiksa INT NOT NULL,
    jumlah_lolos INT NOT NULL DEFAULT 0,
    jumlah_cacat INT NOT NULL DEFAULT 0,
    jenis_cacat ENUM('Tidak Ada', 'Cacat Potong', 'Cacat Jahit', 'Cacat Bahan') NOT NULL DEFAULT 'Tidak Ada',
    catatan_qc TEXT NULL,
    status_qc ENUM('Lolos QC', 'Need Rework', 'Rework Selesai', 'Scrapped') NOT NULL,
    tgl_target_perbaikan DATE NULL COMMENT 'Tenggat perbaikan jika cacat',
    tgl_selesai_perbaikan DATETIME NULL COMMENT 'Kapan rework selesai',
    PRIMARY KEY (id_qc),
    FOREIGN KEY (id_pesanan) REFERENCES Pesanan(id_pesanan) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_user_qc) REFERENCES User(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Tabel Riwayat Transaksi Stok Barang Jadi (Inbound QC / Outbound Mitra & Penjualan)
CREATE TABLE IF NOT EXISTS Riwayat_Stok_Barang_Jadi (
    id_riwayat_stok INT NOT NULL AUTO_INCREMENT,
    id_stok INT NULL,
    id_pola INT NOT NULL,
    id_spesifikasi INT NOT NULL,
    nama_warna VARCHAR(50) NOT NULL,
    jenis_transaksi ENUM('MASUK', 'KELUAR') NOT NULL DEFAULT 'KELUAR',
    jumlah_pcs INT NOT NULL,
    stok_sebelum INT NOT NULL DEFAULT 0,
    stok_sesudah INT NOT NULL DEFAULT 0,
    kategori_alasan VARCHAR(100) NOT NULL,
    nama_mitra VARCHAR(100) NULL COMMENT 'Nama Mitra jika diambil oleh Mitra',
    keterangan VARCHAR(255) NULL COMMENT 'Nomor surat jalan / catatan tambahan',
    id_user INT NULL,
    tgl_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_riwayat_stok)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Indexes
CREATE INDEX idx_pesanan_deadline ON Pesanan(deadline_terbaru, deadline_awal);
CREATE INDEX idx_pesanan_status ON Pesanan(status_pesanan);
CREATE INDEX idx_roll_status ON Detail_Roll_Bahan(status_roll);
CREATE INDEX idx_qc_tahap ON QC(tahap_qc, status_qc);
CREATE INDEX idx_stok_pola_spesifikasi ON Stok_Barang_Jadi(id_pola, id_spesifikasi);

