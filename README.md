# Bong-Garment-Thesis

**SISTEM INFORMASI MANAJEMEN PRODUKSI & INVENTORI CMT BERBASIS WEB (BONG GARMENT)**  
*Skripsi Program Studi Sistem Informasi — Fakultas Teknologi Informasi, Universitas Tarumanagara (UNTAR 2026)*  
**Penulis:** Luis Febry (NIM: 825220088)  
**Repositori Resmi:** `https://github.com/luisfebry/Bong-Garment-Thesis` *(Migrasi dari `Luis-Thesis-Prototype`)*

---

## 📌 1. Executive Summary & Context

**Bong Garment** adalah entitas manufaktur pakaian berskala menengah yang beroperasi dengan model bisnis **CMT (Cut, Make, Trim)**. Sistem ini dirancang untuk mengatasi inefisiensi pencatatan manual pada lantai produksi garmen, antara lain:
1. Ketidakakuratan pelacakan sisa gulungan bahan kain individual (*fabric roll waste*).
2. Hambatan penjadwalan antrean meja potong (*cutting bottleneck*) yang belum mengadopsi algoritma prioritas objektif.
3. Defisit pencatatan aksesoris dan spesifikasi ukuran multi-dimensi (*11-metric sizing*).
4. Penanganan cacat mutu (*quality defects*) dan pengerjaan ulang (*rework*) yang lambat tanpa audit trail.
5. Ketiadaan sinkronisasi real-time antara hasil akhir inspeksi dengan inventori perpetual barang jadi (*finished goods stock*).

Sistem mengadopsi arsitektur ganda (**Dual-Execution Architecture**):
* **Enterprise Backend:** PHP 8.3 + Laravel 11 MVC + MySQL 8.0+ (InnoDB Storage Engine) berstandar ACID dengan Docker Engine (Ubuntu 24.04/26.04 LTS & Nginx).
* **Client-Side Standalone SPA:** Pure HTML5 + Tailwind CSS + Lucide Icons + Vanilla JavaScript (`app.js`) dengan in-memory relational engine (`BongDB` 17 tabel) yang dapat dijalankan secara langsung tanpa server runtime untuk demonstrasi interaktif cepat.

---

## 🏗️ 2. Arsitektur Sistem & Spesifikasi Teknologi

```
+-----------------------------------------------------------------------------------+
|                           LAPISAN PRESENTASI (FRONTEND)                           |
|  - Standalone SPA (index.html + app.js) / Laravel Blade Views (resources/views)   |
|  - Tailwind CSS CDN + Lucide Icons + Google Fonts (Inter & JetBrains Mono)        |
|  - Dynamic Reactive DOM (Pub/Sub Observer, AJAX / Fetch API, Zero Build Step)     |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                        LAPISAN APLIKASI (BACKEND CONTROLLER)                      |
|  - Laravel 11.x MVC (PHP 8.3-FPM + Composer)                                      |
|  - 17 Eloquent ORM Models dengan Transaksi ACID (DB::transaction & lockForUpdate) |
|  - RoleMiddleware: Enforcing 5 RBAC Roles & Read-Only Audit Mode untuk Pemilik    |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                        LAPISAN BASIS DATA (DBMS MYSQL 8.0+)                       |
|  - MySQL 8.0+ Community Server (Engine: InnoDB, Charset: utf8mb4_unicode_ci)      |
|  - Skema Relasional 17 Tabel Canonical dengan Foreign Key Cascading & Indexing   |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                      INFRASTRUKTUR & DEPLOYMENT (CONTAINER)                       |
|  - Ubuntu 24.04 / 26.04 LTS Base Image                                           |
|  - Nginx Reverse Proxy (HTTP/2, Gzip, FastCGI Caching)                            |
|  - Docker Engine & Docker Compose Orchestration                                   |
+-----------------------------------------------------------------------------------+
```

---

## 🗄️ 3. Canonical 17-Table Relational Schema (MySQL 8.0+ InnoDB)

Seluruh struktur basis data disimpan dalam `database/sql/bong_garment_17_tables.sql` dan dieksekusi melalui Laravel Migrations (`database/migrations/2026_11_02_000001_create_bong_garment_17_tables.php`) serta in-memory state engine `BongDB` di `app.js`:

| # | Nama Tabel | Primary Key (PK) | Deskripsi & Atribut Kunci |
|---|---|---|---|
| 1 | `Sumber_Inisiasi` | `id_sumber` | Master klasifikasi inisiasi: Request Mitra, Produk Abadi, Inisiatif Tren Pasar. |
| 2 | `Kategori_Pola` | `id_kategori` | Kategori busana: Kemeja Formal, Kaos Polo, Jaket Windbreaker, Celana Chino, dsb. |
| 3 | `Pola` | `id_pola` | Master pola pakaian dengan `kode_pola` unik (FK: `id_kategori`). |
| 4 | `Spesifikasi_Ukuran` | `id_spesifikasi` | **11 Dimensi Metrik Garment (cm):** `panjang`, `lebar_dada`, `lebar_bahu`, `panjang_lengan`, `lebar_leher`, `lingkar_manset`, `lingkar_ketiak`, `lebar_bawah`, `lingkar_pinggang`, `lingkar_pinggul`, `lingkar_paha`. |
| 5 | `User` | `id_user` | Otentikasi & RBAC dengan 5 peran: `Admin`, `Pemilik`, `Pemotong`, `QC`, `Gudang`. |
| 6 | `Supplier` | `id_supplier` | Direktori pemasok kain dan aksesoris beserta nomor kontak dan alamat operasional. |
| 7 | `Aksesoris` | `id_aksesoris` | Master bahan pelengkap: kancing, ritsleting, benang jahit, karet rib, woven label, dsb. |
| 8 | `Detail_Aksesoris_Pola` | `(id_pola, id_aksesoris)` | Bill of Materials (BOM) pemakaian aksesoris per potong pakaian. |
| 9 | `Penerimaan_Bahan` | `id_penerimaan` | Surat jalan/nota bahan masuk, tanggal, total berat, dan konversi multi-satuan (Kg, Yard, Meter). |
| 10 | `Detail_Bahan_Warna` | `id_detail_warna` | Rincian kelompok warna kain per surat penerimaan (nama warna, kode warna hex). |
| 11 | `Detail_Roll_Bahan` | `id_roll` | **Inventori Fisik Roll Individual:** `no_roll_ke`, `total_roll`, `berat_kg`, `ukuran_awal`, `ukuran_sisa`, `satuan_roll`, `rak_alokasi`, `status_roll` (`Tersedia`, `Dipotong`, `Habis`, `Retur`). |
| 12 | `Pesanan` | `id_pesanan` | Siklus hidup pesanan, progress %, status (`ANTREAN`, `PEMOTONGAN`, `DALAM QC`, `SELESAI`), dan **Audit Trail EDF:** `deadline_awal`, `deadline_terbaru`, `alasan_revisi_deadline`. |
| 13 | `Stok_Barang_Jadi` | `id_stok` | **Inventori Perpetual Barang Jadi:** akumulasi stok pakaian jadi bertambah otomatis setiap inspeksi QC tahap Jahit Akhir dinyatakan lolos (`jumlah_lolos > 0`). |
| 14 | `Pemotongan_Seri` | `id_potong` | Header pekerjaan pemotongan seri di meja potong oleh operator pemotong. |
| 15 | `Detail_Pemotongan_Warna` | `id_potong_warna` | Alokasi variasi warna yang dipotong dan total lembar rencana. |
| 16 | `Detail_Gelaran_Kain` | `id_gelar` | Log teknis gelaran kain: roll fisik yang digunakan, panjang gelar (m), jumlah tumpukan (ply), dan yield lembar hasil gelar. |
| 17 | `QC` | `id_qc` | **Inspeksi Mutu Berjenjang:** `tahap_qc` (`Potong`, `Sablon/Bordir`, `Jahit Awal`, `Jahit Akhir`, `Finishing/Packing`), `jumlah_diperiksa`, `jumlah_lolos`, `jumlah_cacat`, disposisi cacat, target rework, dan PIC. |

---

## 👥 4. Role-Based Access Control (RBAC) & Mode Audit

Sistem membedakan hak akses dan perilaku antarmuka secara dinamis untuk 5 profil pengguna:

| Peran | Akses Layar Utama | Izin Mutasi Data | Keterangan & Karakteristik |
|---|---|---|---|
| **Admin** | Seluruh Layar (1 s/d 7) + Pengaturan | **Penuh (CRUD)** | Mengelola pesanan, master pola 11-metrik, alokasi meja potong, inspeksi, dan database reset. |
| **Pemilik** | Dashboard, Laporan Eksekutif | **Read-Only (Mode Audit)** | Pemantauan kinerja eksekutif. Tombol mutasi diblokir otomatis dengan peringatan mode audit. |
| **Pemotong** | Dashboard, Pemotongan Seri | **Terbatas (Operasional Meja Potong)** | Mengambil antrean prioritas EDF, input gelaran roll, dan finalisasi seri potong. |
| **QC** | Dashboard, Quality Control | **Terbatas (Inspeksi Mutu)** | Mencatat temuan cacat berjenjang, disposisi pengerjaan ulang (rework), dan persetujuan lolos QC. |
| **Gudang** | Dashboard, Penerimaan, Gudang & Aksesoris | **Terbatas (Logistik & Inventori)** | Registrasi surat jalan bahan, barcode/roll fisik, master aksesoris, dan penyesuaian stok jadi. |

---

## 🖥️ 5. Rincian 7 Layar Operasional

### 1. Layar 1: Operational Dashboard (`#screen-dashboard`)
* 4 KPI Eksekutif Utama: Pesanan Aktif, Roll Tersedia di Rak, Antrean Rework QC, Total Output Potong Hari Ini.
* Real-time Earliest Deadline First (EDF) Queue Card dengan indikator revisi deadline.
* Tabel distribusi pesanan aktif dengan filter status (`ALL`, `ANTREAN`, `PEMOTONGAN`, `DALAM QC`, `SELESAI`).

### 2. Layar 2: Kelola Pesanan & Pola (`#screen-pesanan`)
* Mendukung 3 sumber inisiasi (Request Mitra, Produk Abadi, Tren Pasar) sesuai *Gambar 2-25*.
* Input spesifikasi pola pakaian baru dengan **11 Dimensi Metrik Garment** (cm).
* Validasi pemesanan dan penetapan target deadline awal.

### 3. Layar 3: Penerimaan & Roll Bahan (`#screen-penerimaan`)
* Pencatatan surat jalan/nota penerimaan kain dari supplier rekanan sesuai *Gambar 2-26*.
* Dukungan multi-satuan kain (`Kg`, `Yard`, `Meter`) dan pengelompokan variasi warna.
* Registrasi roll individual fisik dengan penetapan rak alokasi gudang (`A-01` s/d `D-04`).

### 4. Layar 4: Pemotongan Seri & Gelaran (`#screen-pemotongan`)
* **Earliest Deadline First (EDF) Queue:** Urutan pemotongan otomatis diprioritaskan berdasarkan tanggal target terdekat (`COALESCE(deadline_terbaru, deadline_awal)`).
* Kalkulator teknis meja potong: input panjang gelar (m), jumlah tumpuk (ply), dan rasio pcs per ply.
* Pengurangan otomatis sisa roll (`ukuran_sisa`) dan transisi status roll menjadi `Dipotong` atau `Habis`.

### 5. Layar 5: Quality Control & Kerusakan (`#screen-qc`)
* Inspeksi mutu 5 tahap: `Potong`, `Sablon/Bordir`, `Jahit Awal`, `Jahit Akhir`, `Finishing/Packing`.
* Kategorisasi cacat mutu (Cacat Kain, Cacat Potong, Cacat Jahit, Sablon/Bordir, Aksesoris).
* Resolusi rework interaktif dan tombol approval **"Lolos QC (Tanpa Cacat)"** yang secara otomatis mengupdate `Stok_Barang_Jadi`.

### 6. Layar 6: Gudang & Inventori Stok Barang Jadi (`#screen-gudang`)
* **Perpetual Finished Goods Stock:** Pemantauan real-time inventori pakaian jadi hasil akumulasi lolos QC.
* **Manajemen Aksesoris CMT:** Registrasi kancing, zipper, benang, dan penambahan cepat stok fisik (`+100`, `+500`).
* Modal penyesuaian stok fisik barang jadi (*stock opname*).

### 7. Layar 7: Laporan Eksekutif (`#screen-laporan`)
* Konsolidasi analitik 17 tabel: Total Konsumsi Bahan, Yield Output Potong, Reject Rate Mutu %, dan On-Time Delivery %.
* Filter rentang tanggal fleksibel dan ekspor multi-format (Cetak PDF berstandar A4 & Ekspor CSV Excel UTF-8 BOM).

---

## 🚀 6. Panduan Menjalankan Sistem

### Opsi A: Standalone Browser Client (Instan Tanpa Instalasi)
Cocok untuk demonstrasi langsung di browser tanpa konfigurasi server:
1. Buka file `index.html` langsung menggunakan browser modern apa saja (Google Chrome, Microsoft Edge, Firefox).
2. Seluruh 17 tabel akan diinisialisasi otomatis ke dalam `localStorage` (`bong_garment_db_v2_17tables`).
3. Anda dapat berpindah peran via menu profil di pojok kanan atas untuk menguji fitur RBAC dan Mode Audit.

### Opsi B: Docker Environment (Ubuntu Linux, Nginx, PHP 8.3 Laravel, MySQL 8.0)
Untuk menjalankan implementasi full-stack berstandar produksi:

1. **Jalankan Container Docker:**
   ```bash
   docker compose up -d --build
   ```

2. **Jalankan Migrasi & Seeder Database (17 Tabel):**
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

3. **Akses Aplikasi:**
   Buka browser pada URL: `http://localhost:8080`

4. **Kredensial Pengujian Akun Demo:**
   * **Admin:** `admin@bonggarment.com` / `password`
   * **Pemilik:** `pemilik@bonggarment.com` / `password` (Mode Audit Aktif)
   * **Pemotong:** `pemotong@bonggarment.com` / `password`
   * **QC:** `qc@bonggarment.com` / `password`
   * **Gudang:** `gudang@bonggarment.com` / `password`

---

## 📜 7. Lisensi & Hak Cipta

Proyek ini dikembangkan sebagai karya tugas akhir (skripsi) sarjana:
* **Penulis:** Luis Febry (NIM: 825220088)
* **Program Studi:** Sistem Informasi
* **Institusi:** Universitas Tarumanagara (UNTAR), Jakarta, 2026.
* **Lisensi:** MIT License (Lihat file `LICENSE` untuk rincian ketentuan penggunaan).
