/* ============================================================================
 * BONG GARMENT — CMT (Cut, Make, Trim) Production & Inventory Management System
 * Undergraduate Thesis · Luis Febry (NIM 825220088) · Information Systems UNTAR
 * ----------------------------------------------------------------------------
 * app.js · Canonical 17-Table Relational Engine & Interactive SPA
 *   1. BongDB   — 17-table relational store with localStorage persistence
 *   2. Router   — switchTab() across 7 screens + URL hash sync
 *   3. Roles    — Admin / Pemilik / Pemotong / QC / Gudang RBAC switcher
 *   4. UI utils — toast, modal, confirm dialog, global search, notifications
 *   5. Renderers — derived from BongDB (auto re-render on state mutation)
 *
 * All mutations execute through BongDB.insert/update/remove;
 * every mutation emits a change event and re-renders active views in real time.
 * ========================================================================== */
(function () {
  'use strict';

  /* --------------------------------------------------------------------------
   * 0. CONSTANTS & HELPERS
   * ------------------------------------------------------------------------ */
  const DB_KEY = 'bong_garment_db_v2_17tables';
  const DB_VERSION = 2;
  const ROLE_KEY = 'bong_garment_role';

  /** Fixed "today" of the demo world so seeded data stays meaningful. */
  const APP_TODAY = '2026-10-28';
  const APP_TODAY_LABEL = 'Senin, 28 Okt 2026';
  const TARGET_HARIAN_PCS = 1250;           // daily cutting capacity target
  const ORDER_ROWS_DEFAULT = 5;
  const CHART_LABELS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Hari Ini'];
  const ACTIVE_STATUSES = ['ANTREAN', 'PEMOTONGAN', 'DALAM QC', 'Jahit', 'Finishing'];

  /** Primary key of each of the 17 physical tables (canonical schema). */
  const TABLE_PK = {
    Sumber_Inisiasi: 'id_sumber',
    Kategori_Pola: 'id_kategori',
    Pola: 'id_pola',
    Spesifikasi_Ukuran: 'id_spesifikasi',
    User: 'id_user',
    Supplier: 'id_supplier',
    Aksesoris: 'id_aksesoris',
    Detail_Aksesoris_Pola: 'id_pola',
    Penerimaan_Bahan: 'id_penerimaan',
    Detail_Bahan_Warna: 'id_detail_warna',
    Detail_Roll_Bahan: 'id_roll',
    Pesanan: 'id_pesanan',
    Stok_Barang_Jadi: 'id_stok',
    Pemotongan_Seri: 'id_potong',
    Detail_Pemotongan_Warna: 'id_potong_warna',
    Detail_Gelaran_Kain: 'id_gelar',
    QC: 'id_qc',
    Kerusakan: 'id_qc', // backward-compatibility alias
  };

  const TABS = ['dashboard', 'pesanan', 'penerimaan', 'pemotongan', 'qc', 'gudang', 'laporan'];
  const TAB_META = {
    dashboard: { label: 'Dashboard', step: 1 },
    pesanan: { label: 'Kelola Pesanan & Pola', step: 2 },
    penerimaan: { label: 'Penerimaan & Roll Bahan', step: 3 },
    pemotongan: { label: 'Pemotongan Seri & Gelaran', step: 4 },
    qc: { label: 'Quality Control & Kerusakan', step: 5 },
    gudang: { label: 'Gudang & Aksesoris', step: 6 },
    laporan: { label: 'Laporan Eksekutif', step: 7 },
    pengaturan: { label: 'Pengaturan Sistem' },
  };

  const ROLES = {
    Admin: { icon: 'user-cog', desc: 'Kelola penuh pesanan, master pola, inventori & laporan', tabs: ['dashboard', 'pesanan', 'penerimaan', 'pemotongan', 'qc', 'gudang', 'laporan', 'pengaturan'] },
    Pemilik: { icon: 'crown', desc: 'Mode Audit (Read-Only) pemantauan kinerja & laporan eksekutif', tabs: ['dashboard', 'laporan'] },
    Pemotong: { icon: 'scissors', desc: 'Eksekusi pemotongan seri & input gelaran', tabs: ['dashboard', 'pemotongan'] },
    QC: { icon: 'shield-check', desc: 'Inspeksi mutu berjenjang & penanganan cacat', tabs: ['dashboard', 'qc'] },
    Gudang: { icon: 'boxes', desc: 'Penerimaan bahan, roll fisik, aksesoris & stok jadi', tabs: ['dashboard', 'penerimaan', 'gudang'] },
  };

  const STATUS_META = {
    'ANTREAN': { badge: 'bg-slate-100 text-slate-700 ring-slate-200', bar: 'bg-slate-400' },
    'Antrean': { badge: 'bg-slate-100 text-slate-700 ring-slate-200', bar: 'bg-slate-400' },
    'PEMOTONGAN': { badge: 'bg-indigo-50 text-indigo-700 ring-indigo-200', bar: 'bg-indigo-500' },
    'Pemotongan': { badge: 'bg-indigo-50 text-indigo-700 ring-indigo-200', bar: 'bg-indigo-500' },
    'DALAM QC': { badge: 'bg-amber-50 text-amber-700 ring-amber-200', bar: 'bg-amber-400' },
    'Jahit': { badge: 'bg-blue-50 text-blue-700 ring-blue-200', bar: 'bg-blue-500' },
    'Finishing': { badge: 'bg-purple-50 text-purple-700 ring-purple-200', bar: 'bg-purple-500' },
    'SELESAI': { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200', bar: 'bg-emerald-500' },
    'Selesai': { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200', bar: 'bg-emerald-500' },
  };

  const SUMBER_META = {
    1: { icon: 'handshake', text: 'text-indigo-700', bg: 'bg-indigo-50 text-indigo-600', bar: 'bg-indigo-500', desc: 'Pesanan makloon dari mitra / klien eksternal dengan deadline kontrak.' },
    2: { icon: 'infinity', text: 'text-emerald-700', bg: 'bg-emerald-50 text-emerald-600', bar: 'bg-emerald-500', desc: 'Produk stok berkelanjutan (evergreen) milik Bong Garment.' },
    3: { icon: 'trending-up', text: 'text-amber-700', bg: 'bg-amber-50 text-amber-600', bar: 'bg-amber-400', desc: 'Produksi berdasarkan analisis tren pasar terkini.' },
    default: { icon: 'circle-dot', text: 'text-slate-700', bg: 'bg-slate-100 text-slate-600', bar: 'bg-slate-400', desc: 'Sumber inisiasi lainnya.' },
  };

  const ROLL_META = {
    Tersedia: { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200', icon: 'bg-emerald-50 text-emerald-600', dot: 'bg-emerald-500' },
    Dipotong: { badge: 'bg-indigo-50 text-indigo-700 ring-indigo-200', icon: 'bg-indigo-50 text-indigo-600', dot: 'bg-indigo-500' },
    Habis: { badge: 'bg-slate-100 text-slate-600 ring-slate-200', icon: 'bg-slate-100 text-slate-500', dot: 'bg-slate-400' },
    Retur: { badge: 'bg-amber-50 text-amber-700 ring-amber-200', icon: 'bg-amber-50 text-amber-600', dot: 'bg-amber-400' },
  };

  const QC_META = {
    'Lolos QC': { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200', icon: 'check-circle-2', iconCls: 'bg-emerald-50 text-emerald-600 ring-emerald-100' },
    'Need Rework': { badge: 'bg-rose-50 text-rose-700 ring-rose-200', icon: 'rotate-ccw', iconCls: 'bg-rose-50 text-rose-600 ring-rose-100' },
    'Rework Selesai': { badge: 'bg-blue-50 text-blue-700 ring-blue-200', icon: 'check-check', iconCls: 'bg-blue-50 text-blue-600 ring-blue-100' },
    'Retur Supplier': { badge: 'bg-amber-50 text-amber-700 ring-amber-200', icon: 'undo-2', iconCls: 'bg-amber-50 text-amber-600 ring-amber-100' },
    'Scrapped': { badge: 'bg-rose-100 text-rose-800 ring-rose-300', icon: 'trash-2', iconCls: 'bg-rose-100 text-rose-700 ring-rose-200' },
    'Finished': { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200', icon: 'check-circle-2', iconCls: 'bg-emerald-50 text-emerald-600 ring-emerald-100' },
  };

  // Icons renamed between Lucide versions → fallback both ways.
  const ICON_FALLBACK = {
    'file-bar-chart': 'file-chart-column', 'file-chart-column': 'file-bar-chart',
    'chart-column': 'bar-chart-3', 'circle-check': 'check-circle-2',
    'triangle-alert': 'alert-triangle', 'circle-x': 'x-circle',
    'circle-dot': 'dot', 'receipt-text': 'receipt', 'table-2': 'table',
    'list-ordered': 'list', 'undo-2': 'undo', 'sliders-horizontal': 'sliders',
  };

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const range = (a, b) => Array.from({ length: b - a + 1 }, (_, i) => a + i);
  const sum = (arr, fn = (x) => x) => arr.reduce((t, x) => t + (Number(fn(x)) || 0), 0);
  const round1 = (n) => Math.round(n * 10) / 10;
  const fmtNum = (n, d = 0) => new Intl.NumberFormat('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }).format(n);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

  function addDays(iso, n) {
    const d = new Date(iso + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + n);
    return d.toISOString().slice(0, 10);
  }
  function diffDays(fromIso, toIso) {
    return Math.round((Date.parse(toIso + 'T00:00:00Z') - Date.parse(fromIso + 'T00:00:00Z')) / 86400000);
  }
  function fmtDate(iso) {
    if (!iso) return '-';
    const [y, m, d] = iso.split('-').map(Number);
    return `${d} ${BULAN[m - 1]} ${y}`;
  }
  /** Deterministic PRNG so the seed is identical on every reset. */
  function mulberry32(a) {
    return function () {
      a |= 0; a = (a + 0x6D2B79F5) | 0;
      let t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  /* --------------------------------------------------------------------------
   * 1. SEED DATA — 17 physical tables (canonical schema)
   * ------------------------------------------------------------------------ */
  function buildSeed() {
    const rnd = mulberry32(825220088);
    const d = (n) => addDays(APP_TODAY, n);

    // 1. Sumber_Inisiasi
    const Sumber_Inisiasi = [
      { id_sumber: 1, nama_sumber: 'Request Mitra' },
      { id_sumber: 2, nama_sumber: 'Produk Abadi' },
      { id_sumber: 3, nama_sumber: 'Inisiatif Tren Pasar' },
    ];

    // 2. Kategori_Pola
    const Kategori_Pola = [
      { id_kategori: 1, nama_kategori: 'Kemeja & Formal' },
      { id_kategori: 2, nama_kategori: 'Kaos & Polo' },
      { id_kategori: 3, nama_kategori: 'Jaket & Outerwear' },
      { id_kategori: 4, nama_kategori: 'Celana & Chino' },
    ];

    // 3. Pola
    const Pola = [
      { id_pola: 1, id_kategori: 1, kode_pola: 'KMJ-FLN-01', nama_pola: 'Kemeja Flanel Casual' },
      { id_pola: 2, id_kategori: 3, kode_pola: 'JKT-CNV-02', nama_pola: 'Jaket Canvas Utility' },
      { id_pola: 3, id_kategori: 2, kode_pola: 'KOS-PLS-03', nama_pola: 'Kaos Polos Cotton Combed 30s' },
      { id_pola: 4, id_kategori: 1, kode_pola: 'KMJ-OXF-04', nama_pola: 'Kemeja Oxford Slim Fit' },
      { id_pola: 5, id_kategori: 2, kode_pola: 'POL-SHR-05', nama_pola: 'Polo Shirt Lacoste CVC' },
      { id_pola: 6, id_kategori: 4, kode_pola: 'CLN-CHN-06', nama_pola: 'Celana Chino Stretch Kasual' },
      { id_pola: 7, id_kategori: 3, kode_pola: 'HDY-FLC-07', nama_pola: 'Hoodie Fleece Heavyweight' },
      { id_pola: 8, id_kategori: 2, kode_pola: 'KOS-RGL-08', nama_pola: 'Kaos Raglan Two-Tone' },
    ];

    // 4. Spesifikasi_Ukuran (11 metric columns)
    const ukuranBase = { // [lingkar_dada, panjang_baju_celana, panjang_pundak, panjang_tangan]
      1: [100, 72, 44, 60], 2: [108, 70, 47, 62], 3: [96, 68, 42, 20], 4: [102, 73, 44, 60],
      5: [98, 69, 43, 22], 7: [110, 68, 50, 60], 8: [96, 68, 42, 58],
    };
    const Spesifikasi_Ukuran = [];
    let idSpec = 1;
    Pola.forEach((p) => {
      ['S', 'M', 'L', 'XL'].forEach((uk, i) => {
        if (p.id_pola === 6) {
          // Bottom / Trousers (Celana Chino)
          Spesifikasi_Ukuran.push({
            id_spesifikasi: idSpec++,
            id_pola: 6,
            nama_ukuran: uk,
            diameter_leher: null,
            panjang_pundak: null,
            lingkar_lengan: null,
            panjang_tangan: null,
            lingkar_dada: null,
            lingkar_pinggang: 72 + i * 4,
            lingkar_selangkang: round1(26 + i * 1.5),
            lingkar_paha: 56 + i * 2,
            lebar_kaki: 34 + i * 1,
            lebar_baju: null,
            panjang_baju_celana: 98 + i * 2,
          });
        } else {
          // Top garments (Kemeja, Kaos, Polo, Jaket, Hoodie)
          const [dd, pj, bh, lg] = ukuranBase[p.id_pola];
          Spesifikasi_Ukuran.push({
            id_spesifikasi: idSpec++,
            id_pola: p.id_pola,
            nama_ukuran: uk,
            diameter_leher: round1(17.5 + i * 0.5),
            panjang_pundak: round1(bh + i * 1.5),
            lingkar_lengan: 42 + i * 2,
            panjang_tangan: round1(lg + i),
            lingkar_dada: dd + i * 4,
            lingkar_pinggang: (dd + i * 4) - 2,
            lingkar_selangkang: null,
            lingkar_paha: null,
            lebar_kaki: null,
            lebar_baju: round1((dd + i * 4) / 2),
            panjang_baju_celana: pj + i * 2,
          });
        }
      });
    });

    // 5. User (5 RBAC Roles)
    const User = [
      { id_user: 1, username: 'luis.febry', nama_user: 'Luis Febry', role: 'Admin' },
      { id_user: 2, username: 'bong.owner', nama_user: 'Bong Hartono', role: 'Pemilik' },
      { id_user: 3, username: 'budi.cutter', nama_user: 'Budi Santoso', role: 'Pemotong' },
      { id_user: 4, username: 'sari.qc', nama_user: 'Siti Rahma', role: 'QC' },
      { id_user: 5, username: 'agus.gudang', nama_user: 'Agus Hermawan', role: 'Gudang' },
    ];

    // 6. Supplier
    const Supplier = [
      { id_supplier: 1, nama_supplier: 'PT Sinar Tekstil Abadi', kontak: '022-6012345', alamat: 'Jl. Soekarno-Hatta No. 112, Bandung' },
      { id_supplier: 2, nama_supplier: 'CV Kahatex Mandiri', kontak: '0812-2201-7788', alamat: 'Jl. Raya Rancaekek KM 23, Sumedang' },
      { id_supplier: 3, nama_supplier: 'PT Busana Indah (Kain Mitra)', kontak: '021-5550192', alamat: 'Jl. Daan Mogot No. 45, Jakarta Barat' },
      { id_supplier: 4, nama_supplier: 'Toko Kain Cigondewah Jaya', kontak: '0857-2034-1122', alamat: 'Jl. Cigondewah Kaler No. 8, Bandung' },
    ];

    // 7. Aksesoris
    const Aksesoris = [
      { id_aksesoris: 1, id_supplier: 2, nama_aksesoris: 'Kancing Jamur Resin Smoke Grey 18L', ukuran: '18L', stok: 15000, satuan: 'pcs' },
      { id_aksesoris: 2, id_supplier: 2, nama_aksesoris: 'Ritsleting Metal YKK Antique Brass 7 Inch', ukuran: '7 Inch', stok: 2400, satuan: 'pcs' },
      { id_aksesoris: 3, id_supplier: 2, nama_aksesoris: 'Benang Jahit Katun Astra 40/2 Hitam', ukuran: '5000 Yds', stok: 180, satuan: 'pcs' },
      { id_aksesoris: 4, id_supplier: 2, nama_aksesoris: 'Label Woven Tag Bong Garment Premium', ukuran: '5x2.5 cm', stok: 12000, satuan: 'pcs' },
      { id_aksesoris: 5, id_supplier: 2, nama_aksesoris: 'Karet Pinggang Stretch Elastic 3.8cm', ukuran: '3.8 cm', stok: 45, satuan: 'gross' },
    ];

    // 8. Detail_Aksesoris_Pola
    const Detail_Aksesoris_Pola = [
      { id_pola: 1, id_aksesoris: 1, jumlah_digunakan: 8 },
      { id_pola: 1, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 1, id_aksesoris: 3, jumlah_digunakan: 1 },
      { id_pola: 2, id_aksesoris: 2, jumlah_digunakan: 1 },
      { id_pola: 2, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 3, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 3, id_aksesoris: 3, jumlah_digunakan: 1 },
      { id_pola: 4, id_aksesoris: 1, jumlah_digunakan: 8 },
      { id_pola: 4, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 5, id_aksesoris: 1, jumlah_digunakan: 3 },
      { id_pola: 5, id_aksesoris: 4, jumlah_digunakan: 1 },
      { id_pola: 6, id_aksesoris: 2, jumlah_digunakan: 1 },
      { id_pola: 6, id_aksesoris: 1, jumlah_digunakan: 1 },
      { id_pola: 6, id_aksesoris: 5, jumlah_digunakan: 1 },
      { id_pola: 6, id_aksesoris: 4, jumlah_digunakan: 1 },
    ];

    // 9. Penerimaan_Bahan
    const pb = (id_penerimaan, id_supplier, no_nota, tgl_nota, nama_kain, lebar_gramasi, no_kendaraan, penerima) =>
      ({ id_penerimaan, id_supplier, no_nota, tgl_nota, nama_kain, lebar_gramasi, no_kendaraan, penerima });
    const Penerimaan_Bahan = [
      pb(1, 2, '796', d(-16), 'Combed 30s', '72" · 180 gsm', 'D 8123 AB', 'Agus Hermawan'),
      pb(2, 3, '797', d(-14), 'Flanel Yarn Dyed', '58" · 165 gsm', 'B 9012 KLA', 'Agus Hermawan'),
      pb(3, 4, '798', d(-13), 'Lacoste PE', '72" · 220 gsm', 'D 1456 TR', 'Agus Hermawan'),
      pb(4, 1, '799', d(-12), 'Cotton Oxford', '58" · 130 gsm', 'D 7781 UF', 'Luis Febry'),
      pb(5, 1, '800', d(-11), 'Twill Drill', '58" · 240 gsm', 'D 7781 UF', 'Luis Febry'),
      pb(6, 2, '801', d(-8), 'Combed 30s', '72" · 180 gsm', 'D 8123 AB', 'Agus Hermawan'),
      pb(7, 4, '802', d(-7), 'Canvas', '60" · 12 oz', 'D 1456 TR', 'Agus Hermawan'),
      pb(8, 1, '803', d(-2), 'Drill', '58" · 230 gsm', 'D 7781 UF', 'Luis Febry'),
      pb(9, 2, '804', d(-1), 'Fleece Cotton', '72" · 280 gsm', 'D 8123 AB', 'Agus Hermawan'),
      pb(10, 4, '805', d(0), 'Canvas', '60" · 12 oz', 'D 1456 TR', 'Luis Febry'),
    ];

    // 10 + 11. Detail_Bahan_Warna & Detail_Roll_Bahan
    const warnaSpec = [
      [1, 1, 'White', 'WHT-01', 'A-03', range(4452, 4458), 0],
      [2, 1, 'Misty Grey', 'GRY-02', 'A-04', range(4459, 4466), 0],
      [3, 3, 'Navy', 'NVY-03', 'C-02', [...range(4467, 4472), 4489], 0],
      [4, 2, 'Red Check', 'RCK-04', 'A-07', [...range(4473, 4479), 4495, 4496, 4497], 6],
      [5, 4, 'Sky Blue', 'SKY-05', 'C-04', range(4480, 4485), 0],
      [6, 2, 'Blue Check', 'BCK-06', 'A-08', [4486, 4487, 4488, ...range(4490, 4494)], 0],
      [7, 6, 'Black', 'BLK-07', 'C-03', [4498, 4499, 4501], 8],
      [8, 7, 'Navy', 'NVY-08', 'A-05', [4500, 4502], 6],
      [9, 8, 'Khaki', 'KHK-09', 'B-04', [], 24],
      [10, 8, 'Black', 'BLK-10', 'B-05', [], 18],
      [11, 5, 'Cream', 'CRM-11', 'C-01', [], 14],
      [12, 9, 'Charcoal', 'CHR-12', 'D-02', [], 20],
      [13, 9, 'Black', 'BLK-13', 'D-03', [], 16],
      [14, 10, 'Navy', 'NVY-14', 'A-01', [], 18],
      [15, 10, 'Olive', 'OLV-15', 'A-02', [], 12],
    ];
    const tersediaOrder = [9, 10, 11, 12, 13, 14, 15, 4, 7, 8]; // FIFO: id_roll 4503 → Drill Khaki
    const tersediaIds = {};
    let nextFree = 4503;
    tersediaOrder.forEach((wid) => {
      const n = warnaSpec.find((w) => w[0] === wid)[6];
      tersediaIds[wid] = range(nextFree, nextFree + n - 1);
      nextFree += n;
    });
    const beratOverride = { 4501: 24.5, 4502: 28.0, 4503: 22.1 };
    const Detail_Bahan_Warna = [];
    const Detail_Roll_Bahan = [];
    warnaSpec.forEach(([id, idPen, nama, kode, rak, used]) => {
      const ids = [...used, ...(tersediaIds[id] || [])].sort((a, b) => a - b);
      const rolls = ids.map((rid, i) => {
        let status = 'Tersedia';
        if (used.includes(rid)) status = rid === 4501 ? 'Dipotong' : 'Habis';
        const initialWeight = beratOverride[rid] ?? round1(20 + rnd() * 8);
        const sisaWeight = status === 'Habis' ? 0.0 : (status === 'Dipotong' ? 6.2 : initialWeight);
        return {
          id_roll: rid,
          id_detail_warna: id,
          no_roll_ke: i + 1,
          ukuran_awal: initialWeight,
          ukuran_sisa: sisaWeight,
          satuan: 'Kg',
          total_roll: ids.length,
          status_roll: status,
          rak_alokasi: status === 'Tersedia' ? rak : status === 'Dipotong' ? 'Meja Potong 02' : '-',
        };
      });
      Detail_Roll_Bahan.push(...rolls);
      const totalUkuran = round1(sum(rolls, (r) => r.ukuran_awal));
      Detail_Bahan_Warna.push({
        id_detail_warna: id,
        id_penerimaan: idPen,
        nama_warna: nama,
        kode_warna: kode,
        total_roll: ids.length,
        total_ukuran: totalUkuran,
        satuan_ukur: 'Kg',
      });
    });
    Detail_Roll_Bahan.sort((a, b) => a.id_roll - b.id_roll);

    // 12. Pesanan
    const ps = (id, pola, sumber, pen, mitra, order, deadline, status, pct, revDl = null, alasan = null) => ({
      id_pesanan: id,
      id_pola: pola,
      id_sumber: sumber,
      id_penerimaan: pen,
      id_user_admin: 1,
      jenis_pesanan: sumber === 1 ? 'Makloon CMT (Mitra)' : (sumber === 2 ? 'Produk Abadi (Bong Garment)' : 'Inisiatif Tren Pasar'),
      nama_mitra: sumber === 1 ? mitra : null,
      tgl_order: d(order),
      deadline_awal: d(deadline),
      deadline_terbaru: revDl ? d(revDl) : d(deadline),
      deadline: revDl ? d(revDl) : d(deadline), // alias
      tgl_perubahan_deadline: revDl ? (d(-2) + ' 14:00:00') : null,
      alasan_perubahan_deadline: alasan,
      status_pesanan: status,
      progress_pct: pct,
    });
    const Pesanan = [
      ps(96, 8, 2, 1, null, -24, -5, 'SELESAI', 100),
      ps(97, 6, 1, 5, 'PT Seragam Nusantara', -22, -3, 'SELESAI', 100),
      ps(98, 5, 1, 3, 'CV Sport Wear Indo', -21, -2, 'SELESAI', 100),
      ps(99, 4, 1, 4, 'PT Seragam Nusantara', -20, -1, 'SELESAI', 100),
      ps(100, 3, 2, 1, null, -19, -1, 'SELESAI', 100),
      ps(101, 1, 1, 2, 'PT Busana Indah', -18, 8, 'PEMOTONGAN', 70, 6, 'Permintaan percepatan jadwal distribusi oleh mitra untuk event peluncuran produk'),
      ps(102, 2, 3, 10, null, -1, 23, 'ANTREAN', 25),
      ps(103, 3, 2, 1, null, -20, 3, 'DALAM QC', 90),
      ps(104, 2, 1, 7, 'CV Maju Garmindo', -10, 11, 'PEMOTONGAN', 55),
      ps(105, 3, 1, 6, 'Distro Urban Kasual', -9, 9, 'PEMOTONGAN', 45),
      ps(106, 6, 2, 5, null, -11, 13, 'PEMOTONGAN', 40),
      ps(107, 7, 3, 9, null, -3, 15, 'PEMOTONGAN', 35),
      ps(108, 4, 1, 4, 'PT Seragam Nusantara', -12, 12, 'PEMOTONGAN', 60),
      ps(109, 7, 1, 9, 'Komunitas Lari Bandung', -2, 18, 'ANTREAN', 15),
      ps(110, 6, 1, 8, 'PT Seragam Nusantara', -2, 21, 'ANTREAN', 10),
      ps(111, 8, 3, null, null, -1, 28, 'ANTREAN', 0),
      ps(112, 5, 1, null, 'CV Sport Wear Indo', -1, 25, 'ANTREAN', 0),
      ps(113, 1, 2, null, null, 0, 34, 'ANTREAN', 0),
      ps(114, 6, 3, 8, null, -1, 31, 'ANTREAN', 20),
      ps(115, 3, 2, null, null, 0, 38, 'ANTREAN', 5),
      ps(116, 5, 1, 3, 'CV Sport Wear Indo', -15, 5, 'DALAM QC', 85),
      ps(117, 3, 2, 1, null, -14, 6, 'DALAM QC', 88),
      ps(118, 4, 1, 4, 'PT Seragam Nusantara', -13, 7, 'DALAM QC', 92),
    ];

    // 13. Stok_Barang_Jadi
    const Stok_Barang_Jadi = [
      { id_stok: 1, id_pola: 3, id_spesifikasi: 9, nama_warna: 'White', jumlah_stok_pcs: 180, tgl_update_terakhir: d(-2) + ' 16:30:00' },
      { id_stok: 2, id_pola: 3, id_spesifikasi: 10, nama_warna: 'White', jumlah_stok_pcs: 240, tgl_update_terakhir: d(-2) + ' 16:30:00' },
      { id_stok: 3, id_pola: 3, id_spesifikasi: 11, nama_warna: 'Misty Grey', jumlah_stok_pcs: 150, tgl_update_terakhir: d(-3) + ' 14:15:00' },
      { id_stok: 4, id_pola: 1, id_spesifikasi: 2, nama_warna: 'Red Check', jumlah_stok_pcs: 95, tgl_update_terakhir: d(-4) + ' 11:00:00' },
      { id_stok: 5, id_pola: 5, id_spesifikasi: 18, nama_warna: 'Navy', jumlah_stok_pcs: 120, tgl_update_terakhir: d(-1) + ' 17:00:00' },
      { id_stok: 6, id_pola: 6, id_spesifikasi: 22, nama_warna: 'Khaki', jumlah_stok_pcs: 85, tgl_update_terakhir: d(-5) + ' 10:30:00' },
    ];

    // 14 + 15 + 16. Pemotongan_Seri, Detail_Pemotongan_Warna, Detail_Gelaran_Kain
    const potongSpec = [
      { id: 294, ord: 103, day: -6, ppl: 4, pjg: 5.2, total: 380, rolls: range(4452, 4458) },
      { id: 295, ord: 117, day: -5, ppl: 6, pjg: 6.0, total: 420, rolls: range(4459, 4466) },
      { id: 296, ord: 116, day: -4, ppl: 2, pjg: 3.1, total: 290, rolls: range(4467, 4472) },
      { id: 297, ord: 101, day: -3, ppl: 4, pjg: 6.4, total: 360, rolls: range(4473, 4479) },
      { id: 298, ord: 118, day: -2, ppl: 2, pjg: 3.4, total: 310, rolls: range(4480, 4485) },
      { id: 299, ord: 101, day: -1, ppl: 2, pjg: 3.3, total: 410, rolls: [4486, 4487, 4488, ...range(4490, 4494)] },
      { id: 300, ord: 101, day: 0, ppl: 4, pjg: 6.4, gel: { 4495: [60, 60, 60], 4496: [60, 60], 4497: [60, 60] } },
      { id: 301, ord: 105, day: 0, ppl: 8, pjg: 5.6, gel: { 4498: [72, 72], 4499: [64, 64], 4501: [72, 72, 64] } },
      { id: 302, ord: 104, day: 0, ppl: 5, pjg: 4.8, gel: { 4500: [70, 70, 70], 4502: [70, 70] } },
    ];
    const rollById = Object.fromEntries(Detail_Roll_Bahan.map((r) => [r.id_roll, r]));
    const Pemotongan_Seri = [];
    const Detail_Pemotongan_Warna = [];
    const Detail_Gelaran_Kain = [];
    const seriCount = {};
    let idPW = 501;
    let idGel = 7001;
    potongSpec.forEach((s, idx) => {
      let gel = s.gel;
      if (!gel) {
        const layers = s.total / s.ppl;
        const base = Math.floor(layers / s.rolls.length);
        const rem = layers % s.rolls.length;
        gel = {};
        s.rolls.forEach((rid, i) => { gel[rid] = [(base + (i < rem ? 1 : 0)) * s.ppl]; });
      }
      const rollIds = Object.keys(gel).map(Number);
      const total = sum(rollIds, (rid) => sum(gel[rid]));
      seriCount[s.ord] = (seriCount[s.ord] || 0) + 1;
      Pemotongan_Seri.push({
        id_potong: s.id, id_pesanan: s.ord, id_user_pemotong: idx % 2 ? 5 : 2,
        kode_seri: `SR-${s.ord}-${String(seriCount[s.ord]).padStart(2, '0')}`,
        jenis_variasi: 'Warna Tunggal', total_lembar_hasil: total, tgl_potong: d(s.day),
      });
      const pw = {
        id_potong_warna: idPW++, id_potong: s.id, id_detail_warna: rollById[rollIds[0]].id_detail_warna,
        total_lembar_direncanakan: Math.ceil(total / 50) * 50,
        kain_terpakai: round1(sum(rollIds, (rid) => rollById[rid].ukuran_awal * (rollById[rid].status_roll === 'Dipotong' ? 0.6 : 1))),
      };
      Detail_Pemotongan_Warna.push(pw);
      rollIds.forEach((rid) => gel[rid].forEach((lembar) => {
        Detail_Gelaran_Kain.push({ id_gelar: idGel++, id_potong_warna: pw.id_potong_warna, id_roll: rid, panjang_gelar: s.pjg, jumlah_tumpuk: lembar / s.ppl, total_lembar_gelar: lembar });
      }));
    });

    // 17. QC (Multi-stage Inspection & Kerusakan)
    const QC = [
      { id_qc: 82, id_pesanan: 96, id_user_qc: 4, tahap_qc: 'Jahit Akhir', tgl_pemeriksaan: d(-9) + ' 14:00:00', jumlah_diperiksa: 380, jumlah_lolos: 375, jumlah_cacat: 5, jenis_cacat: 'Cacat Jahit', catatan_qc: 'Jahitan rantai lepas pada ujung lengan', status_qc: 'Rework Selesai', tgl_target_perbaikan: d(-8), tgl_selesai_perbaikan: d(-8) + ' 16:00:00', pic_rework: 'Tim Jahit B', status: 'Rework Selesai', jumlah_pcs: 5, catatan: 'Jahitan rantai lepas pada ujung lengan', tgl_temuan: d(-9) },
      { id_qc: 83, id_pesanan: 100, id_user_qc: 4, tahap_qc: 'Potong', tgl_pemeriksaan: d(-5) + ' 11:30:00', jumlah_diperiksa: 420, jumlah_lolos: 416, jumlah_cacat: 4, jenis_cacat: 'Cacat Potong', catatan_qc: 'Selisih ukuran panel badan belakang (±1 cm)', status_qc: 'Rework Selesai', tgl_target_perbaikan: d(-4), tgl_selesai_perbaikan: d(-4) + ' 15:00:00', pic_rework: 'Tim Potong A', status: 'Rework Selesai', jumlah_pcs: 4, catatan: 'Selisih ukuran panel badan belakang (±1 cm)', tgl_temuan: d(-5) },
      { id_qc: 84, id_pesanan: 116, id_user_qc: 4, tahap_qc: 'Jahit Akhir', tgl_pemeriksaan: d(-1) + ' 16:15:00', jumlah_diperiksa: 290, jumlah_lolos: 284, jumlah_cacat: 6, jenis_cacat: 'Cacat Jahit', catatan_qc: 'Jahitan kerah polo miring & bergelombang', status_qc: 'Need Rework', tgl_target_perbaikan: d(1), tgl_selesai_perbaikan: null, pic_rework: 'Tim Jahit A', status: 'Need Rework', jumlah_pcs: 6, catatan: 'Jahitan kerah polo miring & bergelombang', tgl_temuan: d(-1) },
      { id_qc: 85, id_pesanan: 103, id_user_qc: 4, tahap_qc: 'Potong', tgl_pemeriksaan: d(-1) + ' 10:00:00', jumlah_diperiksa: 380, jumlah_lolos: 372, jumlah_cacat: 8, jenis_cacat: 'Cacat Potong', catatan_qc: 'Ukuran panel tidak presisi akibat pergeseran layer tumpuk ke-7', status_qc: 'Need Rework', tgl_target_perbaikan: d(1), tgl_selesai_perbaikan: null, pic_rework: 'Tim Potong B (Agus)', status: 'Need Rework', jumlah_pcs: 8, catatan: 'Ukuran panel tidak presisi akibat pergeseran layer tumpuk ke-7', tgl_temuan: d(-1) },
      { id_qc: 86, id_pesanan: 98, id_user_qc: 4, tahap_qc: 'Prototipe', tgl_pemeriksaan: d(-2) + ' 13:00:00', jumlah_diperiksa: 10, jumlah_lolos: 9, jumlah_cacat: 1, jenis_cacat: 'Cacat Bahan', catatan_qc: 'Shading warna belang di tengah roll id_roll: 4489', status_qc: 'Scrapped', tgl_target_perbaikan: null, tgl_selesai_perbaikan: null, pic_rework: 'Gudang · Retur Supplier', status: 'Retur Supplier', jumlah_pcs: 1, catatan: 'Shading warna belang di tengah roll id_roll: 4489', tgl_temuan: d(-2) },
      { id_qc: 87, id_pesanan: 99, id_user_qc: 4, tahap_qc: 'Jahit Akhir', tgl_pemeriksaan: d(-1) + ' 15:45:00', jumlah_diperiksa: 310, jumlah_lolos: 307, jumlah_cacat: 3, jenis_cacat: 'Cacat Jahit', catatan_qc: 'Kerutan pada kerah leher', status_qc: 'Rework Selesai', tgl_target_perbaikan: d(0), tgl_selesai_perbaikan: d(0) + ' 11:30:00', pic_rework: 'Tim Jahit A', status: 'Rework Selesai', jumlah_pcs: 3, catatan: 'Kerutan pada kerah leher', tgl_temuan: d(-1) },
      { id_qc: 88, id_pesanan: 101, id_user_qc: 4, tahap_qc: 'Potong', tgl_pemeriksaan: d(0) + ' 14:00:00', jumlah_diperiksa: 360, jumlah_lolos: 348, jumlah_cacat: 12, jenis_cacat: 'Cacat Potong', catatan_qc: 'Pergeseran pola kain pada gelaran layer 4', status_qc: 'Need Rework', tgl_target_perbaikan: d(1), tgl_selesai_perbaikan: null, pic_rework: 'Tim Potong A (Budi)', status: 'Need Rework', jumlah_pcs: 12, catatan: 'Pergeseran pola kain pada gelaran layer 4', tgl_temuan: d(0) },
    ];

    return {
      _meta: { version: DB_VERSION, app_today: APP_TODAY, seeded_at: new Date().toISOString(), updated_at: new Date().toISOString() },
      Sumber_Inisiasi, Kategori_Pola, Pola, Spesifikasi_Ukuran, User, Supplier, Aksesoris, Detail_Aksesoris_Pola,
      Penerimaan_Bahan, Detail_Bahan_Warna, Detail_Roll_Bahan, Pesanan, Stok_Barang_Jadi,
      Pemotongan_Seri, Detail_Pemotongan_Warna, Detail_Gelaran_Kain, QC,
      Kerusakan: QC, // backward-compatibility alias
    };
  }

  /* --------------------------------------------------------------------------
   * 2. BongDB — relational store with localStorage persistence
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
        console.warn('[BongDB] localStorage tidak tersedia, memakai memori.', err);
        this._memoryOnly = true;
      }
      if (this._isValid(parsed)) {
        this.data = parsed;
      } else {
        this.data = buildSeed();
        this.save();
      }
      // Cross-tab sync
      window.addEventListener('storage', (e) => {
        if (e.key !== DB_KEY || !e.newValue) return;
        try {
          const next = JSON.parse(e.newValue);
          if (this._isValid(next)) { this.data = next; this._emit({ type: 'sync' }); }
        } catch (_) { /* ignore */ }
      });
      return this;
    },

    _isValid(db) {
      return !!db && db._meta && db._meta.version === DB_VERSION &&
        Object.keys(TABLE_PK).every((t) => Array.isArray(db[t]));
    },

    save() {
      this.data._meta.updated_at = new Date().toISOString();
      if (this._memoryOnly) return;
      try { localStorage.setItem(DB_KEY, JSON.stringify(this.data)); }
      catch (err) { console.warn('[BongDB] Gagal menyimpan ke localStorage.', err); }
    },

    pk(table) {
      if (table === 'Kerusakan') table = 'QC';
      const key = TABLE_PK[table];
      if (!key) throw new Error(`[BongDB] Tabel tidak dikenal: ${table}`);
      return key;
    },
    all(table) {
      if (table === 'Kerusakan') table = 'QC';
      this.pk(table);
      return this.data[table] || (table === 'QC' ? (this.data.Kerusakan || []) : []);
    },
    find(table, id) {
      if (id === null || id === undefined) return null;
      if (table === 'Kerusakan') table = 'QC';
      const key = this.pk(table);
      const list = this.all(table);
      return list.find((r) => r[key] === Number(id)) || null;
    },
    where(table, predicate) { return this.all(table).filter(predicate); },
    nextId(table) {
      if (table === 'Kerusakan') table = 'QC';
      const key = this.pk(table);
      return (this.data[table] || []).reduce((m, r) => Math.max(m, Number(r[key]) || 0), 0) + 1;
    },

    insert(table, record) {
      if (table === 'Kerusakan') table = 'QC';
      const key = this.pk(table);
      const rec = { ...record };
      if (rec[key] === undefined || rec[key] === null) rec[key] = this.nextId(table);
      if (this.find(table, rec[key])) throw new Error(`[BongDB] Duplikat PK ${key}=${rec[key]} pada ${table}`);
      if (!this.data[table]) this.data[table] = [];
      this.data[table].push(rec);
      if (table === 'QC') this.data.Kerusakan = this.data.QC;
      this.save();
      this._emit({ type: 'insert', table, record: rec });
      return rec;
    },
    insertMany(table, records) {
      if (table === 'Kerusakan') table = 'QC';
      const out = records.map((r) => {
        const key = this.pk(table);
        const rec = { ...r };
        if (rec[key] === undefined || rec[key] === null) rec[key] = this.nextId(table);
        if (!this.data[table]) this.data[table] = [];
        this.data[table].push(rec);
        return rec;
      });
      if (table === 'QC') this.data.Kerusakan = this.data.QC;
      this.save();
      this._emit({ type: 'insert', table, records: out });
      return out;
    },
    update(table, id, patch) {
      if (table === 'Kerusakan') table = 'QC';
      const rec = this.find(table, id);
      if (!rec) return null;
      Object.assign(rec, patch, { [this.pk(table)]: rec[this.pk(table)] });
      if (table === 'QC') this.data.Kerusakan = this.data.QC;
      this.save();
      this._emit({ type: 'update', table, record: rec });
      return rec;
    },
    remove(table, id) {
      if (table === 'Kerusakan') table = 'QC';
      const key = this.pk(table);
      if (!this.data[table]) return false;
      const before = this.data[table].length;
      this.data[table] = this.data[table].filter((r) => r[key] !== Number(id));
      const removed = before !== this.data[table].length;
      if (table === 'QC') this.data.Kerusakan = this.data.QC;
      if (removed) { this.save(); this._emit({ type: 'remove', table, id: Number(id) }); }
      return removed;
    },

    reset() {
      this.data = buildSeed();
      this.save();
      this._emit({ type: 'reset' });
    },
    stats() {
      return Object.keys(TABLE_PK).map((t, i) => ({ no: i + 1, table: t, pk: TABLE_PK[t], count: (this.data[t] || []).length }));
    },
    totalRecords() { return sum(Object.keys(TABLE_PK), (t) => (this.data[t] || []).length); },
    exportJSON() { return JSON.stringify(this.data, null, 2); },

    subscribe(fn) {
      this._listeners.push(fn);
      return () => { this._listeners = this._listeners.filter((f) => f !== fn); };
    },
    _emit(evt) {
      this._listeners.forEach((fn) => { try { fn(evt); } catch (err) { console.error(err); } });
    },
  };
  window.BongDB = BongDB;

  /* --------------------------------------------------------------------------
   * 3. RELATIONAL QUERY HELPERS (joins)
   * ------------------------------------------------------------------------ */
  const Q = {
    pola: (id) => BongDB.find('Pola', id),
    kategori: (id) => BongDB.find('Kategori_Pola', id),
    sumber: (id) => BongDB.find('Sumber_Inisiasi', id),
    user: (id) => BongDB.find('User', id),
    pesanan: (id) => BongDB.find('Pesanan', id),
    penerimaan: (id) => BongDB.find('Penerimaan_Bahan', id),
    supplier: (id) => BongDB.find('Supplier', id),
    warna: (id) => BongDB.find('Detail_Bahan_Warna', id),
    roll: (id) => BongDB.find('Detail_Roll_Bahan', id),
    aksesoris: (id) => BongDB.find('Aksesoris', id),
    spesifikasi: (id) => BongDB.find('Spesifikasi_Ukuran', id),
    stokJadi: (id) => BongDB.find('Stok_Barang_Jadi', id),
    qc: (id) => BongDB.find('QC', id),

    rollInfo(roll) {
      const w = Q.warna(roll.id_detail_warna);
      const p = w ? Q.penerimaan(w.id_penerimaan) : null;
      return { warna: w, penerimaan: p, label: [p?.nama_kain, w?.nama_warna].filter(Boolean).join(' ') || `Roll ${roll.id_roll}` };
    },
    /** Gelaran rows of a roll, enriched with id_potong / pesanan. */
    rollGelaran(idRoll) {
      return BongDB.where('Detail_Gelaran_Kain', (g) => g.id_roll === Number(idRoll)).map((g) => {
        const pw = BongDB.find('Detail_Pemotongan_Warna', g.id_potong_warna);
        const ps = pw ? BongDB.find('Pemotongan_Seri', pw.id_potong) : null;
        return { ...g, id_potong: ps?.id_potong ?? null, potong: ps };
      });
    },
    /** All gelaran belonging to a list of Pemotongan_Seri records. */
    gelaranOfPotong(potongList) {
      const ids = new Set(potongList.map((p) => p.id_potong));
      const pwIds = new Set(BongDB.where('Detail_Pemotongan_Warna', (w) => ids.has(w.id_potong)).map((w) => w.id_potong_warna));
      return BongDB.where('Detail_Gelaran_Kain', (g) => pwIds.has(g.id_potong_warna));
    },
  };

  function progressLabel(p) {
    if (p.status_pesanan === 'SELESAI') return 'Selesai';
    if (!p.id_penerimaan) return 'Menunggu Bahan';
    const v = Number(p.progress_pct) || 0;
    if (v < 35) return 'Persiapan Gelar';
    if (v < 60) return 'Proses Gelar & Potong';
    if (v < 85) return 'Potong Selesai';
    return 'Tahap Akhir';
  }
  function defectTag(text) {
    const t = String(text || '').toLowerCase();
    if (t.includes('layer') || t.includes('gelaran')) return 'Layering';
    if (t.includes('kerah')) return 'Kerah';
    if (t.includes('shading') || t.includes('belang')) return 'Shading';
    if (t.includes('ukuran') || t.includes('presisi')) return 'Ukuran';
    if (t.includes('jahitan')) return 'Jahitan';
    return null;
  }
  const qcUnit = (k) => (k.jenis_cacat === 'Cacat Bahan' ? 'Roll' : 'Pcs');

  /* --------------------------------------------------------------------------
   * 4. APP STATE
   * ------------------------------------------------------------------------ */
  const state = {
    tab: 'dashboard',
    role: (() => { try { return localStorage.getItem(ROLE_KEY) || 'Admin'; } catch (_) { return 'Admin'; } })(),
    orderFilter: 'ALL',
    showAllOrders: false,
    selectedSumber: 1,
    selectedEdf: null,
    notifSeen: 0,
    countPrev: {},
    confirmFn: null,

    // Step 2: Screen 2 (Pesanan & Pola) state
    pesanan: {
      polaMode: 'existing', // 'existing' | 'new'
      selectedPolaId: 1,
      flashOrderId: null,
    },

    // Step 2: Screen 3 (Penerimaan & Roll Bahan) state
    penerimaan: {
      nextColorGroupId: 1,
      colorGroups: [
        { id: 1, namaWarna: 'Black Jet', kodeWarna: 'BLK-30S-01', rakDefault: 'B-04', hexColor: '#1E293B' },
      ],
      draftRolls: [
        { colorGroupId: 1, beratKg: 24.50, rakAlokasi: 'B-04' },
        { colorGroupId: 1, beratKg: 25.00, rakAlokasi: 'B-04' },
        { colorGroupId: 1, beratKg: 25.00, rakAlokasi: 'B-04' },
      ],
      rollFilter: 'ALL',
      flashRollId: null,
    },

    // Step 4: Screen 4 (Pemotongan Seri & Gelaran) state
    pemotongan: {
      draftGelarRows: [
        { id_roll: 4503, panjang_gelar: 2.2, jumlah_tumpuk: 50, total_lembar_gelar: 60, status_akhir: 'Dipotong' },
      ],
      rollStatusMap: {}, // id_roll -> 'Dipotong' | 'Habis'
    },

    // Step 5: Screen 5 (Quality Control & Kerusakan) state
    qc: {
      selectedDefectCat: 'Cacat Potong', // 'Cacat Potong' | 'Cacat Jahit' | 'Cacat Bahan'
      selectedDecision: 'Need Rework',   // 'Need Rework' | 'Scrapped' | 'Finished' | 'Retur Supplier'
      tableFilter: 'ALL',                // 'ALL' | 'Need Rework' | 'Rework Selesai' | 'Retur / Scrapped'
      selectedOrderId: null,
    },

    // Step 6: Screen 6 (Laporan Eksekutif) state
    laporan: {
      jenis: 'ALL',
      startDate: addDays(APP_TODAY, -30),
      endDate: APP_TODAY,
      sumber: 'ALL',
      status: 'ALL',
    },
  };

  /* --------------------------------------------------------------------------
   * 5. UI UTILITIES — icons, toast, modal, download
   * ------------------------------------------------------------------------ */
  function refreshIcons() {
    if (!window.lucide || typeof window.lucide.createIcons !== 'function') return;
    try { window.lucide.createIcons(); } catch (_) { /* ignore */ }
    let changed = false;
    $$('i[data-lucide]').forEach((el) => {
      const fb = ICON_FALLBACK[el.getAttribute('data-lucide')];
      if (fb && !el.dataset.fb) { el.dataset.fb = '1'; el.setAttribute('data-lucide', fb); changed = true; }
    });
    if (changed) { try { window.lucide.createIcons(); } catch (_) { /* ignore */ } }
  }

  function showToast(message, opts = {}) {
    const { type = 'success', title, duration = 3600 } = opts;
    const cfg = {
      success: { icon: 'circle-check', cls: 'bg-emerald-50 text-emerald-600', bar: 'bg-emerald-500', title: 'Berhasil' },
      info: { icon: 'info', cls: 'bg-indigo-50 text-indigo-600', bar: 'bg-indigo-500', title: 'Informasi' },
      warning: { icon: 'triangle-alert', cls: 'bg-amber-50 text-amber-600', bar: 'bg-amber-500', title: 'Perhatian' },
      error: { icon: 'circle-x', cls: 'bg-rose-50 text-rose-600', bar: 'bg-rose-500', title: 'Gagal' },
    }[type] || {};
    const root = $('#toast-root');
    const el = document.createElement('div');
    el.className = 'toast pointer-events-auto relative w-[22rem] overflow-hidden bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-900/10';
    el.setAttribute('role', 'status');
    el.innerHTML = `
      <div class="flex items-start gap-3 p-3.5 pr-10">
        <span class="w-8 h-8 shrink-0 rounded-lg grid place-items-center ${cfg.cls}"><i data-lucide="${cfg.icon}" class="w-4 h-4"></i></span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-900">${esc(title || cfg.title)}</p>
          <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">${message}</p>
        </div>
      </div>
      <button type="button" class="absolute top-2.5 right-2.5 w-6 h-6 grid place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup notifikasi"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
      <span class="toast-progress absolute bottom-0 left-0 h-0.5 w-full ${cfg.bar}" style="animation-duration:${duration}ms"></span>`;
    root.appendChild(el);
    while (root.children.length > 4) root.firstElementChild.remove();
    refreshIcons();
    const dismiss = () => {
      if (el.classList.contains('is-leaving')) return;
      el.classList.add('is-leaving');
      setTimeout(() => el.remove(), 240);
    };
    el.querySelector('button').addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
    return dismiss;
  }

  function openModal({ title, subtitle = '', icon = 'info', iconCls = 'bg-indigo-50 text-indigo-600', body = '', footer = '', size = 'max-w-2xl' }) {
    const root = $('#modal-root');
    root.innerHTML = `
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="modal-backdrop absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" data-action="close-modal"></div>
        <div class="modal-panel relative w-full ${size} max-h-[88vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-slate-200" role="dialog" aria-modal="true" aria-labelledby="modal-title">
          <div class="flex items-start gap-3 px-6 py-5 border-b border-slate-100">
            <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 ${iconCls}"><i data-lucide="${icon}" class="w-5 h-5"></i></span>
            <div class="flex-1 min-w-0">
              <h3 id="modal-title" class="text-base font-semibold text-slate-900">${title}</h3>
              ${subtitle ? `<p class="text-xs text-slate-500 mt-0.5">${subtitle}</p>` : ''}
            </div>
            <button type="button" class="icon-btn w-8 h-8" data-action="close-modal" aria-label="Tutup"><i data-lucide="x" class="w-4 h-4"></i></button>
          </div>
          <div class="px-6 py-5 overflow-y-auto thin-scroll">${body}</div>
          ${footer ? `<div class="px-6 py-4 border-t border-slate-100 bg-slate-50/70 rounded-b-2xl flex flex-wrap justify-end gap-3">${footer}</div>` : ''}
        </div>
      </div>`;
    root.classList.remove('hidden');
    refreshIcons();
  }
  function closeModal() {
    const root = $('#modal-root');
    root.classList.add('hidden');
    root.innerHTML = '';
    state.confirmFn = null;
  }
  function confirmDialog({ title, message, confirmText = 'Ya, Lanjutkan', danger = false, icon = 'triangle-alert', onConfirm }) {
    openModal({
      title, icon, size: 'max-w-md',
      iconCls: danger ? 'bg-rose-50 text-rose-600' : 'bg-indigo-50 text-indigo-600',
      body: `<p class="text-sm text-slate-600 leading-relaxed">${message}</p>`,
      footer: `<button type="button" class="btn btn-outline" data-action="close-modal">Batal</button>
               <button type="button" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-action="confirm-yes">${confirmText}</button>`,
    });
    state.confirmFn = onConfirm;
  }

  function downloadFile(filename, content, mime) {
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
  }
  function toCsv(rows) {
    if (!rows.length) return '';
    const cols = Object.keys(rows[0]);
    const cell = (v) => {
      const s = v === null || v === undefined ? '' : String(v);
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    return [cols.join(','), ...rows.map((r) => cols.map((c) => cell(r[c])).join(','))].join('\n');
  }

  function runCountUps(root = document) {
    $$('[data-countup]', root).forEach((el) => {
      const key = el.dataset.key || '';
      const to = Number(el.dataset.countup) || 0;
      const dec = Number(el.dataset.decimals || 0);
      const from = key in state.countPrev ? state.countPrev[key] : 0;
      state.countPrev[key] = to;
      if (from === to) { el.textContent = fmtNum(to, dec); return; }
      const t0 = performance.now();
      const dur = 900;
      const step = (now) => {
        const k = Math.min(1, (now - t0) / dur);
        const v = from + (to - from) * (1 - Math.pow(1 - k, 3));
        el.textContent = fmtNum(dec ? v : Math.round(v), dec);
        if (k < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    });
  }

  /* --------------------------------------------------------------------------
   * 6. ROUTER
   * ------------------------------------------------------------------------ */
  function switchTab(tabId, opts = {}) {
    if (tabId === 'pengaturan') { openSettingsModal(); return; }
    if (!TABS.includes(tabId)) tabId = 'dashboard';
    const changed = state.tab !== tabId;
    state.tab = tabId;

    $$('.screen-section').forEach((sec) => sec.classList.toggle('is-active', sec.id === `screen-${tabId}`));
    $$('[data-tab]').forEach((link) => {
      const on = link.dataset.tab === tabId;
      link.classList.toggle('is-active', on);
      if (on) link.setAttribute('aria-current', 'page'); else link.removeAttribute('aria-current');
    });

    if (opts.updateHash !== false && location.hash !== `#${tabId}`) {
      try { history.pushState(null, '', `#${tabId}`); } catch (_) { location.hash = tabId; }
    }
    document.title = `${TAB_META[tabId].label} · Bong Garment — Sistem Produksi CMT`;
    if (changed) { const main = $('#main-content'); if (main) main.scrollTo({ top: 0, behavior: 'smooth' }); }
    if (tabId === 'dashboard') animateChartBars();
    refreshIcons();
  }

  /* --------------------------------------------------------------------------
   * 7. ROLE SWITCHER
   * ------------------------------------------------------------------------ */
  function applyRole(role, { announce = false } = {}) {
    if (!ROLES[role]) role = 'Admin';
    state.role = role;
    try { localStorage.setItem(ROLE_KEY, role); } catch (_) { /* ignore */ }
    const cfg = ROLES[role];

    $('#role-label').textContent = role;
    $('#role-avatar').innerHTML = `<i data-lucide="${cfg.icon}" class="w-4 h-4"></i>`;

    $$('.nav-link[data-tab], .nav-link[data-nav]').forEach((el) => {
      const key = el.dataset.tab || el.dataset.nav;
      const ok = cfg.tabs.includes(key);
      el.classList.toggle('role-ok', ok);
      el.classList.toggle('role-dim', !ok);
      el.title = ok ? `Akses utama peran ${role}` : `Di luar akses utama peran ${role}`;
    });
    const screens = cfg.tabs.filter((t) => TABS.includes(t));
    $('#role-access-label').textContent = `${role} · ${screens.length} layar utama`;

    const auditBadge = $('#audit-mode-badge');
    if (auditBadge) {
      if (role === 'Pemilik') {
        auditBadge.classList.remove('hidden');
        auditBadge.classList.add('inline-flex');
      } else {
        auditBadge.classList.add('hidden');
        auditBadge.classList.remove('inline-flex');
      }
    }

    $('#role-options').innerHTML = Object.entries(ROLES).map(([name, r]) => {
      const on = name === role;
      return `
        <button type="button" data-action="set-role" data-role="${name}" class="w-full flex items-center gap-3 p-2.5 rounded-lg text-left transition ${on ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-slate-50'}">
          <span class="w-8 h-8 rounded-lg grid place-items-center shrink-0 ${on ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500'}"><i data-lucide="${r.icon}" class="w-4 h-4"></i></span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-semibold ${on ? 'text-indigo-700' : 'text-slate-800'}">${name}</span>
            <span class="block text-[11px] text-slate-500 truncate">${r.desc}</span>
          </span>
          ${on ? '<i data-lucide="check" class="w-4 h-4 text-indigo-600"></i>' : ''}
        </button>`;
    }).join('');

    refreshIcons();
    if (announce) {
      const names = screens.filter((t) => t !== 'dashboard').map((t) => `<b class="text-slate-700">${TAB_META[t].label}</b>`).join(', ');
      showToast(`Akses utama: Dashboard${names ? ', ' + names : ''}. Menu terkait ditandai titik indigo.`, { type: 'info', title: `Peran aktif: ${role}` });
    }
  }

  /* --------------------------------------------------------------------------
   * 8. DASHBOARD RENDERER (fully derived from BongDB)
   * ------------------------------------------------------------------------ */
  function computeMetrics() {
    const D = BongDB.data;
    const aktif = D.Pesanan.filter((p) => ACTIVE_STATUSES.includes(p.status_pesanan));
    const status = Object.fromEntries(ACTIVE_STATUSES.map((s) => [s, aktif.filter((p) => p.status_pesanan === s).length]));

    const rollStatus = { Tersedia: 0, Dipotong: 0, Habis: 0, Retur: 0 };
    D.Detail_Roll_Bahan.forEach((r) => { rollStatus[r.status_roll] = (rollStatus[r.status_roll] || 0) + 1; });

    const potongOn = (date) => D.Pemotongan_Seri.filter((p) => String(p.tgl_potong).slice(0, 10) === date);
    const todayPotong = potongOn(APP_TODAY);
    const todayGel = Q.gelaranOfPotong(todayPotong);
    const today = {
      pcs: sum(todayPotong, (p) => p.total_lembar_hasil),
      gelaran: todayGel.length,
      rolls: new Set(todayGel.map((g) => g.id_roll)).size,
    };

    const days = range(-6, 0).map((off, i) => {
      const date = addDays(APP_TODAY, off);
      const p = potongOn(date);
      const g = Q.gelaranOfPotong(p);
      return { date, label: CHART_LABELS[i], isToday: off === 0, pcs: sum(p, (x) => x.total_lembar_hasil), gelaran: g.length, rolls: new Set(g.map((x) => x.id_roll)).size, seri: p.length };
    });
    const weekStart = addDays(APP_TODAY, -6);
    const weekPotong = D.Pemotongan_Seri.filter((p) => {
      const dp = String(p.tgl_potong).slice(0, 10);
      return dp >= weekStart && dp <= APP_TODAY;
    });
    const weekGel = Q.gelaranOfPotong(weekPotong);
    const weekRolls = new Set(weekGel.map((g) => g.id_roll)).size;
    const week = {
      pcs: sum(weekGel, (g) => g.total_lembar_gelar),
      gelaran: weekGel.length,
      rolls: weekRolls,
      avgYield: weekRolls ? sum(weekGel, (g) => g.total_lembar_gelar) / weekRolls : 0,
    };

    const pending = D.Kerusakan.filter((k) => k.status === 'Need Rework');
    const pendingOrders = new Set(pending.map((k) => k.id_pesanan));
    const groups = {};
    pending.forEach((k) => {
      groups[k.jenis_cacat] = groups[k.jenis_cacat] || { orders: new Set(), tags: {} };
      groups[k.jenis_cacat].orders.add(k.id_pesanan);
      const tag = defectTag(k.catatan);
      if (tag) groups[k.jenis_cacat].tags[tag] = (groups[k.jenis_cacat].tags[tag] || 0) + 1;
    });
    const pendingText = Object.entries(groups).map(([jenis, g]) => {
      const tag = Object.entries(g.tags).sort((a, b) => b[1] - a[1])[0];
      return `${g.orders.size} ${jenis}${tag ? ` (${tag[0]})` : ''}`;
    }).join(' • ');

    return { aktif, status, rollStatus, today, days, week, pendingCount: pendingOrders.size, pendingText };
  }

  function renderKpis(m) {
    const host = $('#kpi-grid');
    if (!host) return;
    const total = m.aktif.length || 1;
    const seg = (n, cls) => (n ? `<span class="${cls} h-full first:rounded-l-full last:rounded-r-full" style="width:${(n / total * 100).toFixed(2)}%"></span>` : '');
    const pctTarget = Math.round((m.today.pcs / TARGET_HARIAN_PCS) * 100);
    const targetOk = pctTarget >= 100;
    const pendingOk = m.pendingCount === 0;

    host.innerHTML = `
      <article class="card p-5 flex flex-col hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-start justify-between">
          <p class="kpi-label">Total Pesanan Aktif</p>
          <span class="kpi-icon bg-indigo-50 text-indigo-600"><i data-lucide="clipboard-list" class="w-[18px] h-[18px]"></i></span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="kpi-value" data-key="aktif" data-countup="${m.aktif.length}">0</span>
          <span class="text-sm font-medium text-slate-500">Pesanan</span>
        </div>
        <div class="mt-auto pt-4">
          <div class="flex h-2 w-full gap-0.5 overflow-hidden rounded-full bg-slate-100">
            ${seg(m.status.ANTREAN, 'bg-slate-400')}${seg(m.status.PEMOTONGAN, 'bg-indigo-500')}${seg(m.status['DALAM QC'], 'bg-amber-400')}
          </div>
          <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400"></span><b class="font-mono text-slate-800">${m.status.ANTREAN}</b> Antrean</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-500"></span><b class="font-mono text-slate-800">${m.status.PEMOTONGAN}</b> Potong</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span><b class="font-mono text-slate-800">${m.status['DALAM QC']}</b> QC</span>
          </div>
        </div>
      </article>

      <article class="card p-5 flex flex-col hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-start justify-between">
          <p class="kpi-label">Stok Roll Kain Fisik</p>
          <span class="kpi-icon bg-emerald-50 text-emerald-600"><i data-lucide="cylinder" class="w-[18px] h-[18px]"></i></span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="kpi-value" data-key="roll" data-countup="${m.rollStatus.Tersedia}">0</span>
          <span class="text-sm font-medium text-slate-500">Roll Tersedia</span>
        </div>
        <div class="mt-auto pt-4">
          <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Per-Roll Tracking</span>
          <p class="mt-2 text-[11px] text-slate-500">Tiap <code class="font-mono text-slate-700">id_roll</code> memiliki stok unik individu</p>
        </div>
      </article>

      <article class="card p-5 flex flex-col hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
        <div class="flex items-start justify-between">
          <p class="kpi-label">Hasil Potong Hari Ini</p>
          <span class="kpi-icon bg-violet-50 text-violet-600"><i data-lucide="scissors" class="w-[18px] h-[18px]"></i></span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="kpi-value" data-key="pcs" data-countup="${m.today.pcs}">0</span>
          <span class="text-sm font-medium text-slate-500">Lembar (Pcs)</span>
        </div>
        <div class="mt-auto pt-4">
          <p class="text-[11px] font-medium ${targetOk ? 'text-emerald-600' : 'text-amber-600'} inline-flex items-center gap-1">
            <i data-lucide="${targetOk ? 'circle-check' : 'triangle-alert'}" class="w-3.5 h-3.5"></i>
            ${targetOk ? `${Math.min(pctTarget, 100)}% Sesuai target kapasitas` : `${pctTarget}% dari target kapasitas`}
          </p>
          <div class="mt-2 grid grid-cols-2 gap-2">
            <div class="rounded-md bg-slate-50 border border-slate-100 px-2.5 py-1.5"><p class="text-sm font-bold font-mono text-slate-800">${m.today.gelaran}</p><p class="text-[10px] text-slate-500">Gelaran</p></div>
            <div class="rounded-md bg-slate-50 border border-slate-100 px-2.5 py-1.5"><p class="text-sm font-bold font-mono text-slate-800">${m.today.rolls}</p><p class="text-[10px] text-slate-500">Roll Kain</p></div>
          </div>
        </div>
      </article>

      <article class="card p-5 flex flex-col hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 ${pendingOk ? '' : 'border-rose-200/80 bg-gradient-to-br from-white to-rose-50/50'}">
        <div class="flex items-start justify-between">
          <p class="kpi-label">Pending Rework / Dalam QC</p>
          <span class="kpi-icon ${pendingOk ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'}"><i data-lucide="shield-alert" class="w-[18px] h-[18px]"></i></span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="kpi-value ${pendingOk ? '' : 'text-rose-600'}" data-key="pending" data-countup="${m.pendingCount}">0</span>
          <span class="text-sm font-medium text-slate-500">Pesanan</span>
        </div>
        <div class="mt-auto pt-4">
          ${pendingOk
        ? '<span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">TERKENDALI</span><p class="mt-2 text-[11px] text-slate-500">Tidak ada rework tertunda.</p>'
        : `<span class="badge bg-rose-50 text-rose-700 ring-rose-200"><span class="pulse-dot w-1.5 h-1.5 rounded-full bg-rose-500 text-rose-500"></span>PERLU TINDAK LANJUT</span>
               <p class="mt-2 text-[11px] text-slate-600">${esc(m.pendingText)}</p>`}
        </div>
      </article>`;
  }

  function renderOrdersTable() {
    const D = BongDB.data;
    const tbody = $('#tbl-pesanan-body');
    if (!tbody) return;
    const active = D.Pesanan.filter((p) => ACTIVE_STATUSES.includes(p.status_pesanan)).sort((a, b) => a.id_pesanan - b.id_pesanan);
    const rows = state.orderFilter === 'ALL' ? active : active.filter((p) => p.status_pesanan === state.orderFilter);
    const shown = state.showAllOrders ? rows : rows.slice(0, ORDER_ROWS_DEFAULT);

    tbody.innerHTML = shown.map((p) => {
      const pola = Q.pola(p.id_pola);
      const s = Q.sumber(p.id_sumber);
      const sm = SUMBER_META[p.id_sumber] || SUMBER_META.default;
      const st = STATUS_META[p.status_pesanan] || STATUS_META.ANTREAN;
      return `
        <tr class="cursor-pointer hover:bg-slate-50/80" data-action="open-order" data-id="${p.id_pesanan}" id="row-pesanan-${p.id_pesanan}">
          <td class="pl-5 pr-3 py-3.5"><span class="font-mono text-[13px] font-semibold text-indigo-600">#${p.id_pesanan}</span></td>
          <td class="px-3 py-3.5">
            <p class="font-medium text-slate-900 whitespace-nowrap">${esc(pola?.nama_pola || '-')}</p>
            <p class="font-mono text-[10.5px] text-slate-400">${esc(pola?.kode_pola || '')}</p>
          </td>
          <td class="px-3 py-3.5">
            <span class="inline-flex items-center gap-2 text-xs font-medium whitespace-nowrap ${sm.text}">
              <span class="w-6 h-6 rounded-md grid place-items-center ${sm.bg}"><i data-lucide="${sm.icon}" class="w-3.5 h-3.5"></i></span>${esc(s?.nama_sumber || '-')}
            </span>
          </td>
          <td class="px-3 py-3.5 text-[13px] ${p.nama_mitra ? 'text-slate-700' : 'text-slate-300'} whitespace-nowrap">${p.nama_mitra ? esc(p.nama_mitra) : '-'}</td>
          <td class="px-3 py-3.5 min-w-[150px]">
            <div class="flex items-center justify-between gap-2 text-[11px] mb-1">
              <span class="font-mono font-semibold text-slate-800">${p.progress_pct}%</span>
              <span class="text-slate-400 whitespace-nowrap">${progressLabel(p)}</span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full ${st.bar} transition-all duration-700" style="width:${p.progress_pct}%"></div></div>
          </td>
          <td class="pl-3 pr-5 py-3.5 text-right"><span class="badge ${st.badge}">${p.status_pesanan}</span></td>
        </tr>`;
    }).join('') || `<tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pesanan pada filter ini.</td></tr>`;

    // Filter chips
    $$('#order-filters [data-filter]').forEach((b) => b.classList.toggle('is-active', b.dataset.filter === state.orderFilter));
    $$('[data-filter-count]').forEach((el) => {
      const f = el.dataset.filterCount;
      el.textContent = f === 'ALL' ? active.length : active.filter((p) => p.status_pesanan === f).length;
    });

    // Toggle button
    const btn = $('#btn-toggle-orders');
    if (rows.length > ORDER_ROWS_DEFAULT) {
      btn.classList.remove('hidden');
      btn.innerHTML = state.showAllOrders
        ? `Ringkas<i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>`
        : `Tampilkan Semua (${rows.length})<i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>`;
    } else {
      btn.classList.add('hidden');
    }

    // Sumber distribution
    const dist = D.Sumber_Inisiasi.map((s) => ({ s, n: active.filter((p) => p.id_sumber === s.id_sumber).length }));
    const tot = active.length || 1;
    $('#sumber-dist').innerHTML = `
      <div class="flex items-center gap-3">
        <span class="text-[10.5px] font-semibold tracking-wider uppercase text-slate-400 whitespace-nowrap">Sumber</span>
        <div class="flex-1 flex h-1.5 rounded-full overflow-hidden bg-slate-200 gap-0.5 min-w-[80px]">
          ${dist.map(({ s, n }) => (n ? `<span class="${(SUMBER_META[s.id_sumber] || SUMBER_META.default).bar}" style="width:${(n / tot * 100).toFixed(1)}%"></span>` : '')).join('')}
        </div>
      </div>
      <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
        ${dist.map(({ s, n }) => `<span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full ${(SUMBER_META[s.id_sumber] || SUMBER_META.default).bar}"></span>${esc(s.nama_sumber)} <b class="font-mono text-slate-700">${n}</b></span>`).join('')}
      </div>`;
  }

  function pickFeaturedRolls(n = 3) {
    const R = BongDB.data.Detail_Roll_Bahan;
    const by = (s) => R.filter((r) => r.status_roll === s);
    const dipotong = by('Dipotong').sort((a, b) => b.id_roll - a.id_roll);
    const habis = by('Habis').sort((a, b) => b.id_roll - a.id_roll);       // most recently depleted
    const tersedia = by('Tersedia').sort((a, b) => a.id_roll - b.id_roll); // FIFO next to spread
    const picks = [dipotong[0], habis[0], tersedia[0]].filter(Boolean);
    for (const r of [...tersedia, ...dipotong, ...habis]) {
      if (picks.length >= n) break;
      if (!picks.includes(r)) picks.push(r);
    }
    return picks.slice(0, n);
  }

  function renderRollCards(m) {
    const summary = $('#roll-status-summary');
    if (summary) {
      summary.innerHTML = ['Tersedia', 'Dipotong', 'Habis', 'Retur'].map((s) => `
        <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-2.5 py-2">
          <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400"><span class="w-1.5 h-1.5 rounded-full ${ROLL_META[s].dot}"></span>${s}</p>
          <p class="text-base font-bold font-mono text-slate-800 mt-0.5">${fmtNum(m.rollStatus[s] || 0)}</p>
        </div>`).join('');
    }
    const host = $('#roll-cards');
    if (!host) return;
    host.innerHTML = pickFeaturedRolls(3).map((r) => {
      const info = Q.rollInfo(r);
      const meta = ROLL_META[r.status_roll] || ROLL_META.Habis;
      const gel = Q.rollGelaran(r.id_roll);
      let footer = '';
      if (r.status_roll === 'Dipotong') {
        const last = gel.sort((a, b) => b.id_gelar - a.id_gelar)[0];
        footer = `<div class="flex items-center gap-2 rounded-md bg-indigo-50/70 border border-indigo-100 px-3 py-2 text-xs text-indigo-700">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>Digelar pd Potong PK <b class="font-mono">#${last?.id_potong ?? '-'}</b>
            <span class="ml-auto font-mono text-[10.5px] text-indigo-500">${gel.length} gelaran · ${fmtNum(sum(gel, (g) => g.total_lembar_gelar))} pcs</span></div>`;
      } else if (r.status_roll === 'Habis') {
        footer = `<div class="flex items-center gap-2 rounded-md bg-slate-50 border border-slate-100 px-3 py-2 text-xs text-slate-600">
            <i data-lucide="gauge" class="w-3.5 h-3.5 text-slate-400"></i>Total Yield: <b class="font-mono text-slate-900">${fmtNum(sum(gel, (g) => g.total_lembar_gelar))} Pcs</b>
            <span class="ml-auto font-mono text-[10.5px] text-slate-400">${gel.length} gelaran</span></div>`;
      } else if (r.status_roll === 'Tersedia') {
        footer = `<div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 text-xs text-slate-600"><i data-lucide="warehouse" class="w-3.5 h-3.5 text-slate-400"></i>Rak Alokasi: <b class="font-mono text-slate-900">${esc(r.rak_alokasi)}</b></span>
            <button type="button" data-action="siap-gelar" data-id="${r.id_roll}" class="btn btn-primary btn-sm"><i data-lucide="scissors" class="w-3.5 h-3.5"></i>Siap Gelar di Meja Potong</button></div>`;
      } else {
        footer = `<div class="flex items-center gap-2 rounded-md bg-amber-50 border border-amber-100 px-3 py-2 text-xs text-amber-700"><i data-lucide="undo-2" class="w-3.5 h-3.5"></i>Diretur ke supplier</div>`;
      }
      return `
        <div class="group rounded-lg border border-slate-200 p-4 hover:border-indigo-200 hover:shadow-sm transition cursor-pointer" data-action="open-roll" data-id="${r.id_roll}">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <span class="w-10 h-10 rounded-lg grid place-items-center shrink-0 ${meta.icon}"><i data-lucide="cylinder" class="w-[18px] h-[18px]"></i></span>
              <div class="min-w-0">
                <p class="font-mono text-[11px] text-slate-400">id_roll: <b class="text-slate-900 text-[13px]">${r.id_roll}</b></p>
                <p class="text-sm font-semibold text-slate-900 truncate group-hover:text-indigo-700">${esc(info.label)}</p>
              </div>
            </div>
            <span class="badge ${meta.badge}">${r.status_roll}</span>
          </div>
          <div class="mt-2.5 mb-3 flex items-center gap-4 text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1"><i data-lucide="receipt" class="w-3 h-3"></i>Nota #${esc(info.penerimaan?.no_nota || '-')}</span>
            <span class="inline-flex items-center gap-1"><i data-lucide="weight" class="w-3 h-3"></i><b class="font-mono text-slate-700">${fmtNum(r.ukuran_awal, 1)}</b> ${esc(r.satuan || 'Kg')}</span>
            <span class="inline-flex items-center gap-1 font-mono">Roll ${r.no_roll_ke}/${r.total_roll}</span>
          </div>
          ${footer}
        </div>`;
    }).join('') || '<p class="text-sm text-slate-400 text-center py-8">Belum ada data roll.</p>';
  }

  function renderYieldChart(m) {
    const host = $('#yield-chart');
    if (!host) return;
    const max = Math.max(...m.days.map((d) => d.pcs), 1);
    const niceMax = Math.ceil(max / 250) * 250;
    const ticks = [0, 0.25, 0.5, 0.75, 1].map((f) => Math.round(niceMax * f));

    $('#yield-avg').innerHTML = `<i data-lucide="gauge" class="w-3.5 h-3.5"></i>Rata-rata Yield: <span class="font-mono">${fmtNum(m.week.avgYield, 1)}</span> Pcs / Roll`;

    host.innerHTML = `
      <div class="relative h-60">
        <div class="absolute inset-x-0 top-0 bottom-7 flex flex-col-reverse justify-between pointer-events-none">
          ${ticks.map((t) => `<div class="relative border-t ${t === 0 ? 'border-slate-200' : 'border-dashed border-slate-200/80'}"><span class="absolute -top-2 left-0 pr-2 bg-white text-[10px] font-mono text-slate-400">${fmtNum(t)}</span></div>`).join('')}
        </div>
        <div class="absolute inset-0 pl-12 flex items-end gap-3">
          ${m.days.map((d) => `
            <div class="group flex-1 h-full flex flex-col items-center">
              <div class="relative w-full flex-1 flex items-end justify-center">
                <div class="bar relative w-full max-w-[46px] rounded-t-md ${d.isToday ? 'bg-gradient-to-t from-indigo-600 to-indigo-500 shadow-lg shadow-indigo-500/30' : 'bg-indigo-200 group-hover:bg-indigo-300'}" style="height:0%" data-h="${(d.pcs / niceMax * 100).toFixed(2)}">
                  <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-[10.5px] font-mono font-semibold ${d.isToday ? 'text-indigo-700' : 'text-slate-500'}">${d.pcs ? fmtNum(d.pcs) : ''}</span>
                  <div class="pointer-events-none absolute bottom-full mb-8 left-1/2 -translate-x-1/2 w-40 rounded-lg bg-slate-900 text-white text-[11px] p-2.5 shadow-xl opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition z-10">
                    <p class="font-semibold">${fmtDate(d.date)}</p>
                    <p class="text-slate-300 mt-1 font-mono">${fmtNum(d.pcs)} pcs · ${d.seri} seri</p>
                    <p class="text-slate-300 font-mono">${d.gelaran} gelaran · ${d.rolls} roll</p>
                  </div>
                </div>
              </div>
              <span class="h-7 flex items-end text-[11px] ${d.isToday ? 'font-semibold text-indigo-700' : 'text-slate-500'}">${d.label}</span>
            </div>`).join('')}
        </div>
      </div>`;

    $('#yield-summary').innerHTML = [
      ['Total 7 Hari', `${fmtNum(m.week.pcs)}`, 'Pcs'],
      ['Roll Terpakai', `${fmtNum(m.week.rolls)}`, 'Roll'],
      ['Total Gelaran', `${fmtNum(m.week.gelaran)}`, 'Gelaran'],
    ].map(([label, val, unit]) => `
      <div><p class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-400">${label}</p>
      <p class="mt-0.5 text-lg font-bold font-mono text-slate-900">${val} <span class="text-xs font-sans font-medium text-slate-400">${unit}</span></p></div>`).join('');

    if (state.tab === 'dashboard') animateChartBars();
  }
  function animateChartBars() {
    setTimeout(() => {
      $$('#yield-chart .bar').forEach((b) => { b.style.height = `${b.dataset.h}%`; });
    }, 40);
  }

  function renderQcFeed() {
    const host = $('#qc-feed');
    if (!host) return;
    const items = [...BongDB.data.Kerusakan].sort((a, b) => b.id_qc - a.id_qc).slice(0, 3);
    host.innerHTML = items.map((k) => {
      const p = Q.pesanan(k.id_pesanan);
      const pola = p ? Q.pola(p.id_pola) : null;
      const meta = QC_META[k.status] || QC_META['Need Rework'];
      const inspector = Q.user(k.id_user_qc);
      return `
        <div class="flex gap-3 p-3.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-sm transition cursor-pointer" data-action="open-order" data-id="${k.id_pesanan}">
          <span class="w-9 h-9 shrink-0 rounded-lg grid place-items-center ring-1 ring-inset ${meta.iconCls}"><i data-lucide="${meta.icon}" class="w-4 h-4"></i></span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <p class="font-mono text-[11px] text-slate-400">id_qc: <b class="text-slate-800">${k.id_qc}</b></p>
              <span class="badge ${meta.badge}">${esc(k.status.toUpperCase())}</span>
            </div>
            <p class="text-sm font-semibold text-slate-900 mt-1">Pesanan #${k.id_pesanan} (${esc(pola?.nama_pola || '-')})</p>
            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed"><b class="font-semibold text-slate-800">${esc(k.jenis_cacat)} (${k.jumlah_pcs} ${qcUnit(k)})</b> - ${esc(k.catatan)}</p>
            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[10.5px] text-slate-400">
              <span class="inline-flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i>${fmtDate(k.tgl_temuan)}</span>
              <span class="inline-flex items-center gap-1"><i data-lucide="user-check" class="w-3 h-3"></i>${esc(inspector?.nama_user || '-')}</span>
              <span class="inline-flex items-center gap-1"><i data-lucide="wrench" class="w-3 h-3"></i>${esc(k.pic_rework || '-')}</span>
            </p>
          </div>
        </div>`;
    }).join('') || '<p class="text-sm text-slate-400 text-center py-8">Belum ada temuan QC.</p>';
  }

  function renderDashboard() {
    const m = computeMetrics();
    renderKpis(m);
    renderOrdersTable();
    renderRollCards(m);
    renderYieldChart(m);
    renderQcFeed();
  }

  /* --------------------------------------------------------------------------
   * 9. LIGHTWEIGHT PREVIEWS ON SKELETON SCREENS
   * ------------------------------------------------------------------------ */
  function renderSkeletons() {
    $$('[data-skeleton]').forEach((el) => {
      const variant = el.dataset.skeleton;
      const fields = (el.dataset.fields || '').split('|').filter(Boolean);
      const tables = (el.dataset.tables || '').split(',').map((s) => s.trim()).filter(Boolean);
      const step = el.dataset.step;
      const cols = { 2: 'md:grid-cols-2', 3: 'md:grid-cols-3', 4: 'md:grid-cols-4' }[el.dataset.cols || '3'] || 'md:grid-cols-3';
      const box = (h = 'h-10') => `<div class="shimmer ${h} rounded-lg bg-white border border-slate-200"></div>`;
      let inner = '';
      if (variant === 'table') {
        const rows = Number(el.dataset.rows || 3);
        inner = `
          <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-xs">
              <thead class="bg-slate-50 border-b border-slate-200"><tr>${fields.map((f) => `<th class="th ${f.includes('id_roll') ? 'font-mono normal-case tracking-normal' : ''}">${esc(f)}</th>`).join('')}</tr></thead>
              <tbody class="divide-y divide-slate-100">
                ${range(1, rows).map(() => `<tr>${fields.map((_, i) => `<td class="px-3 py-3"><div class="shimmer h-3 rounded bg-slate-100" style="width:${50 + ((i * 17) % 40)}%"></div></td>`).join('')}</tr>`).join('')}
              </tbody>
            </table>
          </div>`;
      } else if (variant === 'metrics') {
        inner = `<div class="grid grid-cols-2 md:grid-cols-${Math.min(fields.length, 4)} gap-3">
          ${fields.map((f) => `<div class="rounded-lg bg-white border border-slate-200 p-3.5"><p class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-400">${esc(f)}</p><div class="shimmer mt-2.5 h-6 w-2/3 rounded bg-slate-100"></div></div>`).join('')}
        </div>`;
      } else {
        inner = `<div class="grid grid-cols-1 ${cols} gap-4">
          ${fields.map((f) => `<div><p class="field-label">${esc(f)}</p>${box()}</div>`).join('')}
        </div>`;
      }
      el.innerHTML = `
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 p-4">
          ${inner}
          <div class="mt-4 pt-3 border-t border-dashed border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
              <i data-lucide="database" class="w-3.5 h-3.5"></i>Relasi tabel:
              ${tables.map((t) => `<code class="font-mono px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-700">${esc(t)}</code>`).join('')}
            </div>
            <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-200"><i data-lucide="hammer" class="w-3 h-3"></i>Diaktifkan pada Step ${esc(step)}</span>
          </div>
        </div>`;
    });
  }

  function renderSumberTiles() {
    const host = $('#sumber-tiles');
    if (!host) return;
    host.innerHTML = BongDB.data.Sumber_Inisiasi.map((s) => {
      const meta = SUMBER_META[s.id_sumber] || SUMBER_META.default;
      const on = s.id_sumber === state.selectedSumber;
      return `
        <button type="button" data-action="select-sumber" data-id="${s.id_sumber}" id="sumber-${s.id_sumber}"
          class="relative text-left p-4 rounded-xl border-2 transition-all duration-150 ${on ? 'border-indigo-500 bg-indigo-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}">
          <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-lg grid place-items-center ${meta.bg}"><i data-lucide="${meta.icon}" class="w-5 h-5"></i></span>
            <span class="w-5 h-5 rounded-full border-2 grid place-items-center ${on ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300'}">${on ? '<span class="w-1.5 h-1.5 rounded-full bg-white"></span>' : ''}</span>
          </div>
          <p class="mt-3 text-sm font-semibold ${on ? 'text-indigo-700' : 'text-slate-900'}">${esc(s.nama_sumber)}</p>
          <p class="mt-1 text-xs text-slate-500 leading-relaxed">${meta.desc}</p>
          <p class="mt-2 font-mono text-[10px] text-slate-400">id_sumber: ${s.id_sumber}</p>
        </button>`;
    }).join('');
    const isMitra = state.selectedSumber === 1;
    const mitraFields = $('#sumber-mitra-fields');
    if (mitraFields) mitraFields.classList.toggle('hidden', !isMitra);
    const internalNote = $('#sumber-internal-note');
    if (internalNote) internalNote.classList.toggle('hidden', isMitra);
    const nm = Q.sumber(state.selectedSumber)?.nama_sumber || '';
    const internalText = $('#sumber-internal-text');
    if (internalText) {
      internalText.textContent = `${nm} — produksi internal, tidak memerlukan data mitra. Nama mitra otomatis disimpan sebagai null sesuai Flowchart Gambar 2-25.`;
    }
    const pill = $('#sumber-selected-pill');
    if (pill) {
      pill.textContent = `# Selected ID: ${state.selectedSumber} (${nm})`;
    }
  }

  function renderEdfQueue() {
    const host = $('#edf-queue');
    if (!host) return;
    const queue = BongDB.data.Pesanan
      .filter((p) => (p.status_pesanan === 'ANTREAN' || p.status_pesanan === 'PEMOTONGAN') && p.id_penerimaan)
      .sort((a, b) => {
        const da = a.deadline_terbaru || a.deadline_awal || a.deadline || addDays(a.tgl_order || APP_TODAY, 14);
        const db = b.deadline_terbaru || b.deadline_awal || b.deadline || addDays(b.tgl_order || APP_TODAY, 14);
        return da.localeCompare(db) || a.id_pesanan - b.id_pesanan;
      });
    const countEl = $('#edf-count');
    if (countEl) countEl.textContent = `${queue.length} pesanan siap`;
    if (state.selectedEdf === null && queue[0]) state.selectedEdf = queue[0].id_pesanan;

    host.innerHTML = queue.map((p, i) => {
      const pola = Q.pola(p.id_pola);
      const dl = p.deadline_terbaru || p.deadline_awal || p.deadline || addDays(p.tgl_order || APP_TODAY, 14);
      const sisa = diffDays(APP_TODAY, dl);
      const urg = sisa <= 7 ? 'bg-rose-50 text-rose-700 ring-rose-200' : sisa <= 14 ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-100 text-slate-600 ring-slate-200';
      const on = p.id_pesanan === state.selectedEdf;
      const isRevised = Boolean(p.deadline_terbaru);
      return `
        <button type="button" data-action="select-edf" data-id="${p.id_pesanan}" class="w-full flex items-center gap-3 p-3 rounded-lg border text-left transition ${on ? 'border-indigo-400 bg-indigo-50/60 ring-2 ring-indigo-500/15' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}">
          <span class="w-7 h-7 shrink-0 rounded-md grid place-items-center text-[11px] font-bold font-mono ${i === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'}">${i + 1}</span>
          <span class="flex-1 min-w-0">
            <span class="block text-[13px] font-semibold text-slate-900 truncate"><span class="font-mono text-indigo-600">#${p.id_pesanan}</span> · ${esc(pola?.nama_pola || '-')}</span>
            <span class="block text-[11px] text-slate-500">Deadline ${fmtDate(dl)} ${isRevised ? '<span class="text-amber-600 font-semibold font-mono">(Revisi)</span>' : ''} · ${p.status_pesanan}</span>
          </span>
          <span class="badge ${urg} font-mono">${sisa < 0 ? 'Lewat ' + Math.abs(sisa) : sisa} hr</span>
        </button>`;
    }).join('') || '<p class="text-xs text-slate-400 py-6 text-center">Tidak ada pesanan dalam antrean.</p>';

    const sel = Q.pesanan(state.selectedEdf);
    const edfSel = $('#edf-selected');
    if (edfSel) {
      edfSel.innerHTML = sel ? `
        <div class="flex items-center justify-between gap-3 p-3 rounded-lg bg-indigo-50/60 border border-indigo-100">
          <div class="flex items-center gap-2 min-w-0">
            <i data-lucide="crosshair" class="w-4 h-4 text-indigo-600 shrink-0"></i>
            <p class="text-xs text-slate-600 truncate">Pesanan: <b class="font-mono text-indigo-700">#${sel.id_pesanan}</b> · ${esc(Q.pola(sel.id_pola)?.nama_pola || '')} · Nota <b class="font-mono">#${esc(Q.penerimaan(sel.id_penerimaan)?.no_nota || '-')}</b></p>
          </div>
          <button type="button" class="btn btn-xs btn-outline shrink-0 text-amber-700 hover:bg-amber-50" data-action="revise-deadline" data-id="${sel.id_pesanan}" title="Revisi deadline target pesanan"><i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-amber-600"></i>Revisi Deadline</button>
        </div>` : '';
    }
  }

  function renderShellMeta() {
    const st = $('#db-status');
    if (st) st.textContent = `17 tabel · ${fmtNum(BongDB.totalRecords())} record`;
    const nr = $('#next-roll-id');
    if (nr) nr.textContent = BongDB.nextId('Detail_Roll_Bahan');
    const np = $('#next-potong-id');
    if (np) np.textContent = BongDB.nextId('Pemotongan_Seri');
    const nq = $('#next-qc-id');
    if (nq) nq.textContent = BongDB.nextId('QC');
  }

  /* --------------------------------------------------------------------------
   * 9B. STEP 2: SCREEN 2 (KELOLA PESANAN & POLA) LOGIC
   * ------------------------------------------------------------------------ */
  function updateSmartJenisPesanan() {
    const inp = $('#inp-jenis-pesanan');
    if (!inp) return;
    let polaName = '';
    if (state.pesanan.polaMode === 'new') {
      polaName = $('#inp-nama-pola')?.value.trim() || 'Pola Pakaian Baru';
    } else {
      const p = Q.pola(state.pesanan.selectedPolaId) || BongDB.data.Pola[0];
      polaName = p ? p.nama_pola : 'Pola Pakaian';
    }

    let suggestion = '';
    if (state.selectedSumber === 1) {
      const mitra = $('#inp-nama-mitra')?.value.trim();
      suggestion = `${polaName} - Pesanan Mitra${mitra ? ' ' + mitra : ''} 2026`;
    } else if (state.selectedSumber === 2) {
      suggestion = `${polaName} - Stok Abadi Seri Produksi 2026`;
    } else {
      suggestion = `${polaName} - Inisiatif Tren Pasar Seri 2026`;
    }

    if (!inp.value || inp.dataset.auto === '1' || inp.dataset.prevPola !== polaName) {
      inp.value = suggestion;
      inp.dataset.auto = '1';
      inp.dataset.prevPola = polaName;
    }
  }

  function renderNewPolaSpecGrid(isCelana = false) {
    const tbody = $('#tbl-spec-new-body');
    if (!tbody) return;
    const defaults = isCelana ? [
      { uk: 'S', ld: '', pj: 98, lb: '', pl: '', lp: 72 },
      { uk: 'M', ld: '', pj: 100, lb: '', pl: '', lp: 76 },
      { uk: 'L', ld: '', pj: 102, lb: '', pl: '', lp: 80 },
      { uk: 'XL', ld: '', pj: 104, lb: '', pl: '', lp: 84 },
    ] : [
      { uk: 'S', ld: 96, pj: 70, lb: 42, pl: 60, lp: '' },
      { uk: 'M', ld: 102, pj: 72, lb: 44, pl: 61, lp: '' },
      { uk: 'L', ld: 108, pj: 74, lb: 46, pl: 62, lp: '' },
      { uk: 'XL', ld: 114, pj: 76, lb: 48, pl: 63, lp: '' },
    ];
    tbody.innerHTML = defaults.map((d) => `
      <tr id="spec-row-${d.uk}" class="hover:bg-slate-50/60 transition-colors">
        <td class="px-3 py-2.5 font-bold font-mono text-slate-800">${d.uk}</td>
        <td class="px-2 py-1.5"><input type="number" class="field-input h-8 text-center font-mono inp-ld" value="${d.ld}" placeholder="—" /></td>
        <td class="px-2 py-1.5"><input type="number" class="field-input h-8 text-center font-mono inp-pj" value="${d.pj}" placeholder="—" /></td>
        <td class="px-2 py-1.5"><input type="number" class="field-input h-8 text-center font-mono inp-lb" value="${d.lb}" placeholder="—" /></td>
        <td class="px-2 py-1.5"><input type="number" class="field-input h-8 text-center font-mono inp-pl" value="${d.pl}" placeholder="—" /></td>
        <td class="px-2 py-1.5"><input type="number" class="field-input h-8 text-center font-mono inp-lp" value="${d.lp}" placeholder="—" /></td>
      </tr>`).join('');
  }

  function renderPesananScreen() {
    const sec = $('#screen-pesanan');
    if (!sec) return;

    // 1. Next Pesanan ID badge
    const nextOrderId = BongDB.nextId('Pesanan');
    const badgeOrderId = $('#next-pesanan-id');
    if (badgeOrderId) badgeOrderId.textContent = nextOrderId;

    // 2. Card 1 - Sumber & Mitra
    const pill = $('#sumber-selected-pill');
    if (pill) {
      const s = Q.sumber(state.selectedSumber);
      pill.textContent = `# Selected ID: ${state.selectedSumber} (${s?.nama_sumber || '-'})`;
    }
    const isMitra = state.selectedSumber === 1;
    const mitraFields = $('#sumber-mitra-fields');
    if (mitraFields) mitraFields.classList.toggle('hidden', !isMitra);
    const internalNote = $('#sumber-internal-note');
    if (internalNote) internalNote.classList.toggle('hidden', isMitra);
    const internalText = $('#sumber-internal-text');
    if (internalText) {
      const nm = Q.sumber(state.selectedSumber)?.nama_sumber || '';
      internalText.textContent = `${nm} — produksi internal, tidak memerlukan data mitra. Nama mitra disimpan sebagai null.`;
    }

    // Mitra Quick Chips & Datalist
    const mitraList = ['PT Busana Indah', 'PT Seragam Nusantara', 'CV Maju Garmindo', 'CV Sport Wear Indo', 'Distro Urban Kasual'];
    const dlMitra = $('#dl-mitra');
    if (dlMitra) {
      dlMitra.innerHTML = mitraList.map((m) => `<option value="${esc(m)}"></option>`).join('');
    }
    const chipsHost = $('#mitra-quick-chips');
    if (chipsHost) {
      chipsHost.innerHTML = mitraList.map((m) => `
        <button type="button" data-action="select-mitra-chip" data-mitra="${esc(m)}" class="chip text-[11px] bg-white border border-indigo-200/80 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
          + ${esc(m)}
        </button>`).join('');
    }

    // Deadline badge & default dates
    const inpDeadlineMitra = $('#inp-deadline-mitra');
    if (inpDeadlineMitra && !inpDeadlineMitra.value) {
      inpDeadlineMitra.value = addDays(APP_TODAY, 14);
    }
    if (inpDeadlineMitra && inpDeadlineMitra.value) {
      const diff = diffDays(APP_TODAY, inpDeadlineMitra.value);
      const remBadge = $('#deadline-remaining-badge');
      if (remBadge) {
        remBadge.textContent = diff < 0 ? `Lewat ${Math.abs(diff)} Hari` : `${diff} Hari Kerja Tersisa`;
        remBadge.className = `badge font-mono ${diff <= 7 ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200'}`;
      }
    }

    // 3. Card 2 - Pola Mode Toggle & Controls
    const btnModeExisting = $('#btn-pola-mode-existing');
    const btnModeNew = $('#btn-pola-mode-new');
    const isExistingMode = state.pesanan.polaMode === 'existing';
    if (btnModeExisting) btnModeExisting.classList.toggle('is-active', isExistingMode);
    if (btnModeNew) btnModeNew.classList.toggle('is-active', !isExistingMode);

    const wrapExisting = $('#pola-mode-existing-wrap');
    const wrapNew = $('#pola-mode-new-wrap');
    if (wrapExisting) wrapExisting.classList.toggle('hidden', !isExistingMode);
    if (wrapNew) wrapNew.classList.toggle('hidden', isExistingMode);

    // Existing Pola dropdown
    const selPolaExisting = $('#sel-pola-existing');
    if (selPolaExisting) {
      const polas = BongDB.data.Pola;
      const curVal = selPolaExisting.value || String(state.pesanan.selectedPolaId || polas[0]?.id_pola || '');
      selPolaExisting.innerHTML = polas.map((p) => {
        const kat = Q.kategori(p.id_kategori);
        return `<option value="${p.id_pola}">[ID: ${p.id_pola}] ${esc(p.nama_pola)} (${esc(p.kode_pola)} · ${esc(kat?.nama_kategori || '-')})</option>`;
      }).join('');
      if (curVal) {
        selPolaExisting.value = curVal;
        state.pesanan.selectedPolaId = Number(curVal);
      }
    }

    // Pola preview strip
    const previewStrip = $('#pola-preview-strip');
    if (previewStrip) {
      const selId = Number(selPolaExisting?.value || state.pesanan.selectedPolaId || BongDB.data.Pola[0]?.id_pola);
      const curPola = Q.pola(selId);
      if (curPola) {
        const specs = BongDB.where('Spesifikasi_Ukuran', (s) => s.id_pola === curPola.id_pola);
        const kat = Q.kategori(curPola.id_kategori);
        const isCelana = specs.some((s) => s.lingkar_pinggang !== null);
        previewStrip.innerHTML = `
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2.5 mb-2.5">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-800 text-sm">${esc(curPola.nama_pola)}</span>
              <span class="font-mono text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">${esc(curPola.kode_pola)}</span>
              <span class="badge bg-slate-100 text-slate-600 ring-slate-200">${esc(kat?.nama_kategori || '-')}</span>
            </div>
            <span class="font-mono text-[11px] text-slate-400">${specs.length} Ukuran Standar (S–XL)</span>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            ${specs.map((s) => `
              <div class="p-2.5 rounded-lg bg-white border border-slate-200">
                <p class="font-bold font-mono text-slate-900">${s.nama_ukuran}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">
                  ${isCelana
            ? `Pinggang: <b class="font-mono text-slate-700">${s.lingkar_pinggang ?? '-'}</b> cm · Pjg: <b class="font-mono text-slate-700">${s.panjang_baju_celana ?? '-'}</b> cm`
            : `Dada: <b class="font-mono text-slate-700">${s.lingkar_dada ?? '-'}</b> cm · Pjg: <b class="font-mono text-slate-700">${s.panjang_baju_celana ?? '-'}</b> cm`}
                </p>
              </div>`).join('')}
          </div>`;
      }
    }

    // New Pola Form elements
    const nextPolaId = BongDB.nextId('Pola');
    const badgePolaId = $('#next-pola-id');
    if (badgePolaId) badgePolaId.textContent = nextPolaId;

    const selKategori = $('#sel-pola-kategori');
    if (selKategori && selKategori.children.length === 0) {
      selKategori.innerHTML = BongDB.data.Kategori_Pola.map((k) => `
        <option value="${k.id_kategori}">${esc(k.nama_kategori)}</option>
      `).join('');
    }

    const inpKodePola = $('#inp-kode-pola');
    if (inpKodePola && !inpKodePola.value) {
      inpKodePola.value = `KMJ-NEW-${String(nextPolaId).padStart(2, '0')}`;
    }

    const specTbody = $('#tbl-spec-new-body');
    if (specTbody && specTbody.children.length === 0) {
      renderNewPolaSpecGrid(false);
    }

    // 4. Card 3 - Transaksi Pesanan
    const inpTglOrder = $('#inp-tgl-order');
    if (inpTglOrder && !inpTglOrder.value) inpTglOrder.value = APP_TODAY;

    const inpDeadlineOrder = $('#inp-deadline-order');
    if (inpDeadlineOrder && !inpDeadlineOrder.value) {
      inpDeadlineOrder.value = isMitra
        ? (inpDeadlineMitra?.value || addDays(APP_TODAY, 14))
        : addDays(APP_TODAY, 21);
    }

    // Dropdown Penerimaan Bahan
    const selPen = $('#sel-penerimaan-order');
    if (selPen) {
      const curPenVal = selPen.value;
      const opts = [
        '<option value="">— Belum Dialokasikan (Menunggu Bahan) —</option>',
        ...BongDB.data.Penerimaan_Bahan.map((p) => {
          const wids = new Set(BongDB.where('Detail_Bahan_Warna', (w) => w.id_penerimaan === p.id_penerimaan).map((w) => w.id_detail_warna));
          const countTersedia = BongDB.where('Detail_Roll_Bahan', (r) => wids.has(r.id_detail_warna) && r.status_roll === 'Tersedia').length;
          return `<option value="${p.id_penerimaan}">Nota #${esc(p.no_nota)} · ${esc(p.nama_kain)} (${countTersedia} roll tersedia)</option>`;
        }),
      ];
      selPen.innerHTML = opts.join('');
      selPen.value = curPenVal;
    }

    // Initial status indicators
    const hasPen = Boolean(selPen?.value);
    const initStatusText = $('#pesanan-initial-status-text');
    if (initStatusText) {
      initStatusText.textContent = hasPen ? 'ANTREAN · 15% Persiapan Gelar' : 'ANTREAN · 0% Menunggu Bahan';
    }
    const initBadge = $('#pesanan-initial-badge');
    if (initBadge) {
      initBadge.className = hasPen ? 'badge bg-indigo-50 text-indigo-700 ring-indigo-200' : 'badge bg-slate-100 text-slate-700 ring-slate-200';
    }

    updateSmartJenisPesanan();

    // 5. Mini-Table Pesanan All
    const allPesanan = [...BongDB.data.Pesanan].sort((a, b) => b.id_pesanan - a.id_pesanan);
    const countBadge = $('#total-pesanan-badge');
    if (countBadge) countBadge.textContent = `${allPesanan.length} Pesanan`;

    const tblPesananBody = $('#tbl-pesanan-all-body');
    if (tblPesananBody) {
      tblPesananBody.innerHTML = allPesanan.map((p) => {
        const pola = Q.pola(p.id_pola);
        const s = Q.sumber(p.id_sumber);
        const sm = SUMBER_META[p.id_sumber] || SUMBER_META.default;
        const st = STATUS_META[p.status_pesanan] || STATUS_META.ANTREAN;
        const dl = p.deadline || addDays(p.tgl_order || APP_TODAY, 14);
        const isFlash = p.id_pesanan === state.pesanan.flashOrderId;
        return `
          <tr class="cursor-pointer hover:bg-slate-50/80 transition-colors ${isFlash ? 'row-flash' : ''}" data-action="open-order" data-id="${p.id_pesanan}">
            <td class="pl-5 pr-3 py-3 font-mono font-bold text-indigo-600">#${p.id_pesanan}</td>
            <td class="px-3 py-3">
              <p class="font-medium text-slate-900">${esc(pola?.nama_pola || '-')}</p>
              <p class="font-mono text-[10.5px] text-slate-400">${esc(pola?.kode_pola || '')}</p>
            </td>
            <td class="px-3 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs ${sm.text}">
                <span class="w-5 h-5 rounded grid place-items-center ${sm.bg}"><i data-lucide="${sm.icon}" class="w-3 h-3"></i></span>
                ${esc(s?.nama_sumber || '-')}
              </span>
            </td>
            <td class="px-3 py-3 text-xs ${p.nama_mitra ? 'text-slate-800 font-medium' : 'text-slate-400'}">${esc(p.nama_mitra || '-')}</td>
            <td class="px-3 py-3 text-xs text-slate-600">${fmtDate(p.tgl_order)}</td>
            <td class="px-3 py-3 text-xs text-slate-600">${fmtDate(dl)}</td>
            <td class="px-3 py-3">
              <div class="flex items-center gap-2">
                <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                  <div class="h-full ${st.bar}" style="width:${p.progress_pct}%"></div>
                </div>
                <span class="font-mono text-[11px] text-slate-600">${p.progress_pct}%</span>
              </div>
            </td>
            <td class="pl-3 pr-5 py-3 text-right"><span class="badge ${st.badge}">${p.status_pesanan}</span></td>
          </tr>`;
      }).join('');
    }
  }

  function submitPesanan() {
    let namaMitra = null;
    let deadline = null;

    if (state.selectedSumber === 1) {
      namaMitra = $('#inp-nama-mitra')?.value.trim();
      deadline = $('#inp-deadline-mitra')?.value;
      if (!namaMitra) {
        showToast('Nama Mitra / Klien wajib diisi untuk Sumber Request Mitra.', { type: 'error' });
        $('#inp-nama-mitra')?.focus();
        return;
      }
      if (!deadline) {
        showToast('Target Deadline Pengiriman wajib ditentukan.', { type: 'error' });
        $('#inp-deadline-mitra')?.focus();
        return;
      }
    } else {
      deadline = $('#inp-deadline-order')?.value || addDays(APP_TODAY, 21);
    }

    let idPola = null;
    let kodePolaBaru = null;

    if (state.pesanan.polaMode === 'new') {
      const idKategori = Number($('#sel-pola-kategori')?.value);
      const kodePola = $('#inp-kode-pola')?.value.trim().toUpperCase();
      const namaPola = $('#inp-nama-pola')?.value.trim();

      if (!idKategori) {
        showToast('Kategori Pola wajib dipilih.', { type: 'error' });
        $('#sel-pola-kategori')?.focus();
        return;
      }
      if (!kodePola) {
        showToast('Kode Pola Unik wajib diisi.', { type: 'error' });
        $('#inp-kode-pola')?.focus();
        return;
      }
      if (BongDB.data.Pola.some((p) => p.kode_pola.toUpperCase() === kodePola)) {
        showToast(`Kode Pola <b>${esc(kodePola)}</b> sudah terdaftar dalam database. Gunakan kode unik lain.`, { type: 'error' });
        $('#inp-kode-pola')?.focus();
        return;
      }
      if (!namaPola) {
        showToast('Nama Master Pola wajib diisi.', { type: 'error' });
        $('#inp-nama-pola')?.focus();
        return;
      }

      // Insert new Pola
      const newPola = BongDB.insert('Pola', {
        id_kategori: idKategori,
        kode_pola: kodePola,
        nama_pola: namaPola,
      });
      idPola = newPola.id_pola;
      kodePolaBaru = kodePola;

      // Insert 4 size specifications (S, M, L, XL)
      const specs = ['S', 'M', 'L', 'XL'].map((uk) => {
        const row = $(`#spec-row-${uk}`);
        const ld = row?.querySelector('.inp-ld')?.value;
        const pj = row?.querySelector('.inp-pj')?.value;
        const lb = row?.querySelector('.inp-lb')?.value;
        const pl = row?.querySelector('.inp-pl')?.value;
        const lp = row?.querySelector('.inp-lp')?.value;

        return {
          id_pola: idPola,
          nama_ukuran: uk,
          diameter_leher: ld ? 18.0 : null,
          panjang_pundak: lb ? Number(lb) : null,
          lingkar_lengan: ld ? 44.0 : null,
          panjang_tangan: pl ? Number(pl) : null,
          lingkar_dada: ld ? Number(ld) : null,
          lingkar_pinggang: lp ? Number(lp) : (ld ? Number(ld) - 2 : null),
          lingkar_selangkang: ld ? null : 28.0,
          lingkar_paha: ld ? null : 58.0,
          lebar_kaki: ld ? null : 34.0,
          lebar_baju: ld ? round1(Number(ld) / 2) : null,
          panjang_baju_celana: pj ? Number(pj) : 70,
        };
      });
      BongDB.insertMany('Spesifikasi_Ukuran', specs);
    } else {
      idPola = Number($('#sel-pola-existing')?.value || state.pesanan.selectedPolaId || 1);
    }

    const jenisPesanan = $('#inp-jenis-pesanan')?.value.trim();
    if (!jenisPesanan) {
      showToast('Jenis Pesanan / Deskripsi Item wajib diisi.', { type: 'error' });
      $('#inp-jenis-pesanan')?.focus();
      return;
    }

    const tglOrder = $('#inp-tgl-order')?.value || APP_TODAY;
    const idPenerimaanVal = $('#sel-penerimaan-order')?.value;
    const idPenerimaan = idPenerimaanVal ? Number(idPenerimaanVal) : null;

    // Insert Pesanan record
    const newPesanan = BongDB.insert('Pesanan', {
      id_pola: idPola,
      id_sumber: state.selectedSumber,
      id_penerimaan: idPenerimaan,
      id_user_admin: 1,
      jenis_pesanan: jenisPesanan,
      nama_mitra: namaMitra,
      tgl_order: tglOrder,
      deadline_awal: deadline,
      deadline_terbaru: deadline,
      deadline: deadline,
      tgl_perubahan_deadline: null,
      alasan_perubahan_deadline: null,
      status_pesanan: 'ANTREAN',
      progress_pct: idPenerimaan ? 15 : 0,
    });

    state.pesanan.flashOrderId = newPesanan.id_pesanan;

    const toastMsg = kodePolaBaru
      ? `Pesanan <b>#${newPesanan.id_pesanan}</b> dan Master Pola <b>${esc(kodePolaBaru)}</b> (#${idPola}) berhasil diinisiasi!`
      : `Pesanan <b>#${newPesanan.id_pesanan}</b> (${esc(jenisPesanan)}) berhasil diinisiasi!`;

    showToast(toastMsg, { title: 'Pesanan Berhasil Disimpan' });

    // Reset form fields cleanly
    resetPesananForm();
    state.pesanan.flashOrderId = newPesanan.id_pesanan;
    renderPesananScreen();

    const tableCard = $('#tbl-pesanan-all-body');
    if (tableCard) {
      tableCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function resetPesananForm() {
    state.selectedSumber = 1;
    state.pesanan.polaMode = 'existing';
    state.pesanan.selectedPolaId = BongDB.data.Pola[0]?.id_pola || 1;
    state.pesanan.flashOrderId = null;

    if ($('#inp-nama-mitra')) $('#inp-nama-mitra').value = '';
    if ($('#inp-deadline-mitra')) $('#inp-deadline-mitra').value = addDays(APP_TODAY, 14);
    if ($('#inp-tgl-order')) $('#inp-tgl-order').value = APP_TODAY;
    if ($('#inp-deadline-order')) $('#inp-deadline-order').value = addDays(APP_TODAY, 14);
    if ($('#sel-penerimaan-order')) $('#sel-penerimaan-order').value = '';
    if ($('#inp-nama-pola')) $('#inp-nama-pola').value = '';
    if ($('#inp-kode-pola')) {
      const nextPolaId = BongDB.nextId('Pola');
      $('#inp-kode-pola').value = `KMJ-NEW-${String(nextPolaId).padStart(2, '0')}`;
    }
    renderNewPolaSpecGrid(false);
    updateSmartJenisPesanan();
    renderPesananScreen();
  }

  /* --------------------------------------------------------------------------
   * 9C. STEP 2: SCREEN 3 (PENERIMAAN & ROLL BAHAN) LOGIC
   * ------------------------------------------------------------------------ */
  function renderColorGroups() {
    const host = $('#color-groups-container');
    if (!host) return;
    host.innerHTML = state.penerimaan.colorGroups.map((cg) => {
      const rollsInGroup = state.penerimaan.draftRolls.filter((r) => r.colorGroupId === cg.id);
      const totalKgInGroup = sum(rollsInGroup, (r) => r.beratKg);
      return `
        <div class="p-4 rounded-xl border border-slate-200 bg-white grid grid-cols-1 md:grid-cols-4 gap-4 items-end" data-cg-card="${cg.id}">
          <div>
            <label class="field-label">Nama Warna Kain <span class="text-rose-500">*</span></label>
            <div class="flex items-center gap-2">
              <input type="color" value="${cg.hexColor || '#1E293B'}" class="w-9 h-10 p-0.5 rounded border border-slate-200 cursor-pointer color-picker" data-cg-id="${cg.id}" title="Pilih warna representasi" />
              <input type="text" value="${esc(cg.namaWarna)}" class="field-input inp-cg-nama" data-cg-id="${cg.id}" placeholder="cth. Black Jet" />
            </div>
          </div>
          <div>
            <label class="field-label">Kode Lab / Lot Warna <span class="text-rose-500">*</span></label>
            <input type="text" value="${esc(cg.kodeWarna)}" class="field-input font-mono uppercase inp-cg-kode" data-cg-id="${cg.id}" placeholder="cth. BLK-30S-01" />
          </div>
          <div>
            <label class="field-label">Rak Alokasi Default</label>
            <input type="text" value="${esc(cg.rakDefault)}" class="field-input font-mono uppercase inp-cg-rak" data-cg-id="${cg.id}" placeholder="cth. B-04" />
          </div>
          <div class="flex items-center justify-between gap-2">
            <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-200 font-mono py-1.5 px-3">
              ${rollsInGroup.length} Roll (${totalKgInGroup.toFixed(1)} kg)
            </span>
            ${state.penerimaan.colorGroups.length > 1 ? `
              <button type="button" data-action="remove-color-group" data-id="${cg.id}" class="btn btn-outline btn-sm text-rose-600 hover:bg-rose-50 border-rose-200" title="Hapus Kelompok Warna">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Hapus
              </button>` : ''}
          </div>
        </div>`;
    }).join('');
    refreshIcons();
  }

  function renderDraftRollsTable() {
    const tbody = $('#tbl-draft-rolls-body');
    if (!tbody) return;
    const baseId = BongDB.nextId('Detail_Roll_Bahan');

    tbody.innerHTML = state.penerimaan.draftRolls.map((r, i) => {
      const rollPk = baseId + i;
      const colorOpts = state.penerimaan.colorGroups.map((cg) => `
        <option value="${cg.id}" ${r.colorGroupId === cg.id ? 'selected' : ''}>${esc(cg.namaWarna)} (${esc(cg.kodeWarna)})</option>
      `).join('');

      return `
        <tr class="hover:bg-slate-50/60 transition-colors" data-roll-idx="${i}">
          <td class="pl-4 py-2.5 font-bold font-mono text-slate-800">Roll #${i + 1}</td>
          <td class="px-3 py-2.5">
            <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-200 font-mono"># id_roll: ${rollPk}</span>
          </td>
          <td class="px-3 py-2.5">
            <select class="field-input h-8 text-xs sel-roll-color" data-index="${i}">
              ${colorOpts}
            </select>
          </td>
          <td class="px-3 py-2.5">
            <div class="relative flex items-center">
              <input type="number" step="0.1" min="1" class="field-input h-8 text-xs font-mono pr-7 inp-roll-weight" data-index="${i}" value="${Number(r.beratKg).toFixed(2)}" />
              <span class="absolute right-2.5 text-xs text-slate-400 font-mono pointer-events-none">kg</span>
            </div>
          </td>
          <td class="px-3 py-2.5">
            <input type="text" class="field-input h-8 text-xs font-mono uppercase inp-roll-rak" data-index="${i}" value="${esc(r.rakAlokasi)}" />
          </td>
          <td class="px-3 py-2.5 text-center">
            <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">• Tersedia</span>
          </td>
          <td class="pr-4 py-2.5 text-center">
            <button type="button" data-action="remove-roll-row" data-index="${i}" class="icon-btn w-7 h-7 text-slate-400 hover:text-rose-600 rounded" ${state.penerimaan.draftRolls.length <= 1 ? 'disabled style="opacity:0.25;cursor:not-allowed"' : ''} title="Hapus roll">
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>`;
    }).join('');

    updateDraftRollsSummary();
    refreshIcons();
  }

  function updateDraftRollsSummary() {
    const totalRolls = state.penerimaan.draftRolls.length;
    const totalKg = sum(state.penerimaan.draftRolls, (r) => r.beratKg);

    const countEl = $('#summary-rolls-count');
    if (countEl) countEl.textContent = `${totalRolls} Roll Terdaftar`;

    const weightEl = $('#summary-rolls-weight');
    if (weightEl) weightEl.textContent = `Total: ${totalKg.toFixed(2)} kg`;
  }

  function renderInventoryRollsTable() {
    const allRolls = BongDB.data.Detail_Roll_Bahan;
    const filter = state.penerimaan.rollFilter;

    // Filter counts
    const countAll = $('#filter-count-roll-all');
    if (countAll) countAll.textContent = allRolls.length;
    const countTersedia = $('#filter-count-roll-tersedia');
    if (countTersedia) countTersedia.textContent = allRolls.filter((r) => r.status_roll === 'Tersedia').length;
    const countDipotong = $('#filter-count-roll-dipotong');
    if (countDipotong) countDipotong.textContent = allRolls.filter((r) => r.status_roll === 'Dipotong').length;
    const countHabis = $('#filter-count-roll-habis');
    if (countHabis) countHabis.textContent = allRolls.filter((r) => r.status_roll === 'Habis').length;
    const countRetur = $('#filter-count-roll-retur');
    if (countRetur) countRetur.textContent = allRolls.filter((r) => r.status_roll === 'Retur').length;

    // Filter buttons is-active toggle
    $$('#penerimaan-roll-filters [data-filter]').forEach((b) => {
      b.classList.toggle('is-active', b.dataset.filter === filter);
    });

    const filtered = filter === 'ALL' ? allRolls : allRolls.filter((r) => r.status_roll === filter);
    const sorted = [...filtered].sort((a, b) => b.id_roll - a.id_roll);

    const tbody = $('#tbl-inventory-rolls-body');
    if (!tbody) return;

    tbody.innerHTML = sorted.map((r) => {
      const info = Q.rollInfo(r);
      const sup = info.penerimaan ? Q.supplier(info.penerimaan.id_supplier) : null;
      const meta = ROLL_META[r.status_roll] || ROLL_META.Habis;
      const isFlash = r.id_roll === state.penerimaan.flashRollId;

      return `
        <tr class="cursor-pointer hover:bg-slate-50/80 transition-colors ${isFlash ? 'row-flash' : ''}" data-action="open-roll" data-id="${r.id_roll}">
          <td class="pl-5 pr-3 py-3 font-mono font-bold text-indigo-600">#${r.id_roll}</td>
          <td class="px-3 py-3">
            <p class="font-medium text-slate-900">${esc(info.label)}</p>
            <p class="font-mono text-[10.5px] text-slate-400">${esc(info.warna?.kode_warna || '')}</p>
          </td>
          <td class="px-3 py-3 text-xs text-slate-600">
            <p class="font-mono">Nota #${esc(info.penerimaan?.no_nota || '-')}</p>
            <p class="text-[11px] text-slate-400 truncate max-w-[140px]">${esc(sup?.nama_supplier || '-')}</p>
          </td>
          <td class="px-3 py-3 text-xs font-mono font-semibold text-slate-800">${fmtNum(r.ukuran_awal, 1)} ${esc(r.satuan || 'Kg')}</td>
          <td class="px-3 py-3 text-xs font-mono text-slate-600">${r.no_roll_ke} / ${r.total_roll}</td>
          <td class="px-3 py-3 text-xs font-mono text-slate-700">${esc(r.rak_alokasi)}</td>
          <td class="pl-3 pr-5 py-3 text-right"><span class="badge ${meta.badge}">${r.status_roll}</span></td>
        </tr>`;
    }).join('') || `<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada roll kain pada filter ini.</td></tr>`;
  }

  function renderPenerimaanScreen(opts = {}) {
    const sec = $('#screen-penerimaan');
    if (!sec) return;

    // 1. Next Penerimaan ID badge
    const nextPenId = BongDB.nextId('Penerimaan_Bahan');
    const badgePenId = $('#next-penerimaan-id');
    if (badgePenId) badgePenId.textContent = nextPenId;

    // Next Roll ID badge
    const nextRollId = BongDB.nextId('Detail_Roll_Bahan');
    const badgeRollId = $('#next-roll-id');
    if (badgeRollId) badgeRollId.textContent = nextRollId;

    // 2. Card 1 - Supplier & Master fields
    const selSupplier = $('#sel-supplier');
    if (selSupplier) {
      const prevVal = opts.selectedSupplierId || selSupplier.value || '1';
      selSupplier.innerHTML = BongDB.data.Supplier.map((s) => `
        <option value="${s.id_supplier}">[ID: ${s.id_supplier}] ${esc(s.nama_supplier)}</option>
      `).join('');
      selSupplier.value = prevVal;

      const sup = Q.supplier(Number(selSupplier.value));
      const box = $('#supplier-info-box');
      if (box) {
        box.innerHTML = sup ? `
          <div class="flex items-center gap-2 truncate">
            <span class="font-semibold text-slate-800">${esc(sup.nama_supplier)}</span>
            <span class="text-slate-300">|</span>
            <span class="text-slate-600"><i data-lucide="phone" class="w-3 h-3 inline mr-1 text-slate-400"></i>${esc(sup.kontak)}</span>
            <span class="text-slate-300">|</span>
            <span class="text-slate-600 truncate"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1 text-slate-400"></i>${esc(sup.alamat)}</span>
          </div>` : 'Pilih supplier di atas untuk melihat kontak & alamat.';
      }
    }

    // Auto-suggest next nota number
    const inpNoNota = $('#inp-no-nota');
    if (inpNoNota && !inpNoNota.value) {
      const maxNota = BongDB.data.Penerimaan_Bahan.reduce((m, p) => {
        const n = parseInt(String(p.no_nota).replace(/\D/g, ''), 10);
        return !isNaN(n) ? Math.max(m, n) : m;
      }, 805);
      inpNoNota.value = String(maxNota + 1);
    }

    // Default dates & text fields
    const inpTglNota = $('#inp-tgl-nota');
    if (inpTglNota && !inpTglNota.value) inpTglNota.value = APP_TODAY;

    const inpNamaKain = $('#inp-nama-kain');
    if (inpNamaKain && !inpNamaKain.value) inpNamaKain.value = 'Cotton Combed 30s';

    const kainQuickChips = $('#kain-quick-chips');
    if (kainQuickChips && kainQuickChips.children.length === 0) {
      const kainList = ['Cotton Combed 30s', 'Flanel Yarn Dyed', 'Canvas 12 oz', 'Twill Drill', 'Cotton Oxford', 'Fleece Cotton'];
      kainQuickChips.innerHTML = kainList.map((k) => `
        <button type="button" data-action="select-kain-chip" data-kain="${esc(k)}" class="chip text-[11px] bg-white border border-indigo-200/80 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700">
          + ${esc(k)}
        </button>`).join('');
    }

    const inpLebar = $('#inp-lebar-gramasi');
    if (inpLebar && !inpLebar.value) inpLebar.value = '72" · 180 gsm';

    const inpKendaraan = $('#inp-no-kendaraan');
    if (inpKendaraan && !inpKendaraan.value) inpKendaraan.value = 'B 9821 TXA';

    const inpPenerima = $('#inp-penerima');
    if (inpPenerima && !inpPenerima.value) inpPenerima.value = 'Dedi Kurniawan (Staf Gudang)';

    // 3. Card 2 - Color Groups
    renderColorGroups();

    // 4. Card 3 - Draft Rolls
    renderDraftRollsTable();

    // 5. Mini-Table Inventory Rolls
    renderInventoryRollsTable();
  }

  function submitPenerimaan() {
    const idSupplier = Number($('#sel-supplier')?.value);
    const noNota = $('#inp-no-nota')?.value.trim();
    const tglNota = $('#inp-tgl-nota')?.value || APP_TODAY;
    const namaKain = $('#inp-nama-kain')?.value.trim();
    const lebarGramasi = $('#inp-lebar-gramasi')?.value.trim();
    const noKendaraan = $('#inp-no-kendaraan')?.value.trim() || '-';
    const penerima = $('#inp-penerima')?.value.trim();

    if (!idSupplier) {
      showToast('Supplier Kain wajib dipilih.', { type: 'error' });
      $('#sel-supplier')?.focus();
      return;
    }
    if (!noNota) {
      showToast('Nomor Nota / Surat Jalan wajib diisi.', { type: 'error' });
      $('#inp-no-nota')?.focus();
      return;
    }
    if (!namaKain) {
      showToast('Nama / Jenis Kain wajib diisi.', { type: 'error' });
      $('#inp-nama-kain')?.focus();
      return;
    }
    if (!lebarGramasi) {
      showToast('Lebar & Gramasi kain wajib diisi.', { type: 'error' });
      $('#inp-lebar-gramasi')?.focus();
      return;
    }
    if (!penerima) {
      showToast('Petugas Penerima Gudang wajib diisi.', { type: 'error' });
      $('#inp-penerima')?.focus();
      return;
    }

    if (!state.penerimaan.colorGroups.length) {
      showToast('Minimal harus ada 1 kelompok variasi warna.', { type: 'error' });
      return;
    }
    for (const cg of state.penerimaan.colorGroups) {
      if (!cg.namaWarna?.trim()) {
        showToast('Nama Warna pada seluruh kelompok warna wajib diisi.', { type: 'error' });
        return;
      }
    }

    if (!state.penerimaan.draftRolls.length) {
      showToast('Minimal harus ada 1 roll kain fisik terdaftar.', { type: 'error' });
      return;
    }
    for (let i = 0; i < state.penerimaan.draftRolls.length; i++) {
      const r = state.penerimaan.draftRolls[i];
      if (!r.beratKg || Number(r.beratKg) <= 0) {
        showToast(`Berat roll pada baris Roll #${i + 1} tidak valid (harus > 0 kg).`, { type: 'error' });
        return;
      }
    }

    // 1. Insert Penerimaan_Bahan
    const newPen = BongDB.insert('Penerimaan_Bahan', {
      id_supplier: idSupplier,
      no_nota: noNota,
      tgl_nota: tglNota,
      nama_kain: namaKain,
      lebar_gramasi: lebarGramasi,
      no_kendaraan: noKendaraan,
      penerima: penerima,
    });

    // 2. Insert Detail_Bahan_Warna & Detail_Roll_Bahan
    const allRollsToInsert = [];
    state.penerimaan.colorGroups.forEach((cg) => {
      const groupRolls = state.penerimaan.draftRolls.filter((r) => r.colorGroupId === cg.id);
      if (!groupRolls.length) return;

      const totalRoll = groupRolls.length;
      const totalKg = round1(sum(groupRolls, (r) => r.beratKg));

      const newWarna = BongDB.insert('Detail_Bahan_Warna', {
        id_penerimaan: newPen.id_penerimaan,
        nama_warna: cg.namaWarna,
        kode_warna: cg.kodeWarna || 'LOT-01',
        total_roll: totalRoll,
        total_ukuran: totalKg,
        satuan_ukur: 'Kg',
      });

      groupRolls.forEach((r, idx) => {
        allRollsToInsert.push({
          id_detail_warna: newWarna.id_detail_warna,
          no_roll_ke: idx + 1,
          ukuran_awal: round1(r.beratKg),
          ukuran_sisa: round1(r.beratKg),
          satuan: 'Kg',
          total_roll: totalRoll,
          status_roll: 'Tersedia',
          rak_alokasi: r.rakAlokasi || cg.rakDefault || 'A-01',
        });
      });
    });

    const insertedRolls = BongDB.insertMany('Detail_Roll_Bahan', allRollsToInsert);
    const minId = insertedRolls[0]?.id_roll;
    const maxId = insertedRolls[insertedRolls.length - 1]?.id_roll;
    const idRangeStr = minId === maxId ? `#${minId}` : `#${minId} s/d #${maxId}`;

    showToast(`Penerimaan Nota <b>#${esc(noNota)}</b> berhasil disimpan! <b>${insertedRolls.length} roll</b> (${sum(allRollsToInsert, (r) => r.ukuran_awal).toFixed(1)} kg) teregistrasi dengan integer PK id_roll: <b>${idRangeStr}</b>.`, { title: 'Penerimaan Bahan Berhasil' });

    state.penerimaan.flashRollId = minId;

    // Reset form to fresh state
    resetPenerimaanForm();
    state.penerimaan.flashRollId = minId;
    renderPenerimaanScreen();

    const invTable = $('#tbl-inventory-rolls-body');
    if (invTable) {
      invTable.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function resetPenerimaanForm() {
    state.penerimaan.nextColorGroupId = 1;
    state.penerimaan.colorGroups = [
      { id: 1, namaWarna: 'Black Jet', kodeWarna: 'BLK-30S-01', rakDefault: 'B-04', hexColor: '#1E293B' },
    ];
    state.penerimaan.draftRolls = [
      { colorGroupId: 1, beratKg: 24.50, rakAlokasi: 'B-04' },
      { colorGroupId: 1, beratKg: 25.00, rakAlokasi: 'B-04' },
      { colorGroupId: 1, beratKg: 25.00, rakAlokasi: 'B-04' },
    ];
    state.penerimaan.flashRollId = null;

    if ($('#sel-supplier')) $('#sel-supplier').value = '1';
    if ($('#inp-no-nota')) {
      const maxNota = BongDB.data.Penerimaan_Bahan.reduce((m, p) => {
        const n = parseInt(String(p.no_nota).replace(/\D/g, ''), 10);
        return !isNaN(n) ? Math.max(m, n) : m;
      }, 805);
      $('#inp-no-nota').value = String(maxNota + 1);
    }
    if ($('#inp-tgl-nota')) $('#inp-tgl-nota').value = APP_TODAY;
    if ($('#inp-nama-kain')) $('#inp-nama-kain').value = 'Cotton Combed 30s';
    if ($('#inp-lebar-gramasi')) $('#inp-lebar-gramasi').value = '72" · 180 gsm';
    if ($('#inp-no-kendaraan')) $('#inp-no-kendaraan').value = 'B 9821 TXA';
    if ($('#inp-penerima')) $('#inp-penerima').value = 'Dedi Kurniawan (Staf Gudang)';

    renderPenerimaanScreen();
  }

  function modalAddSupplier() {
    openModal({
      title: 'Tambah Supplier Kain Baru',
      subtitle: 'Mendaftarkan rekanan supplier ke tabel Supplier',
      icon: 'truck',
      size: 'max-w-md',
      body: `
        <div class="space-y-4">
          <div>
            <label for="inp-new-sup-nama" class="field-label">Nama Supplier / Perusahaan <span class="text-rose-500">*</span></label>
            <input id="inp-new-sup-nama" type="text" class="field-input" placeholder="cth. CV Tekstil Perkasa Abadi" />
          </div>
          <div>
            <label for="inp-new-sup-kontak" class="field-label">Nomor Kontak / Telepon <span class="text-rose-500">*</span></label>
            <input id="inp-new-sup-kontak" type="text" class="field-input" placeholder="cth. 0812-3456-7890" />
          </div>
          <div>
            <label for="inp-new-sup-alamat" class="field-label">Alamat Lengkap Gudang / Pabrik <span class="text-rose-500">*</span></label>
            <textarea id="inp-new-sup-alamat" rows="2" class="field-input h-auto py-2" placeholder="cth. Jl. Industri Raya No. 45, Bandung"></textarea>
          </div>
        </div>`,
      footer: `
        <button type="button" class="btn btn-outline" data-action="close-modal">Batal</button>
        <button type="button" class="btn btn-primary" data-action="save-new-supplier"><i data-lucide="plus" class="w-4 h-4"></i>Simpan Supplier</button>`,
    });
  }

  /* --------------------------------------------------------------------------
   * 9C. STEP 4: SCREEN 4 (PEMOTONGAN SERI & GELARAN) LOGIC
   * ------------------------------------------------------------------------ */
  function renderPemotonganScreen(opts = {}) {
    const selPesanan = $('#sel-potong-pesanan');
    if (!selPesanan) return;

    // 1. Populate Target Orders (priority: ANTREAN / PEMOTONGAN with id_penerimaan)
    const activeOrders = BongDB.data.Pesanan
      .filter((p) => (p.status_pesanan === 'ANTREAN' || p.status_pesanan === 'PEMOTONGAN') && p.id_penerimaan)
      .sort((a, b) => {
        const da = a.deadline || addDays(a.tgl_order || APP_TODAY, 14);
        const db = b.deadline || addDays(b.tgl_order || APP_TODAY, 14);
        return da.localeCompare(db) || a.id_pesanan - b.id_pesanan;
      });

    // Also include any other active orders if none in queue
    const allEligible = activeOrders.length ? activeOrders : BongDB.data.Pesanan.filter((p) => p.status_pesanan !== 'SELESAI');

    const curSelId = opts.selectedOrderId || state.selectedEdf || (allEligible[0] ? allEligible[0].id_pesanan : 1);
    state.selectedEdf = curSelId;

    selPesanan.innerHTML = allEligible.map((p) => {
      const pola = Q.pola(p.id_pola);
      const sumber = Q.sumber(p.id_sumber);
      const pen = Q.penerimaan(p.id_penerimaan);
      const mitraText = p.nama_mitra ? ` (${p.nama_mitra})` : '';
      return `<option value="${p.id_pesanan}" ${p.id_pesanan === curSelId ? 'selected' : ''}>#${p.id_pesanan} · ${esc(pola?.nama_pola || '-')} — ${esc(sumber?.nama_sumber || '')}${esc(mitraText)}${pen ? ' [Nota #' + esc(pen.no_nota) + ']' : ''}</option>`;
    }).join('');

    const currentOrder = Q.pesanan(curSelId) || allEligible[0];

    // 2. Auto-suggest Kode Seri
    const inpKode = $('#inp-kode-seri');
    if (inpKode && (!inpKode.value || inpKode.dataset.auto === '1' || inpKode.dataset.prevOrder !== String(curSelId))) {
      const existingSeriCount = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === curSelId).length;
      inpKode.value = `SR-${curSelId}-${String(existingSeriCount + 1).padStart(2, '0')}`;
      inpKode.dataset.auto = '1';
      inpKode.dataset.prevOrder = String(curSelId);
    }

    // 3. Tanggal Potong
    const inpTgl = $('#inp-tgl-potong');
    if (inpTgl && !inpTgl.value) inpTgl.value = APP_TODAY;

    // 4. Operator Pemotong
    const selCutter = $('#sel-user-pemotong');
    if (selCutter) {
      const cutters = BongDB.where('User', (u) => u.role === 'Pemotong');
      selCutter.innerHTML = cutters.map((u) => `<option value="${u.id_user}">[ID: ${u.id_user}] ${esc(u.nama_user)} — ${esc(u.role)}</option>`).join('');
    }

    // 5. Populate Detail Warna Bahan
    const selWarna = $('#sel-potong-warna');
    if (selWarna) {
      const penId = currentOrder?.id_penerimaan;
      const orderColors = penId ? BongDB.where('Detail_Bahan_Warna', (w) => w.id_penerimaan === penId) : [];
      const otherColors = BongDB.data.Detail_Bahan_Warna.filter((w) => w.id_penerimaan !== penId);

      const colorOptions = [...orderColors, ...otherColors].map((w) => {
        const pen = Q.penerimaan(w.id_penerimaan);
        const rolls = BongDB.where('Detail_Roll_Bahan', (r) => r.id_detail_warna === w.id_detail_warna);
        const availCount = rolls.filter((r) => r.status_roll === 'Tersedia' || r.status_roll === 'Dipotong').length;
        const isFromOrder = w.id_penerimaan === penId;
        return {
          id: w.id_detail_warna,
          label: `[ID: ${w.id_detail_warna}] ${esc(pen?.nama_kain || 'Kain')} · ${esc(w.nama_warna)} (Lot: ${esc(w.kode_warna || '-')} · ${availCount} Roll Tersedia)${isFromOrder ? ' ★ Nota Pesanan' : ''}`,
          availCount,
        };
      });

      const prevVal = selWarna.value ? Number(selWarna.value) : null;
      selWarna.innerHTML = colorOptions.map((opt, i) => `<option value="${opt.id}" ${(prevVal === opt.id || (!prevVal && i === 0)) ? 'selected' : ''}>${opt.label}</option>`).join('');
    }

    // 6. Ensure draft rows are initialized
    if (!state.pemotongan.draftGelarRows || !state.pemotongan.draftGelarRows.length) {
      const selectedWarnaId = Number($('#sel-potong-warna')?.value || 1);
      const availRolls = BongDB.where('Detail_Roll_Bahan', (r) => (r.status_roll === 'Tersedia' || r.status_roll === 'Dipotong'));
      const preferredRoll = availRolls.find((r) => r.id_detail_warna === selectedWarnaId) || availRolls[0];
      state.pemotongan.draftGelarRows = [
        {
          id_roll: preferredRoll ? preferredRoll.id_roll : 4503,
          panjang_gelar: 2.2,
          jumlah_tumpuk: 50,
          total_lembar_gelar: 60,
          status_akhir: 'Dipotong',
        },
      ];
    }

    renderDraftGelarTable();
    updateCuttingYieldSummary();
    renderCuttingSeriesTable();
  }

  function getAvailableRollOptions(selectedWarnaId) {
    const rolls = BongDB.where('Detail_Roll_Bahan', (r) => r.status_roll === 'Tersedia' || r.status_roll === 'Dipotong');
    rolls.sort((a, b) => {
      const matchA = a.id_detail_warna === selectedWarnaId ? 0 : 1;
      const matchB = b.id_detail_warna === selectedWarnaId ? 0 : 1;
      return matchA - matchB || a.id_roll - b.id_roll;
    });
    return rolls;
  }

  function renderDraftGelarTable() {
    const tbody = $('#tbl-draft-gelar-body');
    if (!tbody) return;

    const selectedWarnaId = Number($('#sel-potong-warna')?.value || 1);
    const availRolls = getAvailableRollOptions(selectedWarnaId);

    const rows = state.pemotongan.draftGelarRows || [];
    tbody.innerHTML = rows.map((r, idx) => {
      const statusAkhir = state.pemotongan.rollStatusMap[r.id_roll] || r.status_akhir || 'Dipotong';
      r.status_akhir = statusAkhir;

      const rollOptionsHtml = availRolls.map((ar) => {
        const info = Q.rollInfo(ar);
        const isMatch = ar.id_detail_warna === selectedWarnaId;
        return `<option value="${ar.id_roll}" ${ar.id_roll === r.id_roll ? 'selected' : ''}>[ID Roll: ${ar.id_roll}] ${esc(info.label)} (${ar.ukuran_awal} ${esc(ar.satuan || 'Kg')} · ${ar.status_roll} · Rak ${esc(ar.rak_alokasi || '-')})${isMatch ? ' ★' : ''}</option>`;
      }).join('');

      return `
        <tr class="hover:bg-slate-50/70 transition-colors">
          <td class="pl-5 py-2.5">
            <select class="field-select font-mono text-xs sel-gelar-roll" data-index="${idx}">
              ${rollOptionsHtml || `<option value="${r.id_roll}">[ID Roll: ${r.id_roll}]</option>`}
            </select>
          </td>
          <td class="py-2.5">
            <div class="relative">
              <input type="number" step="0.1" min="0.1" class="field-input font-mono text-xs pr-6 inp-panjang-gelar" data-index="${idx}" value="${r.panjang_gelar}" />
              <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">m</span>
            </div>
          </td>
          <td class="py-2.5">
            <div class="relative">
              <input type="number" min="1" class="field-input font-mono text-xs pr-8 inp-jumlah-tumpuk" data-index="${idx}" value="${r.jumlah_tumpuk}" />
              <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Ply</span>
            </div>
          </td>
          <td class="py-2.5">
            <div class="relative">
              <input type="number" min="1" class="field-input font-mono text-xs pr-14 font-semibold text-indigo-700 inp-lembar-gelar" data-index="${idx}" value="${r.total_lembar_gelar}" />
              <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-mono">Lembar</span>
            </div>
          </td>
          <td class="py-2.5">
            <select class="field-select font-mono text-xs sel-gelar-status" data-index="${idx}" data-roll-id="${r.id_roll}">
              <option value="Dipotong" ${statusAkhir === 'Dipotong' ? 'selected' : ''}>• Dipotong (Masih Sisa di Meja)</option>
              <option value="Habis" ${statusAkhir === 'Habis' ? 'selected' : ''}>• Habis (Terpakai Penuh)</option>
            </select>
          </td>
          <td class="pr-5 py-2.5 text-center">
            <button type="button" data-action="remove-gelar-row" data-index="${idx}" class="p-1.5 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus baris gelaran">
              <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
          </td>
        </tr>`;
    }).join('');

    const totalRowsEl = $('#lbl-total-gelaran-rows');
    if (totalRowsEl) totalRowsEl.textContent = String(rows.length);

    const totalPanjang = rows.reduce((t, r) => t + (Number(r.panjang_gelar) || 0), 0);
    const totalPanjangEl = $('#lbl-total-panjang-m');
    if (totalPanjangEl) totalPanjangEl.textContent = totalPanjang.toFixed(1);

    refreshIcons();
  }

  function updateCuttingYieldSummary() {
    const rows = state.pemotongan.draftGelarRows || [];
    const totalLembar = sum(rows, (r) => r.total_lembar_gelar);
    const inpTotalHasil = $('#inp-total-lembar-hasil');
    if (inpTotalHasil) inpTotalHasil.value = totalLembar;

    const planned = Number($('#inp-lembar-rencana')?.value) || 120;
    const yieldVsPlanned = $('#yield-vs-planned');
    if (yieldVsPlanned) {
      const pct = Math.round((totalLembar / Math.max(planned, 1)) * 100);
      const uniqueRolls = new Set(rows.map((r) => r.id_roll)).size;
      const avg = uniqueRolls ? Math.round(totalLembar / uniqueRolls) : 0;
      yieldVsPlanned.innerHTML = `
        <i data-lucide="${pct >= 100 ? 'check' : 'alert-circle'}" class="w-3.5 h-3.5 ${pct >= 100 ? 'text-emerald-600' : 'text-amber-500'}"></i>
        <span>${pct}% dari rencana (${planned} Lembar) · Rata-rata ${avg} Pcs/Roll (${rows.length} Gelaran)</span>`;
      refreshIcons();
    }

    const uniqueRollIds = [...new Set(rows.map((r) => r.id_roll))];
    const inpBerat = $('#inp-berat-terpakai');
    if (inpBerat && (!inpBerat.value || inpBerat.dataset.auto === '1')) {
      const totalRollKg = sum(uniqueRollIds.map((id) => Q.roll(id)), (r) => r?.ukuran_awal || 24);
      inpBerat.value = (Math.round(totalRollKg * 10) / 10).toFixed(1);
      inpBerat.dataset.auto = '1';
    }

    const host = $('#cutting-rolls-status-cards');
    if (host) {
      if (!uniqueRollIds.length) {
        host.innerHTML = '<p class="text-xs text-slate-400 py-4 text-center">Pilih roll fisik pada tabel gelaran.</p>';
        return;
      }
      host.innerHTML = uniqueRollIds.map((idRoll) => {
        const roll = Q.roll(idRoll);
        const info = roll ? Q.rollInfo(roll) : { label: `Roll #${idRoll}` };
        const curStatus = state.pemotongan.rollStatusMap[idRoll] || 'Dipotong';
        const isDipotong = curStatus === 'Dipotong';
        return `
          <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-white">
            <div class="min-w-0 pr-2">
              <p class="text-xs font-semibold font-mono text-slate-800 truncate">Roll ID: #${idRoll} (${esc(info.label)})</p>
              <p class="text-[11px] text-slate-400 font-mono">${roll?.ukuran_awal || '-'} ${esc(roll?.satuan || 'Kg')} · Rak ${esc(roll?.rak_alokasi || '-')}</p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button type="button" data-action="set-roll-post-status" data-roll-id="${idRoll}" data-status="Dipotong" class="px-2.5 py-1 text-[11px] font-mono rounded font-semibold transition ${isDipotong ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">• Dipotong</button>
              <button type="button" data-action="set-roll-post-status" data-roll-id="${idRoll}" data-status="Habis" class="px-2.5 py-1 text-[11px] font-mono rounded font-semibold transition ${!isDipotong ? 'bg-slate-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">• Habis</button>
            </div>
          </div>`;
      }).join('');
    }
  }

  function renderCuttingSeriesTable() {
    const tbody = $('#tbl-cutting-history-body');
    if (!tbody) return;

    const list = [...BongDB.data.Pemotongan_Seri].sort((a, b) => b.id_potong - a.id_potong).slice(0, 10);
    tbody.innerHTML = list.map((ps) => {
      const p = Q.pesanan(ps.id_pesanan);
      const pola = p ? Q.pola(p.id_pola) : null;
      const pemotong = Q.user(ps.id_user_pemotong);
      const pwList = BongDB.where('Detail_Pemotongan_Warna', (w) => w.id_potong === ps.id_potong);
      const pwIds = new Set(pwList.map((w) => w.id_potong_warna));
      const gelaran = BongDB.where('Detail_Gelaran_Kain', (g) => pwIds.has(g.id_potong_warna));
      const rollsUsed = [...new Set(gelaran.map((g) => g.id_roll))];

      return `
        <tr class="hover:bg-slate-50/70 transition-colors">
          <td class="pl-5 py-3 font-mono font-bold text-indigo-700">#${ps.id_potong}</td>
          <td class="py-3 font-mono font-semibold text-slate-800">${esc(ps.kode_seri)}</td>
          <td class="py-3">
            <span class="font-medium text-slate-900 cursor-pointer hover:text-indigo-600" data-action="open-order" data-id="${ps.id_pesanan}">#${ps.id_pesanan} · ${esc(pola?.nama_pola || '-')}</span>
            <span class="block text-[11px] text-slate-400">${esc(ps.jenis_variasi || 'Badan Utama')}</span>
          </td>
          <td class="py-3 text-slate-600">${esc(pemotong?.nama_user || '-')}</td>
          <td class="py-3">
            <div class="flex flex-wrap gap-1">
              ${rollsUsed.map((rid) => `<button type="button" data-action="open-roll" data-id="${rid}" class="badge font-mono bg-slate-100 hover:bg-slate-200 text-slate-700">#${rid}</button>`).join('') || '<span class="text-slate-400">-</span>'}
            </div>
          </td>
          <td class="py-3 text-center font-mono font-semibold text-slate-700">${gelaran.length}</td>
          <td class="py-3 text-right font-mono font-bold text-slate-900">${fmtNum(ps.total_lembar_hasil)} Pcs</td>
          <td class="pr-5 py-3 text-right font-mono text-slate-500">${fmtDate(ps.tgl_potong)}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="8" class="text-center py-6 text-slate-400">Belum ada seri pemotongan.</td></tr>';
  }

  function submitPemotongan() {
    const idPesanan = Number($('#sel-potong-pesanan')?.value);
    const kodeSeri = $('#inp-kode-seri')?.value.trim();
    const jenisVariasi = $('#inp-jenis-variasi')?.value || 'Badan Utama + Lengan';
    const tglPotong = $('#inp-tgl-potong')?.value || APP_TODAY;
    const idUserPemotong = Number($('#sel-user-pemotong')?.value || 2);
    const idDetailWarna = Number($('#sel-potong-warna')?.value || 1);
    const lembarRencana = Number($('#inp-lembar-rencana')?.value) || 120;
    const beratTerpakai = Number($('#inp-berat-terpakai')?.value) || 24.0;
    const totalLembarHasil = Number($('#inp-total-lembar-hasil')?.value) || 0;
    const advanceQc = $('#chk-advance-qc')?.checked;

    if (!idPesanan) {
      showToast('Pilih Pesanan Target terlebih dahulu.', { type: 'error' });
      $('#sel-potong-pesanan')?.focus();
      return;
    }
    if (!kodeSeri) {
      showToast('Kode Seri Potong wajib diisi.', { type: 'error' });
      $('#inp-kode-seri')?.focus();
      return;
    }

    const rows = state.pemotongan.draftGelarRows || [];
    if (!rows.length) {
      showToast('Minimal harus ada 1 baris gelaran roll kain fisik.', { type: 'error' });
      return;
    }

    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];
      if (!r.id_roll) { showToast(`Baris gelaran #${i + 1} belum memilih roll kain fisik.`, { type: 'error' }); return; }
      if (Number(r.panjang_gelar) <= 0) { showToast(`Baris gelaran #${i + 1}: Panjang Gelar harus > 0 meter.`, { type: 'error' }); return; }
      if (Number(r.jumlah_tumpuk) <= 0) { showToast(`Baris gelaran #${i + 1}: Jumlah Tumpuk harus > 0 ply.`, { type: 'error' }); return; }
      if (Number(r.total_lembar_gelar) <= 0) { showToast(`Baris gelaran #${i + 1}: Total Lembar Gelar harus > 0 lembar.`, { type: 'error' }); return; }
    }

    // 1. Insert into Pemotongan_Seri
    const newSeri = BongDB.insert('Pemotongan_Seri', {
      id_pesanan: idPesanan,
      id_user_pemotong: idUserPemotong,
      kode_seri: kodeSeri,
      jenis_variasi: jenisVariasi,
      total_lembar_hasil: totalLembarHasil,
      tgl_potong: tglPotong,
    });

    // 2. Insert into Detail_Pemotongan_Warna
    const newPW = BongDB.insert('Detail_Pemotongan_Warna', {
      id_potong: newSeri.id_potong,
      id_detail_warna: idDetailWarna,
      total_lembar_direncanakan: lembarRencana,
      kain_terpakai: beratTerpakai,
    });

    // 3. Insert into Detail_Gelaran_Kain
    const gelaranToInsert = rows.map((r) => ({
      id_potong_warna: newPW.id_potong_warna,
      id_roll: Number(r.id_roll),
      panjang_gelar: Number(r.panjang_gelar),
      jumlah_tumpuk: Number(r.jumlah_tumpuk),
      total_lembar_gelar: Number(r.total_lembar_gelar),
    }));
    BongDB.insertMany('Detail_Gelaran_Kain', gelaranToInsert);

    // 4. Update status & deduct ukuran_sisa of each used physical roll
    const uniqueRollIds = [...new Set(rows.map((r) => Number(r.id_roll)))];
    uniqueRollIds.forEach((rid) => {
      const chosenStatus = state.pemotongan.rollStatusMap[rid] || 'Dipotong';
      const rollRecord = BongDB.find('Detail_Roll_Bahan', rid);
      const usedInRows = rows.filter((r) => Number(r.id_roll) === rid);
      const totalLen = usedInRows.reduce((acc, curr) => acc + (Number(curr.panjang_gelar) || 0) * (Number(curr.jumlah_tumpuk) || 0), 0);

      let sisa = rollRecord ? Number(rollRecord.ukuran_sisa ?? rollRecord.ukuran_awal ?? 25.0) : 25.0;
      if (chosenStatus === 'Habis') {
        sisa = 0;
      } else {
        const deduction = totalLen > 0 ? (totalLen * 0.15) : 5.0;
        sisa = Math.max(0, round1(sisa - Math.min(sisa, deduction)));
      }
      const finalStatus = (sisa <= 0) ? 'Habis' : chosenStatus;

      BongDB.update('Detail_Roll_Bahan', rid, {
        status_roll: finalStatus,
        ukuran_sisa: sisa,
        rak_alokasi: finalStatus === 'Habis' ? '-' : 'Meja Potong 01',
      });
    });

    // 5. Update Pesanan progress & status
    const targetOrder = Q.pesanan(idPesanan);
    const newStatus = advanceQc ? 'DALAM QC' : 'PEMOTONGAN';
    const newPct = advanceQc ? 90 : Math.min(85, Math.max(70, Number(targetOrder?.progress_pct || 0) + 20));
    const orderUpdates = {
      status_pesanan: newStatus,
      progress_pct: newPct,
    };
    if (targetOrder && !targetOrder.id_penerimaan) {
      const w = Q.warna(idDetailWarna);
      if (w) orderUpdates.id_penerimaan = w.id_penerimaan;
    }
    BongDB.update('Pesanan', idPesanan, orderUpdates);

    showToast(`Seri Pemotongan <b>${esc(kodeSeri)}</b> (#${newSeri.id_potong}) berhasil disimpan! <b>${fmtNum(totalLembarHasil)} pcs</b> dipotong across <b>${rows.length} gelaran</b>. Pesanan #${idPesanan} diperbarui ke status <b>${newStatus}</b> (${newPct}%).`, { title: 'Pemotongan Seri Berhasil' });

    resetPemotonganForm();
    renderAll();
  }

  function resetPemotonganForm() {
    state.pemotongan.draftGelarRows = [];
    state.pemotongan.rollStatusMap = {};
    const inpKode = $('#inp-kode-seri');
    if (inpKode) {
      inpKode.value = '';
      delete inpKode.dataset.auto;
      delete inpKode.dataset.prevOrder;
    }
    const chk = $('#chk-advance-qc');
    if (chk) chk.checked = false;
    renderPemotonganScreen();
  }

  /* --------------------------------------------------------------------------
   * 9D. STEP 5: SCREEN 5 (QUALITY CONTROL & KERUSAKAN) LOGIC
   * ------------------------------------------------------------------------ */
  function renderQcScreen(opts = {}) {
    const selPesanan = $('#sel-qc-pesanan');
    if (!selPesanan) return;

    // 1. Populate Target Orders (prioritizing DALAM QC and PEMOTONGAN)
    const orders = [...BongDB.data.Pesanan].sort((a, b) => {
      const prio = (s) => (s === 'DALAM QC' ? 1 : s === 'PEMOTONGAN' ? 2 : s === 'ANTREAN' ? 3 : 4);
      return prio(a.status_pesanan) - prio(b.status_pesanan) || a.id_pesanan - b.id_pesanan;
    });

    const curSelId = opts.selectedOrderId || state.qc.selectedOrderId || (orders[0] ? orders[0].id_pesanan : 1);
    state.qc.selectedOrderId = curSelId;

    selPesanan.innerHTML = orders.map((p) => {
      const pola = Q.pola(p.id_pola);
      const mitraText = p.nama_mitra ? ` · ${p.nama_mitra}` : '';
      return `<option value="${p.id_pesanan}" ${p.id_pesanan === curSelId ? 'selected' : ''}>[ID: ${p.id_pesanan}] ${esc(pola?.nama_pola || '-')} (${p.status_pesanan})${esc(mitraText)}</option>`;
    }).join('');

    // Update Order Info Box
    const selectedOrder = Q.pesanan(curSelId);
    const box = $('#qc-order-info-box');
    if (box && selectedOrder) {
      const seri = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === curSelId);
      const gel = Q.gelaranOfPotong(seri);
      const totalCutPcs = sum(seri, (s) => s.total_lembar_hasil);
      const rollsCount = new Set(gel.map((g) => g.id_roll)).size;
      const meta = STATUS_META[selectedOrder.status_pesanan] || STATUS_META['ANTREAN'];

      box.innerHTML = `
        <span class="badge ${meta.badge} font-mono font-bold">${selectedOrder.status_pesanan} · ${selectedOrder.progress_pct}%</span>
        <span class="text-slate-600 font-mono">${fmtNum(totalCutPcs)} Pcs (${rollsCount} Roll)</span>`;
    }

    // 2. Date input
    const inpTgl = $('#inp-tgl-temuan');
    if (inpTgl && !inpTgl.value) inpTgl.value = APP_TODAY;

    // 3. User QC dropdown
    const selQcUser = $('#sel-user-qc');
    if (selQcUser) {
      const qcUsers = BongDB.where('User', (u) => u.role === 'QC' || u.role === 'Admin');
      selQcUser.innerHTML = qcUsers.map((u) => `<option value="${u.id_user}">[ID: ${u.id_user}] ${esc(u.nama_user)} — ${esc(u.role)}</option>`).join('');
    }

    // 4. Update Defect Category quick-picks & select
    const cat = state.qc.selectedDefectCat || 'Cacat Potong';
    $$('.defect-cat-btn').forEach((btn) => {
      const bcat = btn.dataset.cat;
      const on = bcat === cat;
      btn.className = `defect-cat-btn p-3.5 rounded-xl border text-left transition flex items-start gap-3 ${on ? 'border-rose-400 bg-rose-50/70 ring-2 ring-rose-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}`;
    });
    const selCat = $('#sel-jenis-cacat');
    if (selCat) selCat.value = cat;

    const unitLabel = $('#qc-unit-label');
    if (unitLabel) unitLabel.textContent = cat === 'Cacat Bahan' ? 'Roll' : 'Pcs';

    // 5. Populate Referensi Roll Kain Fisik
    const selRoll = $('#sel-qc-roll');
    if (selRoll) {
      const seri = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === curSelId);
      const gel = Q.gelaranOfPotong(seri);
      const usedRollIds = [...new Set(gel.map((g) => g.id_roll))];

      let availableRolls = [];
      if (usedRollIds.length) {
        availableRolls = usedRollIds.map((rid) => Q.roll(rid)).filter(Boolean);
      } else {
        availableRolls = BongDB.data.Detail_Roll_Bahan.slice(0, 15);
      }

      selRoll.innerHTML = `<option value="">— Tidak Spesifik Roll —</option>` +
        availableRolls.map((r) => {
          const info = Q.rollInfo(r);
          return `<option value="${r.id_roll}">[ID: ${r.id_roll}] ${esc(info.label)} (${r.ukuran_awal} ${r.satuan || 'Kg'} · ${r.status_roll})</option>`;
        }).join('');
    }

    // 6. Update Decision Cards highlight
    const dec = state.qc.selectedDecision || 'Need Rework';
    $$('.qc-decision-btn').forEach((btn) => {
      const bdec = btn.dataset.decision;
      const on = bdec === dec;
      btn.className = `qc-decision-btn p-3.5 rounded-xl border text-left transition ${on ? 'border-indigo-500 bg-indigo-50/70 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}`;
    });

    // 7. Update PIC Rework default
    const selPic = $('#sel-pic-rework');
    if (selPic && (!selPic.dataset.manual || selPic.dataset.prevCat !== cat)) {
      if (cat === 'Cacat Potong') selPic.value = 'Tim Potong A (Budi Santoso)';
      else if (cat === 'Cacat Jahit') selPic.value = 'Tim Jahit Eksternal A';
      else if (cat === 'Cacat Bahan') selPic.value = 'Gudang · Retur Supplier';
      selPic.dataset.prevCat = cat;
    }

    renderQcTable();
  }

  function renderQcTable() {
    const tbody = $('#tbl-qc-log-body');
    if (!tbody) return;

    const filter = state.qc.tableFilter || 'ALL';
    $$('.qc-tbl-filter').forEach((btn) => {
      const on = btn.dataset.filter === filter;
      btn.className = `qc-tbl-filter btn btn-outline h-7 px-2.5 text-xs font-mono ${on ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' : ''}`;
    });

    const items = BongDB.data.Kerusakan.filter((k) => {
      if (filter === 'Need Rework') return k.status === 'Need Rework';
      if (filter === 'Rework Selesai') return k.status === 'Rework Selesai' || k.status === 'Finished';
      if (filter === 'Retur / Scrapped') return k.status === 'Retur Supplier' || k.status === 'Scrapped';
      return true;
    }).sort((a, b) => b.id_qc - a.id_qc);

    tbody.innerHTML = items.map((k) => {
      const p = Q.pesanan(k.id_pesanan);
      const pola = p ? Q.pola(p.id_pola) : null;
      const meta = QC_META[k.status] || QC_META['Need Rework'];
      const unit = qcUnit(k);
      const isNeedRework = k.status === 'Need Rework';

      return `
        <tr class="hover:bg-slate-50/70 transition-colors">
          <td class="pl-5 py-3">
            <span class="font-mono font-bold text-slate-800">#${k.id_qc}</span>
            <span class="block text-[11px] text-slate-400 font-mono">${fmtDate(k.tgl_temuan)}</span>
          </td>
          <td class="py-3">
            <span class="font-medium text-slate-900 cursor-pointer hover:text-indigo-600" data-action="open-order" data-id="${k.id_pesanan}">#${k.id_pesanan} · ${esc(pola?.nama_pola || '-')}</span>
            <span class="block text-[11px] text-slate-500 truncate max-w-xs">${esc(k.catatan)}</span>
          </td>
          <td class="py-3">
            <span class="badge ${k.jenis_cacat === 'Cacat Potong' ? 'bg-rose-50 text-rose-700 ring-rose-200' : k.jenis_cacat === 'Cacat Jahit' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200'} font-semibold">${esc(k.jenis_cacat)}</span>
          </td>
          <td class="py-3 font-mono font-bold text-slate-900">${k.jumlah_pcs} ${unit}</td>
          <td class="py-3">
            <span class="text-xs text-slate-700">${esc(k.pic_rework || '-')}</span>
            ${k.tgl_selesai_perbaikan ? `<span class="block text-[10px] text-emerald-600 font-mono">Selesai: ${fmtDate(k.tgl_selesai_perbaikan)}</span>` : ''}
          </td>
          <td class="py-3">
            <span class="badge ${meta.badge} font-bold">${esc(k.status)}</span>
          </td>
          <td class="pr-5 py-3 text-right">
            ${isNeedRework ? `
              <button type="button" data-action="resolve-qc" data-id="${k.id_qc}" class="btn btn-outline h-7 px-2.5 text-xs text-emerald-700 border-emerald-300 hover:bg-emerald-50" title="Tandai Rework Selesai">
                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>Selesaikan
              </button>` : `
              <span class="text-[11px] text-slate-400 font-mono">✓ Closed</span>`}
          </td>
        </tr>`;
    }).join('') || '<tr><td colspan="7" class="text-center py-6 text-slate-400">Tidak ada data inspeksi QC yang sesuai filter.</td></tr>';

    refreshIcons();
  }

  function submitQc() {
    const idPesanan = Number($('#sel-qc-pesanan')?.value);
    const tglTemuan = $('#inp-tgl-temuan')?.value || APP_TODAY;
    const idUserQc = Number($('#sel-user-qc')?.value || 3);
    const jenisCacat = state.qc.selectedDefectCat || $('#sel-jenis-cacat')?.value || 'Cacat Potong';
    const jumlahPcs = Number($('#inp-jumlah-cacat')?.value) || 0;
    const idRoll = $('#sel-qc-roll')?.value ? Number($('#sel-qc-roll').value) : null;
    const catatan = $('#inp-catatan-qc')?.value.trim();
    const decision = state.qc.selectedDecision || 'Need Rework';
    const tglSelesai = $('#inp-tgl-selesai-qc')?.value || addDays(APP_TODAY, 1);
    const picRework = $('#sel-pic-rework')?.value || 'Tim Potong A (Budi Santoso)';

    if (!idPesanan) { showToast('Pilih pesanan target inspeksi.', { type: 'error' }); return; }
    if (jumlahPcs <= 0) { showToast('Jumlah terkena cacat harus lebih dari 0.', { type: 'error' }); $('#inp-jumlah-cacat')?.focus(); return; }
    if (!catatan) { showToast('Catatan detail temuan kerusakan wajib diisi.', { type: 'error' }); $('#inp-catatan-qc')?.focus(); return; }

    const isFinished = decision === 'Finished' || decision === 'Rework Selesai';
    const mappedStatusQc = isFinished
      ? 'Rework Selesai'
      : (decision === 'Retur Supplier' || decision === 'Scrapped')
        ? 'Scrapped'
        : 'Need Rework';
    const newQc = BongDB.insert('QC', {
      id_pesanan: idPesanan,
      id_user_qc: idUserQc,
      tahap_qc: 'Jahit Akhir',
      tgl_pemeriksaan: tglTemuan + ' 14:00:00',
      jumlah_diperiksa: jumlahPcs + 50,
      jumlah_lolos: isFinished ? 50 : 0,
      jumlah_cacat: jumlahPcs,
      jenis_cacat: jenisCacat,
      catatan_qc: catatan + (idRoll ? ` [Roll ID: #${idRoll}]` : ''),
      status_qc: mappedStatusQc,
      tgl_target_perbaikan: (mappedStatusQc === 'Need Rework') ? tglSelesai : null,
      tgl_selesai_perbaikan: isFinished ? tglSelesai : null,
      pic_rework: picRework,
      status: mappedStatusQc,
      jumlah_pcs: jumlahPcs,
      catatan: catatan + (idRoll ? ` [Roll ID: #${idRoll}]` : ''),
      tgl_temuan: tglTemuan,
    });

    if (mappedStatusQc === 'Need Rework') {
      BongDB.update('Pesanan', idPesanan, {
        status_pesanan: 'DALAM QC',
        progress_pct: 85,
      });
    }

    if (jenisCacat === 'Cacat Bahan' && idRoll) {
      BongDB.update('Detail_Roll_Bahan', idRoll, {
        status_roll: 'Habis',
        ukuran_sisa: 0.0,
        rak_alokasi: 'Karantina BS',
      });
    }

    // Increment perpetual finished stock if Jahit Akhir passed units exist
    if (newQc.jumlah_lolos > 0) {
      const p = Q.pesanan(idPesanan);
      if (p) {
        const existingStock = BongDB.where('Stok_Barang_Jadi', (s) => s.id_pola === p.id_pola)[0];
        if (existingStock) {
          BongDB.update('Stok_Barang_Jadi', existingStock.id_stok, {
            jumlah_stok_pcs: Number(existingStock.jumlah_stok_pcs || 0) + newQc.jumlah_lolos,
            tgl_update_terakhir: APP_TODAY + ' 15:00:00',
          });
        }
      }
    }

    showToast(`Temuan QC <b>#${newQc.id_qc}</b> (${esc(jenisCacat)}) berhasil dicatat dengan keputusan <b>${esc(newQc.status_qc)}</b>. Disposisi PIC: <b>${esc(picRework)}</b>.`, { title: 'Inspeksi QC Tersimpan' });

    resetQcForm();
    renderAll();
  }

  function approveQcPass() {
    const idPesanan = Number($('#sel-qc-pesanan')?.value);
    if (!idPesanan) { showToast('Pilih pesanan target terlebih dahulu.', { type: 'error' }); return; }

    const p = Q.pesanan(idPesanan);
    const pola = p ? Q.pola(p.id_pola) : null;
    const qtyLolos = 120;

    // 1. Record QC pass in table QC
    BongDB.insert('QC', {
      id_pesanan: idPesanan,
      id_user_qc: 4,
      tahap_qc: 'Jahit Akhir',
      tgl_pemeriksaan: APP_TODAY + ' 16:00:00',
      jumlah_diperiksa: qtyLolos,
      jumlah_lolos: qtyLolos,
      jumlah_cacat: 0,
      jenis_cacat: 'Tidak Ada',
      catatan_qc: 'Inspeksi Jahit Akhir Lolos QC (100% lolos mutu).',
      status_qc: 'Lolos QC',
      tgl_target_perbaikan: null,
      tgl_selesai_perbaikan: null,
      pic_rework: '-',
      status: 'Rework Selesai',
      jumlah_pcs: 0,
      catatan: 'Lolos inspeksi 100% sempurna',
      tgl_temuan: APP_TODAY,
    });

    // 2. Increment Stok_Barang_Jadi perpetual stock
    if (p) {
      const existingStock = BongDB.where('Stok_Barang_Jadi', (s) => s.id_pola === p.id_pola)[0];
      if (existingStock) {
        BongDB.update('Stok_Barang_Jadi', existingStock.id_stok, {
          jumlah_stok_pcs: Number(existingStock.jumlah_stok_pcs || 0) + qtyLolos,
          tgl_update_terakhir: APP_TODAY + ' 16:00:00',
        });
      } else {
        const spec = BongDB.where('Spesifikasi_Ukuran', (s) => s.id_pola === p.id_pola)[0];
        BongDB.insert('Stok_Barang_Jadi', {
          id_pola: p.id_pola,
          id_spesifikasi: spec ? spec.id_spesifikasi : 1,
          nama_warna: 'White',
          jumlah_stok_pcs: qtyLolos,
          tgl_update_terakhir: APP_TODAY + ' 16:00:00',
        });
      }
    }

    // 3. Mark order SELESAI (100%)
    BongDB.update('Pesanan', idPesanan, {
      status_pesanan: 'SELESAI',
      progress_pct: 100,
    });

    showToast(`QC Passed: Pesanan <b>#${idPesanan}</b> (${esc(pola?.nama_pola || '')}) dinyatakan <b>Lolos QC (Tanpa Cacat)</b> dan berstatus <b>SELESAI (100%)</b>. Stok Barang Jadi bertambah <b>+${qtyLolos} Pcs</b>.`, { title: 'Pesanan Selesai Produksi' });

    renderAll();
  }

  function resolveQc(idQc) {
    const qc = BongDB.find('QC', Number(idQc));
    if (!qc) return;

    BongDB.update('QC', Number(idQc), {
      status: 'Rework Selesai',
      status_qc: 'Rework Selesai',
      tgl_selesai_perbaikan: APP_TODAY,
    });

    // If order was in QC and no other need rework exists, check progress
    if (qc.id_pesanan) {
      const activeDefects = BongDB.where('QC', (k) => k.id_pesanan === qc.id_pesanan && (k.status === 'Need Rework' || k.status_qc === 'Need Rework'));
      if (!activeDefects.length) {
        const p = Q.pesanan(qc.id_pesanan);
        if (p && p.status_pesanan === 'DALAM QC') {
          BongDB.update('Pesanan', qc.id_pesanan, { progress_pct: 95 });
        }
      }
    }

    showToast(`Rework untuk temuan QC <b>#${idQc}</b> telah diselesaikan dan diverifikasi. Pending counter berkurang.`, { title: 'Rework Selesai' });
    renderAll();
  }

  function resetQcForm() {
    state.qc.selectedDefectCat = 'Cacat Potong';
    state.qc.selectedDecision = 'Need Rework';
    if ($('#inp-jumlah-cacat')) $('#inp-jumlah-cacat').value = '12';
    if ($('#inp-catatan-qc')) $('#inp-catatan-qc').value = 'Terjadi pergeseran pola kain pada gelaran layer 4 saat mesin potong berjalan. Membutuhkan pemotongan ulang lembar kain komponen lengan.';
    if ($('#inp-tgl-selesai-qc')) $('#inp-tgl-selesai-qc').value = addDays(APP_TODAY, 1);
    renderQcScreen();
  }

  /* --------------------------------------------------------------------------
   * 9E. STEP 6: SCREEN 6 (LAPORAN EKSEKUTIF) LOGIC
   * ------------------------------------------------------------------------ */
  function renderLaporanScreen() {
    const hostKpis = $('#laporan-kpis-grid');
    if (!hostKpis) return;

    // 1. Populate filter controls if empty
    const inpStart = $('#inp-lap-start');
    const inpEnd = $('#inp-lap-end');
    if (inpStart && !inpStart.value) inpStart.value = state.laporan.startDate;
    if (inpEnd && !inpEnd.value) inpEnd.value = state.laporan.endDate;

    const selJenis = $('#sel-lap-jenis');
    if (selJenis && selJenis.value !== state.laporan.jenis) selJenis.value = state.laporan.jenis;
    const selSumber = $('#sel-lap-sumber');
    if (selSumber && selSumber.value !== state.laporan.sumber) selSumber.value = state.laporan.sumber;
    const selStatus = $('#sel-lap-status');
    if (selStatus && selStatus.value !== state.laporan.status) selStatus.value = state.laporan.status;

    // 2. Filter dataset
    const start = state.laporan.startDate;
    const end = state.laporan.endDate;
    const sumberFilter = state.laporan.sumber;
    const statusFilter = state.laporan.status;

    const filteredOrders = BongDB.data.Pesanan.filter((p) => {
      const tgl = p.tgl_order || APP_TODAY;
      if (tgl < start || tgl > end) return false;
      if (sumberFilter !== 'ALL' && String(p.id_sumber) !== String(sumberFilter)) return false;
      if (statusFilter !== 'ALL' && p.status_pesanan !== statusFilter) return false;
      return true;
    });

    // 3. Compute Aggregates
    const orderIds = new Set(filteredOrders.map((p) => p.id_pesanan));
    const seriList = BongDB.where('Pemotongan_Seri', (s) => orderIds.has(s.id_pesanan));
    const seriIds = new Set(seriList.map((s) => s.id_potong));
    const pwList = BongDB.where('Detail_Pemotongan_Warna', (w) => seriIds.has(w.id_potong));
    const pwIds = new Set(pwList.map((w) => w.id_potong_warna));
    const gelaranList = BongDB.where('Detail_Gelaran_Kain', (g) => pwIds.has(g.id_potong_warna));
    const usedRollIds = new Set(gelaranList.map((g) => g.id_roll));
    const usedRolls = [...usedRollIds].map((id) => Q.roll(id)).filter(Boolean);

    const totalKgUsed = round1(sum(usedRolls, (r) => r.ukuran_awal));
    const totalYieldPcs = sum(seriList, (s) => s.total_lembar_hasil);
    const avgYield = usedRollIds.size ? Math.round(totalYieldPcs / usedRollIds.size) : 0;
    const totalGelaran = gelaranList.length;

    const qcList = BongDB.where('Kerusakan', (k) => orderIds.has(k.id_pesanan));
    const totalDefectPcs = sum(qcList, (k) => k.jumlah_pcs);
    const rejectRate = totalYieldPcs ? ((totalDefectPcs / totalYieldPcs) * 100).toFixed(1) : '0.0';

    const potongDefects = sum(qcList.filter((k) => k.jenis_cacat === 'Cacat Potong'), (k) => k.jumlah_pcs);
    const jahitDefects = sum(qcList.filter((k) => k.jenis_cacat === 'Cacat Jahit'), (k) => k.jumlah_pcs);
    const bahanDefects = sum(qcList.filter((k) => k.jenis_cacat === 'Cacat Bahan'), (k) => k.jumlah_pcs);

    const completedCount = filteredOrders.filter((p) => p.status_pesanan === 'SELESAI').length;
    const activeCount = filteredOrders.length - completedCount;
    const tersediaCount = BongDB.where('Detail_Roll_Bahan', (r) => r.status_roll === 'Tersedia').length;

    // 4. Render 4 Executive KPI Cards
    hostKpis.innerHTML = `
      <article class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Roll Kain Dikonsumsi</p>
          <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 grid place-items-center"><i data-lucide="package" class="w-4 h-4"></i></span>
        </div>
        <p class="mt-2 text-2xl font-bold font-mono text-slate-900">${usedRollIds.size} <span class="text-sm font-sans font-medium text-slate-500">Roll</span></p>
        <p class="mt-1 text-[11px] text-slate-500 truncate">Total ${totalKgUsed} kg · ${tersediaCount} Roll Tersedia di Gudang</p>
      </article>

      <article class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Lembar Potong Hasil</p>
          <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center"><i data-lucide="scissors" class="w-4 h-4"></i></span>
        </div>
        <p class="mt-2 text-2xl font-bold font-mono text-emerald-700">${fmtNum(totalYieldPcs)} <span class="text-sm font-sans font-medium text-slate-500">Pcs</span></p>
        <p class="mt-1 text-[11px] text-slate-500 truncate">Rata-rata Yield: ${avgYield} Pcs/Roll (${totalGelaran} Gelaran)</p>
      </article>

      <article class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tingkat Cacat QC (Reject Rate)</p>
          <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 grid place-items-center"><i data-lucide="shield-alert" class="w-4 h-4"></i></span>
        </div>
        <p class="mt-2 text-2xl font-bold font-mono text-rose-600">${rejectRate}%</p>
        <p class="mt-1 text-[11px] text-slate-500 truncate">${totalDefectPcs} Pcs Terdampak (${potongDefects} Potong · ${jahitDefects} Jahit · ${bahanDefects} Bahan)</p>
      </article>

      <article class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-start justify-between">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Pesanan Selesai &amp; Ketepatan</p>
          <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 grid place-items-center"><i data-lucide="check-circle-2" class="w-4 h-4"></i></span>
        </div>
        <p class="mt-2 text-2xl font-bold font-mono text-indigo-700">${completedCount} <span class="text-sm font-sans font-medium text-slate-500">Pesanan</span></p>
        <p class="mt-1 text-[11px] text-slate-500 truncate">100% On-Time Delivery · ${activeCount} Pesanan Aktif Berjalan</p>
      </article>`;

    // 5. Render Detail Table
    const tbody = $('#tbl-laporan-body');
    if (tbody) {
      tbody.innerHTML = filteredOrders.map((p) => {
        const pola = Q.pola(p.id_pola);
        const sumber = Q.sumber(p.id_sumber);
        const orderSeri = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === p.id_pesanan);
        const orderGel = Q.gelaranOfPotong(orderSeri);
        const orderRolls = [...new Set(orderGel.map((g) => g.id_roll))];
        const yieldPcs = sum(orderSeri, (s) => s.total_lembar_hasil);
        const orderQc = BongDB.where('Kerusakan', (k) => k.id_pesanan === p.id_pesanan);
        const meta = STATUS_META[p.status_pesanan] || STATUS_META['ANTREAN'];

        return `
          <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="pl-5 py-3">
              <button type="button" data-action="open-order" data-id="${p.id_pesanan}" class="font-mono font-bold text-indigo-600 hover:underline">#${p.id_pesanan}</button>
              <span class="block text-[10px] text-slate-400 font-mono">${fmtDate(p.tgl_order)}</span>
            </td>
            <td class="py-3">
              <span class="font-semibold text-slate-900">${esc(pola?.nama_pola || '-')}</span>
              <span class="block text-[11px] text-slate-400 font-mono">${esc(pola?.kode_pola || '')} · Pola #${p.id_pola}</span>
            </td>
            <td class="py-3">
              <span class="badge bg-slate-100 text-slate-700">${esc(sumber?.nama_sumber || '-')}</span>
              ${p.nama_mitra ? `<span class="block text-[11px] text-slate-500 mt-0.5 truncate max-w-[140px]">${esc(p.nama_mitra)}</span>` : ''}
            </td>
            <td class="py-3">
              <div class="flex flex-wrap gap-1 max-w-[180px]">
                ${orderRolls.map((rid) => `<button type="button" data-action="open-roll" data-id="${rid}" class="badge font-mono bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px]">#${rid}</button>`).join('') || '<span class="text-slate-400 text-xs">Belum Gelar</span>'}
              </div>
            </td>
            <td class="py-3 text-right">
              <span class="font-mono font-bold text-slate-900">${fmtNum(yieldPcs)} Pcs</span>
              <span class="block text-[10px] text-slate-400 font-mono">${orderSeri.length} seri potong</span>
            </td>
            <td class="py-3">
              ${orderQc.length ? orderQc.map((k) => `
                <span class="badge ${k.status === 'Need Rework' ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'} font-mono text-[10px] mb-0.5 mr-1" title="${esc(k.catatan)}">QC #${k.id_qc}: ${k.jumlah_pcs} ${qcUnit(k)} (${esc(k.jenis_cacat.replace('Cacat ', ''))})</span>`).join('') : '<span class="text-slate-400 text-xs">— (0 Pcs)</span>'}
            </td>
            <td class="pr-5 py-3 text-right">
              <span class="badge ${meta.badge} font-bold">${p.status_pesanan}</span>
              <span class="block text-[10px] text-slate-400 font-mono mt-0.5">${p.progress_pct}% Selesai</span>
            </td>
          </tr>`;
      }).join('') || '<tr><td colspan="7" class="text-center py-8 text-slate-400">Tidak ada pesanan dalam rentang filter laporan.</td></tr>';
    }

    // 6. Left Breakdown: Rekapitulasi Klasifikasi Cacat QC
    const hostCacat = $('#laporan-cacat-breakdown');
    if (hostCacat) {
      const potongCount = qcList.filter((k) => k.jenis_cacat === 'Cacat Potong').length;
      const jahitCount = qcList.filter((k) => k.jenis_cacat === 'Cacat Jahit').length;
      const bahanCount = qcList.filter((k) => k.jenis_cacat === 'Cacat Bahan').length;
      const totalCount = qcList.length || 1;

      hostCacat.innerHTML = `
        <div class="space-y-3">
          <div>
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="font-semibold text-slate-800">Cacat Potong (Stasiun Cutting Internal)</span>
              <span class="font-mono text-slate-600"><b>${potongDefects} Pcs</b> (${potongCount} Kasus · ${Math.round(potongCount / totalCount * 100)}%)</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full" style="width:${(potongCount / totalCount * 100).toFixed(1)}%"></div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="font-semibold text-slate-800">Cacat Jahit (Penjahit CMT Eksternal)</span>
              <span class="font-mono text-slate-600"><b>${jahitDefects} Pcs</b> (${jahitCount} Kasus · ${Math.round(jahitCount / totalCount * 100)}%)</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-amber-500 rounded-full" style="width:${(jahitCount / totalCount * 100).toFixed(1)}%"></div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="font-semibold text-slate-800">Cacat Bahan (Supplier Fabric &amp; Lot BS)</span>
              <span class="font-mono text-slate-600"><b>${bahanDefects} Roll</b> (${bahanCount} Kasus · ${Math.round(bahanCount / totalCount * 100)}%)</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-indigo-500 rounded-full" style="width:${(bahanCount / totalCount * 100).toFixed(1)}%"></div>
            </div>
          </div>
        </div>
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Total Kejadian Cacat: <b class="font-mono text-slate-800">${qcList.length}</b></span>
          <span>Total Kerugian Garmen: <b class="font-mono text-rose-600">${totalDefectPcs} Pcs</b></span>
        </div>`;
    }

    // 7. Right Breakdown: Ringkasan Stok Roll Kain per Kategori Status
    const hostStok = $('#laporan-stok-breakdown');
    if (hostStok) {
      const allRolls = BongDB.data.Detail_Roll_Bahan;
      const statuses = ['Tersedia', 'Dipotong', 'Habis'];
      const stData = statuses.map((st) => {
        const group = allRolls.filter((r) => r.status_roll === st);
        return {
          status: st,
          count: group.length,
          kg: round1(sum(group, (r) => r.ukuran_awal)),
          meta: ROLL_META[st] || ROLL_META['Tersedia'],
        };
      });

      hostStok.innerHTML = `
        <div class="grid grid-cols-3 gap-3">
          ${stData.map((d) => `
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50 flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="badge ${d.meta.badge} font-bold">• ${d.status}</span>
                <span class="font-mono text-xs text-slate-500">${d.kg} kg</span>
              </div>
              <p class="mt-2 text-xl font-bold font-mono text-slate-900">${d.count} <span class="text-xs font-sans font-medium text-slate-400">Roll</span></p>
            </div>`).join('')}
        </div>
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <span>Total Seluruh Roll: <b class="font-mono text-slate-800">${allRolls.length} Roll</b></span>
          <span>Total Ukuran Gudang: <b class="font-mono text-slate-800">${round1(sum(allRolls, (r) => r.ukuran_awal))} kg</b></span>
        </div>`;
    }

    refreshIcons();
  }

  function applyLaporanFilter() {
    state.laporan.jenis = $('#sel-lap-jenis')?.value || 'ALL';
    state.laporan.startDate = $('#inp-lap-start')?.value || addDays(APP_TODAY, -30);
    state.laporan.endDate = $('#inp-lap-end')?.value || APP_TODAY;
    state.laporan.sumber = $('#sel-lap-sumber')?.value || 'ALL';
    state.laporan.status = $('#sel-lap-status')?.value || 'ALL';

    renderLaporanScreen();
    showToast(`Filter laporan diterapkan: ${state.laporan.startDate} s/d ${state.laporan.endDate}.`, { type: 'info', title: 'Laporan Diperbarui' });
  }

  function resetLaporanFilter() {
    state.laporan = {
      jenis: 'ALL',
      startDate: addDays(APP_TODAY, -30),
      endDate: APP_TODAY,
      sumber: 'ALL',
      status: 'ALL',
    };
    renderLaporanScreen();
    showToast('Filter laporan telah direset ke nilai default (30 hari terakhir).', { type: 'info' });
  }

  function printReport() {
    window.print();
  }

  /* --------------------------------------------------------------------------
   * 10. NOTIFICATIONS & GLOBAL SEARCH
   * ------------------------------------------------------------------------ */
  function buildNotifications() {
    const D = BongDB.data;
    const items = [];
    D.Kerusakan.filter((k) => k.status === 'Need Rework').sort((a, b) => b.id_qc - a.id_qc).forEach((k) => {
      items.push({ icon: 'shield-alert', cls: 'bg-rose-50 text-rose-600', title: `Rework diperlukan · Pesanan #${k.id_pesanan}`, text: `${k.jenis_cacat} (${k.jumlah_pcs} ${qcUnit(k)}) — ${k.catatan}`, time: fmtDate(k.tgl_temuan), action: 'open-order', id: k.id_pesanan });
    });
    D.Pesanan.filter((p) => ACTIVE_STATUSES.includes(p.status_pesanan))
      .map((p) => {
        const dl = p.deadline || addDays(p.tgl_order || APP_TODAY, 14);
        return { p, dl, sisa: diffDays(APP_TODAY, dl) };
      })
      .filter((x) => x.sisa >= 0 && x.sisa <= 5)
      .sort((a, b) => a.sisa - b.sisa)
      .forEach(({ p, dl, sisa }) => {
        items.push({ icon: 'alarm-clock', cls: 'bg-amber-50 text-amber-600', title: `Deadline ${sisa} hari lagi · #${p.id_pesanan}`, text: `${Q.pola(p.id_pola)?.nama_pola || ''}${p.nama_mitra ? ' · ' + p.nama_mitra : ''} — status ${p.status_pesanan}`, time: fmtDate(dl), action: 'open-order', id: p.id_pesanan });
      });
    D.Penerimaan_Bahan.filter((p) => p.tgl_nota === APP_TODAY).forEach((p) => {
      const rolls = sum(D.Detail_Bahan_Warna.filter((w) => w.id_penerimaan === p.id_penerimaan), (w) => w.total_roll);
      items.push({ icon: 'package-check', cls: 'bg-emerald-50 text-emerald-600', title: `Bahan diterima · Nota #${p.no_nota}`, text: `${p.nama_kain} — ${rolls} roll dari ${Q.supplier(p.id_supplier)?.nama_supplier || '-'}`, time: fmtDate(p.tgl_nota), action: 'goto', target: 'penerimaan' });
    });
    return items;
  }
  function renderNotifications() {
    const items = buildNotifications();
    $('#notif-dot').classList.toggle('hidden', items.length <= state.notifSeen);
    $('#notif-menu').innerHTML = `
      <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <p class="text-sm font-semibold text-slate-900">Notifikasi <span class="ml-1 font-mono text-xs text-slate-400">${items.length}</span></p>
        <span class="text-[11px] text-slate-400">${APP_TODAY_LABEL}</span>
      </div>
      <div class="max-h-96 overflow-y-auto thin-scroll divide-y divide-slate-100">
        ${items.map((n) => `
          <button type="button" class="w-full flex gap-3 px-4 py-3 text-left hover:bg-slate-50" data-action="${n.action}" ${n.id ? `data-id="${n.id}"` : ''} ${n.target ? `data-target="${n.target}"` : ''}>
            <span class="w-8 h-8 shrink-0 rounded-lg grid place-items-center ${n.cls}"><i data-lucide="${n.icon}" class="w-4 h-4"></i></span>
            <span class="min-w-0">
              <span class="block text-[13px] font-medium text-slate-900">${esc(n.title)}</span>
              <span class="block text-xs text-slate-500 truncate">${esc(n.text)}</span>
              <span class="block text-[10.5px] text-slate-400 mt-0.5">${n.time}</span>
            </span>
          </button>`).join('') || '<p class="px-4 py-8 text-center text-sm text-slate-400">Tidak ada notifikasi.</p>'}
      </div>`;
  }

  function runSearch(raw) {
    const box = $('#search-results');
    const q = String(raw || '').trim().toLowerCase().replace(/^#/, '');
    if (!q) { box.classList.add('hidden'); box.innerHTML = ''; return; }
    const D = BongDB.data;
    const orders = D.Pesanan.filter((p) => String(p.id_pesanan).includes(q) || (Q.pola(p.id_pola)?.nama_pola || '').toLowerCase().includes(q) || (p.nama_mitra || '').toLowerCase().includes(q)).slice(0, 5);
    const rolls = D.Detail_Roll_Bahan.filter((r) => String(r.id_roll).includes(q) || Q.rollInfo(r).label.toLowerCase().includes(q)).slice(0, 5);
    const polas = D.Pola.filter((p) => p.nama_pola.toLowerCase().includes(q) || p.kode_pola.toLowerCase().includes(q)).slice(0, 4);

    const group = (title, list) => (list.length ? `<p class="px-4 pt-3 pb-1 text-[10.5px] font-semibold tracking-wider uppercase text-slate-400">${title}</p>${list.join('')}` : '');
    const item = (action, id, icon, main, sub, badge = '') => `
      <button type="button" class="search-item w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-indigo-50/60" data-action="${action}" data-id="${id}">
        <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 grid place-items-center shrink-0"><i data-lucide="${icon}" class="w-4 h-4"></i></span>
        <span class="flex-1 min-w-0"><span class="block text-[13px] font-medium text-slate-900 truncate">${main}</span><span class="block text-[11px] text-slate-500 truncate">${sub}</span></span>${badge}
      </button>`;

    const html = [
      group('Pesanan', orders.map((p) => item('open-order', p.id_pesanan, 'clipboard-list', `<span class="font-mono text-indigo-600">#${p.id_pesanan}</span> · ${esc(Q.pola(p.id_pola)?.nama_pola || '')}`, `${esc(Q.sumber(p.id_sumber)?.nama_sumber || '')}${p.nama_mitra ? ' · ' + esc(p.nama_mitra) : ''}`, `<span class="badge ${(STATUS_META[p.status_pesanan] || STATUS_META.ANTREAN).badge}">${p.status_pesanan}</span>`))),
      group('Roll Kain', rolls.map((r) => item('open-roll', r.id_roll, 'cylinder', `<span class="font-mono">id_roll: ${r.id_roll}</span> · ${esc(Q.rollInfo(r).label)}`, `${fmtNum(r.ukuran_awal, 1)} ${esc(r.satuan || 'Kg')} · Rak ${esc(r.rak_alokasi)}`, `<span class="badge ${(ROLL_META[r.status_roll] || ROLL_META.Habis).badge}">${r.status_roll}</span>`))),
      group('Pola', polas.map((p) => item('open-pola', p.id_pola, 'shirt', esc(p.nama_pola), `<span class="font-mono">${esc(p.kode_pola)}</span> · ${esc(Q.kategori(p.id_kategori)?.nama_kategori || '')}`))),
    ].join('');

    box.innerHTML = html
      ? `${html}<div class="px-4 py-2 mt-1 border-t border-slate-100 text-[10.5px] text-slate-400">Enter untuk membuka hasil pertama · Esc untuk menutup</div>`
      : `<div class="px-4 py-8 text-center"><p class="text-sm text-slate-500">Tidak ditemukan hasil untuk "<b>${esc(raw)}</b>"</p><p class="text-[11px] text-slate-400 mt-1">Coba 101, 4503, atau "flanel"</p></div>`;
    box.classList.remove('hidden');
    refreshIcons();
  }

  /* --------------------------------------------------------------------------
   * 11. DETAIL MODALS (relational joins)
   * ------------------------------------------------------------------------ */
  const kv = (label, value) => `<div><p class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-400">${label}</p><p class="text-sm text-slate-800 mt-0.5">${value}</p></div>`;

  function showPesananDetail(id) {
    const p = Q.pesanan(id);
    if (!p) return showToast(`Pesanan #${id} tidak ditemukan.`, { type: 'error' });
    const pola = Q.pola(p.id_pola);
    const pen = Q.penerimaan(p.id_penerimaan);
    const st = STATUS_META[p.status_pesanan] || STATUS_META.ANTREAN;
    const effectiveDl = p.deadline_terbaru || p.deadline_awal || p.deadline || addDays(p.tgl_order || APP_TODAY, 14);
    const sisa = diffDays(APP_TODAY, effectiveDl);
    const seri = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === p.id_pesanan).sort((a, b) => String(a.tgl_potong || '').localeCompare(String(b.tgl_potong || '')));
    const qc = BongDB.where('QC', (k) => k.id_pesanan === p.id_pesanan);
    const totalCut = sum(seri, (s) => s.total_lembar_hasil);

    openModal({
      title: `Pesanan #${p.id_pesanan} · ${esc(pola?.nama_pola || '-')}`,
      subtitle: `${esc(pola?.kode_pola || '')} · ${esc(p.jenis_pesanan)}`,
      icon: 'clipboard-list', size: 'max-w-3xl',
      body: `
        <div class="flex flex-wrap items-center gap-2 mb-5">
          <span class="badge ${st.badge}">${p.status_pesanan}</span>
          <span class="badge bg-slate-100 text-slate-600 ring-slate-200">${progressLabel(p)} · ${p.progress_pct}%</span>
          ${p.status_pesanan !== 'SELESAI' ? `<span class="badge ${sisa <= 7 ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-slate-100 text-slate-600 ring-slate-200'}">Sisa ${sisa} hari (EDF)</span>` : ''}
          ${p.deadline_terbaru ? `<span class="badge bg-amber-50 text-amber-700 ring-amber-200 font-mono font-medium">Revisi Deadline Aktif</span>` : ''}
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
          ${kv('Sumber Inisiasi', esc(Q.sumber(p.id_sumber)?.nama_sumber || '-'))}
          ${kv('Mitra / Klien', esc(p.nama_mitra || '-'))}
          ${kv('Tanggal Order', fmtDate(p.tgl_order))}
          ${kv('Deadline Awal', fmtDate(p.deadline_awal || p.deadline))}
          ${kv('Deadline Terbaru', p.deadline_terbaru ? `<span class="font-mono font-semibold text-amber-600">${fmtDate(p.deadline_terbaru)}</span>` : '<span class="text-slate-400">Tidak ada revisi</span>')}
          ${kv('Alasan Revisi', esc(p.alasan_perubahan_deadline || '-'))}
          ${kv('Penerimaan Bahan', pen ? `<span class="font-mono">Nota #${esc(pen.no_nota)}</span> · ${esc(pen.nama_kain)}` : '<span class="text-slate-400">Menunggu bahan</span>')}
          ${kv('Supplier', esc(pen ? Q.supplier(pen.id_supplier)?.nama_supplier : '-'))}
          ${kv('Admin', esc(Q.user(p.id_user_admin)?.nama_user || '-'))}
          ${kv('Total Lembar Dipotong', `<span class="font-mono font-semibold">${fmtNum(totalCut)}</span> pcs`)}
        </div>
        <h4 class="mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Riwayat Pemotongan Seri <span class="font-mono text-slate-400">(${seri.length})</span></h4>
        ${seri.length ? `<div class="rounded-lg border border-slate-200 overflow-hidden"><table class="w-full text-xs">
          <thead class="bg-slate-50"><tr><th class="th">id_potong</th><th class="th">Kode Seri</th><th class="th">Tanggal</th><th class="th">Pemotong</th><th class="th text-right">Gelaran</th><th class="th text-right pr-4">Lembar</th></tr></thead>
          <tbody class="divide-y divide-slate-100">${seri.map((s) => `<tr>
            <td class="px-3 py-2 font-mono text-indigo-600">#${s.id_potong}</td><td class="px-3 py-2 font-mono">${esc(s.kode_seri)}</td>
            <td class="px-3 py-2">${fmtDate(s.tgl_potong)}</td><td class="px-3 py-2">${esc(Q.user(s.id_user_pemotong)?.nama_user || '-')}</td>
            <td class="px-3 py-2 text-right font-mono">${Q.gelaranOfPotong([s]).length}</td><td class="px-3 py-2 pr-4 text-right font-mono font-semibold">${fmtNum(s.total_lembar_hasil)}</td></tr>`).join('')}</tbody></table></div>`
          : '<p class="text-xs text-slate-400">Belum ada pemotongan seri.</p>'}
        <h4 class="mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Temuan QC Berjenjang <span class="font-mono text-slate-400">(${qc.length})</span></h4>
        ${qc.length ? qc.map((k) => `<div class="flex items-start justify-between gap-3 p-3 rounded-lg border border-slate-200 mb-2">
            <div><p class="text-xs"><span class="font-mono text-slate-400">id_qc: ${k.id_qc}</span> · <b>[${esc(k.tahap_qc || 'Inspeksi')}] ${esc(k.jenis_cacat || 'Temuan')} (${k.jumlah_cacat ?? k.jumlah_pcs} pcs)</b></p><p class="text-xs text-slate-600 mt-0.5">${esc(k.catatan_qc || k.catatan)}</p></div>
            <span class="badge ${(QC_META[k.status_qc] || QC_META[k.status] || QC_META['Need Rework']).badge}">${esc((k.status_qc || k.status || '').toUpperCase())}</span></div>`).join('')
          : '<p class="text-xs text-slate-400">Tidak ada temuan cacat.</p>'}`,
      footer: `
        <button type="button" class="btn btn-outline mr-auto text-amber-700 hover:bg-amber-50" data-action="revise-deadline" data-id="${p.id_pesanan}"><i data-lucide="calendar-clock" class="w-4 h-4 text-amber-600"></i>Revisi Deadline (EDF)</button>
        <button type="button" class="btn btn-outline" data-action="close-modal">Tutup</button>`,
    });
  }

  function showRollDetail(id) {
    const r = Q.roll(id);
    if (!r) return showToast(`id_roll ${id} tidak ditemukan.`, { type: 'error' });
    const info = Q.rollInfo(r);
    const meta = ROLL_META[r.status_roll] || ROLL_META.Habis;
    const gel = Q.rollGelaran(r.id_roll).sort((a, b) => a.id_gelar - b.id_gelar);
    const sup = info.penerimaan ? Q.supplier(info.penerimaan.id_supplier) : null;
    openModal({
      title: `id_roll: ${r.id_roll} · ${esc(info.label)}`,
      subtitle: `Detail_Roll_Bahan → Detail_Bahan_Warna #${r.id_detail_warna} → Penerimaan_Bahan #${info.penerimaan?.id_penerimaan ?? '-'}`,
      icon: 'cylinder', iconCls: meta.icon,
      body: `
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
          ${kv('Status', `<span class="badge ${meta.badge}">${r.status_roll}</span>`)}
          ${kv('Ukuran Awal / Sisa', `<span class="font-mono font-semibold">${fmtNum(r.ukuran_awal, 1)} / ${fmtNum(r.ukuran_sisa, 1)}</span> ${esc(r.satuan || 'Kg')}`)}
          ${kv('Roll Ke', `<span class="font-mono">#${r.no_roll_ke}</span>`)}
          ${kv('Rak Alokasi', `<span class="font-mono">${esc(r.rak_alokasi)}</span>`)}
          ${kv('Warna', `${esc(info.warna?.nama_warna || '-')} <span class="font-mono text-xs text-slate-400">${esc(info.warna?.kode_warna || '')}</span>`)}
          ${kv('Nota', `<span class="font-mono">#${esc(info.penerimaan?.no_nota || '-')}</span> · ${fmtDate(info.penerimaan?.tgl_nota)}`)}
          ${kv('Lebar / Gramasi', esc(info.penerimaan?.lebar_gramasi || '-'))}
          ${kv('Supplier', esc(sup?.nama_supplier || '-'))}
        </div>
        <h4 class="mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Riwayat Gelaran <span class="font-mono text-slate-400">(${gel.length})</span></h4>
        ${gel.length ? `<div class="rounded-lg border border-slate-200 overflow-hidden"><table class="w-full text-xs">
          <thead class="bg-slate-50"><tr><th class="th">id_gelar</th><th class="th">Potong PK</th><th class="th">Pesanan</th><th class="th text-right">Panjang (m)</th><th class="th text-right">Tumpuk</th><th class="th text-right pr-4">Lembar</th></tr></thead>
          <tbody class="divide-y divide-slate-100">${gel.map((g) => `<tr>
            <td class="px-3 py-2 font-mono">${g.id_gelar}</td><td class="px-3 py-2 font-mono text-indigo-600">#${g.id_potong ?? '-'}</td>
            <td class="px-3 py-2 font-mono">#${g.potong?.id_pesanan ?? '-'}</td><td class="px-3 py-2 text-right font-mono">${fmtNum(g.panjang_gelar, 1)}</td>
            <td class="px-3 py-2 text-right font-mono">${g.jumlah_tumpuk}</td><td class="px-3 py-2 pr-4 text-right font-mono font-semibold">${g.total_lembar_gelar}</td></tr>`).join('')}
          <tr class="bg-slate-50"><td colspan="5" class="px-3 py-2 text-right font-semibold">Total Yield</td><td class="px-3 py-2 pr-4 text-right font-mono font-bold text-indigo-700">${fmtNum(sum(gel, (g) => g.total_lembar_gelar))} pcs</td></tr>
          </tbody></table></div>` : '<p class="text-xs text-slate-400">Roll belum pernah digelar.</p>'}`,
      footer: `<button type="button" class="btn btn-outline" data-action="close-modal">Tutup</button>
        ${r.status_roll === 'Tersedia' ? `<button type="button" class="btn btn-primary" data-action="siap-gelar" data-id="${r.id_roll}"><i data-lucide="scissors" class="w-4 h-4"></i>Siap Gelar di Meja Potong</button>` : ''}`,
    });
  }

  function showPolaDetail(id) {
    const p = Q.pola(id);
    if (!p) return;
    const specs = BongDB.where('Spesifikasi_Ukuran', (s) => s.id_pola === p.id_pola);
    const accList = BongDB.where('Detail_Aksesoris_Pola', (d) => d.id_pola === p.id_pola);
    const orders = BongDB.where('Pesanan', (o) => o.id_pola === p.id_pola);

    openModal({
      title: esc(p.nama_pola),
      subtitle: `${esc(p.kode_pola)} · Kategori ${esc(Q.kategori(p.id_kategori)?.nama_kategori || '-')} · ${orders.length} pesanan terkait`,
      icon: 'shirt', size: 'max-w-4xl',
      body: `
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">1. Spesifikasi Ukuran 11 Metrik Garment (cm)</h4>
        <div class="rounded-lg border border-slate-200 overflow-x-auto thin-scroll mb-5">
          <table class="w-full text-xs text-left whitespace-nowrap">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th class="px-2.5 py-2">Ukuran</th>
                <th class="px-2 py-2 text-right">diameter_leher</th>
                <th class="px-2 py-2 text-right">panjang_pundak</th>
                <th class="px-2 py-2 text-right">lingkar_lengan</th>
                <th class="px-2 py-2 text-right">panjang_tangan</th>
                <th class="px-2 py-2 text-right">lingkar_dada</th>
                <th class="px-2 py-2 text-right">lingkar_pinggang</th>
                <th class="px-2 py-2 text-right">lingkar_selangkang</th>
                <th class="px-2 py-2 text-right">lingkar_paha</th>
                <th class="px-2 py-2 text-right">lebar_kaki</th>
                <th class="px-2 py-2 text-right">lebar_baju</th>
                <th class="px-2 py-2 text-right pr-3">panjang_baju_celana</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              ${specs.map((s) => `
                <tr class="hover:bg-slate-50">
                  <td class="px-2.5 py-2 font-bold text-slate-800">${esc(s.nama_ukuran)}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.diameter_leher ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.panjang_pundak ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lingkar_lengan ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.panjang_tangan ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lingkar_dada ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lingkar_pinggang ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lingkar_selangkang ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lingkar_paha ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lebar_kaki ?? '-'}</td>
                  <td class="px-2 py-2 text-right font-mono">${s.lebar_baju ?? '-'}</td>
                  <td class="px-2 py-2 text-right pr-3 font-mono">${s.panjang_baju_celana ?? '-'}</td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>

        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">2. Kebutuhan Aksesoris CMT (<code class="font-mono text-xs">Detail_Aksesoris_Pola</code>)</h4>
        ${accList.length ? `
          <div class="rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th class="px-3 py-2">Aksesoris</th>
                  <th class="px-3 py-2">Ukuran</th>
                  <th class="px-3 py-2 text-right">jumlah_digunakan</th>
                  <th class="px-3 py-2 pr-4">Satuan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                ${accList.map((d) => {
        const a = BongDB.find('Aksesoris', d.id_aksesoris);
        return `
                    <tr class="hover:bg-slate-50">
                      <td class="px-3 py-2 font-medium text-slate-800">${esc(a?.nama_aksesoris || '-')}</td>
                      <td class="px-3 py-2 text-slate-600 font-mono">${esc(a?.ukuran || '-')}</td>
                      <td class="px-3 py-2 text-right font-mono font-semibold text-indigo-600">${fmtNum(d.jumlah_digunakan, 2)}</td>
                      <td class="px-3 py-2 pr-4 text-slate-500">${esc(a?.satuan || '-')}</td>
                    </tr>`;
      }).join('')}
              </tbody>
            </table>
          </div>` : '<p class="text-xs text-slate-400">Pola ini tidak memerlukan aksesoris khusus.</p>'}`,
      footer: `<button type="button" class="btn btn-outline" data-action="close-modal">Tutup</button>`,
    });
    refreshIcons();
  }

  function showReviseDeadlineModal(idPesanan) {
    const p = Q.pesanan(Number(idPesanan));
    if (!p) return showToast(`Pesanan #${idPesanan} tidak ditemukan.`, { type: 'error' });
    const currentDl = p.deadline_terbaru || p.deadline_awal || p.deadline || APP_TODAY;

    openModal({
      title: `Revisi Deadline Pesanan #${p.id_pesanan}`,
      subtitle: `Audit Trail & Real-time Earliest Deadline First (EDF) Priority Shift`,
      icon: 'calendar-clock', size: 'max-w-lg',
      body: `
        <div class="space-y-4">
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1">
            <div class="flex justify-between">
              <span class="text-slate-500">Pola Pakaian:</span>
              <span class="font-semibold text-slate-800">${esc(Q.pola(p.id_pola)?.nama_pola || '-')}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Mitra:</span>
              <span class="font-semibold text-slate-800">${esc(p.nama_mitra || 'Produksi Internal')}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Deadline Saat Ini:</span>
              <span class="font-mono font-semibold text-indigo-600">${fmtDate(currentDl)}</span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Deadline Baru (Target Selesai) *</label>
            <input type="date" id="inp-revise-deadline-val" value="${currentDl}" class="input font-mono w-full text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Revisi Deadline *</label>
            <select id="sel-revise-deadline-reason" class="input w-full text-xs">
              <option value="Permintaan Percepatan Mitra / Pelanggan">Permintaan Percepatan Mitra / Pelanggan</option>
              <option value="Keterlambatan Pasokan Bahan dari Supplier">Keterlambatan Pasokan Bahan dari Supplier</option>
              <option value="Penyesuaian Kapasitas Meja Potong & Antrean Gelaran">Penyesuaian Kapasitas Meja Potong & Antrean Gelaran</option>
              <option value="Temuan Kerusakan QC & Memerlukan Pengerjaan Ulang (Rework)">Temuan Kerusakan QC & Memerlukan Pengerjaan Ulang (Rework)</option>
              <option value="Perubahan Spesifikasi atau Aksesoris Tambahan">Perubahan Spesifikasi atau Aksesoris Tambahan</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
            <textarea id="inp-revise-deadline-note" rows="2" class="input w-full text-xs" placeholder="Detail memo persetujuan revisi..."></textarea>
          </div>
        </div>`,
      footer: `
        <button type="button" class="btn btn-outline mr-auto" data-action="close-modal">Batal</button>
        <button type="button" class="btn btn-primary" data-action="save-revise-deadline" data-id="${p.id_pesanan}"><i data-lucide="save" class="w-4 h-4"></i>Simpan Revisi &amp; Geser Antrean</button>`,
    });
    refreshIcons();
  }

  function showAddAksesorisModal() {
    const suppliers = BongDB.all('Supplier');
    openModal({
      title: 'Tambah Aksesoris Baru',
      subtitle: 'Registrasi Master Aksesoris CMT ke Tabel Aksesoris',
      icon: 'plus', size: 'max-w-md',
      body: `
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aksesoris *</label>
            <input type="text" id="inp-new-acc-nama" class="input w-full text-xs" placeholder="Contoh: Kancing Kemeja 4 Lubang">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran / Varian *</label>
              <input type="text" id="inp-new-acc-ukuran" class="input w-full text-xs" placeholder="Contoh: 18L (11.5mm)">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan *</label>
              <select id="sel-new-acc-satuan" class="input w-full text-xs">
                <option value="Pcs">Pcs</option>
                <option value="Gross">Gross (144 pcs)</option>
                <option value="Meter">Meter</option>
                <option value="Yard">Yard</option>
                <option value="Roll">Roll</option>
                <option value="Lusin">Lusin (12 pcs)</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Awal Fisik *</label>
              <input type="number" id="inp-new-acc-stok" class="input font-mono w-full text-xs" value="500" min="0">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Rekanan</label>
              <select id="sel-new-acc-supplier" class="input w-full text-xs">
                ${suppliers.map((s) => `<option value="${s.id_supplier}">${esc(s.nama_supplier)}</option>`).join('')}
              </select>
            </div>
          </div>
        </div>`,
      footer: `
        <button type="button" class="btn btn-outline mr-auto" data-action="close-modal">Batal</button>
        <button type="button" class="btn btn-primary" data-action="save-new-aksesoris"><i data-lucide="save" class="w-4 h-4"></i>Simpan Aksesoris</button>`,
    });
    refreshIcons();
  }

  function showAdjustStokModal() {
    const existingStok = BongDB.all('Stok_Barang_Jadi');
    openModal({
      title: 'Penyesuaian Stok Barang Jadi',
      subtitle: 'Audit Fisik Perpetual Inventory (Stok_Barang_Jadi)',
      icon: 'sliders', size: 'max-w-md',
      body: `
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Item Stok Tersedia</label>
            <select id="sel-adjust-stok-id" class="input w-full text-xs">
              ${existingStok.map((s) => {
        const po = BongDB.find('Pola', s.id_pola);
        const sp = BongDB.find('Spesifikasi_Ukuran', s.id_spesifikasi);
        return `<option value="${s.id_stok}">[#${s.id_stok}] ${esc(po?.kode_pola || '')} - Size ${esc(sp?.nama_ukuran || '-')} - ${esc(s.nama_warna || '-')} (Stok: ${s.jumlah_stok_pcs} pcs)</option>`;
      }).join('')}
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Penyesuaian (+ / - Pcs) *</label>
            <input type="number" id="inp-adjust-stok-qty" class="input font-mono w-full text-xs" value="10" placeholder="+/- Pcs">
            <p class="text-[11px] text-slate-400 mt-1">Masukkan angka positif untuk menambah stok fisik, atau negatif untuk mencatat pengurangan (misal penjualan/pengiriman).</p>
          </div>
        </div>`,
      footer: `
        <button type="button" class="btn btn-outline mr-auto" data-action="close-modal">Batal</button>
        <button type="button" class="btn btn-primary" data-action="save-adjust-stok"><i data-lucide="check" class="w-4 h-4"></i>Terapkan Penyesuaian</button>`,
    });
    refreshIcons();
  }

  function renderGudangScreen() {
    const tblStok = $('#tbl-stok-jadi-body');
    const tblAcc = $('#tbl-aksesoris-body');
    if (!tblStok && !tblAcc) return;

    // 1. Render Stok Barang Jadi
    const stokList = BongDB.all('Stok_Barang_Jadi') || [];
    const totalPcs = stokList.reduce((sum, s) => sum + (Number(s.jumlah_stok_pcs) || 0), 0);
    const badgeStok = $('#badge-total-stok-jadi');
    if (badgeStok) badgeStok.textContent = `${fmtNum(totalPcs)} Pcs`;

    if (tblStok) {
      tblStok.innerHTML = stokList.map((s) => {
        const pola = BongDB.find('Pola', s.id_pola);
        const spec = BongDB.find('Spesifikasi_Ukuran', s.id_spesifikasi);
        const colorName = s.nama_warna || '-';
        return `
          <tr class="hover:bg-slate-50 transition">
            <td class="pl-5 py-3 font-mono font-medium text-indigo-600">#${s.id_stok}</td>
            <td class="py-3">
              <span class="font-medium text-slate-900">${esc(pola?.nama_pola || '-')}</span>
              <span class="block font-mono text-[11px] text-slate-400">${esc(pola?.kode_pola || '')}</span>
            </td>
            <td class="py-3 font-semibold text-slate-700">${esc(spec?.nama_ukuran || '-')}</td>
            <td class="py-3">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full border border-slate-300" style="background-color: ${colorName.toLowerCase().includes('navy') ? '#1E3A8A' : colorName.toLowerCase().includes('khaki') ? '#D97706' : '#334155'}"></span>
                <span>${esc(colorName)}</span>
              </span>
            </td>
            <td class="py-3 text-right font-mono font-bold text-emerald-600 text-sm">${fmtNum(s.jumlah_stok_pcs)} Pcs</td>
            <td class="pr-5 py-3 text-right font-mono text-slate-500">${fmtDate(s.tgl_update_terakhir)}</td>
          </tr>`;
      }).join('') || '<tr><td colspan="6" class="text-center py-6 text-slate-400">Belum ada data stok barang jadi.</td></tr>';
    }

    // 2. Render Aksesoris
    const accList = BongDB.all('Aksesoris') || [];
    const badgeAcc = $('#badge-total-aksesoris');
    if (badgeAcc) badgeAcc.textContent = `${accList.length} Item Master`;

    if (tblAcc) {
      tblAcc.innerHTML = accList.map((a) => {
        const sup = BongDB.find('Supplier', a.id_supplier);
        const stock = Number(a.stok) || 0;
        const stockBadge = stock < 200
          ? 'bg-rose-50 text-rose-700 ring-rose-200'
          : stock < 500
            ? 'bg-amber-50 text-amber-700 ring-amber-200'
            : 'bg-emerald-50 text-emerald-700 ring-emerald-200';

        return `
          <tr class="hover:bg-slate-50 transition">
            <td class="pl-5 py-3 font-mono font-medium text-indigo-600">#${a.id_aksesoris}</td>
            <td class="py-3 font-medium text-slate-900">${esc(a.nama_aksesoris)}</td>
            <td class="py-3 font-mono text-slate-600">${esc(a.ukuran)}</td>
            <td class="py-3 font-mono font-bold">
              <span class="badge ${stockBadge}">${fmtNum(stock)}</span>
            </td>
            <td class="py-3 text-slate-600 font-mono text-xs">${esc(a.satuan)}</td>
            <td class="py-3 text-slate-600">${esc(sup?.nama_supplier || '-')}</td>
            <td class="pr-5 py-3 text-right">
              <div class="inline-flex items-center gap-1.5">
                <button type="button" class="btn btn-xs btn-outline" data-action="quick-add-acc-stock" data-id="${a.id_aksesoris}" data-amount="100" title="Tambah 100 ${esc(a.satuan)}">+100</button>
                <button type="button" class="btn btn-xs btn-outline" data-action="quick-add-acc-stock" data-id="${a.id_aksesoris}" data-amount="500" title="Tambah 500 ${esc(a.satuan)}">+500</button>
              </div>
            </td>
          </tr>`;
      }).join('') || '<tr><td colspan="7" class="text-center py-6 text-slate-400">Belum ada master aksesoris.</td></tr>';
    }
    refreshIcons();
  }

  function openSettingsModal() {
    const stats = BongDB.stats();
    const meta = BongDB.data._meta;
    openModal({
      title: 'Pengaturan Sistem · BongDB',
      subtitle: `localStorage key <code class="font-mono">${DB_KEY}</code> · ${fmtNum(BongDB.totalRecords())} record di 17 tabel MySQL 8.0 Schema`,
      icon: 'database', size: 'max-w-3xl',
      body: `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[360px] overflow-y-auto thin-scroll">
          ${stats.map((s) => `
            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 hover:border-indigo-200 transition">
              <div class="min-w-0">
                <p class="text-[10px] font-mono text-slate-400">${String(s.no).padStart(2, '0')} · PK ${s.pk}</p>
                <p class="text-[13px] font-medium font-mono text-slate-800 truncate">${s.table}</p>
              </div>
              <span class="font-mono text-sm font-bold text-indigo-600">${fmtNum(s.count)}</span>
            </div>`).join('')}
        </div>
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
          <div class="rounded-lg bg-slate-50 border border-slate-100 p-3">${kv('Tanggal Demo', APP_TODAY_LABEL)}</div>
          <div class="rounded-lg bg-slate-50 border border-slate-100 p-3">${kv('Seed Dibuat', new Date(meta.seeded_at).toLocaleString('id-ID'))}</div>
          <div class="rounded-lg bg-slate-50 border border-slate-100 p-3">${kv('Terakhir Diperbarui', new Date(meta.updated_at).toLocaleString('id-ID'))}</div>
        </div>
        <div class="mt-5 flex items-start gap-3 p-4 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800">
          <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i>
          <p><b>Reset &amp; Seed Ulang</b> akan menghapus seluruh perubahan pada 17 tabel MySQL 8.0 schema dan memulihkan data demo awal (18 pesanan aktif, 142 roll tersedia, stok perpetual barang jadi, dan 11 metrik ukuran).</p>
        </div>`,
      footer: `
        <button type="button" class="btn btn-outline mr-auto" data-action="export-json"><i data-lucide="file-json" class="w-4 h-4"></i>Ekspor Backup JSON</button>
        <button type="button" class="btn btn-outline" data-action="close-modal">Tutup</button>
        <button type="button" class="btn btn-danger" data-action="reset-db"><i data-lucide="rotate-ccw" class="w-4 h-4"></i>Reset &amp; Seed Ulang Data Demo</button>`,
    });
  }

  /* --------------------------------------------------------------------------
   * 12. EXPORTS
   * ------------------------------------------------------------------------ */
  function exportShiftLog() {
    const todayPotong = BongDB.where('Pemotongan_Seri', (p) => String(p.tgl_potong).slice(0, 10) === APP_TODAY);
    const rows = Q.gelaranOfPotong(todayPotong).map((g) => {
      const pw = BongDB.find('Detail_Pemotongan_Warna', g.id_potong_warna);
      const ps = pw ? BongDB.find('Pemotongan_Seri', pw.id_potong) : null;
      const roll = Q.roll(g.id_roll);
      return {
        tgl_potong: ps?.tgl_potong || '', id_potong: ps?.id_potong || '', kode_seri: ps?.kode_seri || '', id_pesanan: ps?.id_pesanan || '',
        pemotong: Q.user(ps?.id_user_pemotong)?.nama_user || '', id_gelar: g.id_gelar, id_roll: g.id_roll,
        kain: roll ? Q.rollInfo(roll).label : '', panjang_gelar_m: g.panjang_gelar, jumlah_tumpuk: g.jumlah_tumpuk, total_lembar_gelar: g.total_lembar_gelar,
      };
    });
    if (!rows.length) return showToast('Belum ada gelaran pada shift hari ini.', { type: 'warning' });
    downloadFile(`shift_log_${APP_TODAY}.csv`, '\uFEFF' + toCsv(rows), 'text/csv;charset=utf-8');
    showToast(`${rows.length} baris gelaran · ${fmtNum(sum(rows, (r) => r.total_lembar_gelar))} pcs diekspor ke <b>shift_log_${APP_TODAY}.csv</b>`, { title: 'Shift log diekspor' });
  }

  function exportExcel() {
    let dataset = BongDB.data.Pesanan;
    if (state.tab === 'laporan') {
      const start = state.laporan.startDate;
      const end = state.laporan.endDate;
      const sumberFilter = state.laporan.sumber;
      const statusFilter = state.laporan.status;
      dataset = dataset.filter((p) => {
        const tgl = p.tgl_order || APP_TODAY;
        if (tgl < start || tgl > end) return false;
        if (sumberFilter !== 'ALL' && String(p.id_sumber) !== String(sumberFilter)) return false;
        if (statusFilter !== 'ALL' && p.status_pesanan !== statusFilter) return false;
        return true;
      });
    }

    const rows = dataset.map((p) => {
      const seri = BongDB.where('Pemotongan_Seri', (s) => s.id_pesanan === p.id_pesanan);
      const gel = Q.gelaranOfPotong(seri);
      const rollsUsed = [...new Set(gel.map((g) => g.id_roll))].join('; ');
      return {
        id_pesanan: p.id_pesanan, kode_pola: Q.pola(p.id_pola)?.kode_pola || '', nama_pola: Q.pola(p.id_pola)?.nama_pola || '',
        sumber_inisiasi: Q.sumber(p.id_sumber)?.nama_sumber || '', nama_mitra: p.nama_mitra || '-', tgl_order: p.tgl_order,
        deadline: p.deadline || addDays(p.tgl_order || APP_TODAY, 14),
        status_pesanan: p.status_pesanan, progress_pct: p.progress_pct, lembar_dipotong: sum(seri, (s) => s.total_lembar_hasil),
        roll_terpakai: rollsUsed || '-', temuan_cacat: BongDB.where('Kerusakan', (k) => k.id_pesanan === p.id_pesanan).length,
      };
    });
    downloadFile(`laporan_konsolidasi_${APP_TODAY}.csv`, '\uFEFF' + toCsv(rows), 'text/csv;charset=utf-8');
    showToast(`${rows.length} baris pesanan dikonsolidasi diekspor ke <b>laporan_konsolidasi_${APP_TODAY}.csv</b> (UTF-8 BOM, kompatibel Excel).`, { title: 'Laporan Konsolidasi Diekspor' });
  }

  /* --------------------------------------------------------------------------
   * 13. EVENT HANDLING
   * ------------------------------------------------------------------------ */
  function toggleDropdown(id, force) {
    const el = $(id);
    const open = force !== undefined ? force : el.classList.contains('hidden');
    el.classList.toggle('hidden', !open);
    if (open) el.classList.add('pop-in');
    return open;
  }
  function closeDropdowns(except) {
    ['#role-menu', '#notif-menu', '#search-results'].forEach((id) => { if (id !== except) $(id).classList.add('hidden'); });
    $('#btn-role-menu').setAttribute('aria-expanded', 'false');
  }

  const ACTIONS = {
    'goto': (el) => { closeModal(); closeDropdowns(); switchTab(el.dataset.target); },
    'open-settings': () => { closeDropdowns(); openSettingsModal(); },
    'close-modal': () => closeModal(),
    'confirm-yes': () => { const fn = state.confirmFn; closeModal(); if (fn) fn(); },
    'reset-db': () => confirmDialog({
      title: 'Reset & seed ulang database?', danger: true, icon: 'rotate-ccw', confirmText: 'Ya, Reset Data',
      message: `Seluruh data pada 17 tabel di <code class="font-mono">${DB_KEY}</code> akan dikembalikan ke kondisi demo awal. Tindakan ini tidak dapat dibatalkan.`,
      onConfirm: () => {
        state.selectedEdf = null; state.notifSeen = 0;
        state.pesanan.flashOrderId = null;
        state.penerimaan.flashRollId = null;
        BongDB.reset();
        showToast(`Database dipulihkan · ${fmtNum(BongDB.totalRecords())} record di 17 tabel.`, { title: 'Reset berhasil' });
      },
    }),
    'export-json': () => {
      downloadFile(`bong_garment_db_backup_${APP_TODAY}.json`, BongDB.exportJSON(), 'application/json');
      showToast('Backup seluruh 17 tabel berhasil diunduh.', { title: 'Backup JSON' });
    },
    'export-shift-log': () => exportShiftLog(),
    'export-excel': () => exportExcel(),
    'step-pending': (el) => {
      const step = el.dataset.step;
      showToast(`Aksi <b>${esc(el.dataset.label || 'Simpan')}</b> akan aktif pada <b>Step ${esc(step)}</b>. Skema tabel terkait sudah tersedia di <code class="font-mono">BongDB</code>.`, { type: 'info', title: `Modul Step ${step}` });
    },
    'logout': () => confirmDialog({
      title: 'Keluar dari sistem?', icon: 'log-out', danger: true, confirmText: 'Keluar',
      message: 'Sesi <b>Luis Febry</b> akan diakhiri. Pada prototipe ini data tetap tersimpan di localStorage browser.',
      onConfirm: () => {
        applyRole('Admin');
        switchTab('dashboard');
        showToast('Sesi demo diakhiri. Anda masuk kembali sebagai Admin.', { type: 'info', title: 'Keluar sistem' });
      },
    }),
    'toggle-role-menu': (el) => {
      closeDropdowns('#role-menu');
      const open = toggleDropdown('#role-menu');
      el.setAttribute('aria-expanded', String(open));
    },
    'set-role': (el) => {
      closeDropdowns();
      if (el.dataset.role === state.role) return;
      applyRole(el.dataset.role, { announce: true });
    },
    'toggle-notif': () => {
      closeDropdowns('#notif-menu');
      const open = toggleDropdown('#notif-menu');
      if (open) { state.notifSeen = buildNotifications().length; $('#notif-dot').classList.add('hidden'); refreshIcons(); }
    },
    'filter-orders': (el) => { state.orderFilter = el.dataset.filter; state.showAllOrders = false; renderOrdersTable(); refreshIcons(); },
    'toggle-orders': () => { state.showAllOrders = !state.showAllOrders; renderOrdersTable(); refreshIcons(); },
    'open-order': (el) => { closeDropdowns(); showPesananDetail(el.dataset.id); },
    'open-roll': (el) => { closeDropdowns(); showRollDetail(el.dataset.id); },
    'open-pola': (el) => { closeDropdowns(); showPolaDetail(el.dataset.id); },
    'siap-gelar': (el) => {
      closeModal();
      const idRoll = Number(el.dataset.id);
      const r = Q.roll(idRoll);
      if (r) {
        state.pemotongan.draftGelarRows = [
          {
            id_roll: idRoll,
            panjang_gelar: 2.2,
            jumlah_tumpuk: 50,
            total_lembar_gelar: 60,
            status_akhir: 'Dipotong',
          },
        ];
        state.pemotongan.rollStatusMap[idRoll] = 'Dipotong';
      }
      switchTab('pemotongan');
      renderDraftGelarTable();
      updateCuttingYieldSummary();
      showToast(`<span class="font-mono">id_roll: ${idRoll}</span> (${esc(r ? Q.rollInfo(r).label : '')}) ditambahkan ke draft gelaran di Meja Potong. Pilih pesanan target dari Cutter Queue EDF.`, { type: 'info', title: 'Roll siap digelar' });
    },
    'select-sumber': (el) => {
      state.selectedSumber = Number(el.dataset.id);
      renderSumberTiles();
      renderPesananScreen();
      refreshIcons();
    },
    'select-edf': (el) => {
      state.selectedEdf = Number(el.dataset.id);
      renderEdfQueue();
      renderPemotonganScreen({ selectedOrderId: state.selectedEdf });
      refreshIcons();
    },

    // Screen 2 Actions
    'submit-pesanan': () => submitPesanan(),
    'reset-pesanan-form': () => {
      resetPesananForm();
      showToast('Form pesanan telah direset ke nilai awal.', { type: 'info' });
    },
    'toggle-pola-mode': (el) => {
      state.pesanan.polaMode = el.dataset.mode;
      renderPesananScreen();
      refreshIcons();
    },
    'view-selected-pola': () => {
      const selId = Number($('#sel-pola-existing')?.value || state.pesanan.selectedPolaId || BongDB.data.Pola[0]?.id_pola);
      showPolaDetail(selId);
    },
    'select-mitra-chip': (el) => {
      const m = el.dataset.mitra;
      const inp = $('#inp-nama-mitra');
      if (inp) {
        inp.value = m;
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      }
    },

    // Screen 3 Actions
    'submit-penerimaan': () => submitPenerimaan(),
    'reset-penerimaan-form': () => {
      resetPenerimaanForm();
      showToast('Form penerimaan bahan telah direset ke nilai awal.', { type: 'info' });
    },
    'modal-add-supplier': () => modalAddSupplier(),
    'save-new-supplier': () => {
      const nama = $('#inp-new-sup-nama')?.value.trim();
      const kontak = $('#inp-new-sup-kontak')?.value.trim();
      const alamat = $('#inp-new-sup-alamat')?.value.trim();
      if (!nama) { showToast('Nama Supplier wajib diisi.', { type: 'error' }); $('#inp-new-sup-nama')?.focus(); return; }
      if (!kontak) { showToast('Kontak Supplier wajib diisi.', { type: 'error' }); $('#inp-new-sup-kontak')?.focus(); return; }
      if (!alamat) { showToast('Alamat Supplier wajib diisi.', { type: 'error' }); $('#inp-new-sup-alamat')?.focus(); return; }

      const newSup = BongDB.insert('Supplier', { nama_supplier: nama, kontak, alamat });
      closeModal();
      showToast(`Supplier <b>${esc(nama)}</b> (ID: #${newSup.id_supplier}) berhasil ditambahkan ke database.`, { title: 'Supplier Terdaftar' });
      renderPenerimaanScreen({ selectedSupplierId: newSup.id_supplier });
    },
    'add-color-group': () => {
      const nextId = ++state.penerimaan.nextColorGroupId;
      const palette = [
        { name: 'Navy', code: 'NVY-02', hex: '#1E3A8A', rack: 'A-05' },
        { name: 'Khaki', code: 'KHK-03', hex: '#D97706', rack: 'B-04' },
        { name: 'Olive Green', code: 'OLV-04', hex: '#166534', rack: 'C-02' },
        { name: 'Maroon', code: 'MRN-05', hex: '#991B1B', rack: 'D-01' },
        { name: 'White Bright', code: 'WHT-06', hex: '#F8FAFC', rack: 'A-03' },
      ];
      const pick = palette[(nextId - 1) % palette.length];
      state.penerimaan.colorGroups.push({
        id: nextId,
        namaWarna: pick.name,
        kodeWarna: pick.code,
        rakDefault: pick.rack,
        hexColor: pick.hex,
      });
      renderColorGroups();
      renderDraftRollsTable();
    },
    'remove-color-group': (el) => {
      if (state.penerimaan.colorGroups.length <= 1) return;
      const cgId = Number(el.dataset.id);
      state.penerimaan.colorGroups = state.penerimaan.colorGroups.filter((g) => g.id !== cgId);
      const fallbackId = state.penerimaan.colorGroups[0].id;
      state.penerimaan.draftRolls.forEach((r) => {
        if (r.colorGroupId === cgId) r.colorGroupId = fallbackId;
      });
      renderColorGroups();
      renderDraftRollsTable();
    },
    'add-roll-row': () => {
      const defaultGroup = state.penerimaan.colorGroups[0] || { id: 1, rakDefault: 'B-04' };
      state.penerimaan.draftRolls.push({
        colorGroupId: defaultGroup.id,
        beratKg: 25.0,
        rakAlokasi: defaultGroup.rakDefault || 'B-04',
      });
      renderDraftRollsTable();
      renderColorGroups();
    },
    'add-5-rolls': () => {
      const defaultGroup = state.penerimaan.colorGroups[0] || { id: 1, rakDefault: 'B-04' };
      for (let i = 0; i < 5; i++) {
        state.penerimaan.draftRolls.push({
          colorGroupId: defaultGroup.id,
          beratKg: 25.0,
          rakAlokasi: defaultGroup.rakDefault || 'B-04',
        });
      }
      renderDraftRollsTable();
      renderColorGroups();
      showToast('5 baris roll kain baru ditambahkan ke draft.', { type: 'info' });
    },
    'remove-roll-row': (el) => {
      if (state.penerimaan.draftRolls.length <= 1) {
        showToast('Minimal harus ada 1 roll fisik dalam penerimaan bahan.', { type: 'warning' });
        return;
      }
      const idx = Number(el.dataset.index);
      state.penerimaan.draftRolls.splice(idx, 1);
      renderDraftRollsTable();
      renderColorGroups();
    },
    'filter-rolls': (el) => {
      state.penerimaan.rollFilter = el.dataset.filter;
      renderInventoryRollsTable();
      refreshIcons();
    },
    'select-kain-chip': (el) => {
      const k = el.dataset.kain;
      const inp = $('#inp-nama-kain');
      if (inp) {
        inp.value = k;
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      }
    },

    // Screen 4 Actions (Pemotongan)
    'submit-pemotongan': () => submitPemotongan(),
    'reset-pemotongan-form': () => {
      resetPemotonganForm();
      showToast('Form pemotongan seri telah direset ke nilai awal.', { type: 'info' });
    },
    'add-gelar-row': () => {
      const selectedWarnaId = Number($('#sel-potong-warna')?.value || 1);
      const availRolls = getAvailableRollOptions(selectedWarnaId);
      const usedRollIds = new Set((state.pemotongan.draftGelarRows || []).map((r) => r.id_roll));
      const nextRoll = availRolls.find((r) => !usedRollIds.has(r.id_roll)) || availRolls[0];
      const pcsPerPly = Number($('#inp-pcs-per-ply')?.value) || 2;
      state.pemotongan.draftGelarRows.push({
        id_roll: nextRoll ? nextRoll.id_roll : 4503,
        panjang_gelar: 2.2,
        jumlah_tumpuk: 50,
        total_lembar_gelar: 50 * pcsPerPly,
        status_akhir: 'Dipotong',
      });
      renderDraftGelarTable();
      updateCuttingYieldSummary();
    },
    'remove-gelar-row': (el) => {
      if (state.pemotongan.draftGelarRows.length <= 1) {
        showToast('Minimal harus ada 1 baris gelaran roll.', { type: 'warning' });
        return;
      }
      const idx = Number(el.dataset.index);
      state.pemotongan.draftGelarRows.splice(idx, 1);
      renderDraftGelarTable();
      updateCuttingYieldSummary();
    },
    'set-roll-post-status': (el) => {
      const idRoll = Number(el.dataset.rollId);
      const status = el.dataset.status;
      state.pemotongan.rollStatusMap[idRoll] = status;
      state.pemotongan.draftGelarRows.forEach((r) => {
        if (r.id_roll === idRoll) r.status_akhir = status;
      });
      renderDraftGelarTable();
      updateCuttingYieldSummary();
    },

    // Screen 5 Actions (Quality Control)
    'submit-qc': () => submitQc(),
    'reset-qc-form': () => {
      resetQcForm();
      showToast('Form inspeksi QC telah direset ke nilai awal.', { type: 'info' });
    },
    'approve-qc-pass': () => approveQcPass(),
    'resolve-qc': (el) => resolveQc(el.dataset.id),
    'pick-defect-cat': (el) => {
      state.qc.selectedDefectCat = el.dataset.cat;
      renderQcScreen();
    },
    'pick-qc-decision': (el) => {
      state.qc.selectedDecision = el.dataset.decision;
      renderQcScreen();
    },
    'fill-qc-template': (el) => {
      const cat = el.dataset.cat;
      const textarea = $('#inp-catatan-qc');
      if (!textarea) return;
      if (cat === 'potong') {
        state.qc.selectedDefectCat = 'Cacat Potong';
        textarea.value = 'Terjadi pergeseran pola kain pada gelaran layer 4 saat mesin potong berjalan. Membutuhkan pemotongan ulang lembar kain komponen lengan.';
      } else if (cat === 'jahit') {
        state.qc.selectedDefectCat = 'Cacat Jahit';
        textarea.value = 'Kerutan berlebih dan jahitan loncat pada bagian kerah kemeja. Perlu dibongkar dan dijahit ulang oleh penjahit CMT.';
      } else if (cat === 'bahan') {
        state.qc.selectedDefectCat = 'Cacat Bahan';
        state.qc.selectedDecision = 'Retur Supplier';
        textarea.value = 'Ditemukan perbedaan saturasi warna (shading belang) antar gulungan kain pada lot yang sama. Roll kain ditandai klaim retur supplier.';
      }
      renderQcScreen();
    },
    'filter-qc-table': (el) => {
      state.qc.tableFilter = el.dataset.filter;
      renderQcTable();
    },

    // Screen 6 Actions (Laporan)
    'print-report': () => printReport(),
    'apply-laporan-filter': () => applyLaporanFilter(),
    'reset-laporan-filter': () => resetLaporanFilter(),

    // Screen 7 Actions (Gudang & Aksesoris)
    'modal-add-aksesoris': () => showAddAksesorisModal(),
    'save-new-aksesoris': () => {
      const nama = $('#inp-new-acc-nama')?.value.trim();
      const ukuran = $('#inp-new-acc-ukuran')?.value.trim();
      const satuan = $('#sel-new-acc-satuan')?.value || 'Pcs';
      const stok = Number($('#inp-new-acc-stok')?.value) || 0;
      const idSupplier = Number($('#sel-new-acc-supplier')?.value) || 1;

      if (!nama) { showToast('Nama Aksesoris wajib diisi.', { type: 'error' }); $('#inp-new-acc-nama')?.focus(); return; }
      if (!ukuran) { showToast('Ukuran Aksesoris wajib diisi.', { type: 'error' }); $('#inp-new-acc-ukuran')?.focus(); return; }

      const newAcc = BongDB.insert('Aksesoris', {
        id_supplier: idSupplier,
        nama_aksesoris: nama,
        ukuran,
        stok,
        satuan,
      });

      closeModal();
      showToast(`Aksesoris <b>${esc(nama)}</b> (#${newAcc.id_aksesoris}) berhasil ditambahkan ke inventori.`, { title: 'Aksesoris Terdaftar' });
      renderAll();
    },
    'quick-add-acc-stock': (el) => {
      const id = Number(el.dataset.id);
      const amount = Number(el.dataset.amount) || 100;
      const acc = BongDB.find('Aksesoris', id);
      if (acc) {
        const nextVal = Number(acc.stok || 0) + amount;
        BongDB.update('Aksesoris', id, { stok: nextVal });
        showToast(`Stok <b>${esc(acc.nama_aksesoris)}</b> bertambah +${amount} ${esc(acc.satuan)} (Total: ${fmtNum(nextVal)} ${esc(acc.satuan)}).`, { title: 'Stok Aksesoris Ditambah' });
        renderAll();
      }
    },
    'modal-adjust-stok': () => showAdjustStokModal(),
    'save-adjust-stok': () => {
      const idStok = Number($('#sel-adjust-stok-id')?.value);
      const delta = Number($('#inp-adjust-stok-qty')?.value) || 0;
      const st = BongDB.find('Stok_Barang_Jadi', idStok);
      if (st) {
        const nextVal = Math.max(0, Number(st.jumlah_stok_pcs || 0) + delta);
        BongDB.update('Stok_Barang_Jadi', idStok, {
          jumlah_stok_pcs: nextVal,
          tgl_update_terakhir: APP_TODAY + ' 16:00:00',
        });
        closeModal();
        showToast(`Stok Barang Jadi ID #${idStok} disesuaikan ${delta >= 0 ? '+' + delta : delta} pcs (Stok Baru: ${fmtNum(nextVal)} pcs).`, { title: 'Penyesuaian Stok Disimpan' });
        renderAll();
      }
    },

    // EDF Deadline Revision Actions
    'revise-deadline': (el) => {
      showReviseDeadlineModal(Number(el.dataset.id));
    },
    'save-revise-deadline': (el) => {
      const idPesanan = Number(el.dataset.id);
      const newDate = $('#inp-revise-deadline-val')?.value;
      const reasonSelect = $('#sel-revise-deadline-reason')?.value;
      const reasonNote = $('#inp-revise-deadline-note')?.value.trim();
      if (!newDate) {
        showToast('Tanggal deadline baru wajib diisi.', { type: 'error' });
        return;
      }
      const fullReason = reasonNote ? `${reasonSelect} (${reasonNote})` : reasonSelect;
      BongDB.update('Pesanan', idPesanan, {
        deadline_terbaru: newDate,
        tgl_perubahan_deadline: APP_TODAY + ' 14:00:00',
        alasan_perubahan_deadline: fullReason,
      });
      closeModal();
      showToast(`Deadline Pesanan <b>#${idPesanan}</b> diperbarui menjadi <b>${fmtDate(newDate)}</b>. Urutan Cutter Queue EDF telah disesuaikan secara real-time.`, { title: 'Revisi Deadline Berhasil' });
      renderAll();
    },
  };

  function bindEvents() {
    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!target.closest('#role-switcher')) $('#role-menu').classList.add('hidden');
      if (!target.closest('#notif-wrap')) $('#notif-menu').classList.add('hidden');
      if (!target.closest('#search-wrap')) $('#search-results').classList.add('hidden');

      const tabEl = target.closest('[data-tab]');
      if (tabEl) { e.preventDefault(); switchTab(tabEl.dataset.tab); return; }

      const el = target.closest('[data-action]');
      if (!el) return;
      const action = el.dataset.action;

      const mutatingActions = [
        'submit-pesanan', 'submit-penerimaan', 'submit-pemotongan', 'submit-qc',
        'approve-qc-pass', 'resolve-qc', 'modal-add-supplier', 'save-new-supplier',
        'modal-add-aksesoris', 'save-new-aksesoris', 'modal-adjust-stok', 'save-adjust-stok',
        'quick-add-acc-stock', 'save-revise-deadline', 'reset-db', 'siap-gelar'
      ];
      if (state.role === 'Pemilik' && mutatingActions.includes(action)) {
        e.preventDefault();
        showToast('<b>Mode Audit Pemilik Aktif:</b> Peran Pemilik memiliki izin baca penuh (read-only) untuk pengawasan eksekutif dan tidak dapat melakukan mutasi data.', { type: 'warning', title: 'Mode Audit (Read-Only)' });
        return;
      }

      const fn = ACTIONS[action];
      if (fn) { e.preventDefault(); fn(el, e); }
    });

    window.addEventListener('hashchange', () => {
      const t = location.hash.slice(1);
      if (t && t !== state.tab) switchTab(t, { updateHash: false });
    });
    window.addEventListener('popstate', () => {
      const t = location.hash.slice(1) || 'dashboard';
      if (t !== state.tab) switchTab(t, { updateHash: false });
    });

    // Delegated input event listeners for dynamic reactivity
    document.addEventListener('input', (e) => {
      const id = e.target.id;
      if (id === 'inp-deadline-mitra') {
        const val = e.target.value;
        if (val) {
          const diff = diffDays(APP_TODAY, val);
          const remBadge = $('#deadline-remaining-badge');
          if (remBadge) {
            remBadge.textContent = diff < 0 ? `Lewat ${Math.abs(diff)} Hari` : `${diff} Hari Kerja Tersisa`;
            remBadge.className = `badge font-mono ${diff <= 7 ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-200'}`;
          }
          const orderDl = $('#inp-deadline-order');
          if (orderDl && state.selectedSumber === 1) orderDl.value = val;
        }
      } else if (id === 'inp-nama-mitra') {
        updateSmartJenisPesanan();
      } else if (id === 'inp-nama-pola') {
        updateSmartJenisPesanan();
      } else if (e.target.classList.contains('inp-roll-weight')) {
        const idx = Number(e.target.dataset.index);
        if (state.penerimaan.draftRolls[idx]) {
          state.penerimaan.draftRolls[idx].beratKg = Number(e.target.value) || 0;
          updateDraftRollsSummary();
          renderColorGroups();
        }
      } else if (e.target.classList.contains('inp-roll-rak')) {
        const idx = Number(e.target.dataset.index);
        if (state.penerimaan.draftRolls[idx]) {
          state.penerimaan.draftRolls[idx].rakAlokasi = e.target.value;
        }
      } else if (e.target.classList.contains('inp-cg-nama')) {
        const cgId = Number(e.target.dataset.cgId);
        const cg = state.penerimaan.colorGroups.find((g) => g.id === cgId);
        if (cg) {
          cg.namaWarna = e.target.value;
          renderDraftRollsTable();
        }
      } else if (e.target.classList.contains('inp-cg-kode')) {
        const cgId = Number(e.target.dataset.cgId);
        const cg = state.penerimaan.colorGroups.find((g) => g.id === cgId);
        if (cg) {
          cg.kodeWarna = e.target.value;
          renderDraftRollsTable();
        }
      } else if (e.target.classList.contains('inp-cg-rak')) {
        const cgId = Number(e.target.dataset.cgId);
        const cg = state.penerimaan.colorGroups.find((g) => g.id === cgId);
        if (cg) cg.rakDefault = e.target.value;
      } else if (e.target.classList.contains('color-picker')) {
        const cgId = Number(e.target.dataset.cgId);
        const cg = state.penerimaan.colorGroups.find((g) => g.id === cgId);
        if (cg) cg.hexColor = e.target.value;
      } else if (e.target.classList.contains('inp-panjang-gelar')) {
        const idx = Number(e.target.dataset.index);
        if (state.pemotongan.draftGelarRows[idx]) {
          state.pemotongan.draftGelarRows[idx].panjang_gelar = Number(e.target.value) || 0;
          const totalPanjang = state.pemotongan.draftGelarRows.reduce((t, r) => t + (Number(r.panjang_gelar) || 0), 0);
          const totalPanjangEl = $('#lbl-total-panjang-m');
          if (totalPanjangEl) totalPanjangEl.textContent = totalPanjang.toFixed(1);
        }
      } else if (e.target.classList.contains('inp-jumlah-tumpuk')) {
        const idx = Number(e.target.dataset.index);
        if (state.pemotongan.draftGelarRows[idx]) {
          const ply = Number(e.target.value) || 0;
          state.pemotongan.draftGelarRows[idx].jumlah_tumpuk = ply;
          const pcsPerPly = Number($('#inp-pcs-per-ply')?.value) || 2;
          state.pemotongan.draftGelarRows[idx].total_lembar_gelar = ply * pcsPerPly;
          const tr = e.target.closest('tr');
          const lembarInp = tr?.querySelector('.inp-lembar-gelar');
          if (lembarInp) lembarInp.value = state.pemotongan.draftGelarRows[idx].total_lembar_gelar;
          updateCuttingYieldSummary();
        }
      } else if (e.target.classList.contains('inp-lembar-gelar')) {
        const idx = Number(e.target.dataset.index);
        if (state.pemotongan.draftGelarRows[idx]) {
          state.pemotongan.draftGelarRows[idx].total_lembar_gelar = Number(e.target.value) || 0;
          updateCuttingYieldSummary();
        }
      } else if (id === 'inp-total-lembar-hasil' || id === 'inp-lembar-rencana') {
        const totalLembar = Number($('#inp-total-lembar-hasil')?.value) || 0;
        const planned = Number($('#inp-lembar-rencana')?.value) || 120;
        const yieldVsPlanned = $('#yield-vs-planned');
        if (yieldVsPlanned) {
          const pct = Math.round((totalLembar / Math.max(planned, 1)) * 100);
          const rows = state.pemotongan.draftGelarRows || [];
          const uniqueRolls = new Set(rows.map((r) => r.id_roll)).size;
          const avg = uniqueRolls ? Math.round(totalLembar / uniqueRolls) : 0;
          yieldVsPlanned.innerHTML = `
            <i data-lucide="${pct >= 100 ? 'check' : 'alert-circle'}" class="w-3.5 h-3.5 ${pct >= 100 ? 'text-emerald-600' : 'text-amber-500'}"></i>
            <span>${pct}% dari rencana (${planned} Lembar) · Rata-rata ${avg} Pcs/Roll (${rows.length} Gelaran)</span>`;
          refreshIcons();
        }
      }
    });

    // Delegated change event listeners
    document.addEventListener('change', (e) => {
      const id = e.target.id;
      if (id === 'sel-pola-existing') {
        state.pesanan.selectedPolaId = Number(e.target.value);
        renderPesananScreen();
        refreshIcons();
      } else if (id === 'sel-pola-kategori') {
        const katId = Number(e.target.value);
        const kat = Q.kategori(katId);
        const isCelana = kat?.nama_kategori?.toLowerCase().includes('celana');
        renderNewPolaSpecGrid(isCelana);
        const nextPolaId = BongDB.nextId('Pola');
        const prefix = isCelana ? 'CLN' : kat?.nama_kategori?.toLowerCase().includes('jaket') ? 'JKT' : kat?.nama_kategori?.toLowerCase().includes('kaos') ? 'KOS' : 'KMJ';
        const inpKode = $('#inp-kode-pola');
        if (inpKode) inpKode.value = `${prefix}-NEW-${String(nextPolaId).padStart(2, '0')}`;
      } else if (id === 'sel-penerimaan-order') {
        const hasPen = Boolean(e.target.value);
        const initStatusText = $('#pesanan-initial-status-text');
        if (initStatusText) {
          initStatusText.textContent = hasPen ? 'ANTREAN · 15% Persiapan Gelar' : 'ANTREAN · 0% Menunggu Bahan';
        }
        const initBadge = $('#pesanan-initial-badge');
        if (initBadge) {
          initBadge.className = hasPen ? 'badge bg-indigo-50 text-indigo-700 ring-indigo-200' : 'badge bg-slate-100 text-slate-700 ring-slate-200';
        }
      } else if (id === 'sel-supplier') {
        const sup = Q.supplier(Number(e.target.value));
        const box = $('#supplier-info-box');
        if (box && sup) {
          box.innerHTML = `
            <div class="flex items-center gap-2 truncate">
              <span class="font-semibold text-slate-800">${esc(sup.nama_supplier)}</span>
              <span class="text-slate-300">|</span>
              <span class="text-slate-600"><i data-lucide="phone" class="w-3 h-3 inline mr-1 text-slate-400"></i>${esc(sup.kontak)}</span>
              <span class="text-slate-300">|</span>
              <span class="text-slate-600 truncate"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1 text-slate-400"></i>${esc(sup.alamat)}</span>
            </div>`;
          refreshIcons();
        }
      } else if (e.target.classList.contains('sel-roll-color')) {
        const idx = Number(e.target.dataset.index);
        const cgId = Number(e.target.value);
        if (state.penerimaan.draftRolls[idx]) {
          state.penerimaan.draftRolls[idx].colorGroupId = cgId;
          const cg = state.penerimaan.colorGroups.find((g) => g.id === cgId);
          if (cg && cg.rakDefault) {
            state.penerimaan.draftRolls[idx].rakAlokasi = cg.rakDefault;
            const tr = e.target.closest('tr');
            const rakInp = tr?.querySelector('.inp-roll-rak');
            if (rakInp) rakInp.value = cg.rakDefault;
          }
          renderColorGroups();
        }
      } else if (id === 'sel-potong-pesanan') {
        state.selectedEdf = Number(e.target.value);
        renderEdfQueue();
        renderPemotonganScreen({ selectedOrderId: state.selectedEdf });
        refreshIcons();
      } else if (id === 'sel-potong-warna') {
        renderDraftGelarTable();
        updateCuttingYieldSummary();
        refreshIcons();
      } else if (e.target.classList.contains('sel-gelar-roll')) {
        const idx = Number(e.target.dataset.index);
        const newRollId = Number(e.target.value);
        if (state.pemotongan.draftGelarRows[idx]) {
          state.pemotongan.draftGelarRows[idx].id_roll = newRollId;
          if (!state.pemotongan.rollStatusMap[newRollId]) {
            state.pemotongan.rollStatusMap[newRollId] = 'Dipotong';
          }
          updateCuttingYieldSummary();
        }
      } else if (e.target.classList.contains('sel-gelar-status')) {
        const idx = Number(e.target.dataset.index);
        const rollId = Number(e.target.dataset.rollId);
        const val = e.target.value;
        if (state.pemotongan.draftGelarRows[idx]) {
          state.pemotongan.draftGelarRows[idx].status_akhir = val;
        }
        if (rollId) state.pemotongan.rollStatusMap[rollId] = val;
        updateCuttingYieldSummary();
      } else if (id === 'sel-qc-pesanan') {
        state.qc.selectedOrderId = Number(e.target.value);
        renderQcScreen({ selectedOrderId: state.qc.selectedOrderId });
        refreshIcons();
      } else if (id === 'sel-jenis-cacat') {
        state.qc.selectedDefectCat = e.target.value;
        renderQcScreen();
        refreshIcons();
      }
    });

    // Global search
    const input = $('#global-search');
    let timer = null;
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => runSearch(input.value), 120); });
    input.addEventListener('focus', () => { if (input.value.trim()) runSearch(input.value); });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { const first = $('#search-results .search-item'); if (first) { first.click(); input.blur(); } }
      if (e.key === 'Escape') { input.value = ''; runSearch(''); input.blur(); }
    });

    document.addEventListener('keydown', (e) => {
      const typing = /input|textarea|select/i.test(document.activeElement?.tagName || '');
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); input.focus(); input.select(); }
      else if (e.key === '/' && !typing) { e.preventDefault(); input.focus(); }
      else if (e.key === 'Escape') { closeModal(); closeDropdowns(); }
    });
  }

  /* --------------------------------------------------------------------------
   * 14. INIT
   * ------------------------------------------------------------------------ */
  function renderAll() {
    renderDashboard();
    renderSumberTiles();
    renderEdfQueue();
    renderShellMeta();
    renderNotifications();
    renderPesananScreen();
    renderPenerimaanScreen();
    renderPemotonganScreen();
    renderQcScreen();
    renderGudangScreen();
    renderLaporanScreen();
    refreshIcons();
    runCountUps();
  }

  function init() {
    BongDB.init();
    const todayEl = $('#today-label');
    if (todayEl) todayEl.textContent = APP_TODAY_LABEL;

    renderSkeletons();
    applyRole(state.role);
    renderAll();
    bindEvents();

    const initial = location.hash.slice(1);
    switchTab(TABS.includes(initial) ? initial : 'dashboard', { updateHash: false });

    BongDB.subscribe(() => renderAll());
  }

  // Public API for Steps 1–7
  window.BongApp = {
    state, switchTab, applyRole, showToast, openModal, closeModal, confirmDialog,
    renderAll, renderDashboard, renderPesananScreen, renderPenerimaanScreen,
    renderPemotonganScreen, renderQcScreen, renderGudangScreen, renderLaporanScreen,
    submitPesanan, submitPenerimaan, resetPesananForm, resetPenerimaanForm,
    submitPemotongan, resetPemotonganForm, submitQc, resetQcForm, approveQcPass, resolveQc,
    showReviseDeadlineModal, showAddAksesorisModal, showAdjustStokModal,
    applyLaporanFilter, resetLaporanFilter, printReport,
    computeMetrics, refreshIcons, Q,
    utils: { addDays, diffDays, fmtDate, fmtNum, esc, sum, round1, downloadFile, toCsv },
    constants: { APP_TODAY, APP_TODAY_LABEL, TARGET_HARIAN_PCS, ACTIVE_STATUSES, TABLE_PK, ROLES },
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
