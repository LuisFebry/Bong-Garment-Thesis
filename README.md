# Bong Garment Thesis Prototype

**Pembangunan Manajemen Produksi dan Inventori Garment Berbasis Web (Bong Garment)**  
*Skripsi Program Studi Sistem Informasi - Universitas Tarumanagara (2026)*  
**Disusun oleh:** Luis Febry (NIM: 825220088)

---

## 📌 Executive Overview

**Bong Garment Management System (Static HTML Prototype)** is a 100% client-side, static HTML/CSS/JavaScript prototype matching the visual layout, workflows, 18-table relational database schema, and 5-role access control of the full Laravel web application (`Bong-Garment-Thesis-main`), running purely in the browser via `localStorage` without any backend server or build step.

---

## 🚀 Key Features & Screen Breakdown (8 Screens + Login)

### 0. Autentikasi Pengguna (`#login-overlay`)
* Matches `bong/login.blade.php` with username/password authentication and quick-login demo chips for all 5 operational roles.
* **Demo Accounts:**
  * **Admin:** `admin` / `admin123` (Full Access + Role View Simulator)
  * **Pemilik:** `pemilik` / `pemilik123` (Mode Audit Read-Only: Dashboard, Gudang, Laporan Eksekutif)
  * **Pemotong:** `pemotong` / `potong123` (Dashboard & Pemotongan Seri)
  * **QC:** `qc` / `qc123` (Dashboard & Quality Control)
  * **Gudang:** `gudang` / `gudang123` (Dashboard, Penerimaan Bahan, Gudang & Aksesoris)

### 1. Dashboard Produksi CMT (`#screen-dashboard`)
* 4 KPI summary cards: *Pesanan Berjalan*, *Roll Kain Siap*, *Stok Barang Jadi*, and *Perbaikan QC*.
* Earliest Deadline First (EDF) priority cutting queue with deadline revision indicators.
* Recent QC inspection feed.

### 2. Kelola Pesanan & Master Pola (`#screen-pesanan`)
* Order initiation with EDF scheduling, source tracking (`Sumber_Inisiasi`), material receipt linking (`id_penerimaan`), and inline **11-metric size specification** & accessory mapping (`Detail_Aksesoris_Pola`) when registering a new Master Pola.
* Full order management: Edit, Revise Deadline (audited), Delete (with FK guard), plus Master Pola Catalog & Kategori/Sumber modals.

### 3. Penerimaan Bahan & Roll (`#screen-penerimaan`)
* Multi-color group (`Detail_Bahan_Warna`) and multi-roll physical registration (`Detail_Roll_Bahan` in `Kg` / `Yard` / `Meter`).
* Quick `+1 Roll` / `+5 Roll Sekaligus` draft builders, inline Supplier CRUD modal, and individual edit/add-roll/delete actions.

### 4. Pemotongan Seri & Gelaran (`#screen-pemotongan`)
* EDF priority queue with one-click **"Pilih Potong"** auto-population.
* Dynamic `Detail_Gelaran_Kain` rows (`panjang_gelar`, `jumlah_tumpuk`, `total_lembar_gelar`) with automatic roll balance deduction and status sync (`Dipotong` / `Habis`), plus Edit & Delete modals with optional roll stock restoration.

### 5. Quality Control & Kerusakan (`#screen-qc`)
* 3-stage inspection (`Prototipe`, `Potong`, `Jahit Akhir`) with quantity validation (`lolos + cacat === diperiksa`) and automatic PIC rework suggestions.
* Automatic integration to `Stok_Barang_Jadi` & `Riwayat_Stok_Barang_Jadi` when `Produk Abadi` passes `Jahit Akhir`.
* Interactive `Selesaikan Rework`, Edit, and Delete (with auto-stock reversal).

### 6. Gudang & Stok Barang Jadi (`#screen-gudang`)
* Global low-stock alert banner with configurable thresholds (`min_stok_jadi`, `min_stok_aksesoris`).
* Perpetual finished goods stock (`Stok_Barang_Jadi`), CMT accessories inventory (`Aksesoris`) with quick `-500 / -100 / +100 / +500` stock mutations, pattern accessory mapping (`Detail_Aksesoris_Pola`), and immutable stock ledger (`Riwayat_Stok_Barang_Jadi`) with reverse-delete support.

### 7. Laporan Eksekutif & Dokumen Rekapan Resmi (`#screen-laporan`)
* 5 formal report modes: `KONSOLIDASI`, `STOK`, `BAHAN_MASUK`, `MUTASI_KELUAR`, and `QC`.
* Editable company letterhead (*Ubah Kop Surat & Alamat* for Admin), formal 2-column consolidation table, signature authorization block, and one-click **Word (`.doc`)**, **CSV (`.csv`)**, and **Print / PDF** export.

### 8. Pengaturan & Manajemen Pengguna (`#screen-pengaturan`)
* User account & RBAC management (`User`), 9-counter database summary, Clean Production Reset (with optional master wipe), Sample Dataset reload, and reference master management (`Kategori_Pola`, `Sumber_Inisiasi`, `Supplier`).

---

## 🛠️ Architecture & Tech Stack
* **Static HTML5 & Tailwind CSS (CDN):** Zero build step; works directly via `file://` or any static HTTP server / GitHub Pages.
* **State & Domain Engine (`db.js`):** `BongDB` (18 canonical tables persisted in `localStorage`), `BongAuth` (5-role RBAC + Admin View Simulator + Pemilik Read-Only Audit Mode), `BongQ` (relational joins), and `BongService` (business logic & report builder).
* **UI Controller (`app.js`):** Hash router, `dd/mm/yyyy` & `dd/mm/yyyy hh:mm` date input enhancer, modal/toast management, screen renderers, and Word/CSV/PDF exporters.

---

## 🗄️ Database Schema (`BongDB` - 18 Canonical Tables)

| # | Table Name | Key Role |
|---|---|---|
| 1 | `Sumber_Inisiasi` | Order origin types (`Request Mitra`, `Produk Abadi`, `Inisiatif Tren Pasar`) |
| 2 | `Kategori_Pola` | Garment pattern classifications |
| 3 | `Pola` | Master garment pattern catalog |
| 4 | `Spesifikasi_Ukuran` | 11-metric size measurements per pattern |
| 5 | `User` | Staff accounts & 5 RBAC roles (`Admin`, `Pemilik`, `Pemotong`, `QC`, `Gudang`) |
| 6 | `Supplier` | Fabric & accessory supplier directory |
| 7 | `Aksesoris` | Trims & accessories stock inventory |
| 8 | `Detail_Aksesoris_Pola` | Accessory BOM per garment piece & pattern |
| 9 | `Penerimaan_Bahan` | Incoming fabric delivery receipts |
| 10 | `Detail_Bahan_Warna` | Color batch groups per receipt |
| 11 | `Detail_Roll_Bahan` | Individual physical fabric rolls (`id_roll`, `ukuran_awal`, `ukuran_sisa`, `status_roll`) |
| 12 | `Pesanan` | Production orders with EDF initial & revised deadlines |
| 13 | `Stok_Barang_Jadi` | Perpetual finished goods warehouse inventory |
| 14 | `Pemotongan_Seri` | Cutting batch sessions |
| 15 | `Detail_Pemotongan_Warna` | Planned sheets & fabric consumption per color |
| 16 | `Detail_Gelaran_Kain` | Spreading logs (`panjang_gelar`, `jumlah_tumpuk`, `total_lembar_gelar`) per roll |
| 17 | `QC` | Multi-stage quality control inspections & rework tracking |
| 18 | `Riwayat_Stok_Barang_Jadi` | Immutable finished goods inbound/outbound ledger |

---

## ⚡ Local Execution
1. Open `index.html` directly in any modern browser, or serve locally:
   ```bash
   python -m http.server 8000
   ```
2. Visit `http://localhost:8000` (defaults to logged in as `Administrator` with full access; click **Keluar** or use **Mode Tampilan** in the top bar to switch roles).
