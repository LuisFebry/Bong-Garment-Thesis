# Bong-Garment-Thesis

**WEB-BASED CUT, MAKE, TRIM (CMT) PRODUCTION & INVENTORY MANAGEMENT SYSTEM (BONG GARMENT)**  
*Undergraduate Thesis — Department of Information Systems, Faculty of Information Technology, Universitas Tarumanagara (UNTAR 2026)*  
**Author:** Luis Febry (Student ID / NIM: 825220088)  
**Official Repository Path:** `D:\Documents\GitHub\Bong-Garment-Thesis`  
**GitHub Repository:** `https://github.com/luisfebry/Bong-Garment-Thesis`

---

## 1. Executive Summary & Problem Context

**Bong Garment** is an established garment manufacturing business in Indonesia operating under the **Cut, Make, Trim (CMT)** production paradigm. The enterprise handles customized orders from retail brand partners (*Request Mitra*), in-house evergreen staple lines (*Produk Abadi*), and trend-driven production batches (*Tren Pasar*).

This production management system resolves critical operational inefficiencies:
1. **Fabric Roll Tracking & Waste Elimination:** Replaces aggregate weight estimates with individual roll-level tracking (`Detail_Roll_Bahan`), maintaining precise balances of initial and remaining lengths (`ukuran_awal` and `ukuran_sisa`) across standard units (`Kg`, `Yard`, `Meter`).
2. **Earliest Deadline First (EDF) Cutting Dispatch:** Prioritizes cutting queue allocations dynamically using Earliest Deadline First heuristics with audit logs for deadline changes (`deadline_awal`, `deadline_terbaru`, `tgl_perubahan_deadline`, and `alasan_perubahan_deadline`).
3. **11-Metric Anatomical Clothing Specification:** Eliminates pattern translation errors between pattern makers and external sewing partners through a standardized 11-dimension measurement matrix.
4. **Multi-Stage Quality Control & Closed-Loop Rework:** Enforces milestone inspections across production phases (`Prototipe`, `Potong`, `Jahit Akhir`) with status classification (`Lolos QC`, `Need Rework`, `Rework Selesai`, `Scrapped`).
5. **Perpetual Finished Goods Stock Accrual:** Automatically credits finished warehouse inventory (`Stok_Barang_Jadi`) as soon as pieces pass final sewing inspection (`Jahit Akhir` with `jumlah_lolos > 0`).

---

## 2. Dual-Execution Architecture

The system delivers a dual-execution runtime model, offering both enterprise containerized backend capabilities and instant zero-build browser testing:

```
+-----------------------------------------------------------------------------------+
|                           PRESENTATION LAYER (FRONTEND)                           |
|  - Standalone SPA (index.html + app.js) / Laravel Blade Views (resources/views)   |
|  - Tailwind CSS CDN + Lucide Icons + Google Fonts (Inter & JetBrains Mono)        |
|  - Dynamic Reactive DOM (Pub/Sub Observer, AJAX / Fetch API, Zero Build Step)     |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                        APPLICATION LAYER (BACKEND MVC)                            |
|  - Laravel 11.x MVC (PHP 8.3-FPM + Composer)                                      |
|  - 17 Eloquent ORM Models with ACID Transactions (DB::transaction & lockForUpdate)|
|  - RoleMiddleware: Enforcing 5 RBAC Roles & Read-Only Audit Mode for Owner        |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                       DATABASE LAYER (MYSQL 8.0+ INNODB)                          |
|  - MySQL 8.0+ Community Server (Engine: InnoDB, Charset: utf8mb4_unicode_ci)      |
|  - Canonical 17-Table Relational Schema with Foreign Keys & Row-Level Locking     |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                     INFRASTRUCTURE & CONTAINER DEPLOYMENT                         |
|  - Ubuntu Linux Containerized Runtime                                             |
|  - Nginx Web Server (HTTP/2, FastCGI Caching, Static Asset Compression)           |
|  - Docker Engine & Docker Compose Multi-Container Orchestration                   |
+-----------------------------------------------------------------------------------+
```

### Execution Mode Comparison

| Characteristic | Mode 1: Full-Stack Laravel 11 MVC | Mode 2: Standalone Browser SPA |
|---|---|---|
| **Entry Point** | `public/index.php` via Nginx / Docker | `index.html` opened directly in browser |
| **Backend Engine** | PHP 8.3 + Laravel 11 Framework | In-browser JavaScript runtime (`app.js`) |
| **Database** | MySQL 8.0+ Community Server (InnoDB) | `BongDB` 17-table relational `localStorage` store |
| **Views / Templating** | Blade Templates (`resources/views/bong/*.blade.php`) | Semantic HTML5 + Vanilla JS reactive renderers |
| **Asset Pipeline** | CDN-based (Zero build step, zero Node compile) | CDN-based (Zero build step, zero package manager) |
| **Deployment Target** | Docker Compose on Ubuntu Linux / VPS Server | Any web browser or GitHub Pages static hosting |

---

## 3. Complete 17-Table Relational Data Dictionary (MySQL 8.0+ InnoDB)

The authoritative database schema is defined in `database/sql/bong_garment_17_tables.sql` and mirrored in Laravel Migrations (`database/migrations/2026_11_02_000001_create_bong_garment_17_tables.php`) and `app.js`:

| # | Table Name | Primary Key | Foreign Keys & Constraints | Column Dictionary & English Description |
|---|---|---|---|---|
| 1 | `Sumber_Inisiasi` | `id_sumber` | None | `id_sumber` (PK, INT AUTO_INCREMENT), `nama_sumber` (VARCHAR(50)), `keterangan` (TEXT NULL). Master classifications: Partner Request, Perpetual Product, Market Trend Initiative. |
| 2 | `Kategori_Pola` | `id_kategori` | None | `id_kategori` (PK, INT AUTO_INCREMENT), `nama_kategori` (VARCHAR(50)), `deskripsi` (TEXT NULL). Master apparel categories: Formal Shirts, Polo Shirts, Windbreaker Jackets, Chino Pants. |
| 3 | `Pola` | `id_pola` | `id_kategori` -> `Kategori_Pola(id_kategori)` | `id_pola` (PK, INT AUTO_INCREMENT), `id_kategori` (INT), `kode_pola` (VARCHAR(20) UNIQUE), `nama_pola` (VARCHAR(100)). Master garment style and pattern header. |
| 4 | `Spesifikasi_Ukuran` | `id_spesifikasi` | `id_pola` -> `Pola(id_pola)` | `id_spesifikasi` (PK, INT AUTO_INCREMENT), `id_pola` (INT), `nama_ukuran` (VARCHAR(20)), **11 Decimal Sizing Columns (cm, DECIMAL(5,2)):** `diameter_leher` (neck diameter), `panjang_pundak` (shoulder length), `lingkar_lengan` (armhole circumference), `panjang_tangan` (sleeve length), `lingkar_dada` (chest circumference), `lingkar_pinggang` (waist circumference), `lingkar_selangkang` (crotch circumference), `lingkar_paha` (thigh circumference), `lebar_kaki` (leg opening width), `lebar_baju` (garment width), `panjang_baju_celana` (garment / pant length). |
| 5 | `User` | `id_user` | None | `id_user` (PK, INT AUTO_INCREMENT), `username` (VARCHAR(50) UNIQUE), `password` (VARCHAR(255)), `nama_user` (VARCHAR(100)), `role` (ENUM: `'Admin'`, `'Pemilik'`, `'Pemotong'`, `'QC'`, `'Gudang'`), `status_aktif` (BOOLEAN DEFAULT TRUE). Authenticated staff profiles and RBAC credentials. |
| 6 | `Supplier` | `id_supplier` | None | `id_supplier` (PK, INT AUTO_INCREMENT), `nama_supplier` (VARCHAR(100)), `kontak` (VARCHAR(50)), `alamat` (TEXT). Verified textile mills and accessory supplier directory. |
| 7 | `Aksesoris` | `id_aksesoris` | `id_supplier` -> `Supplier(id_supplier)` | `id_aksesoris` (PK, INT AUTO_INCREMENT), `id_supplier` (INT), `nama_aksesoris` (VARCHAR(100)), `ukuran` (VARCHAR(30) NULL), `stok` (INT DEFAULT 0), `satuan` (VARCHAR(20)). Trims and notions master inventory: buttons, zippers, sewing thread, elastic bands, woven labels. |
| 8 | `Detail_Aksesoris_Pola` | `(id_pola, id_aksesoris)` | `id_pola` -> `Pola(id_pola)`, `id_aksesoris` -> `Aksesoris(id_aksesoris)` | Composite PK: `id_pola` (INT), `id_aksesoris` (INT), `jumlah_digunakan` (INT NOT NULL). Bill of Materials (BOM) specifying accessory quantities consumed per finished garment piece. |
| 9 | `Penerimaan_Bahan` | `id_penerimaan` | `id_supplier` -> `Supplier(id_supplier)` | `id_penerimaan` (PK, INT AUTO_INCREMENT), `id_supplier` (INT), `no_nota` (VARCHAR(50) NOT NULL), `tgl_nota` (DATE NOT NULL), `nama_kain` (VARCHAR(100)), `lebar_gramasi` (VARCHAR(50) NULL), `no_kendaraan` (VARCHAR(20) NULL), `penerima` (VARCHAR(100) NULL). Inbound delivery receipts from textile suppliers. |
| 10 | `Detail_Bahan_Warna` | `id_detail_warna` | `id_penerimaan` -> `Penerimaan_Bahan(id_penerimaan)` | `id_detail_warna` (PK, INT AUTO_INCREMENT), `id_penerimaan` (INT), `nama_warna` (VARCHAR(50)), `kode_warna` (VARCHAR(30) NULL), `total_roll` (INT DEFAULT 0), `total_ukuran` (DECIMAL(8,2) DEFAULT 0.00), `satuan_ukur` (ENUM: `'Kg'`, `'Yard'`, `'Meter'`). Color-grouped fabric batch header within a delivery receipt. |
| 11 | `Detail_Roll_Bahan` | `id_roll` | `id_detail_warna` -> `Detail_Bahan_Warna(id_detail_warna)` | `id_roll` (PK, INT AUTO_INCREMENT), `id_detail_warna` (INT), `no_roll_ke` (INT), `ukuran_awal` (DECIMAL(6,2)), `ukuran_sisa` (DECIMAL(6,2)), `satuan` (ENUM: `'Kg'`, `'Yard'`, `'Meter'`), `rak_alokasi` (VARCHAR(20) DEFAULT 'A-01'), `status_roll` (ENUM: `'Tersedia'`, `'Dipotong'`, `'Habis'`). Individual physical roll entities tracked from arrival to cutting exhaustion. |
| 12 | `Pesanan` | `id_pesanan` | `id_pola`, `id_sumber`, `id_penerimaan`, `id_user_admin` | `id_pesanan` (PK, INT AUTO_INCREMENT), `id_pola` (INT), `id_sumber` (INT), `id_penerimaan` (INT NULL), `id_user_admin` (INT), `jenis_pesanan` (VARCHAR(50)), `nama_mitra` (VARCHAR(100) NULL), `tgl_order` (DATE NOT NULL), `deadline_awal` (DATE NULL), `deadline_terbaru` (DATE NULL), `tgl_perubahan_deadline` (DATETIME NULL), `alasan_perubahan_deadline` (VARCHAR(255) NULL), `status_pesanan` (VARCHAR(50) DEFAULT 'Antrean'). Production order lifecycle with EDF scheduling and deadline audit trail. |
| 13 | `Stok_Barang_Jadi` | `id_stok` | `id_pola`, `id_spesifikasi` | `id_stok` (PK, INT AUTO_INCREMENT), `id_pola` (INT), `id_spesifikasi` (INT), `nama_warna` (VARCHAR(50)), `jumlah_stok_pcs` (INT DEFAULT 0), `tgl_update_terakhir` (DATETIME). Perpetual inventory ledger storing verified, defect-free finished garments. |
| 14 | `Pemotongan_Seri` | `id_potong` | `id_pesanan`, `id_user_pemotong` | `id_potong` (PK, INT AUTO_INCREMENT), `id_pesanan` (INT), `id_user_pemotong` (INT), `kode_seri` (VARCHAR(50)), `jenis_variasi` (VARCHAR(50) NULL), `total_lembar_hasil` (INT), `tgl_potong` (DATETIME). Cutting batch execution header performed on the cutting table. |
| 15 | `Detail_Pemotongan_Warna` | `id_potong_warna` | `id_potong`, `id_detail_warna` | `id_potong_warna` (PK, INT AUTO_INCREMENT), `id_potong` (INT), `id_detail_warna` (INT), `total_lembar_direncanakan` (INT), `kain_terpakai` (DECIMAL(6,2)). Color-specific cutting allocation specifying target pieces and consumed fabric quantity. |
| 16 | `Detail_Gelaran_Kain` | `id_gelar` | `id_potong_warna`, `id_roll` | `id_gelar` (PK, INT AUTO_INCREMENT), `id_potong_warna` (INT), `id_roll` (INT), `panjang_gelar` (DECIMAL(4,2)), `jumlah_tumpuk` (INT), `total_lembar_gelar` (INT). Physical spreading technical log recording roll identifier, spread length (m), ply count, and cut yield. |
| 17 | `QC` | `id_qc` | `id_pesanan`, `id_user_qc` | `id_qc` (PK, INT AUTO_INCREMENT), `id_pesanan` (INT), `id_user_qc` (INT), `tahap_qc` (ENUM: `'Prototipe'`, `'Potong'`, `'Jahit Akhir'`), `tgl_pemeriksaan` (DATETIME), `jumlah_diperiksa` (INT), `jumlah_lolos` (INT), `jumlah_cacat` (INT DEFAULT 0), `jenis_cacat` (ENUM: `'Tidak Ada'`, `'Cacat Potong'`, `'Cacat Jahit'`, `'Cacat Bahan'`), `catatan_qc` (TEXT NULL), `status_qc` (ENUM: `'Lolos QC'`, `'Need Rework'`, `'Rework Selesai'`, `'Scrapped'`), `tgl_target_perbaikan` (DATE NULL), `tgl_selesai_perbaikan` (DATE NULL). Multi-stage quality control log with rework tracking and perpetual stock integration. |

---

## 4. Role-Based Access Control (RBAC) & Audit Mode

The system enforces strict permission boundaries across 5 user roles:

| Role | Accessible Views | Mutation Rights | Operational Responsibilities |
|---|---|---|---|
| **Admin** | Screens 1 to 7 + System Settings | **Full Access (CRUD)** | Manages orders, 11-metric sizing specs, cutting queue priorities, receiving notes, and database backups. |
| **Pemilik (Owner)** | Screen 1 (Dashboard), Screen 7 (Executive Reports) | **Read-Only (Audit Mode)** | Executive oversight and KPI governance. All mutating buttons trigger an informative Audit Mode modal. |
| **Pemotong (Cutter)** | Screen 1 (Dashboard), Screen 4 (Cutting & Spreading) | **Operational Cutting** | Claims cutting jobs based on Earliest Deadline First (EDF), logs roll spreads, and finalizes cut batches. |
| **QC (Inspector)** | Screen 1 (Dashboard), Screen 5 (Quality Control) | **Quality Assurance** | Records inspection counts, assigns rework disposition and completion, approves zero-defect finished orders. |
| **Gudang (Warehouse)** | Screen 1 (Dashboard), Screen 3 (Receiving), Screen 6 (Finished Stock & Trims) | **Inventory Control** | Registers fabric delivery receipts, assigns warehouse racks, manages accessory stock, and records stock adjustments. |

---

## 5. Operational Functional Screens

1. **Screen 1: Operational Dashboard (`#screen-dashboard`):** Real-time command center displaying 4 core executive KPIs (Active Orders, Available Rolls, QC Rework Queue, Daily Cutting Output), Earliest Deadline First (EDF) scheduler, and production trend charts.
2. **Screen 2: Order & Pattern Management (`#screen-pesanan`):** Supports three initiation channels (Mitra Contract, Perpetual Product, Market Initiative) with full input forms for the 11-metric anatomical clothing dimensions.
3. **Screen 3: Fabric Receiving & Roll Registration (`#screen-penerimaan`):** Vendor delivery logs supporting multiple units of measurement (`Kg`, `Yard`, `Meter`), dynamic color variants, and unit-level physical roll allocation to warehouse bins (`A-01` to `D-04`).
4. **Screen 4: Cutting Queue & Roll Spreading (`#screen-pemotongan`):** Live EDF priority dispatch, technical cutting table yield calculator (spread length, ply count, pieces per ply), and automatic roll status updating (`Dipotong` or `Habis`).
5. **Screen 5: Multi-Stage Quality Control (`#screen-qc`):** Defect logging across production phases (`Prototipe`, `Potong`, `Jahit Akhir`), defect classification, rework resolution workflows, and single-click zero-defect completion.
6. **Screen 6: Warehouse Stock & Trims Management (`#screen-gudang`):** Real-time perpetual finished goods inventory monitor (`Stok_Barang_Jadi`), master accessory register, quick stock increments (`+100`, `+500`), and physical inventory adjustment.
7. **Screen 7: Consolidated Executive Reports (`#screen-laporan`):** Consolidated analytical summaries: fabric consumption, cutting yield ratios, defect distribution breakdown, date-range filtering, and export to A4 Printable PDF and Excel UTF-8 BOM CSV.

---

## 6. Execution & Deployment Guide

### Option A: Standalone Browser Mode (Zero-Build Instant Evaluation)
1. Open `index.html` directly in any modern web browser (Google Chrome, Microsoft Edge, Mozilla Firefox, Safari).
2. The 17 relational tables initialize automatically into browser `localStorage` (`bong_garment_db_v2_17tables`).
3. Switch roles via the user profile dropdown in the top-right header to test role-based access control and Owner Audit Mode.

### Option B: Docker Containerized Production Stack (Ubuntu Linux, Nginx, PHP 8.3 Laravel, MySQL 8.0)
1. **Start Docker Containers:**
   ```bash
   docker compose up -d --build
   ```

2. **Execute Database Migrations & Canonical 17-Table Seeder:**
   ```bash
   docker compose exec app php artisan migrate:fresh --seed
   ```

3. **Access Application:**
   Open your browser at `http://localhost:8080` (or configured Nginx port).

4. **Default Demonstration Credentials:**
   * **Admin:** `admin@bonggarment.com` / `password`
   * **Pemilik:** `pemilik@bonggarment.com` / `password` (Audit Mode Active)
   * **Pemotong:** `pemotong@bonggarment.com` / `password`
   * **QC:** `qc@bonggarment.com` / `password`
   * **Gudang:** `gudang@bonggarment.com` / `password`

---

## 7. License & Academic Attribution

This prototype and codebase are developed as part of an undergraduate thesis project:
* **Author:** Luis Febry (Student ID / NIM: 825220088)
* **Program:** Information Systems (Sistem Informasi)
* **Faculty:** Faculty of Information Technology (Fakultas Teknologi Informasi)
* **Institution:** Universitas Tarumanagara (UNTAR), Jakarta, 2026.
* **License:** MIT License (refer to `LICENSE` for details).
