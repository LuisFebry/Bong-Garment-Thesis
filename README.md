# Bong-Garment-Thesis-Prototype

**Pembangunan Manajemen Produksi dan Inventori Garment Berbasis Web (Bong Garment)**  
*Skripsi Program Studi Sistem Informasi - Universitas Tarumanagara (2026)*  
**Disusun oleh:** Luis Febry (NIM: 825220088)

---

## 📌 Executive Overview

**Bong Garment Management System** is a lightweight, responsive Web-based Production and Inventory Management prototype tailored for garment Cut, Make, and Trim (CMT) operations. It solves core shop-floor challenges such as untracked fabric roll waste, cutting schedule bottlenecks, unclassified quality defects, and fragmented operational reporting.

The system is built as a single-page application (SPA) driven by a reactive, pub/sub in-memory relational state engine (`BongDB`) spanning **14 interconnected tables**, ensuring real-time state synchronization across all operational views without page reloads.

---

## 🚀 Key Features & Screen Breakdown

### 1. Layar 1: Operational Dashboard (`#screen-dashboard`)
* Real-time production status cards (Active Orders, Rolls in Stock, Pending Reworks, Completed Orders).
* Earliest Deadline First (EDF) priority order feed.
* Quick-action shortcuts to allocate raw material rolls directly to cutting tables.

### 2. Layar 2: Order Management & Scheduling (`#screen-pesanan`)
* Master order entry and target deadline tracking.
* Dynamic status pipeline: `DRAFT` → `ANTREAN` → `PEMOTONGAN` → `JAHIT` → `QC` → `SELESAI`.
* Material requirement forecasting based on garment style specifications.

### 3. Layar 3: Material Intake & Fabric Roll Tracking (`#screen-bahan`)
* Unique tracking per individual physical roll via `id_roll`.
* Real-time stock status management (`Tersedia`, `Dipotong`, `Habis`, `Retur`).
* Physical warehouse location assignment (e.g., `Rak A-02`, `Meja Potong 01`).

### 4. Layar 4: Cutting Queue & Spreading Layer Yield (`#screen-pemotongan`)
* **EDF Cutting Queue:** Automated order priority sorting based on closest target completion dates.
* **Master Series & Operator Assignment:** Auto-suggests series codes and filters dedicated cutting operators.
* **Spreading & Yield Math:** Dynamic table recording roll spreading layers (`panjang_gelar`, `jumlah_tumpuk`, `lembar_gelar`) and comparing actual output vs. planned target yield.
* **Post-Cut Status Sync:** Toggles roll status between `Dipotong` and `Habis` with instant table updates.

### 5. Layar 5: Quality Control & Defect Rework (`#screen-qc`)
* **Multi-Category Defect Logging:** Categorizes defects into *Cutting*, *Sewing*, or *Fabric Material* errors with automatic unit toggling (`Pcs` / `Roll`).
* **Quick-Fill Rework Templates:** Preset notes (`Shift Layer`, `Wrinkled Collar`, `Shading BS`), target completion dates, and assigned Rework PICs.
* **Interactive Rework Lifecycle:** Live **"✓ Selesaikan"** resolution toggles that update inspection records to `Rework Selesai` and decrement global dashboard pending counters.
* **Direct QC Pass:** One-click approval (**"Lolos QC (Tanpa Cacat)"**) transitioning order status straight to `SELESAI (100%)`.

### 6. Layar 6: Executive Analytics & Reports (`#screen-laporan`)
* **14-Table Consolidation:** Displays 4 core executive KPIs: Total Fabric Rolls Consumed, Total Output Yield (Pcs), QC Reject Rate (%), and Order On-Time Delivery Rate.
* **Defect Source Breakdown:** Visual and tabular classification of defect origin (Cutting Station vs. CMT Subcontractor vs. Raw Material Supplier).
* **Multi-Format Exporting:**
  * **Print / PDF:** Optimized `@media print` rules hiding navigation bars and adjusting margins for clean document output.
  * **Excel CSV:** One-click UTF-8 BOM CSV export (`laporan_konsolidasi_YYYY-MM-DD.csv`) compatible with Microsoft Excel.

---

## 🛠️ Architecture & Tech Stack

### System Layers
* **Presentation Layer:** Layar 1 through Layar 6 Single Page Application (SPA) views.
* **Application Controller:** `app.js` (Event handlers & `renderAll()` state sync).
* **State Engine:** `BongDB` (14 Relational Tables with Pub/Sub Observer pattern and `localStorage` persistence).

### Core Technologies
* **Frontend:** Vanilla HTML5, Custom CSS3 / Utility Classes, Native ES6+ JavaScript.
* **State Management:** Custom Pub/Sub Event System (`BongDB.subscribe()` & `BongDB.notify()`).
* **Storage:** In-Memory Relational Engine with `localStorage` persistence.
* **Dependencies:** Zero external framework dependencies (No React/Vue/jQuery required).

---

## 🗄️ Database Schema (`BongDB` - 14 Tables)

| # | Table Name | Key Role |
|---|---|---|
| 1 | `Pelanggan` | Client directory & contact details |
| 2 | `Pesanan` | Master orders, deadlines, and overall lifecycle status |
| 3 | `Detail_Pesanan` | Itemized garment styles, sizes, and order quantities |
| 4 | `Supplier` | Fabric and trim raw material vendor directory |
| 5 | `Bahan_Baku` | Raw material catalog and unit definitions |
| 6 | `Detail_Roll_Bahan` | Individual roll tracking (`id_roll`), yards/meters, location |
| 7 | `Operator` | Cutting, sewing, and QC operator registry |
| 8 | `Pemotongan` | Master cutting job sessions and assigned operators |
| 9 | `Gelaran_Roll` | Spreading logs per roll, layer counts, and calculated yields |
| 10 | `Hasil_Potong` | Finished cut component parts ready for assembly |
| 11 | `Inspeksi_QC` | QC inspection header logs and pass/fail metrics |
| 12 | `Detail_Cacat` | Itemized defect tracking (defect type, severity, origin) |
| 13 | `Rework` | Repair task assignments, target dates, and resolution status |
| 14 | `Laporan_Eksekutif` | Consolidated production & inventory metrics |

---

## ⚡ Getting Started

### Prerequisites
* Any modern web browser (Google Chrome, Microsoft Edge, Mozilla Firefox, Safari).
* No Node.js, database server, or build step required.

### Local Execution
1. Clone the repository:
   `git clone https://github.com/luisfebry/Luis-Thesis-Prototype.git`
2. Navigate to the project directory:
   `cd Luis-Thesis-Prototype`
3. Open `index.html` directly in your web browser, or serve it using a simple local server:
   `python -m http.server 8000`
4. Access the application at `http://localhost:8000`.

---

## 📄 License & Attribution

Developed specifically for academic evaluation and thesis defense at **Universitas Tarumanagara (UNTAR)**, Program Studi Sistem Informasi, 2026.
