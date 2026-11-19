/* ============================================================================
 * BONG GARMENT — Static Prototype Relational Store & Domain Services
 * 18 Canonical Tables matching Laravel backend (Bong-Garment-Thesis-main)
 * Works via file:// and static HTTP servers using localStorage persistence.
 * ========================================================================== */
(function () {
  'use strict';

  const DB_KEY = 'bong_garment_proto_18t_v1';
  const SESSION_KEY = 'bong_garment_proto_session_v1';
  const DB_VERSION = 2;

  const TABLE_PK = {
    Sumber_Inisiasi: 'id_sumber',
    Kategori_Pola: 'id_kategori',
    Pola: 'id_pola',
    Spesifikasi_Ukuran: 'id_spesifikasi',
    User: 'id_user',
    Supplier: 'id_supplier',
    Aksesoris: 'id_aksesoris',
    Detail_Aksesoris_Pola: null, // composite PK: id_pola + id_aksesoris
    Penerimaan_Bahan: 'id_penerimaan',
    Detail_Bahan_Warna: 'id_detail_warna',
    Detail_Roll_Bahan: 'id_roll',
    Pesanan: 'id_pesanan',
    Stok_Barang_Jadi: 'id_stok',
    Pemotongan_Seri: 'id_potong',
    Detail_Pemotongan_Warna: 'id_potong_warna',
    Detail_Gelaran_Kain: 'id_gelar',
    QC: 'id_qc',
    Riwayat_Stok_Barang_Jadi: 'id_riwayat_stok',
  };

  const ROLE_LABELS = {
    Admin: 'Admin (Akses Penuh)',
    Pemilik: 'Pemilik (Mode Audit)',
    Pemotong: 'Pemotong (Cutting)',
    QC: 'QC (Inspeksi)',
    Gudang: 'Gudang (Bahan & Stok)',
  };

  const ROLE_REDIRECT = {
    Admin: 'dashboard',
    Pemilik: 'dashboard',
    Pemotong: 'pemotongan',
    QC: 'qc',
    Gudang: 'penerimaan',
  };

  const ROLE_TABS = {
    Admin: ['dashboard', 'pesanan', 'penerimaan', 'pemotongan', 'qc', 'gudang', 'laporan', 'pengaturan'],
    Pemilik: ['dashboard', 'gudang', 'laporan'],
    Pemotong: ['dashboard', 'pemotongan'],
    QC: ['dashboard', 'qc'],
    Gudang: ['dashboard', 'penerimaan', 'gudang'],
  };

  const BULAN_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const BULAN_LONG = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  /* --------------------------------------------------------------------------
   * Utilities
   * ------------------------------------------------------------------------ */
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));

  const sum = (arr, fn = (x) => x) => (arr || []).reduce((acc, x) => acc + (Number(fn(x)) || 0), 0);
  const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;
  const round1 = (n) => Math.round((Number(n) || 0) * 10) / 10;

  function fmtNum(n, dec = 0) {
    const val = Number(n) || 0;
    if (dec > 0) return val.toFixed(dec);
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(val);
  }

  function pad2(n) { return String(n).padStart(2, '0'); }
  function pad4(n) { return String(n).padStart(4, '0'); }
  function pad3(n) { return String(n).padStart(3, '0'); }

  function todayIso() {
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  }

  function nowSql() {
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
  }

  function nowLocalIsoMin() {
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}T${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
  }

  function addDays(iso, days) {
    const base = iso || todayIso();
    const dt = new Date(base.slice(0, 10) + 'T00:00:00Z');
    dt.setUTCDate(dt.getUTCDate() + days);
    return dt.toISOString().slice(0, 10);
  }

  function datePart(val) {
    if (!val) return '';
    const m = String(val).trim().match(/^(\d{4}-\d{2}-\d{2})/);
    return m ? m[1] : '';
  }

  function toInputDateTime(val) {
    if (!val) return '';
    const m = String(val).trim().match(/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/);
    if (m) return `${m[1]}T${m[2]}`;
    const d = datePart(val);
    return d ? `${d}T00:00` : '';
  }

  function fmtDateDmy(val) {
    const d = datePart(val);
    if (!d) return '-';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
  }

  function fmtDateTimeDmy(val) {
    if (!val) return '-';
    const m = String(val).trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
    if (!m) return '-';
    const base = `${m[3]}/${m[2]}/${m[1]}`;
    if (m[4] !== undefined && m[5] !== undefined) return `${base} ${m[4]}:${m[5]}`;
    return base;
  }

  function fmtDateShortId(val) {
    const d = datePart(val);
    if (!d) return '-';
    const [y, m, day] = d.split('-').map(Number);
    return `${String(day).padStart(2, '0')} ${BULAN_SHORT[m - 1]} ${y}`;
  }

  function fmtDateLongId(val) {
    const d = datePart(val);
    if (!d) return '-';
    const [y, m, day] = d.split('-').map(Number);
    return `${day} ${BULAN_LONG[m - 1]} ${y}`;
  }

  function fmtNowWib() {
    const d = new Date();
    return `${pad2(d.getDate())} ${BULAN_SHORT[d.getMonth()]} ${d.getFullYear()}, ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())} WIB`;
  }

  /* --------------------------------------------------------------------------
   * Default Configuration & 18-Table Seed (verbatim from BongGarment18TableSeeder.php)
   * ------------------------------------------------------------------------ */
  function defaultConfig() {
    return {
      thresholds: {
        min_stok_jadi: 20,
        min_stok_aksesoris: 500,
      },
      companyProfile: {
        nama_perusahaan: 'BONG GARMENT',
        tagline: 'KONVEKSI & MANUFAKTUR PAKAIAN CMT',
        alamat: 'Jl. Industri Konveksi Raya No. 18, Jakarta Barat',
        telepon: '(021) 5589-2410',
        deskripsi_sistem: 'Sistem Informasi Manajemen Produksi & Inventori Konveksi Cut, Make, and Trim (CMT)',
        kota_pengesahan: 'Jakarta',
      },
    };
  }

  function buildSeed(preserveUsers = null, preserveConfig = null) {
    const Sumber_Inisiasi = [
      { id_sumber: 1, nama_sumber: 'Request Mitra' },
      { id_sumber: 2, nama_sumber: 'Produk Abadi' },
      { id_sumber: 3, nama_sumber: 'Inisiatif Tren Pasar' },
    ];

    const Kategori_Pola = [
      { id_kategori: 1, nama_kategori: 'Kaos & Polo' },
      { id_kategori: 2, nama_kategori: 'Kemeja & Formal' },
      { id_kategori: 3, nama_kategori: 'Celana & Chino' },
      { id_kategori: 4, nama_kategori: 'Jaket & Outerwear' },
    ];

    const Pola = [
      { id_pola: 1, id_kategori: 1, kode_pola: 'POLA-KOS-01', nama_pola: 'Kaos Polos Cotton Combed 30s Crewneck' },
      { id_pola: 2, id_kategori: 2, kode_pola: 'POLA-KMJ-02', nama_pola: 'Kemeja Slim Fit Oxford Lengan Panjang' },
      { id_pola: 3, id_kategori: 3, kode_pola: 'POLA-CHN-03', nama_pola: 'Celana Chino Stretch Kasual Pria' },
      { id_pola: 4, id_kategori: 1, kode_pola: 'POLA-PLO-04', nama_pola: 'Polo Shirt Lacoste CVC Rib Collar' },
    ];

    const Spesifikasi_Ukuran = [
      { id_spesifikasi: 1, id_pola: 1, nama_ukuran: 'S', diameter_leher: 17.50, panjang_pundak: 13.50, lingkar_lengan: 42.00, panjang_tangan: 21.00, lingkar_dada: 96.00, lingkar_pinggang: 94.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 48.00, panjang_baju_celana: 68.00 },
      { id_spesifikasi: 2, id_pola: 1, nama_ukuran: 'M', diameter_leher: 18.00, panjang_pundak: 14.50, lingkar_lengan: 44.00, panjang_tangan: 22.00, lingkar_dada: 102.00, lingkar_pinggang: 100.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 51.00, panjang_baju_celana: 70.00 },
      { id_spesifikasi: 3, id_pola: 1, nama_ukuran: 'L', diameter_leher: 18.50, panjang_pundak: 15.50, lingkar_lengan: 46.00, panjang_tangan: 23.50, lingkar_dada: 108.00, lingkar_pinggang: 106.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 54.00, panjang_baju_celana: 72.00 },
      { id_spesifikasi: 4, id_pola: 1, nama_ukuran: 'XL', diameter_leher: 19.00, panjang_pundak: 16.50, lingkar_lengan: 48.00, panjang_tangan: 25.00, lingkar_dada: 114.00, lingkar_pinggang: 112.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 57.00, panjang_baju_celana: 74.00 },
      { id_spesifikasi: 5, id_pola: 2, nama_ukuran: 'S', diameter_leher: 38.00, panjang_pundak: 43.00, lingkar_lengan: 46.00, panjang_tangan: 58.00, lingkar_dada: 98.00, lingkar_pinggang: 90.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 49.00, panjang_baju_celana: 71.00 },
      { id_spesifikasi: 6, id_pola: 2, nama_ukuran: 'M', diameter_leher: 39.50, panjang_pundak: 45.00, lingkar_lengan: 48.00, panjang_tangan: 60.00, lingkar_dada: 104.00, lingkar_pinggang: 96.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 52.00, panjang_baju_celana: 73.00 },
      { id_spesifikasi: 7, id_pola: 2, nama_ukuran: 'L', diameter_leher: 41.00, panjang_pundak: 47.00, lingkar_lengan: 50.00, panjang_tangan: 62.00, lingkar_dada: 110.00, lingkar_pinggang: 102.00, lingkar_selangkang: null, lingkar_paha: null, lebar_kaki: null, lebar_baju: 55.00, panjang_baju_celana: 75.00 },
      { id_spesifikasi: 8, id_pola: 3, nama_ukuran: 'M', diameter_leher: null, panjang_pundak: null, lingkar_lengan: null, panjang_tangan: null, lingkar_dada: null, lingkar_pinggang: 82.00, lingkar_selangkang: 28.00, lingkar_paha: 58.00, lebar_kaki: 34.00, lebar_baju: null, panjang_baju_celana: 98.00 },
      { id_spesifikasi: 9, id_pola: 3, nama_ukuran: 'L', diameter_leher: null, panjang_pundak: null, lingkar_lengan: null, panjang_tangan: null, lingkar_dada: null, lingkar_pinggang: 88.00, lingkar_selangkang: 30.00, lingkar_paha: 62.00, lebar_kaki: 36.00, lebar_baju: null, panjang_baju_celana: 100.00 },
    ];

    const User = preserveUsers && preserveUsers.length
      ? preserveUsers.map((u) => ({ ...u }))
      : [
          { id_user: 1, username: 'admin', password: 'admin123', nama_user: 'Administrator', role: 'Admin' },
          { id_user: 2, username: 'pemilik', password: 'pemilik123', nama_user: 'Bong (Pemilik CMT)', role: 'Pemilik' },
          { id_user: 3, username: 'pemotong', password: 'potong123', nama_user: 'Budi Santoso (Head Cutter)', role: 'Pemotong' },
          { id_user: 4, username: 'qc', password: 'qc123', nama_user: 'Siti Rahma (Lead Quality Control)', role: 'QC' },
          { id_user: 5, username: 'gudang', password: 'gudang123', nama_user: 'Agus Hermawan (Kepala Gudang & Inventori)', role: 'Gudang' },
        ];

    const Supplier = [
      { id_supplier: 1, nama_supplier: 'PT Kahatex Tekstil Prima', kontak: '022-7798888', alamat: 'Jl. Raya Rancaekek No.KM 23, Sumedang, Jawa Barat' },
      { id_supplier: 2, nama_supplier: 'CV Aksesoris Garment Harapan', kontak: '021-6901234', alamat: 'Pasar Pagi Mangga Dua Lt. 2 Blok B No. 45, Jakarta Barat' },
      { id_supplier: 3, nama_supplier: 'PT Dan Liris Tekstil Sukses', kontak: '0271-714400', alamat: 'Banaran, Grogol, Sukoharjo, Jawa Tengah' },
    ];

    const Aksesoris = [
      { id_aksesoris: 1, id_supplier: 2, nama_aksesoris: 'Kancing Jamur Resin Smoke Grey 18L', ukuran: '18L', stok: 15000, satuan: 'pcs' },
      { id_aksesoris: 2, id_supplier: 2, nama_aksesoris: 'Ritsleting Metal YKK Antique Brass 7 Inch', ukuran: '7 Inch', stok: 2400, satuan: 'pcs' },
      { id_aksesoris: 3, id_supplier: 2, nama_aksesoris: 'Benang Jahit Katun Astra 40/2 Hitam', ukuran: '5000 Yds', stok: 180, satuan: 'pcs' },
      { id_aksesoris: 4, id_supplier: 2, nama_aksesoris: 'Label Woven Tag Bong Garment Premium', ukuran: '5x2.5 cm', stok: 12000, satuan: 'pcs' },
      { id_aksesoris: 5, id_supplier: 2, nama_aksesoris: 'Karet Pinggang Stretch Elastic 3.8cm', ukuran: '3.8 cm', stok: 45, satuan: 'gross' },
    ];

    const Detail_Aksesoris_Pola = [
      { id_pola: 1, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 1, id_aksesoris: 3, jumlah_digunakan: 1 },
      { id_pola: 2, id_aksesoris: 1, jumlah_digunakan: 8 },
      { id_pola: 2, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 2, id_aksesoris: 3, jumlah_digunakan: 1 },
      { id_pola: 3, id_aksesoris: 2, jumlah_digunakan: 1 },
      { id_pola: 3, id_aksesoris: 1, jumlah_digunakan: 1 },
      { id_pola: 3, id_aksesoris: 4, jumlah_digunakan: 1 },
    ];

    const Penerimaan_Bahan = [
      { id_penerimaan: 1, id_supplier: 1, no_nota: 'NOTA-2026-1001', tgl_nota: '2026-10-15', nama_kain: 'Cotton Combed 30s Super Soft', lebar_gramasi: 'Gramasi 145 / Lebar 42" Tubular', no_kendaraan: 'B 9821 TAA', penerima: 'Agus Hermawan' },
      { id_penerimaan: 2, id_supplier: 1, no_nota: 'NOTA-2026-1014', tgl_nota: '2026-10-22', nama_kain: 'Katun Oxford Solid Premium', lebar_gramasi: 'Gramasi 180 / Lebar 58"', no_kendaraan: 'B 9112 PQU', penerima: 'Agus Hermawan' },
      { id_penerimaan: 3, id_supplier: 3, no_nota: 'NOTA-2026-1028', tgl_nota: '2026-10-28', nama_kain: 'Twill Chino Stretch Spandex', lebar_gramasi: 'Gramasi 260 / Lebar 60"', no_kendaraan: 'B 9345 SXZ', penerima: 'Agus Hermawan' },
    ];

    const Detail_Bahan_Warna = [
      { id_detail_warna: 1, id_penerimaan: 1, nama_warna: 'Hitam Reaktif', kode_warna: 'CLR-BLK-01', total_roll: 4, total_ukuran: 100.00, satuan_ukur: 'Kg' },
      { id_detail_warna: 2, id_penerimaan: 1, nama_warna: 'Navy Blue', kode_warna: 'CLR-NVY-02', total_roll: 3, total_ukuran: 75.00, satuan_ukur: 'Kg' },
      { id_detail_warna: 3, id_penerimaan: 2, nama_warna: 'Putih Bersih', kode_warna: 'CLR-WHT-03', total_roll: 2, total_ukuran: 240.00, satuan_ukur: 'Yard' },
      { id_detail_warna: 4, id_penerimaan: 2, nama_warna: 'Sky Blue', kode_warna: 'CLR-SKY-04', total_roll: 2, total_ukuran: 240.00, satuan_ukur: 'Yard' },
      { id_detail_warna: 5, id_penerimaan: 3, nama_warna: 'Khaki Beige', kode_warna: 'CLR-KHK-05', total_roll: 3, total_ukuran: 360.00, satuan_ukur: 'Meter' },
    ];

    const Detail_Roll_Bahan = [
      { id_roll: 1, id_detail_warna: 1, no_roll_ke: 1, ukuran_awal: 25.00, ukuran_sisa: 5.00, satuan: 'Kg', status_roll: 'Dipotong' },
      { id_roll: 2, id_detail_warna: 1, no_roll_ke: 2, ukuran_awal: 25.00, ukuran_sisa: 25.00, satuan: 'Kg', status_roll: 'Tersedia' },
      { id_roll: 3, id_detail_warna: 1, no_roll_ke: 3, ukuran_awal: 25.00, ukuran_sisa: 25.00, satuan: 'Kg', status_roll: 'Tersedia' },
      { id_roll: 4, id_detail_warna: 1, no_roll_ke: 4, ukuran_awal: 25.00, ukuran_sisa: 0.00, satuan: 'Kg', status_roll: 'Habis' },
      { id_roll: 5, id_detail_warna: 2, no_roll_ke: 1, ukuran_awal: 25.00, ukuran_sisa: 25.00, satuan: 'Kg', status_roll: 'Tersedia' },
      { id_roll: 6, id_detail_warna: 2, no_roll_ke: 2, ukuran_awal: 25.00, ukuran_sisa: 25.00, satuan: 'Kg', status_roll: 'Tersedia' },
      { id_roll: 7, id_detail_warna: 2, no_roll_ke: 3, ukuran_awal: 25.00, ukuran_sisa: 25.00, satuan: 'Kg', status_roll: 'Tersedia' },
      { id_roll: 8, id_detail_warna: 3, no_roll_ke: 1, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Yard', status_roll: 'Tersedia' },
      { id_roll: 9, id_detail_warna: 3, no_roll_ke: 2, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Yard', status_roll: 'Tersedia' },
      { id_roll: 10, id_detail_warna: 4, no_roll_ke: 1, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Yard', status_roll: 'Tersedia' },
      { id_roll: 11, id_detail_warna: 4, no_roll_ke: 2, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Yard', status_roll: 'Tersedia' },
      { id_roll: 12, id_detail_warna: 5, no_roll_ke: 1, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Meter', status_roll: 'Tersedia' },
      { id_roll: 13, id_detail_warna: 5, no_roll_ke: 2, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Meter', status_roll: 'Tersedia' },
      { id_roll: 14, id_detail_warna: 5, no_roll_ke: 3, ukuran_awal: 120.00, ukuran_sisa: 120.00, satuan: 'Meter', status_roll: 'Tersedia' },
    ];

    const Pesanan = [
      { id_pesanan: 1, id_pola: 1, id_sumber: 1, id_penerimaan: 1, id_user_admin: 1, jenis_pesanan: 'Kaos Combed 30s Custom Sablon', nama_mitra: 'PT Busana Mandiri Kreasi', tgl_order: '2026-10-20', deadline_awal: '2026-11-10', deadline_terbaru: '2026-11-08', tgl_perubahan_deadline: '2026-10-26 14:00:00', alasan_perubahan_deadline: 'Permintaan percepatan jadwal distribusi oleh mitra untuk event peluncuran produk', status_pesanan: 'Pemotongan' },
      { id_pesanan: 2, id_pola: 2, id_sumber: 1, id_penerimaan: 2, id_user_admin: 1, jenis_pesanan: 'Kemeja Formal Oxford Putih & Sky Blue', nama_mitra: 'CV Sinar Apparel Distro', tgl_order: '2026-10-22', deadline_awal: '2026-11-15', deadline_terbaru: '2026-11-15', tgl_perubahan_deadline: null, alasan_perubahan_deadline: null, status_pesanan: 'Antrean' },
      { id_pesanan: 3, id_pola: 1, id_sumber: 2, id_penerimaan: 1, id_user_admin: 1, jenis_pesanan: 'Kaos Polos Standar Bong Garment', nama_mitra: null, tgl_order: '2026-10-25', deadline_awal: '2026-11-20', deadline_terbaru: '2026-11-20', tgl_perubahan_deadline: null, alasan_perubahan_deadline: null, status_pesanan: 'Antrean' },
      { id_pesanan: 4, id_pola: 3, id_sumber: 3, id_penerimaan: 3, id_user_admin: 1, jenis_pesanan: 'Celana Chino Slim Fit Tren Musim Hujan', nama_mitra: null, tgl_order: '2026-10-28', deadline_awal: '2026-11-25', deadline_terbaru: '2026-11-28', tgl_perubahan_deadline: '2026-10-30 09:30:00', alasan_perubahan_deadline: 'Penyesuaian jadwal kedatangan kancing saku belakang dari vendor aksesoris', status_pesanan: 'Antrean' },
    ];

    const Stok_Barang_Jadi = [
      { id_stok: 1, id_pola: 1, id_spesifikasi: 1, nama_warna: 'Hitam Reaktif', jumlah_stok_pcs: 80, tgl_update_terakhir: '2026-10-30 16:30:00' },
      { id_stok: 2, id_pola: 1, id_spesifikasi: 2, nama_warna: 'Hitam Reaktif', jumlah_stok_pcs: 140, tgl_update_terakhir: '2026-10-30 16:30:00' },
      { id_stok: 3, id_pola: 1, id_spesifikasi: 3, nama_warna: 'Hitam Reaktif', jumlah_stok_pcs: 110, tgl_update_terakhir: '2026-10-30 16:30:00' },
      { id_stok: 4, id_pola: 2, id_spesifikasi: 6, nama_warna: 'Putih Bersih', jumlah_stok_pcs: 65, tgl_update_terakhir: '2026-10-29 11:15:00' },
    ];

    const Pemotongan_Seri = [
      { id_potong: 1, id_pesanan: 1, id_user_pemotong: 3, kode_seri: 'SERI-2026-001', jenis_variasi: 'Kaos Combed Hitam All Size', total_lembar_hasil: 300, tgl_potong: '2026-10-28 10:00:00' },
    ];

    const Detail_Pemotongan_Warna = [
      { id_potong_warna: 1, id_potong: 1, id_detail_warna: 1, total_lembar_direncanakan: 300, kain_terpakai: 45.00 },
    ];

    const Detail_Gelaran_Kain = [
      { id_gelar: 1, id_potong_warna: 1, id_roll: 4, panjang_gelar: 2.50, jumlah_tumpuk: 60, total_lembar_gelar: 150 },
      { id_gelar: 2, id_potong_warna: 1, id_roll: 1, panjang_gelar: 2.50, jumlah_tumpuk: 60, total_lembar_gelar: 150 },
    ];

    const QC = [
      { id_qc: 1, id_pesanan: 1, id_user_qc: 4, tahap_qc: 'Potong', tgl_pemeriksaan: '2026-10-28 15:30:00', jumlah_diperiksa: 300, jumlah_lolos: 294, jumlah_cacat: 6, jenis_cacat: 'Cacat Potong', catatan_qc: '6 lembar kain terpotong miring pada bagian kerung lengan (armhole)', status_qc: 'Need Rework', tgl_target_perbaikan: '2026-10-29', tgl_selesai_perbaikan: '2026-10-29 11:00:00' },
      { id_qc: 2, id_pesanan: 1, id_user_qc: 4, tahap_qc: 'Jahit Akhir', tgl_pemeriksaan: '2026-10-30 16:00:00', jumlah_diperiksa: 294, jumlah_lolos: 290, jumlah_cacat: 4, jenis_cacat: 'Cacat Jahit', catatan_qc: '[PIC: Mitra Penjahit CMT] Obras kerah terlepas pada 4 pcs kaos, segera diperbaiki operator jahit', status_qc: 'Rework Selesai', tgl_target_perbaikan: '2026-10-31', tgl_selesai_perbaikan: '2026-10-31 09:00:00' },
      { id_qc: 3, id_pesanan: 2, id_user_qc: 4, tahap_qc: 'Prototipe', tgl_pemeriksaan: '2026-10-31 11:00:00', jumlah_diperiksa: 20, jumlah_lolos: 18, jumlah_cacat: 2, jenis_cacat: 'Cacat Bahan', catatan_qc: '[PIC: Gudang Bahan (Klaim Retur Supplier)] Ditemukan noda oli tenun (fabric stain) pada 2 pcs sampel kain, diajukan klaim retur ke supplier', status_qc: 'Need Rework', tgl_target_perbaikan: '2026-11-05', tgl_selesai_perbaikan: null },
    ];

    const Riwayat_Stok_Barang_Jadi = [
      { id_riwayat_stok: 1, id_stok: 1, id_pola: 1, id_spesifikasi: 1, nama_warna: 'Hitam Reaktif', jenis_transaksi: 'KELUAR', jumlah_pcs: 20, stok_sebelum: 100, stok_sesudah: 80, kategori_alasan: 'Diambil Mitra (Serah Terima Request Mitra)', nama_mitra: 'CV Sinar Apparel Distro', keterangan: 'Surat Jalan No. SJ-2026-1030 (Batch 1 Pengiriman)', id_user: 1, tgl_transaksi: '2026-10-30 16:30:00' },
    ];

    return {
      _meta: { version: DB_VERSION, updated_at: new Date().toISOString() },
      _config: preserveConfig || defaultConfig(),
      Sumber_Inisiasi,
      Kategori_Pola,
      Pola,
      Spesifikasi_Ukuran,
      User,
      Supplier,
      Aksesoris,
      Detail_Aksesoris_Pola,
      Penerimaan_Bahan,
      Detail_Bahan_Warna,
      Detail_Roll_Bahan,
      Pesanan,
      Stok_Barang_Jadi,
      Pemotongan_Seri,
      Detail_Pemotongan_Warna,
      Detail_Gelaran_Kain,
      QC,
      Riwayat_Stok_Barang_Jadi,
    };
  }

  /* --------------------------------------------------------------------------
   * BongDB Relational Store
   * ------------------------------------------------------------------------ */
  const BongDB = {
    KEY: DB_KEY,
    TABLES: TABLE_PK,
    data: null,
    _listeners: [],
    _memoryOnly: false,

    init() {
      let parsed = null;
      try {
        const raw = localStorage.getItem(DB_KEY);
        parsed = raw ? JSON.parse(raw) : null;
      } catch (err) {
        this._memoryOnly = true;
      }
      if (this._isValid(parsed)) {
        this.data = parsed;
        if (!this.data._config) this.data._config = defaultConfig();
        if (!this.data._config.thresholds) this.data._config.thresholds = defaultConfig().thresholds;
        if (!this.data._config.companyProfile) this.data._config.companyProfile = defaultConfig().companyProfile;
      } else {
        this.data = buildSeed();
        this.save();
      }
      window.addEventListener('storage', (e) => {
        if (e.key !== DB_KEY || !e.newValue) return;
        try {
          const next = JSON.parse(e.newValue);
          if (this._isValid(next)) {
            this.data = next;
            this._emit({ type: 'sync' });
          }
        } catch (_) { /* ignore */ }
      });
      return this;
    },

    _isValid(db) {
      return !!db && db._meta && db._meta.version === DB_VERSION &&
        Object.keys(TABLE_PK).every((t) => Array.isArray(db[t]));
    },

    save(silent = false) {
      this.data._meta.updated_at = new Date().toISOString();
      if (!this._memoryOnly) {
        try { localStorage.setItem(DB_KEY, JSON.stringify(this.data)); } catch (_) { /* ignore */ }
      }
      if (!silent) this._emit({ type: 'save' });
    },

    pk(table) {
      if (!(table in TABLE_PK)) throw new Error(`Tabel tidak dikenal: ${table}`);
      return TABLE_PK[table];
    },

    all(table) {
      this.pk(table);
      return this.data[table];
    },

    find(table, id) {
      if (id === null || id === undefined || id === '') return null;
      const key = this.pk(table);
      if (!key) return null;
      return this.data[table].find((r) => Number(r[key]) === Number(id)) || null;
    },

    where(table, fn) {
      return this.all(table).filter(fn);
    },

    nextId(table) {
      const key = this.pk(table);
      if (!key) return 1;
      return this.data[table].reduce((m, r) => Math.max(m, Number(r[key]) || 0), 0) + 1;
    },

    insert(table, record, { emit = true } = {}) {
      const key = this.pk(table);
      const rec = { ...record };
      if (key && (rec[key] === undefined || rec[key] === null)) {
        rec[key] = this.nextId(table);
      }
      this.data[table].push(rec);
      this.save(!emit);
      if (emit) this._emit({ type: 'insert', table, record: rec });
      return rec;
    },

    update(table, id, patch, { emit = true } = {}) {
      const key = this.pk(table);
      const rec = this.find(table, id);
      if (!rec) return null;
      Object.assign(rec, patch);
      if (key) rec[key] = Number(rec[key]);
      this.save(!emit);
      if (emit) this._emit({ type: 'update', table, record: rec });
      return rec;
    },

    remove(table, id, { emit = true } = {}) {
      const key = this.pk(table);
      if (!key) return false;
      const before = this.data[table].length;
      this.data[table] = this.data[table].filter((r) => Number(r[key]) !== Number(id));
      const removed = this.data[table].length !== before;
      if (removed) {
        this.save(!emit);
        if (emit) this._emit({ type: 'remove', table, id: Number(id) });
      }
      return removed;
    },

    removeWhere(table, fn, { emit = true } = {}) {
      const before = this.all(table).length;
      this.data[table] = this.data[table].filter((r) => !fn(r));
      const removed = this.data[table].length !== before;
      if (removed) {
        this.save(!emit);
        if (emit) this._emit({ type: 'remove', table });
      }
      return removed;
    },

    config() {
      if (!this.data._config) this.data._config = defaultConfig();
      return this.data._config;
    },

    subscribe(fn) {
      this._listeners.push(fn);
      return () => { this._listeners = this._listeners.filter((f) => f !== fn); };
    },

    _emit(evt) {
      this._listeners.forEach((fn) => { try { fn(evt); } catch (err) { console.error(err); } });
    },
  };

  /* --------------------------------------------------------------------------
   * Auth & Session Manager (5 Roles + Admin Role View Simulator)
   * ------------------------------------------------------------------------ */
  const BongAuth = {
    _memSession: null,

    _defaultSession() {
      return { loggedIn: true, userId: 1, simRole: 'Admin' };
    },

    getSession() {
      if (this._memSession) return this._memSession;
      try {
        const raw = localStorage.getItem(SESSION_KEY);
        if (raw) {
          const parsed = JSON.parse(raw);
          if (parsed && typeof parsed.loggedIn === 'boolean') {
            this._memSession = parsed;
            return parsed;
          }
        }
      } catch (_) { /* ignore */ }
      this._memSession = this._defaultSession();
      this._saveSession();
      return this._memSession;
    },

    _saveSession() {
      try {
        localStorage.setItem(SESSION_KEY, JSON.stringify(this._memSession));
      } catch (_) { /* ignore */ }
    },

    isLoggedIn() {
      const s = this.getSession();
      if (!s.loggedIn) return false;
      const u = BongDB.find('User', s.userId);
      if (!u) {
        s.loggedIn = false;
        this._saveSession();
        return false;
      }
      return true;
    },

    getUser() {
      const s = this.getSession();
      return BongDB.find('User', s.userId) || BongDB.all('User')[0] || {
        id_user: 1, username: 'admin', nama_user: 'Administrator', role: 'Admin',
      };
    },

    getTrueRole() {
      return this.getUser()?.role || 'Admin';
    },

    isAdminAccount() {
      return this.getTrueRole() === 'Admin';
    },

    getEffectiveRole() {
      const trueRole = this.getTrueRole();
      const s = this.getSession();
      if (trueRole === 'Admin' && s.simRole && ROLE_TABS[s.simRole]) {
        return s.simRole;
      }
      return trueRole;
    },

    isReadOnly() {
      return this.getEffectiveRole() === 'Pemilik';
    },

    setSimulatedRole(role) {
      if (!this.isAdminAccount()) return false;
      if (!ROLE_TABS[role]) return false;
      const s = this.getSession();
      s.simRole = role;
      this._saveSession();
      return true;
    },

    canAccessTab(tab) {
      if (tab === 'pengaturan') return this.isAdminAccount();
      const eff = this.getEffectiveRole();
      return (ROLE_TABS[eff] || ['dashboard']).includes(tab);
    },

    login(username, password) {
      const uName = String(username || '').trim();
      const user = BongDB.all('User').find((u) => u.username === uName);
      if (!user || user.password !== String(password || '')) {
        throw new Error('Username atau password tidak sesuai.');
      }
      this._memSession = {
        loggedIn: true,
        userId: user.id_user,
        simRole: user.role,
      };
      this._saveSession();
      return {
        user,
        redirectTab: ROLE_REDIRECT[user.role] || 'dashboard',
      };
    },

    logout() {
      const s = this.getSession();
      s.loggedIn = false;
      this._saveSession();
    },

    getRoleLabel(role) {
      return ROLE_LABELS[role] || role;
    },

    getInitials(name) {
      const clean = String(name || 'A').replace(/\([^)]*\)/g, '').trim();
      const words = clean.split(/\s+/).filter(Boolean);
      const a = (words[0] || 'A').charAt(0);
      const b = (words[1] || words[0] || 'D').charAt(0);
      return (a + b).toUpperCase();
    },
  };

  /* --------------------------------------------------------------------------
   * Relational Query Helpers
   * ------------------------------------------------------------------------ */
  const BongQ = {
    sumber: (id) => BongDB.find('Sumber_Inisiasi', id),
    kategori: (id) => BongDB.find('Kategori_Pola', id),
    pola: (id) => BongDB.find('Pola', id),
    spec: (id) => BongDB.find('Spesifikasi_Ukuran', id),
    user: (id) => BongDB.find('User', id),
    supplier: (id) => BongDB.find('Supplier', id),
    aksesoris: (id) => BongDB.find('Aksesoris', id),
    penerimaan: (id) => BongDB.find('Penerimaan_Bahan', id),
    warna: (id) => BongDB.find('Detail_Bahan_Warna', id),
    roll: (id) => BongDB.find('Detail_Roll_Bahan', id),
    pesanan: (id) => BongDB.find('Pesanan', id),

    specsOfPola(idPola) {
      return BongDB.where('Spesifikasi_Ukuran', (s) => Number(s.id_pola) === Number(idPola));
    },

    aksesorisOfPola(idPola) {
      return BongDB.where('Detail_Aksesoris_Pola', (d) => Number(d.id_pola) === Number(idPola))
        .map((d) => ({ ...d, aksesoris: BongDB.find('Aksesoris', d.id_aksesoris) }));
    },

    warnaOfPenerimaan(idPenerimaan) {
      return BongDB.where('Detail_Bahan_Warna', (w) => Number(w.id_penerimaan) === Number(idPenerimaan));
    },

    rollsOfWarna(idDetailWarna) {
      return BongDB.where('Detail_Roll_Bahan', (r) => Number(r.id_detail_warna) === Number(idDetailWarna))
        .sort((a, b) => a.no_roll_ke - b.no_roll_ke || a.id_roll - b.id_roll);
    },

    isProdukAbadi(order) {
      if (!order) return false;
      const sumber = BongQ.sumber(order.id_sumber);
      const sName = sumber ? sumber.nama_sumber : '';
      if (sName) return sName.toLowerCase().includes('produk abadi');
      return Number(order.id_sumber) === 2;
    },

    edfSortOrders(orders) {
      return [...orders].sort((a, b) => {
        const da = datePart(a.deadline_terbaru || a.deadline_awal) || '9999-12-31';
        const db = datePart(b.deadline_terbaru || b.deadline_awal) || '9999-12-31';
        if (da !== db) return da.localeCompare(db);
        return Number(a.id_pesanan) - Number(b.id_pesanan);
      });
    },

    recalcWarnaTotals(idDetailWarna) {
      const warna = BongDB.find('Detail_Bahan_Warna', idDetailWarna);
      if (!warna) return;
      const rolls = BongQ.rollsOfWarna(idDetailWarna);
      BongDB.update('Detail_Bahan_Warna', idDetailWarna, {
        total_roll: rolls.length,
        total_ukuran: round2(sum(rolls, (r) => r.ukuran_awal)),
      }, { emit: false });
    },
  };

  function guardWritable() {
    if (BongAuth.isReadOnly()) {
      throw new Error('Peran Pemilik berada dalam Mode Audit (Read-Only). Mutasi data tidak diizinkan.');
    }
  }

  /* --------------------------------------------------------------------------
   * Domain Services (Business Logic matching Laravel Controllers)
   * ------------------------------------------------------------------------ */
  const BongService = {
    /* --- PESANAN & POLA --- */
    createPesanan(payload) {
      guardWritable();
      let idPola = Number(payload.id_pola);

      if (payload.pola_mode === 'new' && payload.new_pola) {
        const createdPola = this.createPola(payload.new_pola, { emit: false });
        idPola = createdPola.id_pola;
      }

      if (!idPola || !BongQ.pola(idPola)) throw new Error('Master Pola wajib dipilih.');
      if (!payload.jenis_pesanan || !String(payload.jenis_pesanan).trim()) throw new Error('Jenis / Keterangan Pesanan wajib diisi.');
      if (!payload.deadline_awal) throw new Error('Tanggal Deadline Awal wajib diisi.');

      const idSumber = Number(payload.id_sumber);
      const namaMitra = idSumber === 1 ? (String(payload.nama_mitra || '').trim() || null) : null;
      const tglOrder = datePart(payload.tgl_order) || todayIso();
      const deadlineAwal = datePart(payload.deadline_awal);

      const order = BongDB.insert('Pesanan', {
        id_pola: idPola,
        id_sumber: idSumber,
        id_penerimaan: payload.id_penerimaan ? Number(payload.id_penerimaan) : null,
        id_user_admin: BongAuth.getUser()?.id_user || 1,
        jenis_pesanan: String(payload.jenis_pesanan).trim(),
        nama_mitra: namaMitra,
        tgl_order: tglOrder,
        deadline_awal: deadlineAwal,
        deadline_terbaru: deadlineAwal,
        tgl_perubahan_deadline: null,
        alasan_perubahan_deadline: null,
        status_pesanan: 'Antrean',
      });
      return order;
    },

    updatePesanan(id, payload) {
      guardWritable();
      const order = BongDB.find('Pesanan', id);
      if (!order) throw new Error('Pesanan tidak ditemukan.');
      if (!payload.deadline_awal) throw new Error('Tanggal Deadline Awal wajib diisi.');

      const idSumber = Number(payload.id_sumber);
      const deadlineAwal = datePart(payload.deadline_awal);
      const deadlineTerbaru = datePart(payload.deadline_terbaru) || order.deadline_terbaru || deadlineAwal;
      const changedDeadline = datePart(order.deadline_terbaru) !== deadlineTerbaru;

      return BongDB.update('Pesanan', id, {
        id_pola: Number(payload.id_pola),
        id_sumber: idSumber,
        id_penerimaan: payload.id_penerimaan ? Number(payload.id_penerimaan) : null,
        jenis_pesanan: String(payload.jenis_pesanan).trim(),
        nama_mitra: idSumber === 1 ? (String(payload.nama_mitra || '').trim() || null) : null,
        tgl_order: datePart(payload.tgl_order) || order.tgl_order,
        deadline_awal: deadlineAwal,
        deadline_terbaru: deadlineTerbaru,
        tgl_perubahan_deadline: changedDeadline ? nowSql() : order.tgl_perubahan_deadline,
        status_pesanan: payload.status_pesanan || order.status_pesanan,
        alasan_perubahan_deadline: String(payload.alasan_perubahan_deadline || '').trim() || null,
      });
    },

    reviseDeadline(id, deadlineTerbaru, alasan) {
      guardWritable();
      const order = BongDB.find('Pesanan', id);
      if (!order) throw new Error('Pesanan tidak ditemukan.');
      const dBaru = datePart(deadlineTerbaru);
      if (!dBaru) throw new Error('Format tanggal deadline baru tidak valid.');
      const reason = String(alasan || '').trim();
      if (!reason) throw new Error('Alasan revisi deadline wajib diisi.');

      return BongDB.update('Pesanan', id, {
        deadline_terbaru: dBaru,
        alasan_perubahan_deadline: reason,
        tgl_perubahan_deadline: nowSql(),
      });
    },

    deletePesanan(id) {
      guardWritable();
      const oid = Number(id);
      const blockedBy = [];
      if (BongDB.where('Pemotongan_Seri', (p) => Number(p.id_pesanan) === oid).length > 0) {
        blockedBy.push('Pemotongan Seri');
      }
      if (BongDB.where('QC', (q) => Number(q.id_pesanan) === oid).length > 0) {
        blockedBy.push('Quality Control (QC)');
      }
      if (blockedBy.length > 0) {
        throw new Error(`Pesanan #${oid} tidak dapat dihapus karena masih memiliki data terkait pada ${blockedBy.join(' dan ')}. Hapus catatan terkait terlebih dahulu.`);
      }
      BongDB.remove('Pesanan', oid);
    },

    createPola(payload, { emit = true } = {}) {
      guardWritable();
      const kode = String(payload.kode_pola || '').trim().toUpperCase();
      const nama = String(payload.nama_pola || '').trim();
      const idKategori = Number(payload.id_kategori);
      if (!kode || !nama) throw new Error('Kode Pola dan Nama Master Pola wajib diisi.');
      if (BongDB.all('Pola').some((p) => p.kode_pola.toUpperCase() === kode)) {
        throw new Error(`Kode Pola "${kode}" sudah digunakan.`);
      }
      const specs = Array.isArray(payload.spesifikasi) ? payload.spesifikasi.filter((s) => s && String(s.nama_ukuran || '').trim()) : [];
      if (specs.length === 0) throw new Error('Minimal harus ada 1 baris spesifikasi ukuran.');

      const pola = BongDB.insert('Pola', {
        id_kategori: idKategori,
        kode_pola: kode,
        nama_pola: nama,
      }, { emit: false });

      specs.forEach((sp) => {
        const numOrNull = (v) => (v !== '' && v !== null && v !== undefined && !isNaN(Number(v)) ? Number(v) : null);
        BongDB.insert('Spesifikasi_Ukuran', {
          id_pola: pola.id_pola,
          nama_ukuran: String(sp.nama_ukuran).trim(),
          diameter_leher: numOrNull(sp.diameter_leher),
          panjang_pundak: numOrNull(sp.panjang_pundak),
          lingkar_lengan: numOrNull(sp.lingkar_lengan),
          panjang_tangan: numOrNull(sp.panjang_tangan),
          lingkar_dada: numOrNull(sp.lingkar_dada),
          lingkar_pinggang: numOrNull(sp.lingkar_pinggang),
          lingkar_selangkang: numOrNull(sp.lingkar_selangkang),
          lingkar_paha: numOrNull(sp.lingkar_paha),
          lebar_kaki: numOrNull(sp.lebar_kaki),
          lebar_baju: numOrNull(sp.lebar_baju),
          panjang_baju_celana: numOrNull(sp.panjang_baju_celana),
        }, { emit: false });
      });

      const accs = Array.isArray(payload.aksesoris) ? payload.aksesoris : [];
      accs.forEach((acc) => {
        if (!acc.id_aksesoris || !acc.jumlah_digunakan) return;
        this._upsertPolaAcc(pola.id_pola, Number(acc.id_aksesoris), Number(acc.jumlah_digunakan));
      });

      BongDB.save(!emit);
      if (emit) BongDB._emit({ type: 'insert', table: 'Pola', record: pola });
      return pola;
    },

    updatePola(id, payload) {
      guardWritable();
      const pola = BongDB.find('Pola', id);
      if (!pola) throw new Error('Master Pola tidak ditemukan.');
      const kode = String(payload.kode_pola || '').trim().toUpperCase();
      const nama = String(payload.nama_pola || '').trim();
      if (!kode || !nama) throw new Error('Kode Pola dan Nama Master Pola wajib diisi.');
      if (BongDB.all('Pola').some((p) => Number(p.id_pola) !== Number(id) && p.kode_pola.toUpperCase() === kode)) {
        throw new Error(`Kode Pola "${kode}" sudah digunakan oleh pola lain.`);
      }

      BongDB.update('Pola', id, {
        id_kategori: Number(payload.id_kategori),
        kode_pola: kode,
        nama_pola: nama,
      }, { emit: false });

      const namaUkuran = String(payload.nama_ukuran || '').trim();
      if (namaUkuran) {
        const specs = BongQ.specsOfPola(id);
        const spec = specs.find((s) => s.nama_ukuran === namaUkuran) || specs[0];
        const numOrKeep = (v, fallback) => (v !== '' && v !== null && v !== undefined && !isNaN(Number(v)) ? Number(v) : fallback);
        if (spec) {
          BongDB.update('Spesifikasi_Ukuran', spec.id_spesifikasi, {
            nama_ukuran: namaUkuran,
            lingkar_dada: numOrKeep(payload.lingkar_dada, spec.lingkar_dada),
            panjang_baju_celana: numOrKeep(payload.panjang_baju_celana, spec.panjang_baju_celana),
            panjang_pundak: numOrKeep(payload.panjang_pundak, spec.panjang_pundak),
            panjang_tangan: numOrKeep(payload.panjang_tangan, spec.panjang_tangan),
          }, { emit: false });
        } else {
          BongDB.insert('Spesifikasi_Ukuran', {
            id_pola: Number(id),
            nama_ukuran: namaUkuran,
            diameter_leher: null,
            panjang_pundak: numOrKeep(payload.panjang_pundak, null),
            lingkar_lengan: null,
            panjang_tangan: numOrKeep(payload.panjang_tangan, null),
            lingkar_dada: numOrKeep(payload.lingkar_dada, null),
            lingkar_pinggang: null,
            lingkar_selangkang: null,
            lingkar_paha: null,
            lebar_kaki: null,
            lebar_baju: null,
            panjang_baju_celana: numOrKeep(payload.panjang_baju_celana, null),
          }, { emit: false });
        }
      }

      BongDB.save();
      return BongDB.find('Pola', id);
    },

    deletePola(id) {
      guardWritable();
      const pid = Number(id);
      const pola = BongDB.find('Pola', pid);
      if (!pola) throw new Error('Master Pola tidak ditemukan.');
      const blockedBy = [];
      if (BongDB.where('Pesanan', (p) => Number(p.id_pola) === pid).length > 0) blockedBy.push('Pesanan');
      if (BongDB.where('Stok_Barang_Jadi', (s) => Number(s.id_pola) === pid).length > 0) blockedBy.push('Stok Barang Jadi');
      if (blockedBy.length > 0) {
        throw new Error(`Master Pola ${pola.kode_pola} (${pola.nama_pola}) tidak dapat dihapus karena masih digunakan pada ${blockedBy.join(' dan ')}.`);
      }
      BongDB.removeWhere('Detail_Aksesoris_Pola', (d) => Number(d.id_pola) === pid, { emit: false });
      BongDB.removeWhere('Spesifikasi_Ukuran', (s) => Number(s.id_pola) === pid, { emit: false });
      BongDB.remove('Pola', pid);
    },

    /* --- PENERIMAAN BAHAN & ROLL --- */
    createPenerimaan(payload) {
      guardWritable();
      if (!payload.id_supplier || !payload.no_nota || !payload.tgl_nota || !payload.nama_kain) {
        throw new Error('Supplier, Nomor Nota, Tanggal Nota, dan Nama Kain wajib diisi.');
      }
      if (!Array.isArray(payload.warna) || payload.warna.length === 0) {
        throw new Error('Minimal harus ada 1 kelompok warna.');
      }

      const penerimaan = BongDB.insert('Penerimaan_Bahan', {
        id_supplier: Number(payload.id_supplier),
        no_nota: String(payload.no_nota).trim(),
        tgl_nota: datePart(payload.tgl_nota),
        nama_kain: String(payload.nama_kain).trim(),
        lebar_gramasi: String(payload.lebar_gramasi || '').trim() || null,
        no_kendaraan: String(payload.no_kendaraan || '').trim() || null,
        penerima: String(payload.penerima || '').trim() || null,
      }, { emit: false });

      payload.warna.forEach((w) => {
        const rolls = Array.isArray(w.rolls) ? w.rolls : [];
        const totalUkuran = round2(sum(rolls, (r) => r.ukuran_awal));
        const satuan = w.satuan_ukur || 'Kg';

        const detailWarna = BongDB.insert('Detail_Bahan_Warna', {
          id_penerimaan: penerimaan.id_penerimaan,
          nama_warna: String(w.nama_warna).trim(),
          kode_warna: w.kode_warna ? String(w.kode_warna).trim().toUpperCase() : null,
          total_roll: rolls.length,
          total_ukuran: totalUkuran,
          satuan_ukur: satuan,
        }, { emit: false });

        rolls.forEach((r, idx) => {
          const uk = round2(r.ukuran_awal);
          BongDB.insert('Detail_Roll_Bahan', {
            id_detail_warna: detailWarna.id_detail_warna,
            no_roll_ke: idx + 1,
            ukuran_awal: uk,
            ukuran_sisa: uk,
            satuan,
            status_roll: 'Tersedia',
          }, { emit: false });
        });
      });

      BongDB.save();
      return penerimaan;
    },

    updatePenerimaan(id, payload) {
      guardWritable();
      const p = BongDB.find('Penerimaan_Bahan', id);
      if (!p) throw new Error('Nota penerimaan tidak ditemukan.');
      return BongDB.update('Penerimaan_Bahan', id, {
        id_supplier: Number(payload.id_supplier),
        no_nota: String(payload.no_nota).trim(),
        tgl_nota: datePart(payload.tgl_nota),
        nama_kain: String(payload.nama_kain).trim(),
        lebar_gramasi: String(payload.lebar_gramasi || '').trim() || null,
        no_kendaraan: String(payload.no_kendaraan || '').trim() || null,
        penerima: String(payload.penerima || '').trim() || null,
      });
    },

    updateWarna(id, payload) {
      guardWritable();
      const w = BongDB.find('Detail_Bahan_Warna', id);
      if (!w) throw new Error('Kelompok warna tidak ditemukan.');
      const satuan = payload.satuan_ukur || w.satuan_ukur;
      BongDB.update('Detail_Bahan_Warna', id, {
        nama_warna: String(payload.nama_warna).trim(),
        kode_warna: payload.kode_warna ? String(payload.kode_warna).trim().toUpperCase() : null,
        satuan_ukur: satuan,
      }, { emit: false });
      BongQ.rollsOfWarna(id).forEach((r) => {
        BongDB.update('Detail_Roll_Bahan', r.id_roll, { satuan }, { emit: false });
      });
      BongDB.save();
    },

    addRollToWarna(idDetailWarna, ukuranAwal) {
      guardWritable();
      const w = BongDB.find('Detail_Bahan_Warna', idDetailWarna);
      if (!w) throw new Error('Kelompok warna tidak ditemukan.');
      const uk = round2(ukuranAwal);
      if (isNaN(uk) || uk < 0.1) throw new Error('Ukuran awal roll minimal 0.1.');
      const existingRolls = BongQ.rollsOfWarna(idDetailWarna);
      const nextNo = existingRolls.reduce((m, r) => Math.max(m, Number(r.no_roll_ke) || 0), 0) + 1;
      const roll = BongDB.insert('Detail_Roll_Bahan', {
        id_detail_warna: Number(idDetailWarna),
        no_roll_ke: nextNo,
        ukuran_awal: uk,
        ukuran_sisa: uk,
        satuan: w.satuan_ukur || 'Kg',
        status_roll: 'Tersedia',
      }, { emit: false });
      BongQ.recalcWarnaTotals(idDetailWarna);
      BongDB.save();
      return roll;
    },

    updateRoll(idRoll, payload) {
      guardWritable();
      const roll = BongDB.find('Detail_Roll_Bahan', idRoll);
      if (!roll) throw new Error('Roll kain tidak ditemukan.');
      const ukAwal = round2(payload.ukuran_awal);
      let ukSisa = round2(payload.ukuran_sisa);
      let status = payload.status_roll;
      if (ukSisa <= 0) {
        ukSisa = 0;
        status = 'Habis';
      }
      BongDB.update('Detail_Roll_Bahan', idRoll, {
        no_roll_ke: Number(payload.no_roll_ke),
        ukuran_awal: ukAwal,
        ukuran_sisa: ukSisa,
        satuan: payload.satuan,
        status_roll: status,
      }, { emit: false });

      const warna = BongDB.find('Detail_Bahan_Warna', roll.id_detail_warna);
      if (warna) {
        const siblingRolls = BongQ.rollsOfWarna(warna.id_detail_warna);
        BongDB.update('Detail_Bahan_Warna', warna.id_detail_warna, {
          total_roll: siblingRolls.length,
          total_ukuran: round2(sum(siblingRolls, (r) => r.ukuran_awal)),
          satuan_ukur: payload.satuan,
        }, { emit: false });
      }
      BongDB.save();
    },

    deletePenerimaan(id) {
      guardWritable();
      const pid = Number(id);
      const p = BongDB.find('Penerimaan_Bahan', pid);
      if (!p) throw new Error('Nota penerimaan tidak ditemukan.');
      const warnaIds = BongQ.warnaOfPenerimaan(pid).map((w) => w.id_detail_warna);
      const rollIds = BongDB.where('Detail_Roll_Bahan', (r) => warnaIds.includes(r.id_detail_warna)).map((r) => r.id_roll);

      const usedPw = warnaIds.length > 0 && BongDB.where('Detail_Pemotongan_Warna', (pw) => warnaIds.includes(pw.id_detail_warna)).length > 0;
      const usedGel = rollIds.length > 0 && BongDB.where('Detail_Gelaran_Kain', (g) => rollIds.includes(g.id_roll)).length > 0;
      if (usedPw || usedGel) {
        throw new Error(`Nota #${p.id_penerimaan} (${p.no_nota}) tidak dapat dihapus karena kainnya sudah digunakan pada proses Pemotongan Seri.`);
      }

      BongDB.where('Pesanan', (ord) => Number(ord.id_penerimaan) === pid).forEach((ord) => {
        BongDB.update('Pesanan', ord.id_pesanan, { id_penerimaan: null }, { emit: false });
      });
      if (warnaIds.length > 0) {
        BongDB.removeWhere('Detail_Roll_Bahan', (r) => warnaIds.includes(r.id_detail_warna), { emit: false });
        BongDB.removeWhere('Detail_Bahan_Warna', (w) => Number(w.id_penerimaan) === pid, { emit: false });
      }
      BongDB.remove('Penerimaan_Bahan', pid);
    },

    deleteWarna(idDetailWarna) {
      guardWritable();
      const wid = Number(idDetailWarna);
      const w = BongDB.find('Detail_Bahan_Warna', wid);
      if (!w) throw new Error('Kelompok warna tidak ditemukan.');
      const rollIds = BongQ.rollsOfWarna(wid).map((r) => r.id_roll);
      const usedPw = BongDB.where('Detail_Pemotongan_Warna', (pw) => Number(pw.id_detail_warna) === wid).length > 0;
      const usedGel = rollIds.length > 0 && BongDB.where('Detail_Gelaran_Kain', (g) => rollIds.includes(g.id_roll)).length > 0;
      if (usedPw || usedGel) {
        throw new Error(`Kelompok warna '${w.nama_warna}' tidak dapat dihapus karena sudah digunakan pada proses Pemotongan Seri.`);
      }
      BongDB.removeWhere('Detail_Roll_Bahan', (r) => Number(r.id_detail_warna) === wid, { emit: false });
      BongDB.remove('Detail_Bahan_Warna', wid);
    },

    deleteRoll(idRoll) {
      guardWritable();
      const rid = Number(idRoll);
      const roll = BongDB.find('Detail_Roll_Bahan', rid);
      if (!roll) throw new Error('Roll tidak ditemukan.');
      if (BongDB.where('Detail_Gelaran_Kain', (g) => Number(g.id_roll) === rid).length > 0) {
        throw new Error(`Roll #${roll.no_roll_ke} (#${roll.id_roll}) tidak dapat dihapus karena sudah digunakan pada gelaran Pemotongan Seri.`);
      }
      const wid = roll.id_detail_warna;
      BongDB.remove('Detail_Roll_Bahan', rid, { emit: false });
      BongQ.recalcWarnaTotals(wid);
      BongDB.save();
    },

    /* --- PEMOTONGAN SERI & GELARAN --- */
    createPemotongan(payload) {
      guardWritable();
      if (!payload.id_pesanan) throw new Error('Silakan pilih Pesanan Target terlebih dahulu.');
      if (!payload.kode_seri || !String(payload.kode_seri).trim()) throw new Error('Kode Seri Potong wajib diisi.');

      const potong = BongDB.insert('Pemotongan_Seri', {
        id_pesanan: Number(payload.id_pesanan),
        id_user_pemotong: Number(payload.id_user_pemotong),
        kode_seri: String(payload.kode_seri).trim(),
        jenis_variasi: String(payload.jenis_variasi || '').trim() || null,
        total_lembar_hasil: Number(payload.total_lembar_hasil),
        tgl_potong: payload.tgl_potong ? toInputDateTime(payload.tgl_potong).replace('T', ' ') + ':00' : nowSql(),
      }, { emit: false });

      (payload.potong_warna || []).forEach((pw) => {
        const detailPw = BongDB.insert('Detail_Pemotongan_Warna', {
          id_potong: potong.id_potong,
          id_detail_warna: Number(pw.id_detail_warna),
          total_lembar_direncanakan: Number(pw.total_lembar_direncanakan),
          kain_terpakai: round2(pw.kain_terpakai),
        }, { emit: false });

        const gelaranList = pw.gelaran || [];
        const gelaranCount = Math.max(1, gelaranList.length);
        const perRollKain = round2(Number(pw.kain_terpakai) / gelaranCount);

        gelaranList.forEach((g) => {
          BongDB.insert('Detail_Gelaran_Kain', {
            id_potong_warna: detailPw.id_potong_warna,
            id_roll: Number(g.id_roll),
            panjang_gelar: round2(g.panjang_gelar),
            jumlah_tumpuk: Number(g.jumlah_tumpuk),
            total_lembar_gelar: Number(g.total_lembar_gelar),
          }, { emit: false });

          const roll = BongDB.find('Detail_Roll_Bahan', g.id_roll);
          if (roll) {
            const deduction = perRollKain > 0 ? perRollKain : round2(Number(g.panjang_gelar) * Number(g.jumlah_tumpuk) * 0.15);
            const newSisa = Math.max(0, round2(Number(roll.ukuran_sisa) - deduction));
            if (g.status_roll_baru === 'Habis' || newSisa <= 0.05) {
              BongDB.update('Detail_Roll_Bahan', roll.id_roll, { ukuran_sisa: 0, status_roll: 'Habis' }, { emit: false });
            } else {
              BongDB.update('Detail_Roll_Bahan', roll.id_roll, { ukuran_sisa: newSisa, status_roll: 'Dipotong' }, { emit: false });
            }
          }
        });
      });

      const order = BongDB.find('Pesanan', payload.id_pesanan);
      if (order && ['antrean', 'pending'].includes(String(order.status_pesanan || '').toLowerCase())) {
        BongDB.update('Pesanan', order.id_pesanan, { status_pesanan: 'Pemotongan' }, { emit: false });
      }

      BongDB.save();
      return potong;
    },

    updatePemotongan(id, payload) {
      guardWritable();
      const potong = BongDB.find('Pemotongan_Seri', id);
      if (!potong) throw new Error('Data pemotongan tidak ditemukan.');

      BongDB.update('Pemotongan_Seri', id, {
        id_pesanan: Number(payload.id_pesanan),
        id_user_pemotong: Number(payload.id_user_pemotong),
        kode_seri: String(payload.kode_seri).trim(),
        jenis_variasi: String(payload.jenis_variasi || '').trim() || null,
        tgl_potong: payload.tgl_potong ? toInputDateTime(payload.tgl_potong).replace('T', ' ') + ':00' : potong.tgl_potong,
        total_lembar_hasil: Number(payload.total_lembar_hasil),
      }, { emit: false });

      let detailPw = BongDB.where('Detail_Pemotongan_Warna', (w) => Number(w.id_potong) === Number(id))[0];
      const oldKain = detailPw ? Number(detailPw.kain_terpakai) || 0 : 0;
      const newKain = payload.kain_terpakai !== undefined ? round2(payload.kain_terpakai) : oldKain;

      if (detailPw) {
        BongDB.update('Detail_Pemotongan_Warna', detailPw.id_potong_warna, {
          id_detail_warna: payload.id_detail_warna ? Number(payload.id_detail_warna) : detailPw.id_detail_warna,
          total_lembar_direncanakan: payload.total_lembar_direncanakan ? Number(payload.total_lembar_direncanakan) : detailPw.total_lembar_direncanakan,
          kain_terpakai: newKain,
        }, { emit: false });
      } else if (payload.id_detail_warna) {
        detailPw = BongDB.insert('Detail_Pemotongan_Warna', {
          id_potong: Number(id),
          id_detail_warna: Number(payload.id_detail_warna),
          total_lembar_direncanakan: Number(payload.total_lembar_direncanakan || payload.total_lembar_hasil),
          kain_terpakai: newKain,
        }, { emit: false });
      }

      if (detailPw) {
        const gelaran = BongDB.where('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(detailPw.id_potong_warna))[0];
        const oldRollId = gelaran ? gelaran.id_roll : null;
        const newRollId = payload.id_roll ? Number(payload.id_roll) : oldRollId;

        if (gelaran) {
          BongDB.update('Detail_Gelaran_Kain', gelaran.id_gelar, {
            id_roll: newRollId,
            panjang_gelar: payload.panjang_gelar !== undefined ? round2(payload.panjang_gelar) : gelaran.panjang_gelar,
            jumlah_tumpuk: payload.jumlah_tumpuk !== undefined ? Number(payload.jumlah_tumpuk) : gelaran.jumlah_tumpuk,
            total_lembar_gelar: payload.total_lembar_gelar !== undefined ? Number(payload.total_lembar_gelar) : gelaran.total_lembar_gelar,
          }, { emit: false });
        } else if (newRollId) {
          BongDB.insert('Detail_Gelaran_Kain', {
            id_potong_warna: detailPw.id_potong_warna,
            id_roll: newRollId,
            panjang_gelar: round2(payload.panjang_gelar || 2.5),
            jumlah_tumpuk: Number(payload.jumlah_tumpuk || 50),
            total_lembar_gelar: Number(payload.total_lembar_gelar || payload.total_lembar_hasil),
          }, { emit: false });
        }

        if (oldRollId && newRollId && Number(oldRollId) !== Number(newRollId)) {
          const oldRoll = BongDB.find('Detail_Roll_Bahan', oldRollId);
          if (oldRoll) {
            const otherOldCuts = BongDB.where('Detail_Gelaran_Kain', (g) => Number(g.id_roll) === Number(oldRollId) && (!gelaran || Number(g.id_gelar) !== Number(gelaran.id_gelar))).length > 0;
            if (!otherOldCuts) {
              BongDB.update('Detail_Roll_Bahan', oldRollId, { ukuran_sisa: oldRoll.ukuran_awal, status_roll: 'Tersedia' }, { emit: false });
            } else {
              const restored = Math.min(Number(oldRoll.ukuran_awal), round2(Number(oldRoll.ukuran_sisa) + oldKain));
              BongDB.update('Detail_Roll_Bahan', oldRollId, {
                ukuran_sisa: restored,
                status_roll: restored >= Number(oldRoll.ukuran_awal) - 0.01 ? 'Tersedia' : 'Dipotong',
              }, { emit: false });
            }
          }
          const newRoll = BongDB.find('Detail_Roll_Bahan', newRollId);
          if (newRoll) {
            const statusReq = payload.status_roll_baru || 'Dipotong';
            const sisa = Math.max(0, round2(Number(newRoll.ukuran_sisa) - newKain));
            if (statusReq === 'Habis' || sisa <= 0.05) {
              BongDB.update('Detail_Roll_Bahan', newRollId, { ukuran_sisa: 0, status_roll: 'Habis' }, { emit: false });
            } else {
              BongDB.update('Detail_Roll_Bahan', newRollId, { ukuran_sisa: sisa, status_roll: statusReq }, { emit: false });
            }
          }
        } else if (newRollId) {
          const roll = BongDB.find('Detail_Roll_Bahan', newRollId);
          if (roll) {
            const wasHabis = roll.status_roll === 'Habis';
            const diff = newKain - oldKain;
            let adjustedSisa = Math.max(0, Math.min(Number(roll.ukuran_awal), round2(Number(roll.ukuran_sisa) - diff)));
            const statusReq = payload.status_roll_baru || roll.status_roll;
            if (statusReq === 'Habis') {
              BongDB.update('Detail_Roll_Bahan', newRollId, { ukuran_sisa: 0, status_roll: 'Habis' }, { emit: false });
            } else if (statusReq === 'Tersedia') {
              BongDB.update('Detail_Roll_Bahan', newRollId, { ukuran_sisa: Number(roll.ukuran_awal), status_roll: 'Tersedia' }, { emit: false });
            } else {
              if (wasHabis && Number(roll.ukuran_sisa) <= 0.05) {
                adjustedSisa = Math.max(0, round2(Number(roll.ukuran_awal) - newKain));
              }
              BongDB.update('Detail_Roll_Bahan', newRollId, { ukuran_sisa: adjustedSisa, status_roll: statusReq }, { emit: false });
            }
          }
        }
      }

      BongDB.save();
    },

    deletePemotongan(id, reverseRoll = true) {
      guardWritable();
      const pid = Number(id);
      const pwList = BongDB.where('Detail_Pemotongan_Warna', (pw) => Number(pw.id_potong) === pid);
      pwList.forEach((pw) => {
        const gelaranList = BongDB.where('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna));
        const gelaranCount = Math.max(1, gelaranList.length);
        const perRollKain = round2((Number(pw.kain_terpakai) || 0) / gelaranCount);
        const gelarIds = new Set(gelaranList.map((g) => Number(g.id_gelar)));

        if (reverseRoll) {
          gelaranList.forEach((gk) => {
            const roll = BongDB.find('Detail_Roll_Bahan', gk.id_roll);
            if (!roll) return;
            const otherCutsExist = BongDB.where('Detail_Gelaran_Kain', (g) => Number(g.id_roll) === Number(gk.id_roll) && !gelarIds.has(Number(g.id_gelar))).length > 0;
            if (!otherCutsExist) {
              BongDB.update('Detail_Roll_Bahan', roll.id_roll, { ukuran_sisa: Number(roll.ukuran_awal), status_roll: 'Tersedia' }, { emit: false });
            } else {
              const restoreAmt = perRollKain > 0 ? perRollKain : round2(Number(gk.panjang_gelar) * Number(gk.jumlah_tumpuk) * 0.15);
              const restored = Math.min(Number(roll.ukuran_awal), round2(Number(roll.ukuran_sisa) + restoreAmt));
              BongDB.update('Detail_Roll_Bahan', roll.id_roll, {
                ukuran_sisa: restored,
                status_roll: restored >= Number(roll.ukuran_awal) - 0.01 ? 'Tersedia' : 'Dipotong',
              }, { emit: false });
            }
          });
        }

        BongDB.removeWhere('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna), { emit: false });
        BongDB.remove('Detail_Pemotongan_Warna', pw.id_potong_warna, { emit: false });
      });

      BongDB.remove('Pemotongan_Seri', pid);
    },

    /* --- QUALITY CONTROL (QC) --- */
    createQc(payload) {
      guardWritable();
      const idPesanan = Number(payload.id_pesanan);
      const order = BongDB.find('Pesanan', idPesanan);
      if (!order) throw new Error('Silakan pilih Pesanan Target terlebih dahulu.');

      const diperiksa = Number(payload.jumlah_diperiksa) || 0;
      const lolos = Number(payload.jumlah_lolos) || 0;
      const cacat = Number(payload.jumlah_cacat) || 0;
      if (diperiksa < 1) throw new Error('Jumlah Diperiksa minimal 1 Pcs.');
      if ((lolos + cacat) !== diperiksa) {
        throw new Error(`Validasi Mutu Gagal: Jumlah Lolos (${lolos}) + Jumlah Cacat (${cacat}) harus sama dengan Jumlah Diperiksa (${diperiksa})!`);
      }

      let catatanFinal = String(payload.catatan_qc || '').trim();
      if (payload.pic_rework && String(payload.pic_rework).trim()) {
        catatanFinal = `[PIC: ${String(payload.pic_rework).trim()}] ${catatanFinal}`.trim();
      }

      const qc = BongDB.insert('QC', {
        id_pesanan: idPesanan,
        id_user_qc: Number(payload.id_user_qc),
        tahap_qc: payload.tahap_qc,
        tgl_pemeriksaan: payload.tgl_pemeriksaan ? toInputDateTime(payload.tgl_pemeriksaan).replace('T', ' ') + ':00' : nowSql(),
        jumlah_diperiksa: diperiksa,
        jumlah_lolos: lolos,
        jumlah_cacat: cacat,
        jenis_cacat: payload.jenis_cacat || 'Tidak Ada',
        catatan_qc: catatanFinal || null,
        status_qc: payload.status_qc || 'Lolos QC',
        tgl_target_perbaikan: datePart(payload.tgl_target_perbaikan) || null,
        tgl_selesai_perbaikan: payload.tgl_selesai_perbaikan ? toInputDateTime(payload.tgl_selesai_perbaikan).replace('T', ' ') + ':00' : null,
      }, { emit: false });

      let allocatedMsg = '';
      if (BongQ.isProdukAbadi(order) && payload.tahap_qc === 'Jahit Akhir' && lolos > 0) {
        const idPola = order.id_pola;
        const specs = BongQ.specsOfPola(idPola);
        const idSpec = payload.id_spesifikasi ? Number(payload.id_spesifikasi) : (specs[0]?.id_spesifikasi || null);
        const warna = String(payload.nama_warna || '').trim() || 'Hitam';

        if (idSpec) {
          let stock = BongDB.all('Stok_Barang_Jadi').find(
            (s) => Number(s.id_pola) === Number(idPola) && Number(s.id_spesifikasi) === Number(idSpec) && s.nama_warna === warna
          );
          const stokSebelum = stock ? Number(stock.jumlah_stok_pcs) : 0;
          const stokSesudah = stokSebelum + lolos;

          if (stock) {
            stock = BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
              jumlah_stok_pcs: stokSesudah,
              tgl_update_terakhir: nowSql(),
            }, { emit: false });
          } else {
            stock = BongDB.insert('Stok_Barang_Jadi', {
              id_pola: idPola,
              id_spesifikasi: idSpec,
              nama_warna: warna,
              jumlah_stok_pcs: stokSesudah,
              tgl_update_terakhir: nowSql(),
            }, { emit: false });
          }

          BongDB.insert('Riwayat_Stok_Barang_Jadi', {
            id_stok: stock.id_stok,
            id_pola: idPola,
            id_spesifikasi: idSpec,
            nama_warna: warna,
            jenis_transaksi: 'MASUK',
            jumlah_pcs: lolos,
            stok_sebelum: stokSebelum,
            stok_sesudah: stokSesudah,
            kategori_alasan: `Lolos QC Jahit Akhir (Pesanan #${order.id_pesanan})`,
            nama_mitra: order.nama_mitra || null,
            keterangan: `Masuk otomatis dari hasil inspeksi QC #${qc.id_qc}`,
            id_user: Number(payload.id_user_qc),
            tgl_transaksi: nowSql(),
          }, { emit: false });

          const specObj = BongQ.spec(idSpec);
          allocatedMsg = ` Dialokasikan +${lolos} Pcs ke Stok_Barang_Jadi (Ukuran ${specObj?.nama_ukuran || 'All'}, Warna ${warna}).`;
        }
      }

      if (payload.status_qc === 'Need Rework') {
        BongDB.update('Pesanan', order.id_pesanan, { status_pesanan: 'Dalam QC' }, { emit: false });
      } else if (payload.tahap_qc === 'Jahit Akhir' && payload.status_qc === 'Lolos QC') {
        BongDB.update('Pesanan', order.id_pesanan, { status_pesanan: 'Selesai' }, { emit: false });
      }

      BongDB.save();
      return {
        qc,
        message: `Inspeksi QC (${qc.tahap_qc}) berhasil dicatat. Status: ${qc.status_qc}.${allocatedMsg}`,
      };
    },

    completeRework(idQc, tglSelesai) {
      guardWritable();
      const qc = BongDB.find('QC', idQc);
      if (!qc) throw new Error('Data QC tidak ditemukan.');
      const dtSql = tglSelesai ? toInputDateTime(tglSelesai).replace('T', ' ') + ':00' : nowSql();
      return BongDB.update('QC', idQc, {
        status_qc: 'Rework Selesai',
        tgl_selesai_perbaikan: dtSql,
      });
    },

    updateQc(idQc, payload) {
      guardWritable();
      const qc = BongDB.find('QC', idQc);
      if (!qc) throw new Error('Data QC tidak ditemukan.');
      const diperiksa = Number(payload.jumlah_diperiksa) || 0;
      const lolos = Number(payload.jumlah_lolos) || 0;
      const cacat = Number(payload.jumlah_cacat) || 0;
      if ((lolos + cacat) !== diperiksa) {
        throw new Error(`Validasi Mutu Gagal: Jumlah Lolos (${lolos}) + Jumlah Cacat (${cacat}) harus sama dengan Jumlah Diperiksa (${diperiksa})!`);
      }

      const oldOrder = BongDB.find('Pesanan', qc.id_pesanan);
      const newOrder = BongDB.find('Pesanan', payload.id_pesanan);
      const oldLolosInStok = (BongQ.isProdukAbadi(oldOrder) && qc.tahap_qc === 'Jahit Akhir') ? Number(qc.jumlah_lolos) : 0;
      const newLolosInStok = (BongQ.isProdukAbadi(newOrder) && payload.tahap_qc === 'Jahit Akhir') ? lolos : 0;
      const deltaStok = newLolosInStok - oldLolosInStok;

      BongDB.update('QC', idQc, {
        id_pesanan: Number(payload.id_pesanan),
        id_user_qc: Number(payload.id_user_qc),
        tahap_qc: payload.tahap_qc,
        tgl_pemeriksaan: payload.tgl_pemeriksaan ? toInputDateTime(payload.tgl_pemeriksaan).replace('T', ' ') + ':00' : qc.tgl_pemeriksaan,
        jumlah_diperiksa: diperiksa,
        jumlah_lolos: lolos,
        jumlah_cacat: cacat,
        jenis_cacat: payload.jenis_cacat,
        catatan_qc: String(payload.catatan_qc || '').trim() || null,
        status_qc: payload.status_qc,
        tgl_target_perbaikan: datePart(payload.tgl_target_perbaikan) || null,
        tgl_selesai_perbaikan: payload.tgl_selesai_perbaikan ? toInputDateTime(payload.tgl_selesai_perbaikan).replace('T', ' ') + ':00' : null,
      }, { emit: false });

      if (deltaStok !== 0 && newOrder && BongQ.isProdukAbadi(newOrder)) {
        const stock = BongDB.all('Stok_Barang_Jadi').find((s) => Number(s.id_pola) === Number(newOrder.id_pola));
        if (stock) {
          BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
            jumlah_stok_pcs: Math.max(0, Number(stock.jumlah_stok_pcs) + deltaStok),
            tgl_update_terakhir: nowSql(),
          }, { emit: false });
        } else if (deltaStok > 0) {
          const spec = BongQ.specsOfPola(newOrder.id_pola)[0];
          if (spec) {
            BongDB.insert('Stok_Barang_Jadi', {
              id_pola: newOrder.id_pola,
              id_spesifikasi: spec.id_spesifikasi,
              nama_warna: 'Hitam',
              jumlah_stok_pcs: deltaStok,
              tgl_update_terakhir: nowSql(),
            }, { emit: false });
          }
        }
      } else if (oldLolosInStok > 0 && newLolosInStok === 0 && oldOrder) {
        const stock = BongDB.all('Stok_Barang_Jadi').find((s) => Number(s.id_pola) === Number(oldOrder.id_pola));
        if (stock) {
          BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
            jumlah_stok_pcs: Math.max(0, Number(stock.jumlah_stok_pcs) - oldLolosInStok),
            tgl_update_terakhir: nowSql(),
          }, { emit: false });
        }
      }

      BongDB.save();
    },

    deleteQc(idQc) {
      guardWritable();
      const qid = Number(idQc);
      const qc = BongDB.find('QC', qid);
      if (!qc) throw new Error('Data QC tidak ditemukan.');
      const order = BongDB.find('Pesanan', qc.id_pesanan);

      if (qc.tahap_qc === 'Jahit Akhir' && Number(qc.jumlah_lolos) > 0) {
        const autoKey = `Masuk otomatis dari hasil inspeksi QC #${qc.id_qc}`;
        const riwayatAuto = BongDB.all('Riwayat_Stok_Barang_Jadi').find((r) => r.keterangan === autoKey);
        if (riwayatAuto) {
          const stock = BongDB.find('Stok_Barang_Jadi', riwayatAuto.id_stok) ||
            BongDB.all('Stok_Barang_Jadi').find(
              (s) => Number(s.id_pola) === Number(riwayatAuto.id_pola) && Number(s.id_spesifikasi) === Number(riwayatAuto.id_spesifikasi) && s.nama_warna === riwayatAuto.nama_warna
            );
          if (stock) {
            BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
              jumlah_stok_pcs: Math.max(0, Number(stock.jumlah_stok_pcs) - Number(riwayatAuto.jumlah_pcs)),
              tgl_update_terakhir: nowSql(),
            }, { emit: false });
          }
          BongDB.remove('Riwayat_Stok_Barang_Jadi', riwayatAuto.id_riwayat_stok, { emit: false });
        } else if (order && BongQ.isProdukAbadi(order)) {
          const stock = BongDB.all('Stok_Barang_Jadi').find((s) => Number(s.id_pola) === Number(order.id_pola));
          if (stock) {
            BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
              jumlah_stok_pcs: Math.max(0, Number(stock.jumlah_stok_pcs) - Number(qc.jumlah_lolos)),
              tgl_update_terakhir: nowSql(),
            }, { emit: false });
          }
        }
      }

      BongDB.remove('QC', qid);
    },

    /* --- GUDANG BARANG JADI & AKSESORIS --- */
    updateThresholds(minJadi, minAcc) {
      guardWritable();
      const cfg = BongDB.config();
      cfg.thresholds = {
        min_stok_jadi: Math.max(0, parseInt(minJadi, 10) || 0),
        min_stok_aksesoris: Math.max(0, parseInt(minAcc, 10) || 0),
      };
      BongDB.save();
      return cfg.thresholds;
    },

    adjustStokJadi(payload) {
      guardWritable();
      const idPola = Number(payload.id_pola);
      const idSpec = Number(payload.id_spesifikasi);
      const namaWarna = String(payload.nama_warna || '').trim();
      const deltaPcs = Number(payload.delta_pcs) || 0;
      const alasan = String(payload.alasan_penyesuaian || 'Transaksi Gudang').trim();
      const namaMitra = String(payload.nama_mitra || '').trim() || null;
      const keterangan = String(payload.keterangan || '').trim() || null;

      if (!idPola || !idSpec || !namaWarna || deltaPcs === 0) {
        throw new Error('Harap lengkapi seluruh kolom penyesuaian stok barang jadi.');
      }
      if (alasan.toLowerCase().includes('mitra') && !namaMitra) {
        throw new Error('Nama Mitra wajib diisi ketika mencatat pengambilan barang oleh Mitra.');
      }

      let stock = BongDB.all('Stok_Barang_Jadi').find(
        (s) => Number(s.id_pola) === idPola && Number(s.id_spesifikasi) === idSpec && s.nama_warna === namaWarna
      );
      const stokSebelum = stock ? Number(stock.jumlah_stok_pcs) : 0;
      const stokSesudah = stokSebelum + deltaPcs;
      if (stokSesudah < 0) {
        throw new Error(`Stok barang jadi tidak mencukupi (tersedia ${stokSebelum} pcs, tidak boleh bernilai negatif).`);
      }

      if (stock) {
        stock = BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
          jumlah_stok_pcs: stokSesudah,
          tgl_update_terakhir: nowSql(),
        }, { emit: false });
      } else {
        stock = BongDB.insert('Stok_Barang_Jadi', {
          id_pola: idPola,
          id_spesifikasi: idSpec,
          nama_warna: namaWarna,
          jumlah_stok_pcs: stokSesudah,
          tgl_update_terakhir: nowSql(),
        }, { emit: false });
      }

      BongDB.insert('Riwayat_Stok_Barang_Jadi', {
        id_stok: stock.id_stok,
        id_pola: idPola,
        id_spesifikasi: idSpec,
        nama_warna: namaWarna,
        jenis_transaksi: deltaPcs < 0 ? 'KELUAR' : 'MASUK',
        jumlah_pcs: Math.abs(deltaPcs),
        stok_sebelum: stokSebelum,
        stok_sesudah: stokSesudah,
        kategori_alasan: alasan,
        nama_mitra: namaMitra,
        keterangan: keterangan,
        id_user: BongAuth.getUser()?.id_user || 1,
        tgl_transaksi: nowSql(),
      }, { emit: false });

      BongDB.save();
      return stock;
    },

    updateStokJadi(idStok, payload) {
      guardWritable();
      const stock = BongDB.find('Stok_Barang_Jadi', idStok);
      if (!stock) throw new Error('Data stok barang jadi tidak ditemukan.');
      const stokSebelum = Number(stock.jumlah_stok_pcs);
      const stokSesudah = Math.max(0, Number(payload.jumlah_stok_pcs) || 0);
      const delta = stokSesudah - stokSebelum;

      BongDB.update('Stok_Barang_Jadi', idStok, {
        id_pola: Number(payload.id_pola),
        id_spesifikasi: Number(payload.id_spesifikasi),
        nama_warna: String(payload.nama_warna).trim(),
        jumlah_stok_pcs: stokSesudah,
        tgl_update_terakhir: nowSql(),
      }, { emit: false });

      if (delta !== 0) {
        BongDB.insert('Riwayat_Stok_Barang_Jadi', {
          id_stok: stock.id_stok,
          id_pola: Number(payload.id_pola),
          id_spesifikasi: Number(payload.id_spesifikasi),
          nama_warna: String(payload.nama_warna).trim(),
          jenis_transaksi: delta < 0 ? 'KELUAR' : 'MASUK',
          jumlah_pcs: Math.abs(delta),
          stok_sebelum: stokSebelum,
          stok_sesudah: stokSesudah,
          kategori_alasan: 'Revisi / Koreksi Data Stok Gudang',
          nama_mitra: null,
          keterangan: 'Edit langsung pada tabel Stok Barang Jadi',
          id_user: BongAuth.getUser()?.id_user || 1,
          tgl_transaksi: nowSql(),
        }, { emit: false });
      }
      BongDB.save();
    },

    deleteStokJadi(idStok) {
      guardWritable();
      const sid = Number(idStok);
      BongDB.removeWhere('Riwayat_Stok_Barang_Jadi', (r) => Number(r.id_stok) === sid, { emit: false });
      BongDB.remove('Stok_Barang_Jadi', sid);
    },

    deleteRiwayatStok(idRiwayat) {
      guardWritable();
      const rid = Number(idRiwayat);
      const rw = BongDB.find('Riwayat_Stok_Barang_Jadi', rid);
      if (!rw) throw new Error('Riwayat transaksi tidak ditemukan.');

      let stock = rw.id_stok ? BongDB.find('Stok_Barang_Jadi', rw.id_stok) : null;
      if (!stock) {
        stock = BongDB.all('Stok_Barang_Jadi').find(
          (s) => Number(s.id_pola) === Number(rw.id_pola) && Number(s.id_spesifikasi) === Number(rw.id_spesifikasi) && s.nama_warna === rw.nama_warna
        );
      }

      const qty = Number(rw.jumlah_pcs) || 0;
      if (rw.jenis_transaksi === 'KELUAR') {
        if (!stock) {
          BongDB.insert('Stok_Barang_Jadi', {
            id_pola: rw.id_pola,
            id_spesifikasi: rw.id_spesifikasi,
            nama_warna: rw.nama_warna,
            jumlah_stok_pcs: qty,
            tgl_update_terakhir: nowSql(),
          }, { emit: false });
        } else {
          BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
            jumlah_stok_pcs: Number(stock.jumlah_stok_pcs) + qty,
            tgl_update_terakhir: nowSql(),
          }, { emit: false });
        }
      } else if (stock) {
        BongDB.update('Stok_Barang_Jadi', stock.id_stok, {
          jumlah_stok_pcs: Math.max(0, Number(stock.jumlah_stok_pcs) - qty),
          tgl_update_terakhir: nowSql(),
        }, { emit: false });
      }

      BongDB.remove('Riwayat_Stok_Barang_Jadi', rid);
    },

    createAksesoris(payload) {
      guardWritable();
      return BongDB.insert('Aksesoris', {
        id_supplier: Number(payload.id_supplier),
        nama_aksesoris: String(payload.nama_aksesoris).trim(),
        ukuran: String(payload.ukuran || '').trim() || null,
        stok: Math.max(0, Number(payload.stok) || 0),
        satuan: String(payload.satuan).trim(),
      });
    },

    updateAksesoris(id, payload) {
      guardWritable();
      return BongDB.update('Aksesoris', id, {
        id_supplier: Number(payload.id_supplier),
        nama_aksesoris: String(payload.nama_aksesoris).trim(),
        ukuran: String(payload.ukuran || '').trim() || null,
        stok: Math.max(0, Number(payload.stok) || 0),
        satuan: String(payload.satuan).trim(),
      });
    },

    quickStockAksesoris(id, delta) {
      guardWritable();
      const acc = BongDB.find('Aksesoris', id);
      if (!acc) throw new Error('Aksesoris tidak ditemukan.');
      const next = Number(acc.stok) + Number(delta);
      if (next < 0) throw new Error('Stok tidak mencukupi untuk pengurangan ini.');
      return BongDB.update('Aksesoris', id, { stok: next });
    },

    deleteAksesoris(id) {
      guardWritable();
      const aid = Number(id);
      BongDB.removeWhere('Detail_Aksesoris_Pola', (d) => Number(d.id_aksesoris) === aid, { emit: false });
      BongDB.remove('Aksesoris', aid);
    },

    _upsertPolaAcc(idPola, idAcc, qty) {
      const pid = Number(idPola);
      const aid = Number(idAcc);
      const q = Math.max(1, Number(qty) || 1);
      const existing = BongDB.all('Detail_Aksesoris_Pola').find(
        (d) => Number(d.id_pola) === pid && Number(d.id_aksesoris) === aid
      );
      if (existing) {
        existing.jumlah_digunakan = q;
      } else {
        BongDB.data.Detail_Aksesoris_Pola.push({ id_pola: pid, id_aksesoris: aid, jumlah_digunakan: q });
      }
    },

    savePolaAksesorisMapping(idPola, idAcc, qty) {
      guardWritable();
      this._upsertPolaAcc(idPola, idAcc, qty);
      BongDB.save();
    },

    deletePolaAksesorisMapping(idPola, idAcc) {
      guardWritable();
      const pid = Number(idPola);
      const aid = Number(idAcc);
      BongDB.removeWhere('Detail_Aksesoris_Pola', (d) => Number(d.id_pola) === pid && Number(d.id_aksesoris) === aid);
    },

    /* --- MASTER DATA & PENGATURAN --- */
    createUser(payload) {
      guardWritable();
      const uname = String(payload.username || '').trim();
      const nama = String(payload.nama_user || '').trim();
      const pw = String(payload.password || '');
      if (!uname || !nama || pw.length < 4) throw new Error('Nama, username, dan password (minimal 4 karakter) wajib diisi.');
      if (BongDB.all('User').some((u) => u.username.toLowerCase() === uname.toLowerCase())) {
        throw new Error(`Username "${uname}" sudah digunakan.`);
      }
      return BongDB.insert('User', { username: uname, password: pw, nama_user: nama, role: payload.role });
    },

    updateUser(id, payload) {
      guardWritable();
      const user = BongDB.find('User', id);
      if (!user) throw new Error('Pengguna tidak ditemukan.');
      const uname = String(payload.username || '').trim();
      const nama = String(payload.nama_user || '').trim();
      if (!uname || !nama) throw new Error('Nama dan username wajib diisi.');
      if (BongDB.all('User').some((u) => Number(u.id_user) !== Number(id) && u.username.toLowerCase() === uname.toLowerCase())) {
        throw new Error(`Username "${uname}" sudah digunakan oleh akun lain.`);
      }
      const patch = { username: uname, nama_user: nama, role: payload.role };
      if (payload.password && String(payload.password).length > 0) {
        if (String(payload.password).length < 4) throw new Error('Password baru minimal 4 karakter.');
        patch.password = String(payload.password);
      }
      return BongDB.update('User', id, patch);
    },

    deleteUser(id) {
      guardWritable();
      const uid = Number(id);
      const user = BongDB.find('User', uid);
      if (!user) throw new Error('Pengguna tidak ditemukan.');
      if (Number(BongAuth.getUser()?.id_user) === uid) {
        throw new Error('Tidak dapat menghapus akun yang sedang Anda gunakan untuk login saat ini.');
      }
      if (user.role === 'Admin' && BongDB.where('User', (u) => u.role === 'Admin').length <= 1) {
        throw new Error('Tidak dapat menghapus satu-satunya akun Admin yang tersisa di sistem.');
      }
      const linked =
        BongDB.where('Pesanan', (p) => Number(p.id_user_admin) === uid).length > 0 ||
        BongDB.where('Pemotongan_Seri', (p) => Number(p.id_user_pemotong) === uid).length > 0 ||
        BongDB.where('QC', (q) => Number(q.id_user_qc) === uid).length > 0;
      if (linked) {
        throw new Error('Akun tidak dapat dihapus karena masih terhubung dengan riwayat pesanan/pemotongan/QC.');
      }
      BongDB.remove('User', uid);
    },

    createKategori(nama) {
      guardWritable();
      const n = String(nama || '').trim();
      if (!n) throw new Error('Nama kategori wajib diisi.');
      return BongDB.insert('Kategori_Pola', { nama_kategori: n });
    },
    updateKategori(id, nama) {
      guardWritable();
      const n = String(nama || '').trim();
      if (!n) throw new Error('Nama kategori wajib diisi.');
      return BongDB.update('Kategori_Pola', id, { nama_kategori: n });
    },
    deleteKategori(id) {
      guardWritable();
      const kid = Number(id);
      if (BongDB.where('Pola', (p) => Number(p.id_kategori) === kid).length > 0) {
        throw new Error('Kategori ini sedang digunakan oleh Master Pola dan tidak dapat dihapus.');
      }
      BongDB.remove('Kategori_Pola', kid);
    },

    createSumber(nama) {
      guardWritable();
      const n = String(nama || '').trim();
      if (!n) throw new Error('Nama sumber inisiasi wajib diisi.');
      return BongDB.insert('Sumber_Inisiasi', { nama_sumber: n });
    },
    updateSumber(id, nama) {
      guardWritable();
      const n = String(nama || '').trim();
      if (!n) throw new Error('Nama sumber inisiasi wajib diisi.');
      return BongDB.update('Sumber_Inisiasi', id, { nama_sumber: n });
    },
    deleteSumber(id) {
      guardWritable();
      const sid = Number(id);
      if (BongDB.where('Pesanan', (p) => Number(p.id_sumber) === sid).length > 0) {
        throw new Error('Sumber inisiasi ini sedang digunakan pada Pesanan dan tidak dapat dihapus.');
      }
      BongDB.remove('Sumber_Inisiasi', sid);
    },

    createSupplier(payload) {
      guardWritable();
      const nama = String(payload.nama_supplier || '').trim();
      if (!nama) throw new Error('Nama supplier wajib diisi.');
      return BongDB.insert('Supplier', {
        nama_supplier: nama,
        kontak: String(payload.kontak || '').trim() || null,
        alamat: String(payload.alamat || '').trim() || null,
      });
    },
    updateSupplier(id, payload) {
      guardWritable();
      const nama = String(payload.nama_supplier || '').trim();
      if (!nama) throw new Error('Nama supplier wajib diisi.');
      return BongDB.update('Supplier', id, {
        nama_supplier: nama,
        kontak: String(payload.kontak || '').trim() || null,
        alamat: String(payload.alamat || '').trim() || null,
      });
    },
    deleteSupplier(id) {
      guardWritable();
      const sid = Number(id);
      const linked =
        BongDB.where('Penerimaan_Bahan', (p) => Number(p.id_supplier) === sid).length > 0 ||
        BongDB.where('Aksesoris', (a) => Number(a.id_supplier) === sid).length > 0;
      if (linked) {
        throw new Error('Supplier ini masih terhubung dengan nota penerimaan atau aksesoris.');
      }
      BongDB.remove('Supplier', sid);
    },

    resetOperationalData(includeMaster = false) {
      guardWritable();
      const d = BongDB.data;
      d.Riwayat_Stok_Barang_Jadi = [];
      d.QC = [];
      d.Detail_Gelaran_Kain = [];
      d.Detail_Pemotongan_Warna = [];
      d.Pemotongan_Seri = [];
      d.Stok_Barang_Jadi = [];
      d.Pesanan = [];
      d.Detail_Roll_Bahan = [];
      d.Detail_Bahan_Warna = [];
      d.Penerimaan_Bahan = [];
      if (includeMaster) {
        d.Detail_Aksesoris_Pola = [];
        d.Aksesoris = [];
        d.Supplier = [];
        d.Spesifikasi_Ukuran = [];
        d.Pola = [];
      }
      BongDB.save();
      return includeMaster
        ? 'Seluruh data transaksi operasional DAN katalog master (Pola, Aksesoris, Supplier) berhasil dikosongkan. Akun Pengguna tetap tersimpan.'
        : 'Seluruh data transaksi operasional (Pesanan, Nota & Roll Bahan, Pemotongan, QC, Stok Barang Jadi) berhasil dikosongkan. Akun Pengguna dan Master Pola/Aksesoris tetap tersimpan.';
    },

    seedSampleData() {
      guardWritable();
      const currentUsers = BongDB.all('User').map((u) => ({ ...u }));
      const currentCfg = BongDB.config();
      BongDB.data = buildSeed(currentUsers, currentCfg);
      BongDB.save();
      return 'Data contoh produksi berhasil dimuat ulang tanpa mengubah akun pengguna Anda.';
    },

    updateCompanyProfile(profile) {
      guardWritable();
      if (!BongAuth.isAdminAccount() || BongAuth.getEffectiveRole() !== 'Admin') {
        throw new Error('Hanya peran Admin yang dapat mengubah Kop Surat perusahaan.');
      }
      const cfg = BongDB.config();
      cfg.companyProfile = {
        nama_perusahaan: String(profile.nama_perusahaan || '').trim() || 'BONG GARMENT',
        tagline: String(profile.tagline || '').trim() || 'KONVEKSI & MANUFAKTUR PAKAIAN CMT',
        alamat: String(profile.alamat || '').trim(),
        telepon: String(profile.telepon || '').trim(),
        deskripsi_sistem: String(profile.deskripsi_sistem || '').trim(),
        kota_pengesahan: String(profile.kota_pengesahan || '').trim() || 'Jakarta',
      };
      BongDB.save();
      return cfg.companyProfile;
    },

    /* --- EXECUTIVE REPORT ENGINE (mirrors LaporanEksekutifController) --- */
    buildReportData(filters = {}) {
      const allowedJenis = ['KONSOLIDASI', 'STOK', 'BAHAN_MASUK', 'MUTASI_KELUAR', 'QC'];
      let jenis = String(filters.jenis_laporan || 'KONSOLIDASI').toUpperCase();
      if (!allowedJenis.includes(jenis)) jenis = 'KONSOLIDASI';
      const startDate = datePart(filters.start_date);
      const endDate = datePart(filters.end_date);
      const idSumber = filters.id_sumber ? Number(filters.id_sumber) : null;

      const user = BongAuth.getUser();
      const effRole = BongAuth.getEffectiveRole();
      const company = BongDB.config().companyProfile || defaultConfig().companyProfile;
      const thresholds = BongDB.config().thresholds || defaultConfig().thresholds;
      const minStokJadi = Number(thresholds.min_stok_jadi ?? 20);
      const minStokAksesoris = Number(thresholds.min_stok_aksesoris ?? 500);

      const inDateRange = (val) => {
        const d = datePart(val);
        if (!d) return !startDate && !endDate;
        if (startDate && d < startDate) return false;
        if (endDate && d > endDate) return false;
        return true;
      };

      const ordersList = BongDB.all('Pesanan')
        .filter((o) => (!startDate && !endDate ? true : inDateRange(o.tgl_order)))
        .filter((o) => (!idSumber ? true : Number(o.id_sumber) === idSumber))
        .sort((a, b) => Number(a.id_pesanan) - Number(b.id_pesanan));

      const totalOrders = ordersList.length;
      const completedOrders = ordersList.filter((o) => o.status_pesanan === 'Selesai').length;
      const inProgressOrders = ordersList.filter((o) => ['Antrean', 'Pemotongan', 'Dalam QC', 'Jahit', 'Finishing'].includes(o.status_pesanan)).length;

      const qcList = BongDB.all('QC')
        .filter((q) => (!startDate && !endDate ? true : inDateRange(q.tgl_pemeriksaan)))
        .sort((a, b) => String(b.tgl_pemeriksaan || '').localeCompare(String(a.tgl_pemeriksaan || '')) || Number(b.id_qc) - Number(a.id_qc));

      const rawChecked = sum(qcList, (q) => q.jumlah_diperiksa);
      const divisor = rawChecked > 0 ? rawChecked : 1;
      const totalPassed = sum(qcList, (q) => q.jumlah_lolos);
      const totalDefective = sum(qcList, (q) => q.jumlah_cacat);
      const totalReworkPending = qcList.filter((q) => q.status_qc === 'Need Rework').length;
      const passRate = round2((totalPassed / divisor) * 100);
      const defectRate = round2((totalDefective / divisor) * 100);

      const defectMap = { 'Cacat Potong': 0, 'Cacat Jahit': 0, 'Cacat Bahan': 0 };
      qcList.forEach((q) => {
        if (q.jenis_cacat && q.jenis_cacat !== 'Tidak Ada' && q.jenis_cacat in defectMap) {
          defectMap[q.jenis_cacat] += Number(q.jumlah_cacat) || 0;
        }
      });

      const defectBreakdown = [
        { jenis_cacat: 'Cacat Potong', label_lengkap: 'Cacat Potong (Stasiun Pemotongan / Pola)', total_cacat: defectMap['Cacat Potong'] },
        { jenis_cacat: 'Cacat Jahit', label_lengkap: 'Cacat Jahit (Mitra Penjahit CMT)', total_cacat: defectMap['Cacat Jahit'] },
        { jenis_cacat: 'Cacat Bahan', label_lengkap: 'Cacat Bahan / Fabric Stain (Klaim Retur Supplier)', total_cacat: defectMap['Cacat Bahan'] },
      ];

      const stageOrder = ['Prototipe', 'Potong', 'Jahit Akhir'];
      const stageMap = {};
      qcList.forEach((q) => {
        if (!stageMap[q.tahap_qc]) stageMap[q.tahap_qc] = { tahap_qc: q.tahap_qc, total_diperiksa: 0, total_lolos: 0, total_cacat: 0 };
        stageMap[q.tahap_qc].total_diperiksa += Number(q.jumlah_diperiksa) || 0;
        stageMap[q.tahap_qc].total_lolos += Number(q.jumlah_lolos) || 0;
        stageMap[q.tahap_qc].total_cacat += Number(q.jumlah_cacat) || 0;
      });
      const stageBreakdown = Object.values(stageMap).sort((a, b) => stageOrder.indexOf(a.tahap_qc) - stageOrder.indexOf(b.tahap_qc));

      const allPemotongan = BongDB.all('Pemotongan_Seri');
      const allPw = BongDB.all('Detail_Pemotongan_Warna');
      const allGel = BongDB.all('Detail_Gelaran_Kain');
      const rollList = [...BongDB.all('Detail_Roll_Bahan')].sort((a, b) => Number(a.id_roll) - Number(b.id_roll));

      const totalCutSheets = sum(allPemotongan, (p) => p.total_lembar_hasil);
      const totalBatchPotong = allPemotongan.length;
      const distinctGelRollIds = new Set(allGel.map((g) => Number(g.id_roll)));
      let distinctRollsConsumed = distinctGelRollIds.size;
      if (distinctRollsConsumed === 0) {
        distinctRollsConsumed = rollList.filter((r) => r.status_roll === 'Dipotong' || r.status_roll === 'Habis').length;
      }
      const totalFabricConsumed = round2(sum(allPw, (w) => w.kain_terpakai));

      const stokBarangJadiList = [...BongDB.all('Stok_Barang_Jadi')].sort((a, b) => Number(a.id_stok) - Number(b.id_stok));
      const totalFinishedStock = sum(stokBarangJadiList, (s) => s.jumlah_stok_pcs);
      const lowStockJadiCount = stokBarangJadiList.filter((s) => Number(s.jumlah_stok_pcs) <= minStokJadi).length;

      const aksesorisList = [...BongDB.all('Aksesoris')].sort((a, b) => Number(a.id_aksesoris) - Number(b.id_aksesoris));
      const totalAksesorisUnit = sum(aksesorisList, (a) => a.stok);
      const lowStockAksesorisCount = aksesorisList.filter((a) => Number(a.stok) < minStokAksesoris).length;

      const rollsTersedia = rollList.filter((r) => r.status_roll === 'Tersedia');
      const rollsDipotong = rollList.filter((r) => r.status_roll === 'Dipotong');
      const rollsHabis = rollList.filter((r) => r.status_roll === 'Habis');

      const penerimaanList = BongDB.all('Penerimaan_Bahan')
        .filter((p) => (!startDate && !endDate ? true : inDateRange(p.tgl_nota)))
        .sort((a, b) => String(b.tgl_nota || '').localeCompare(String(a.tgl_nota || '')) || Number(b.id_penerimaan) - Number(a.id_penerimaan));

      const riwayatStokList = BongDB.all('Riwayat_Stok_Barang_Jadi')
        .filter((r) => (!startDate && !endDate ? true : inDateRange(r.tgl_transaksi)))
        .sort((a, b) => String(b.tgl_transaksi || '').localeCompare(String(a.tgl_transaksi || '')) || Number(b.id_riwayat_stok) - Number(a.id_riwayat_stok));

      const totalOutboundTrx = riwayatStokList.filter((r) => r.jenis_transaksi === 'KELUAR').length;
      const totalOutboundPcs = sum(riwayatStokList.filter((r) => r.jenis_transaksi === 'KELUAR'), (r) => r.jumlah_pcs);
      const totalInboundPcs = sum(riwayatStokList.filter((r) => r.jenis_transaksi === 'MASUK'), (r) => r.jumlah_pcs);

      const consolidatedOrders = ordersList.map((ord) => {
        const pola = BongQ.pola(ord.id_pola);
        const sumber = BongQ.sumber(ord.id_sumber);
        const seris = allPemotongan.filter((p) => Number(p.id_pesanan) === Number(ord.id_pesanan));
        const rollIds = [];
        let actualYield = 0;
        let kainTerpakaiOrder = 0;

        seris.forEach((seri) => {
          actualYield += Number(seri.total_lembar_hasil) || 0;
          const pws = allPw.filter((w) => Number(w.id_potong) === Number(seri.id_potong));
          pws.forEach((pw) => {
            kainTerpakaiOrder += Number(pw.kain_terpakai) || 0;
            allGel.filter((g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna)).forEach((g) => {
              const tag = `#${g.id_roll}`;
              if (!rollIds.includes(tag)) rollIds.push(tag);
            });
          });
        });

        const ordQcs = BongDB.where('QC', (q) => Number(q.id_pesanan) === Number(ord.id_pesanan));
        return {
          id_pesanan: ord.id_pesanan,
          kode_pola: pola?.kode_pola || '-',
          nama_pola: pola?.nama_pola || ord.jenis_pesanan,
          nama_mitra: ord.nama_mitra || 'Internal (Produk Abadi)',
          nama_sumber: sumber?.nama_sumber || '-',
          tgl_order: ord.tgl_order,
          deadline_terbaru: ord.deadline_terbaru || ord.deadline_awal,
          rolls_used: rollIds.length > 0 ? rollIds.join(', ') : 'Belum Gelar',
          rolls_count: rollIds.length,
          kain_terpakai: round2(kainTerpakaiOrder),
          actual_yield: actualYield,
          qc_lolos: sum(ordQcs, (q) => q.jumlah_lolos),
          qc_cacat: sum(ordQcs, (q) => q.jumlah_cacat),
          status_pesanan: ord.status_pesanan,
        };
      });

      const printerRoleLabel = effRole === 'Pemilik'
        ? `Pemilik ${company.nama_perusahaan}`
        : effRole === 'Admin'
          ? `Admin / Manajemen ${company.nama_perusahaan}`
          : `${effRole} ${company.nama_perusahaan}`;

      return {
        filters: { jenis_laporan: jenis, start_date: startDate, end_date: endDate, id_sumber: idSumber },
        meta: {
          printer_name: user?.nama_user || 'Administrator',
          printer_username: user?.username || 'admin',
          printer_role: effRole,
          printer_role_label: printerRoleLabel,
          printed_at_formatted: fmtNowWib(),
          printed_date_short: fmtDateShortId(todayIso()),
          printed_date_long: fmtDateLongId(todayIso()),
        },
        companyProfile: company,
        thresholds: { min_stok_jadi: minStokJadi, min_stok_aksesoris: minStokAksesoris },
        summary: {
          total_pesanan: totalOrders,
          pesanan_selesai: completedOrders,
          pesanan_proses: inProgressOrders,
          total_roll_dikonsumsi: distinctRollsConsumed,
          total_kain_terpakai: totalFabricConsumed,
          total_lembar_potong: totalCutSheets,
          total_batch_potong: totalBatchPotong,
          total_stok_jadi: totalFinishedStock,
          total_sku_stok_jadi: stokBarangJadiList.length,
          low_stock_jadi_count: lowStockJadiCount,
          total_aksesoris_item: aksesorisList.length,
          total_aksesoris_unit: totalAksesorisUnit,
          low_stock_aksesoris_count: lowStockAksesorisCount,
          total_roll_terdaftar: rollList.length,
          roll_tersedia_count: rollsTersedia.length,
          roll_tersedia_qty: round2(sum(rollsTersedia, (r) => r.ukuran_sisa)),
          roll_dipotong_count: rollsDipotong.length,
          roll_dipotong_qty: round2(sum(rollsDipotong, (r) => r.ukuran_sisa)),
          roll_habis_count: rollsHabis.length,
          total_ukuran_awal_roll: round2(sum(rollList, (r) => r.ukuran_awal)),
          total_ukuran_sisa_roll: round2(sum(rollList, (r) => r.ukuran_sisa)),
          total_penerimaan_nota: penerimaanList.length,
          total_outbound_trx: totalOutboundTrx,
          total_outbound_pcs: totalOutboundPcs,
          total_inbound_pcs: totalInboundPcs,
          total_sampel_diperiksa: rawChecked,
          total_lolos_qc: totalPassed,
          total_cacat_qc: totalDefective,
          total_rework_pending: totalReworkPending,
          pass_rate_pct: passRate,
          defect_rate_pct: defectRate,
        },
        defectBreakdown,
        stageBreakdown,
        consolidatedOrders,
        stokBarangJadiList,
        aksesorisList,
        rollList,
        penerimaanList,
        riwayatStokList,
        qcList,
      };
    },
  };

  window.BongUtil = {
    esc, sum, round2, round1, fmtNum, pad2, pad3, pad4,
    todayIso, nowSql, nowLocalIsoMin, addDays, datePart, toInputDateTime,
    fmtDateDmy, fmtDateTimeDmy, fmtDateShortId, fmtDateLongId, fmtNowWib,
  };
  window.BongDB = BongDB;
  window.BongAuth = BongAuth;
  window.BongQ = BongQ;
  window.BongService = BongService;
})();
