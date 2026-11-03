# Bong Garment - CMT Production & Inventory System

A web-based production and inventory management system designed for **Bong Garment**, an apparel manufacturer operating under the Cut, Make, Trim (CMT) workflow. 

This repository contains the software implementation for an undergraduate thesis at Universitas Tarumanagara (UNTAR), Faculty of Information Technology (Department of Information Systems).

- **Live Demo:** [https://thesis.megumin.me](https://thesis.megumin.me)
- **Author:** Luis Febry (NIM: 825220088)
- **Institution:** Universitas Tarumanagara, Jakarta (2026)

---

## Overview

In CMT garment manufacturing, production efficiency depends on fabric yield, accurate cutting schedules, and reliable quality control. This system addresses several operational challenges faced on the factory floor:

- **Roll-Level Fabric Tracking:** Instead of estimating bulk fabric weight, every arriving roll (`Detail_Roll_Bahan`) is registered with its initial and remaining measurements in standard units (`Kg`, `Yard`, or `Meter`) and tracked until exhausted.
- **Earliest Deadline First (EDF) Scheduling:** Automatically sorts and prioritizes the cutting floor queue according to delivery deadlines, with an audit log tracking deadline revisions and reasons.
- **11-Point Measurement Matrix:** Standardizes pattern dimensions across 11 key anatomical metrics (neck diameter, chest circumference, sleeve length, etc.) to minimize errors between pattern makers and sewing teams.
- **Multi-Stage Quality Inspection:** Tracks inspection checkpoints (`Prototipe`, `Potong`, `Jahit Akhir`) and manages rework lifecycles (`Need Rework`, `Rework Selesai`, `Scrapped`).
- **Perpetual Finished Stock:** Items that pass final inspection (`Jahit Akhir`) automatically credit the finished goods warehouse inventory (`Stok_Barang_Jadi`).

---

## Tech Stack & Architecture

The application is built with a dual-execution design:

1. **Full-Stack Mode (Laravel 11 + MySQL):**
   - **Framework:** Laravel 11 running on PHP 8.3
   - **Database:** MySQL 8.0 (InnoDB) with foreign key integrity and ACID transactions
   - **Views:** Blade templates styled with Tailwind CSS and Lucide icons
   - **Deployment:** Apache on Heroku with JawsDB MySQL integration (or Docker)

2. **Standalone Browser Mode (Zero Backend):**
   - Opening `index.html` runs a complete, client-side replica of the system.
   - Uses `localStorage` to emulate the 17 relational database tables and business logic without needing PHP, Composer, or MySQL installed locally.

---

## Database Structure (17 Tables)

The database schema is defined in `database/sql/bong_garment_17_tables.sql` and `database/migrations/`:

| Module | Tables | Purpose |
|---|---|---|
| **Master & Patterns** | `Sumber_Inisiasi`, `Kategori_Pola`, `Pola`, `Spesifikasi_Ukuran` | Manages style catalogs, customer order sources, and 11-dimension measurement specs. |
| **Users & Suppliers** | `User`, `Supplier` | System accounts with role-based access and external textile/notion vendors. |
| **Accessories & BOM** | `Aksesoris`, `Detail_Aksesoris_Pola` | Trims inventory (buttons, zippers, threads) and Bill of Materials per garment pattern. |
| **Fabric Inbound** | `Penerimaan_Bahan`, `Detail_Bahan_Warna`, `Detail_Roll_Bahan` | Supplier delivery receipts, color batch headers, and individual physical roll tracking. |
| **Orders & Queue** | `Pesanan` | Order intake, EDF scheduling, and deadline revision history. |
| **Cutting & Spreading** | `PemotonganSeri`, `Detail_Pemotongan_Warna`, `Detail_Gelaran_Kain` | Cutting batch headers, color breakdowns, and spreading table technical logs (length, plies, cut yield). |
| **Quality Control** | `QC` | Multi-stage inspection records, defect classification, and rework tracking. |
| **Finished Inventory** | `Stok_Barang_Jadi` | Perpetual stock balances for defect-free garments ready for packaging or shipping. |

---

## Roles & Permissions

The system implements Role-Based Access Control (RBAC) across 5 roles:

- **Admin (`admin@megumin.me`):** Full CRUD access across all modules, orders, specs, priority scheduling, and system configuration.
- **Pemilik (`pemilik@megumin.me`):** Executive Owner mode. Has view-only audit access to dashboards and executive reports. Data modifications are blocked.
- **Pemotong (`pemotong@megumin.me`):** Access to the cutting schedule, roll spreading logs, and cut yield records.
- **QC (`qc@megumin.me`):** Records inspection results across prototype, cutting, and sewing stages, and manages defect resolutions.
- **Gudang (`gudang@megumin.me`):** Manages inbound fabric deliveries, roll warehouse bins, accessories, and finished stock balances.

---

## Getting Started

### 1. Standalone Browser Preview (Instant)
Simply open `index.html` in Google Chrome, Microsoft Edge, or Firefox. No web server or database setup required.

### 2. Local Docker Setup
To run the full Laravel and MySQL environment locally:

```bash
# Clone the repository
git clone https://github.com/luisfebry/Bong-Garment-Thesis.git
cd Bong-Garment-Thesis

# Start containers
docker compose up -d --build

# Run migrations and seed sample data
docker compose exec app php artisan migrate:fresh --seed
```

The application will be available at `http://localhost:8080`.

### 3. Heroku Deployment
The repository includes standard deployment files (`Procfile`, `app.json`, and `config/database.php`):
- Connect the GitHub repository to your Heroku app.
- Attach the **JawsDB MySQL** add-on.
- The release command in `Procfile` runs database migrations and seeds automatically upon deployment.
- If needed, the database can also be re-seeded from the browser at `https://thesis.megumin.me/migrate-db`.

---

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.
