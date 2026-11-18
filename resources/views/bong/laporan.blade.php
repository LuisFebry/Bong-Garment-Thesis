@extends('layouts.app')

@section('title', 'Laporan Eksekutif & Rekapan - Bong Garment')

@section('content')
@php
  $filters = $reports['filters'] ?? [];
  $summary = $reports['summary'] ?? [];
  $thresholds = $reports['thresholds'] ?? ['min_stok_jadi' => 20, 'min_stok_aksesoris' => 500];
  $meta = $reports['meta'] ?? [];
  $company = $reports['companyProfile'] ?? \App\Http\Controllers\LaporanEksekutifController::getCompanyProfile();
  $jenis = $filters['jenis_laporan'] ?? 'KONSOLIDASI';

  $cookieAuthRole = $_COOKIE['bong_garment_auth_role'] ?? request()->cookie('bong_garment_auth_role');
  $cookieSimRole = $_COOKIE['bong_garment_role'] ?? request()->cookie('bong_garment_role');
  $effectiveViewRole = ($cookieAuthRole === 'Admin' && $cookieSimRole) ? $cookieSimRole : ($meta['printer_role'] ?? 'Admin');
  $canEditKopSurat = ($effectiveViewRole === 'Admin');

  $printedAt = $meta['printed_at'] ?? \Carbon\Carbon::now('Asia/Jakarta');
  $printedAtFormatted = $printedAt instanceof \Carbon\Carbon
      ? $printedAt->locale('id')->translatedFormat('d M Y, H:i:s') . ' WIB'
      : date('d M Y, H:i:s') . ' WIB';
  $printedDateShort = $printedAt instanceof \Carbon\Carbon
      ? $printedAt->locale('id')->translatedFormat('d M Y')
      : date('d M Y');
  $printedDateLong = $printedAt instanceof \Carbon\Carbon
      ? $printedAt->locale('id')->translatedFormat('d F Y')
      : date('d F Y');

  $printerName = $meta['printer_name'] ?? 'Administrator';
  $printerUsername = $meta['printer_username'] ?? 'admin';
  $printerRole = $meta['printer_role'] ?? 'Admin';
  $companyDisplayName = $company['nama_perusahaan'] ?? 'BONG GARMENT';
  $printerRoleLabel = match($printerRole) {
      'Pemilik' => 'Pemilik ' . $companyDisplayName,
      'Admin' => 'Admin / Manajemen ' . $companyDisplayName,
      default => $printerRole . ' ' . $companyDisplayName,
  };

  $startLabel = !empty($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : null;
  $endLabel = !empty($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') : null;
  if ($startLabel && $endLabel) {
      $periodeText = $startLabel . ' s/d ' . $endLabel;
  } elseif ($startLabel) {
      $periodeText = 'Mulai ' . $startLabel . ' s/d ' . $printedDateShort;
  } elseif ($endLabel) {
      $periodeText = 'Hingga ' . $endLabel;
  } else {
      $periodeText = ($jenis === 'STOK')
          ? 'Posisi Stok Per ' . $printedDateShort
          : 'Semua Periode Operasional (s/d ' . $printedDateShort . ')';
  }

  $reportTitles = [
      'KONSOLIDASI' => 'Laporan Rekapitulasi & Konsolidasi Produksi CMT (End-to-End)',
      'STOK' => 'Laporan Posisi Stok Gudang Barang Jadi, Aksesoris & Roll Kain',
      'BAHAN_MASUK' => 'Laporan Penerimaan Bahan Baku & Registrasi Roll Supplier',
      'MUTASI_KELUAR' => 'Laporan Pengeluaran Barang Jadi (Outbound Mitra) & Riwayat Mutasi',
      'QC' => 'Laporan Rekapitulasi Inspeksi Quality Control (QC) & Kerusakan',
  ];
  $activeReportTitle = $reportTitles[$jenis] ?? $reportTitles['KONSOLIDASI'];

  $stokList = $reports['stokBarangJadiList'] ?? collect();
  $aksesorisList = $reports['aksesorisList'] ?? collect();
  $rollList = $reports['rollList'] ?? collect();
  $penerimaanList = $reports['penerimaanList'] ?? collect();
  $riwayatList = $reports['riwayatStokList'] ?? collect();
  $qcList = $reports['qcList'] ?? collect();
  $consolidatedOrders = $reports['consolidatedOrders'] ?? collect();

  // Compute totals for footers
  $sumStokMin = (int) $thresholds['min_stok_jadi'];
  $sumAccMin = (int) $thresholds['min_stok_aksesoris'];

  $totalRollAllInPenerimaan = $penerimaanList->sum(fn($p) => $p->detailBahanWarna->sum('total_roll'));
  $totalUkuranInPenerimaan = $penerimaanList->sum(fn($p) => $p->detailBahanWarna->sum('total_ukuran'));

  $totalMutasiMasukCount = $riwayatList->where('jenis_transaksi', 'MASUK')->count();
  $totalMutasiKeluarCount = $riwayatList->where('jenis_transaksi', 'KELUAR')->count();
@endphp

<style>
  @page {
    size: A4 landscape;
    margin: 10mm 12mm;
  }
  @media print {
    html, body, body > div, main {
      display: block !important;
      height: auto !important;
      min-height: 0 !important;
      overflow: visible !important;
      background: #ffffff !important;
      padding: 0 !important;
      margin: 0 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .doc-sheet {
      border: none !important;
      box-shadow: none !important;
      border-radius: 0 !important;
      padding: 0 !important;
      margin: 0 !important;
      display: block !important;
    }
    .doc-sheet > .print-page-break {
      margin-top: 0 !important;
      padding-top: 0 !important;
      break-before: page !important;
      page-break-before: always !important;
    }
    .print-avoid-break {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
    .doc-table thead {
      display: table-header-group;
    }
    .doc-table tr {
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .doc-table th,
    .doc-table td {
      padding-top: 4px !important;
      padding-bottom: 4px !important;
    }
    .signature-block {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
    }
  }
</style>

<div class="space-y-6 print:space-y-0">
  @if(session('success'))
    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between gap-2 print:hidden">
      <div class="flex items-center gap-2">
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium print:hidden">
      <i data-lucide="alert-circle" class="w-4 h-4 inline mr-1 text-rose-600"></i>
      {{ $errors->first() }}
    </div>
  @endif

  <!-- Header Kontrol & Tombol Ekspor Dokumen -->
  <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Eksekutif &amp; Dokumen Rekapan Resmi</h2>
      <p class="text-xs text-slate-500 mt-1">Format dokumen formal perusahaan lengkap dengan Kop Surat, Tabel Rekapitulasi, Pengesahan, serta ekspor Word (<code>.doc</code>), CSV, dan PDF</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      @if($canEditKopSurat)
        <button type="button" onclick="openCompanyProfileModal()" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
          <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
          <span>Ubah Kop Surat &amp; Alamat</span>
        </button>
      @endif
      <button type="button" onclick="exportLaporanWord()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="file-text" class="w-4 h-4"></i>
        <span>Unduh Dokumen (.doc)</span>
      </button>
      <button type="button" onclick="exportLaporanExcel()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
        <span>Ekspor Tabel (.csv)</span>
      </button>
      <button type="button" onclick="window.print()" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="printer" class="w-4 h-4"></i>
        <span>Cetak Dokumen (PDF)</span>
      </button>
    </div>
  </div>

  <!-- 1. Panel Filter Parameter -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 print:hidden">
    <form method="GET" action="{{ route('laporan.index', [], false) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Dokumen Laporan</label>
        <select name="jenis_laporan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
          <option value="KONSOLIDASI" {{ $jenis === 'KONSOLIDASI' ? 'selected' : '' }}>1. Rekapitulasi &amp; Konsolidasi CMT (Semua)</option>
          <option value="STOK" {{ $jenis === 'STOK' ? 'selected' : '' }}>2. Posisi Stok Gudang (Jadi, Aksesoris &amp; Roll)</option>
          <option value="BAHAN_MASUK" {{ $jenis === 'BAHAN_MASUK' ? 'selected' : '' }}>3. Penerimaan Bahan Baku &amp; Roll Supplier</option>
          <option value="MUTASI_KELUAR" {{ $jenis === 'MUTASI_KELUAR' ? 'selected' : '' }}>4. Pengeluaran Barang (Outbound Mitra &amp; Mutasi)</option>
          <option value="QC" {{ $jenis === 'QC' ? 'selected' : '' }}>5. Rekapitulasi Inspeksi QC &amp; Kerusakan</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Inisiasi (Konsolidasi)</label>
        <select name="id_sumber" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
          <option value="">-- Semua Sumber Inisiasi --</option>
          @foreach($sumberList ?? [] as $sb)
            <option value="{{ $sb->id_sumber }}" {{ (string)($filters['id_sumber'] ?? '') === (string)$sb->id_sumber ? 'selected' : '' }}>
              {{ $sb->nama_sumber }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="flex items-center gap-2">
        <button type="submit" class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center justify-center gap-1.5 transition-colors">
          <i data-lucide="filter" class="w-3.5 h-3.5"></i>
          <span>Tampilkan Laporan</span>
        </button>
        <a href="{{ route('laporan.index', [], false) }}" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Reset
        </a>
      </div>
    </form>
  </div>

  <!-- 2. LEMBAR DOKUMEN RESMI PERUSAHAAN (.docx / PDF Layout) -->
  <div id="laporan-dokumen-resmi" class="doc-sheet bg-white rounded-xl border border-slate-300 shadow-sm p-6 md:p-10 space-y-6 text-slate-900">

    <!-- KOP SURAT RESMI PERUSAHAAN -->
    <div class="border border-slate-300 rounded-xl p-5 bg-white print-avoid-break">
      <div class="flex items-start gap-4">
        <!-- Logo Konsisten dengan Navbar (Indigo Rounded + Ikon Gunting Putih) -->
        <div class="w-14 h-14 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-100 shrink-0" style="background-color:#4f46e5; color:#ffffff;">
          <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="6" cy="6" r="3"/>
            <path d="M8.12 8.12 12 12"/>
            <path d="M20 4 8.12 15.88"/>
            <circle cx="6" cy="18" r="3"/>
            <path d="M14.8 14.8 20 20"/>
          </svg>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-baseline gap-x-2.5">
            <h1 class="font-serif text-xl md:text-2xl font-extrabold text-indigo-950 tracking-wide uppercase">{{ $company['nama_perusahaan'] }}</h1>
            <span class="text-[11px] font-bold text-rose-700 uppercase tracking-widest">{{ $company['tagline'] }}</span>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">{{ $company['alamat'] }} | Telp: {{ $company['telepon'] }}</p>
          <p class="text-[11px] text-slate-500">{{ $company['deskripsi_sistem'] }}</p>
        </div>
      </div>

      <!-- Kotak Metadata Dokumen -->
      <div class="mt-4 bg-slate-50/70 border border-slate-200 rounded-lg px-4 py-3 text-xs space-y-1">
        <div><span class="font-semibold text-slate-700">Judul Laporan:</span> <strong class="text-slate-900">{{ $activeReportTitle }}</strong></div>
        <div><span class="font-semibold text-slate-700">Periode:</span> <span class="font-semibold text-indigo-700">{{ $periodeText }}</span></div>
        <div><span class="font-semibold text-slate-700">Diotorisasi / Dicetak Oleh:</span> <strong class="text-slate-900">{{ $printerUsername }} ({{ $printerName }} / {{ $printerRole }})</strong></div>
        <div><span class="font-semibold text-slate-700">Waktu Cetak:</span> <span class="font-mono text-slate-800">{{ $printedAtFormatted }}</span></div>
      </div>

      <div class="border-t-2 border-slate-900 mt-4"></div>
    </div>

    <!-- 3 KARTU RINGKASAN EKSEKUTIF DINAMIS (Selalu 3 Kolom Sejajar pada Halaman 1 PDF) -->
    @if($jenis === 'KONSOLIDASI')
      <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PESANAN PRODUKSI</p>
          <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_pesanan'] ?? 0) }} Pesanan</p>
          <p class="text-[11px] text-slate-500 mt-0.5">{{ $summary['pesanan_selesai'] ?? 0 }} Selesai &bull; {{ $summary['pesanan_proses'] ?? 0 }} Dalam Proses</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL HASIL POTONG &amp; KAIN</p>
          <p class="text-xl font-extrabold text-indigo-700 mt-1">{{ number_format($summary['total_lembar_potong'] ?? 0) }} Lembar</p>
          <p class="text-[11px] text-slate-500 mt-0.5">{{ $summary['total_roll_dikonsumsi'] ?? 0 }} Roll ({{ number_format((float)($summary['total_kain_terpakai'] ?? 0), 2, '.', '') }} Kg/Yd/M)</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">KELOLOSAN QC &amp; STOK JADI</p>
          <p class="text-xl font-extrabold text-emerald-700 mt-1">{{ $summary['pass_rate_pct'] ?? 0 }}% Lolos QC</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Gudang Abadi: {{ number_format($summary['total_stok_jadi'] ?? 0) }} Pcs</p>
        </div>
      </div>
    @elseif($jenis === 'STOK')
      <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL VARIASI STOK JADI</p>
          <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_sku_stok_jadi'] ?? 0) }} Item ({{ number_format($summary['total_stok_jadi'] ?? 0) }} Pcs)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Batas min. global: &le; {{ $sumStokMin }} Pcs</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">PERSEDIAAN AKSESORIS CMT</p>
          <p class="text-xl font-extrabold text-indigo-700 mt-1">{{ number_format($summary['total_aksesoris_item'] ?? 0) }} Jenis ({{ number_format($summary['total_aksesoris_unit'] ?? 0) }} Unit)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Batas min. aksesoris: &lt; {{ $sumAccMin }} Unit</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ITEM STOK MINIMUM &amp; ROLL SISA</p>
          <p class="text-xl font-extrabold {{ ($summary['low_stock_jadi_count'] + $summary['low_stock_aksesoris_count']) > 0 ? 'text-rose-700' : 'text-emerald-700' }} mt-1">
            {{ ($summary['low_stock_jadi_count'] ?? 0) + ($summary['low_stock_aksesoris_count'] ?? 0) }} Item Kritis
          </p>
          <p class="text-[11px] text-slate-500 mt-0.5">{{ ($summary['roll_tersedia_count'] ?? 0) + ($summary['roll_dipotong_count'] ?? 0) }} Roll kain belum habis</p>
        </div>
      </div>
    @elseif($jenis === 'BAHAN_MASUK')
      <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL NOTA PENERIMAAN</p>
          <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_penerimaan_nota'] ?? 0) }} Nota</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Surat jalan bahan baku supplier</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL ROLL DITERIMA</p>
          <p class="text-xl font-extrabold text-indigo-700 mt-1">{{ number_format($totalRollAllInPenerimaan) }} Roll Fisik</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Terdaftar pada kelompok warna</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL KUANTITAS BAHAN MASUK</p>
          <p class="text-xl font-extrabold text-emerald-700 mt-1">{{ number_format((float)$totalUkuranInPenerimaan, 2, '.', '') }} Kg / Yd / M</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Akumulasi bruto penerimaan kain</p>
        </div>
      </div>
    @elseif($jenis === 'MUTASI_KELUAR')
      <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL LOG TRANSAKSI STOK</p>
          <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($riwayatList->count()) }} Transaksi</p>
          <p class="text-[11px] text-slate-500 mt-0.5">{{ $totalMutasiKeluarCount }} Keluar &bull; {{ $totalMutasiMasukCount }} Masuk</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PENGELUARAN (OUTBOUND)</p>
          <p class="text-xl font-extrabold text-rose-700 mt-1">-{{ number_format($summary['total_outbound_pcs'] ?? 0) }} Pcs</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Diserahkan ke Mitra / Distribusi</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PEMASUKAN (INBOUND)</p>
          <p class="text-xl font-extrabold text-emerald-700 mt-1">+{{ number_format($summary['total_inbound_pcs'] ?? 0) }} Pcs</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Masuk dari QC Produk Abadi / Opname</p>
        </div>
      </div>
    @elseif($jenis === 'QC')
      <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL SAMPEL DIPERIKSA</p>
          <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_sampel_diperiksa'] ?? 0) }} Pcs ({{ $qcList->count() }} Log)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Prototipe, Potong &amp; Jahit Akhir</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TINGKAT KELOLOSAN (PASS RATE)</p>
          <p class="text-xl font-extrabold text-emerald-700 mt-1">{{ $summary['pass_rate_pct'] ?? 0 }}% ({{ number_format($summary['total_lolos_qc'] ?? 0) }} Pcs)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Target standar mutu: &ge; 95.0%</p>
        </div>
        <div class="border border-slate-300 rounded-xl p-4 bg-white">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">CACAT MUTU &amp; ANTREAN REWORK</p>
          <p class="text-xl font-extrabold text-rose-700 mt-1">{{ number_format($summary['total_cacat_qc'] ?? 0) }} Pcs ({{ $summary['defect_rate_pct'] ?? 0 }}%)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">{{ $summary['total_rework_pending'] ?? 0 }} batch status Need Rework</p>
        </div>
      </div>
    @endif

    <!-- TABEL REKAPITULASI FORMAL 2-KOLOM ALA DOKUMEN .DOCX (Halaman 2 Khusus KONSOLIDASI) -->
    @if($jenis === 'KONSOLIDASI')
      <div class="rekapan-sheet-block print-page-break print-avoid-break pt-2">
        <div class="text-center mb-3 font-serif">
          <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900">KONVEKSI {{ strtoupper($company['nama_perusahaan']) }}</h3>
          <p class="text-sm font-bold text-slate-900">Rekapitulasi Operasional Produksi &amp; Posisi Inventori CMT</p>
          <p class="text-xs font-bold text-slate-800">Per {{ $printedDateLong }}</p>
        </div>

        <table class="doc-table w-full border-collapse border border-slate-900 text-xs font-serif">
          <thead>
            <tr class="bg-slate-100">
              <th colspan="2" class="border border-slate-900 px-3 py-1.5 text-center font-bold uppercase tracking-wide text-slate-900 w-1/2">
                PRODUKSI &amp; BAHAN BAKU
              </th>
              <th colspan="2" class="border border-slate-900 px-3 py-1.5 text-center font-bold uppercase tracking-wide text-slate-900 w-1/2">
                MUTU (QC) &amp; INVENTORI GUDANG
              </th>
            </tr>
          </thead>
          <tbody>
            <!-- Sub-Header Blok 1 -->
            <tr class="bg-slate-50">
              <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">
                Status Pesanan Produksi (EDF)
              </td>
              <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">
                Hasil Quality Control (3 Tahap)
              </td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800 w-[34%]">- Pesanan Selesai</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900 w-[16%]">{{ number_format($summary['pesanan_selesai'] ?? 0) }} Order</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800 w-[34%]">- Total Sampel Diperiksa</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900 w-[16%]">{{ number_format($summary['total_sampel_diperiksa'] ?? 0) }} Pcs</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Pesanan Dalam Proses (Antrean/Potong/QC)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['pesanan_proses'] ?? 0) }} Order</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Unit Lolos QC ({{ $summary['pass_rate_pct'] ?? 0 }}%)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_lolos_qc'] ?? 0) }} Pcs</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Batch Pemotongan Seri Terealisasi</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_batch_potong'] ?? 0) }} Batch</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Unit Cacat ({{ $summary['defect_rate_pct'] ?? 0 }}%)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_cacat_qc'] ?? 0) }} Pcs</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Akumulasi Lembar Hasil Potong</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_lembar_potong'] ?? 0) }} Lembar</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Batch Antrean Perbaikan (Need Rework)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_rework_pending'] ?? 0) }} Batch</td>
            </tr>
            <tr class="bg-slate-50/70 font-bold">
              <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Pesanan Terdaftar</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_pesanan'] ?? 0) }} Order</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Inspeksi QC (Lolos + Cacat)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format(($summary['total_lolos_qc'] ?? 0) + ($summary['total_cacat_qc'] ?? 0)) }} Pcs</td>
            </tr>

            <!-- Sub-Header Blok 2 -->
            <tr class="bg-slate-50">
              <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">
                Penerimaan &amp; Konsumsi Roll Kain Fisik
              </td>
              <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">
                Inventori Barang Jadi &amp; Aksesoris CMT
              </td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Nota Penerimaan Supplier</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_penerimaan_nota'] ?? 0) }} Nota</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Stok Barang Jadi (Produk Abadi)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_stok_jadi'] ?? 0) }} Pcs</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Utuh Tersedia ({{ $summary['roll_tersedia_count'] ?? 0 }} Roll)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format((float)($summary['roll_tersedia_qty'] ?? 0), 2, '.', '') }}</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Variasi SKU Barang Jadi Aktif</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_sku_stok_jadi'] ?? 0) }} SKU</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Sedang Dipotong ({{ $summary['roll_dipotong_count'] ?? 0 }} Roll)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format((float)($summary['roll_dipotong_qty'] ?? 0), 2, '.', '') }}</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Persediaan Aksesoris CMT ({{ $summary['total_aksesoris_item'] ?? 0 }} Jenis)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_aksesoris_unit'] ?? 0) }} Unit</td>
            </tr>
            <tr>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Habis Terpakai</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['roll_habis_count'] ?? 0) }} Roll</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-800">- Barang Jadi Keluar / Outbound Mitra</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format($summary['total_outbound_pcs'] ?? 0) }} Pcs</td>
            </tr>
            <tr class="bg-slate-50/70 font-bold">
              <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Sisa Kain Roll di Rak</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format((float)($summary['total_ukuran_sisa_roll'] ?? 0), 2, '.', '') }}</td>
              <td class="border border-slate-900 px-3 py-1 text-slate-900">Item Stok Rendah (&le;{{ $sumStokMin }} Pcs / &lt;{{ $sumAccMin }} Unit)</td>
              <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">{{ number_format(($summary['low_stock_jadi_count'] ?? 0) + ($summary['low_stock_aksesoris_count'] ?? 0)) }} Item</td>
            </tr>

            <!-- Grand Total Footer Row -->
            <tr class="bg-slate-100 font-bold text-slate-900">
              <td class="border border-slate-900 px-3 py-1.5">Total Kain Terpakai Produksi ({{ $summary['total_roll_dikonsumsi'] ?? 0 }} Roll)</td>
              <td class="border border-slate-900 px-3 py-1.5 text-right font-mono">{{ number_format((float)($summary['total_kain_terpakai'] ?? 0), 2, '.', '') }}</td>
              <td class="border border-slate-900 px-3 py-1.5">Total Stok Masuk Gudang (Inbound)</td>
              <td class="border border-slate-900 px-3 py-1.5 text-right font-mono">{{ number_format($summary['total_inbound_pcs'] ?? 0) }} Pcs</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endif

    <!-- TABEL RINCIAN DATA SESUAI JENIS LAPORAN (Halaman 3 pada KONSOLIDASI / Halaman 2+ pada Laporan Lainnya) -->
    <div class="print-page-break space-y-6">
    @if($jenis === 'KONSOLIDASI')
      <!-- Detail Konsolidasi End-to-End Pesanan -->
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Rincian Konsolidasi Operasional Pesanan (End-to-End)</h3>
            <p class="text-[11px] text-slate-500">Pelacakan pesanan dari pola, pemakaian roll kain fisik, hasil potong, hingga kualitas QC akhir</p>
          </div>
          <span class="text-xs font-mono font-bold text-indigo-700">{{ $consolidatedOrders->count() }} Baris</span>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">NO. ORDER</th>
                <th class="py-2.5 px-3 border-r border-slate-200">TGL ORDER &amp; EDF</th>
                <th class="py-2.5 px-3 border-r border-slate-200">POLA &amp; MITRA / SUMBER</th>
                <th class="py-2.5 px-3 border-r border-slate-200">ROLL TERPAKAI</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">YIELD POTONG</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">HASIL QC</th>
                <th class="py-2.5 px-3 text-center">STATUS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($consolidatedOrders as $row)
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">#ORD-{{ str_pad((string)$row->id_pesanan, 4, '0', STR_PAD_LEFT) }}</td>
                  <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200">
                    <div>{{ $row->tgl_order ? \Carbon\Carbon::parse($row->tgl_order)->format('d M Y') : '-' }}</div>
                    <div class="text-amber-700 font-semibold">EDF: {{ $row->deadline_terbaru ? \Carbon\Carbon::parse($row->deadline_terbaru)->format('d M Y') : '-' }}</div>
                  </td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <div class="font-bold text-slate-900">{{ $row->nama_pola }} <span class="font-mono text-[10px] text-slate-500">({{ $row->kode_pola }})</span></div>
                    <div class="text-[11px] text-slate-600">{{ $row->nama_sumber }} &bull; {{ $row->nama_mitra }}</div>
                  </td>
                  <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200">
                    <div class="font-semibold text-slate-800">{{ $row->rolls_used }}</div>
                    @if($row->kain_terpakai > 0)
                      <div class="text-[10px] text-slate-500">{{ number_format((float)$row->kain_terpakai, 2, '.', '') }} Kg/Yd/M ({{ $row->rolls_count }} roll)</div>
                    @endif
                  </td>
                  <td class="py-2.5 px-3 font-mono font-bold text-slate-900 text-right border-r border-slate-200">{{ number_format($row->actual_yield) }} lbr</td>
                  <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">
                    <span class="text-emerald-700 font-bold">{{ $row->qc_lolos }}</span> / <span class="text-rose-700 font-bold">{{ $row->qc_cacat }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border
                      {{ $row->status_pesanan === 'Selesai' ? 'border-emerald-300 text-emerald-800 bg-emerald-50' : ($row->status_pesanan === 'Dalam QC' ? 'border-amber-300 text-amber-800 bg-amber-50' : 'border-slate-300 text-slate-700 bg-slate-50') }}">
                      {{ $row->status_pesanan }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="py-6 text-center text-slate-400">Tidak ada data pesanan yang sesuai filter.</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL KONSOLIDASI ({{ $consolidatedOrders->count() }} PESANAN)</td>
                <td class="py-2.5 px-3 font-mono border-r border-slate-200">{{ number_format((float)$consolidatedOrders->sum('kain_terpakai'), 2, '.', '') }} Kg/Yd/M</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format($consolidatedOrders->sum('actual_yield')) }} lbr</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ $consolidatedOrders->sum('qc_lolos') }} / {{ $consolidatedOrders->sum('qc_cacat') }}</td>
                <td class="py-2.5 px-3 text-center">{{ $summary['pesanan_selesai'] ?? 0 }} Selesai</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    @elseif($jenis === 'STOK')
      <!-- Tabel A: Stok Barang Jadi -->
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">A. Laporan Posisi Stok Gudang Barang Jadi (Produk Abadi)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">KODE</th>
                <th class="py-2.5 px-3 border-r border-slate-200">NAMA PRODUK / POLA</th>
                <th class="py-2.5 px-3 border-r border-slate-200">KATEGORI</th>
                <th class="py-2.5 px-3 border-r border-slate-200">UKURAN &amp; WARNA</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">STOK AKTUAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">BATAS MIN.</th>
                <th class="py-2.5 px-3 text-center">STATUS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($stokList as $st)
                @php
                  $qty = (int) $st->jumlah_stok_pcs;
                  $isHabis = $qty <= 0;
                  $isMin = !$isHabis && $qty <= $sumStokMin;
                @endphp
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-slate-700 border-r border-slate-200">{{ $st->pola->kode_pola ?? 'SKU-' . $st->id_stok }}</td>
                  <td class="py-2.5 px-3 font-bold text-slate-900 border-r border-slate-200">{{ $st->pola->nama_pola ?? '-' }}</td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-indigo-200 bg-indigo-50 text-indigo-800">
                      {{ $st->pola->kategori->nama_kategori ?? 'Umum' }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 border-r border-slate-200">{{ $st->spesifikasiUkuran->nama_ukuran ?? '-' }} &bull; {{ $st->nama_warna }}</td>
                  <td class="py-2.5 px-3 font-mono font-bold text-right border-r border-slate-200 {{ ($isHabis || $isMin) ? 'text-rose-700' : 'text-slate-900' }}">{{ number_format($qty) }} pcs</td>
                  <td class="py-2.5 px-3 font-mono text-right text-slate-600 border-r border-slate-200">{{ number_format($sumStokMin) }} pcs</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border
                      {{ $isHabis ? 'border-rose-400 bg-rose-50 text-rose-800' : ($isMin ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700') }}">
                      {{ $isHabis ? 'Habis' : ($isMin ? 'Stok Minimum' : 'Normal') }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada stok barang jadi di gudang.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL STOK BARANG JADI ({{ $stokList->count() }} SKU)</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format($stokList->sum('jumlah_stok_pcs')) }} pcs</td>
                <td colspan="2" class="py-2.5 px-3 text-center text-rose-700">{{ $summary['low_stock_jadi_count'] ?? 0 }} SKU Minimum/Habis</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Tabel B: Posisi Stok Aksesoris CMT -->
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">B. Laporan Posisi Inventori Aksesoris CMT</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">KODE</th>
                <th class="py-2.5 px-3 border-r border-slate-200">NAMA AKSESORIS &amp; UKURAN</th>
                <th class="py-2.5 px-3 border-r border-slate-200">SUPPLIER REKANAN</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">STOK AKTUAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">BATAS MIN.</th>
                <th class="py-2.5 px-3 text-center">STATUS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($aksesorisList as $acc)
                @php
                  $accQty = (int) $acc->stok;
                  $accLow = $accQty < $sumAccMin;
                @endphp
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-slate-700 border-r border-slate-200">ACC-{{ str_pad((string)$acc->id_aksesoris, 3, '0', STR_PAD_LEFT) }}</td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <span class="font-bold text-slate-900">{{ $acc->nama_aksesoris }}</span>
                    @if($acc->ukuran)<span class="text-slate-500">({{ $acc->ukuran }})</span>@endif
                  </td>
                  <td class="py-2.5 px-3 border-r border-slate-200 text-slate-700">{{ $acc->supplier->nama_supplier ?? '-' }}</td>
                  <td class="py-2.5 px-3 font-mono font-bold text-right border-r border-slate-200 {{ $accLow ? 'text-rose-700' : 'text-slate-900' }}">{{ number_format($accQty) }} {{ $acc->satuan }}</td>
                  <td class="py-2.5 px-3 font-mono text-right text-slate-600 border-r border-slate-200">{{ number_format($sumAccMin) }} {{ $acc->satuan }}</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $accLow ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700' }}">
                      {{ $accLow ? 'Stok Minimum' : 'Normal' }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada data aksesoris CMT.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL PERSEDIAAN AKSESORIS ({{ $aksesorisList->count() }} ITEM)</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format($aksesorisList->sum('stok')) }} Unit</td>
                <td colspan="2" class="py-2.5 px-3 text-center text-rose-700">{{ $summary['low_stock_aksesoris_count'] ?? 0 }} Item Minimum</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Tabel C: Posisi Roll Kain Fisik -->
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">C. Laporan Posisi Sisa Roll Bahan Baku Kain ({{ $rollList->count() }} Roll)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">ID ROLL</th>
                <th class="py-2.5 px-3 border-r border-slate-200">NOTA &amp; SUPPLIER</th>
                <th class="py-2.5 px-3 border-r border-slate-200">KAIN &amp; WARNA</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">UKURAN AWAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">SISA AKTUAL</th>
                <th class="py-2.5 px-3 text-center">STATUS ROLL</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($rollList as $rl)
                @php
                  $warna = $rl->detailBahanWarna;
                  $penerimaan = $warna?->penerimaanBahan;
                @endphp
                <tr>
                  <td class="py-2 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">#{{ $rl->id_roll }} (Roll {{ $rl->no_roll_ke }})</td>
                  <td class="py-2 px-3 border-r border-slate-200">
                    <span class="font-mono font-semibold">{{ $penerimaan->no_nota ?? '-' }}</span>
                    <span class="text-slate-500"> &bull; {{ $penerimaan?->supplier?->nama_supplier ?? '-' }}</span>
                  </td>
                  <td class="py-2 px-3 border-r border-slate-200">{{ $penerimaan->nama_kain ?? '-' }} ({{ $warna->nama_warna ?? '-' }})</td>
                  <td class="py-2 px-3 font-mono text-right border-r border-slate-200">{{ number_format((float)$rl->ukuran_awal, 2, '.', '') }} {{ $rl->satuan }}</td>
                  <td class="py-2 px-3 font-mono font-bold text-right border-r border-slate-200">{{ number_format((float)$rl->ukuran_sisa, 2, '.', '') }} {{ $rl->satuan }}</td>
                  <td class="py-2 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border
                      {{ $rl->status_roll === 'Tersedia' ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : ($rl->status_roll === 'Dipotong' ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-slate-300 bg-slate-100 text-slate-600') }}">
                      {{ $rl->status_roll }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada gulungan roll kain terdaftar.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL GULUNGAN KAIN ({{ $rollList->count() }} ROLL)</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format((float)$rollList->sum('ukuran_awal'), 2, '.', '') }}</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format((float)$rollList->sum('ukuran_sisa'), 2, '.', '') }}</td>
                <td class="py-2.5 px-3 text-center">{{ $summary['roll_tersedia_count'] ?? 0 }} Tersedia</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    @elseif($jenis === 'BAHAN_MASUK')
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">Laporan Penerimaan Bahan Baku &amp; Registrasi Roll Supplier</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">NO. DOKUMEN / NOTA</th>
                <th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200">SUPPLIER / PEMASOK &amp; KAIN</th>
                <th class="py-2.5 px-3 border-r border-slate-200">PENERIMA / KENDARAAN</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">JML ROLL</th>
                <th class="py-2.5 px-3 text-right">TOTAL KUANTITAS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($penerimaanList as $pn)
                @php
                  $warnaSummary = $pn->detailBahanWarna->map(function($w) {
                      return $w->nama_warna . ' (' . $w->total_roll . ' roll, ' . number_format((float)$w->total_ukuran, 2, '.', '') . ' ' . $w->satuan_ukur . ')';
                  })->implode(', ');
                  $notaRolls = $pn->detailBahanWarna->sum('total_roll');
                  $notaUkuran = $pn->detailBahanWarna->sum('total_ukuran');
                  $notaSatuan = $pn->detailBahanWarna->first()?->satuan_ukur ?? 'Kg';
                @endphp
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">{{ $pn->no_nota }}</td>
                  <td class="py-2.5 px-3 font-mono border-r border-slate-200">{{ $pn->tgl_nota ? $pn->tgl_nota->format('d M Y') : '-' }}</td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <div class="font-bold text-slate-900">{{ $pn->supplier->nama_supplier ?? '-' }}</div>
                    <div class="text-[11px] text-slate-700 font-medium">{{ $pn->nama_kain }} @if($pn->lebar_gramasi) ({{ $pn->lebar_gramasi }}) @endif</div>
                    @if($warnaSummary)
                      <div class="text-[10px] text-slate-500 mt-0.5">{{ $warnaSummary }}</div>
                    @endif
                  </td>
                  <td class="py-2.5 px-3 border-r border-slate-200 text-slate-600">
                    <div>{{ $pn->penerima ?? '-' }}</div>
                    @if($pn->no_kendaraan)<div class="font-mono text-[10px]">{{ $pn->no_kendaraan }}</div>@endif
                  </td>
                  <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format($notaRolls) }} roll</td>
                  <td class="py-2.5 px-3 font-mono font-bold text-right text-slate-900">{{ number_format((float)$notaUkuran, 2, '.', '') }} {{ $notaSatuan }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-400">Tidak ada nota penerimaan bahan baku pada periode ini.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL PENERIMAAN BAHAN BAKU ({{ $penerimaanList->count() }} NOTA)</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ number_format($totalRollAllInPenerimaan) }} roll</td>
                <td class="py-2.5 px-3 font-mono text-right">{{ number_format((float)$totalUkuranInPenerimaan, 2, '.', '') }} Kg/Yd/M</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    @elseif($jenis === 'MUTASI_KELUAR')
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">Laporan Pengeluaran Barang Jadi (Outbound Mitra) &amp; Riwayat Mutasi Stok</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">NO. DOKUMEN</th>
                <th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200">MITRA / ALASAN &amp; PRODUK</th>
                <th class="py-2.5 px-3 border-r border-slate-200">PETUGAS</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-center">SALDO</th>
                <th class="py-2.5 px-3 text-right">QTY MUTASI</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($riwayatList as $rw)
                @php $isKeluar = ($rw->jenis_transaksi === 'KELUAR'); @endphp
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">
                    {{ $isKeluar ? 'OUT' : 'INB' }}-{{ str_pad((string)$rw->id_riwayat_stok, 4, '0', STR_PAD_LEFT) }}
                  </td>
                  <td class="py-2.5 px-3 font-mono border-r border-slate-200">{{ $rw->tgl_transaksi ? $rw->tgl_transaksi->format('d M Y') : '-' }}</td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <div class="font-bold text-slate-900">
                      [{{ $rw->jenis_transaksi }}] {{ $rw->nama_mitra ? $rw->nama_mitra . ' — ' : '' }}{{ $rw->kategori_alasan }}
                    </div>
                    <div class="text-[11px] text-slate-600">
                      {{ $rw->pola->nama_pola ?? '-' }} ({{ $rw->spesifikasiUkuran->nama_ukuran ?? '-' }} / {{ $rw->nama_warna }})
                      @if($rw->keterangan) &bull; {{ $rw->keterangan }} @endif
                    </div>
                  </td>
                  <td class="py-2.5 px-3 text-slate-600 border-r border-slate-200">{{ $rw->user->username ?? '-' }}</td>
                  <td class="py-2.5 px-3 font-mono text-center text-slate-600 border-r border-slate-200">{{ $rw->stok_sebelum }} &rarr; {{ $rw->stok_sesudah }}</td>
                  <td class="py-2.5 px-3 font-mono font-bold text-right {{ $isKeluar ? 'text-rose-700' : 'text-emerald-700' }}">
                    {{ $isKeluar ? '-' : '+' }}{{ number_format($rw->jumlah_pcs) }} pcs
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada catatan mutasi stok barang jadi pada periode ini.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL MUTASI STOK ({{ $riwayatList->count() }} TRANSAKSI)</td>
                <td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 text-emerald-700">+{{ number_format($summary['total_inbound_pcs'] ?? 0) }} In</td>
                <td class="py-2.5 px-3 font-mono text-right text-rose-700">-{{ number_format($summary['total_outbound_pcs'] ?? 0) }} Out</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    @elseif($jenis === 'QC')
      <!-- Tabel A: Ringkasan Cacat & Tahapan QC -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-300">
            <h3 class="font-bold text-slate-900 text-xs uppercase">A1. Sebaran Kategori Kerusakan</h3>
          </div>
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2 px-3 border-r border-slate-200">KATEGORI KERUSAKAN</th>
                <th class="py-2 px-3 text-right">TOTAL CACAT</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @foreach($reports['defectBreakdown'] ?? [] as $df)
                <tr>
                  <td class="py-2 px-3 border-r border-slate-200 text-slate-800 font-medium">{{ $df->label_lengkap ?? $df->jenis_cacat }}</td>
                  <td class="py-2 px-3 font-mono font-bold text-right text-rose-700">{{ number_format($df->total_cacat) }} pcs</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td class="py-2 px-3 border-r border-slate-200">TOTAL CACAT TERCATAT</td>
                <td class="py-2 px-3 font-mono text-right text-rose-700">{{ number_format(($reports['defectBreakdown'] ?? collect())->sum('total_cacat')) }} pcs</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-300">
            <h3 class="font-bold text-slate-900 text-xs uppercase">A2. Rekapitulasi per Tahapan QC</h3>
          </div>
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2 px-3 border-r border-slate-200">TAHAP QC</th>
                <th class="py-2 px-3 border-r border-slate-200 text-right">DIPERIKSA</th>
                <th class="py-2 px-3 border-r border-slate-200 text-right">LOLOS</th>
                <th class="py-2 px-3 text-right">CACAT</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($reports['stageBreakdown'] ?? [] as $st)
                <tr>
                  <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-800">{{ $st->tahap_qc }}</td>
                  <td class="py-2 px-3 font-mono text-right border-r border-slate-200">{{ number_format($st->total_diperiksa) }} pcs</td>
                  <td class="py-2 px-3 font-mono font-bold text-right text-emerald-700 border-r border-slate-200">{{ number_format($st->total_lolos) }} pcs</td>
                  <td class="py-2 px-3 font-mono font-bold text-right text-rose-700">{{ number_format($st->total_cacat) }} pcs</td>
                </tr>
              @empty
                <tr><td colspan="4" class="py-4 text-center text-slate-400">Belum ada data tahapan QC.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td class="py-2 px-3 border-r border-slate-200">TOTAL</td>
                <td class="py-2 px-3 font-mono text-right border-r border-slate-200">{{ number_format(($reports['stageBreakdown'] ?? collect())->sum('total_diperiksa')) }} pcs</td>
                <td class="py-2 px-3 font-mono text-right text-emerald-700 border-r border-slate-200">{{ number_format(($reports['stageBreakdown'] ?? collect())->sum('total_lolos')) }} pcs</td>
                <td class="py-2 px-3 font-mono text-right text-rose-700">{{ number_format(($reports['stageBreakdown'] ?? collect())->sum('total_cacat')) }} pcs</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Tabel B: Detail Log Inspeksi QC -->
      <div class="border border-slate-300 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-300">
          <h3 class="font-bold text-slate-900 text-sm">B. Catatan Detail Log Inspeksi Quality Control &amp; Rework</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="doc-table w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                <th class="py-2.5 px-3 border-r border-slate-200">NO. QC</th>
                <th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th>
                <th class="py-2.5 px-3 border-r border-slate-200">PESANAN, TAHAP &amp; CACAT</th>
                <th class="py-2.5 px-3 border-r border-slate-200">PETUGAS</th>
                <th class="py-2.5 px-3 border-r border-slate-200 text-right">DIPERIKSA / LOLOS / CACAT</th>
                <th class="py-2.5 px-3 text-center">STATUS</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              @forelse($qcList as $q)
                <tr>
                  <td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">QC-{{ str_pad((string)$q->id_qc, 4, '0', STR_PAD_LEFT) }}</td>
                  <td class="py-2.5 px-3 font-mono border-r border-slate-200">{{ $q->tgl_pemeriksaan ? $q->tgl_pemeriksaan->format('d M Y') : '-' }}</td>
                  <td class="py-2.5 px-3 border-r border-slate-200">
                    <div class="font-bold text-slate-900">[{{ $q->tahap_qc }}] {{ $q->pesanan?->pola?->nama_pola ?? $q->pesanan?->jenis_pesanan ?? '-' }}</div>
                    <div class="text-[11px] text-slate-600">
                      Cacat: {{ $q->jenis_cacat }} @if($q->catatan_qc) &bull; {{ $q->catatan_qc }} @endif
                    </div>
                  </td>
                  <td class="py-2.5 px-3 border-r border-slate-200 text-slate-600">{{ $q->userQc->username ?? '-' }}</td>
                  <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">
                    {{ $q->jumlah_diperiksa }} / <span class="text-emerald-700 font-bold">{{ $q->jumlah_lolos }}</span> / <span class="text-rose-700 font-bold">{{ $q->jumlah_cacat }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border
                      {{ $q->status_qc === 'Lolos QC' || $q->status_qc === 'Rework Selesai' ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-rose-300 bg-rose-50 text-rose-700' }}">
                      {{ $q->status_qc }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada log inspeksi QC pada periode ini.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                <td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL INSPEKSI ({{ $qcList->count() }} LOG)</td>
                <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">{{ $qcList->sum('jumlah_diperiksa') }} / {{ $qcList->sum('jumlah_lolos') }} / {{ $qcList->sum('jumlah_cacat') }}</td>
                <td class="py-2.5 px-3 text-center">{{ $summary['pass_rate_pct'] ?? 0 }}% Lolos</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    @endif

    <!-- BLOK PENGESAHAN RESMI (TANDA TANGAN KANAN BAWAH) -->
    <div class="signature-block pt-4 flex justify-end">
      <div class="w-72 text-center text-xs space-y-1">
        <p class="text-slate-700">{{ $company['kota_pengesahan'] ?? 'Jakarta' }}, {{ $printedDateShort }}</p>
        <p class="font-bold text-slate-900">Diotorisasi Oleh &mdash; {{ $printerRoleLabel }}</p>
        <div class="h-14"></div>
        <p class="font-bold text-slate-900 uppercase underline underline-offset-4 tracking-wide">{{ $printerUsername }}</p>
        <p class="text-[11px] text-slate-500">{{ $printerName }} &bull; {{ $printerRoleLabel }}</p>
      </div>
    </div>
    </div>

  </div>
</div>

@if($canEditKopSurat)
<!-- MODAL UBAH KOP SURAT & PROFIL PERUSAHAAN -->
<div id="modal-company-profile" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 print:hidden">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg overflow-hidden">
    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center">
          <i data-lucide="building-2" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Ubah Kop Surat, Alamat &amp; Telepon Perusahaan</h3>
          <p class="text-[11px] text-slate-500">Perubahan diterapkan otomatis ke halaman laporan, PDF, dan dokumen Word (<code>.doc</code>)</p>
        </div>
      </div>
      <button type="button" onclick="closeCompanyProfileModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form method="POST" action="{{ route('laporan.profile.update', [], false) }}" class="p-6 space-y-4 text-xs">
      @csrf
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nama Perusahaan <span class="text-rose-600">*</span></label>
          <input type="text" name="nama_perusahaan" required maxlength="100" value="{{ $company['nama_perusahaan'] }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-semibold">
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Tagline / Bidang Usaha <span class="text-rose-600">*</span></label>
          <input type="text" name="tagline" required maxlength="120" value="{{ $company['tagline'] }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div>
        <label class="block font-semibold text-slate-700 mb-1">Alamat Lengkap Konveksi <span class="text-rose-600">*</span></label>
        <input type="text" name="alamat" required maxlength="255" value="{{ $company['alamat'] }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Nomor Telepon / HP <span class="text-rose-600">*</span></label>
          <input type="text" name="telepon" required maxlength="60" value="{{ $company['telepon'] }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Kota Pengesahan Tanda Tangan <span class="text-rose-600">*</span></label>
          <input type="text" name="kota_pengesahan" required maxlength="60" value="{{ $company['kota_pengesahan'] ?? 'Jakarta' }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div>
        <label class="block font-semibold text-slate-700 mb-1">Subjudul / Deskripsi Sistem <span class="text-rose-600">*</span></label>
        <input type="text" name="deskripsi_sistem" required maxlength="200" value="{{ $company['deskripsi_sistem'] }}" class="w-full p-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
        <button type="button" onclick="closeCompanyProfileModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
          Simpan Kop Surat
        </button>
      </div>
    </form>
  </div>
</div>
@endif

@php
  $exportRows = [];
  if ($jenis === 'KONSOLIDASI') {
      foreach ($consolidatedOrders as $r) {
          $exportRows[] = [
              'No Order' => '#ORD-' . str_pad((string)$r->id_pesanan, 4, '0', STR_PAD_LEFT),
              'Kode Pola' => $r->kode_pola,
              'Nama Pola' => $r->nama_pola,
              'Sumber Inisiasi' => $r->nama_sumber,
              'Mitra' => $r->nama_mitra,
              'Tgl Order' => $r->tgl_order ? \Carbon\Carbon::parse($r->tgl_order)->format('d/m/Y') : '-',
              'Deadline EDF' => $r->deadline_terbaru ? \Carbon\Carbon::parse($r->deadline_terbaru)->format('d/m/Y') : '-',
              'Roll Terpakai' => $r->rolls_used,
              'Kain Terpakai' => number_format((float)$r->kain_terpakai, 2, '.', ''),
              'Actual Yield (Lembar)' => $r->actual_yield,
              'QC Lolos (Pcs)' => $r->qc_lolos,
              'QC Cacat (Pcs)' => $r->qc_cacat,
              'Status Pesanan' => $r->status_pesanan,
          ];
      }
  } elseif ($jenis === 'STOK') {
      foreach ($stokList as $st) {
          $qty = (int)$st->jumlah_stok_pcs;
          $exportRows[] = [
              'Tipe' => 'Barang Jadi',
              'Kode' => $st->pola->kode_pola ?? ('SKU-' . $st->id_stok),
              'Nama Item' => ($st->pola->nama_pola ?? '-') . ' (' . ($st->spesifikasiUkuran->nama_ukuran ?? '-') . ' / ' . $st->nama_warna . ')',
              'Kategori / Supplier' => $st->pola->kategori->nama_kategori ?? 'Umum',
              'Stok Aktual' => $qty . ' Pcs',
              'Batas Min.' => $sumStokMin . ' Pcs',
              'Status' => $qty <= 0 ? 'Habis' : ($qty <= $sumStokMin ? 'Stok Minimum' : 'Normal'),
          ];
      }
      foreach ($aksesorisList as $acc) {
          $qty = (int)$acc->stok;
          $exportRows[] = [
              'Tipe' => 'Aksesoris CMT',
              'Kode' => 'ACC-' . str_pad((string)$acc->id_aksesoris, 3, '0', STR_PAD_LEFT),
              'Nama Item' => $acc->nama_aksesoris . ($acc->ukuran ? ' (' . $acc->ukuran . ')' : ''),
              'Kategori / Supplier' => $acc->supplier->nama_supplier ?? '-',
              'Stok Aktual' => $qty . ' ' . $acc->satuan,
              'Batas Min.' => $sumAccMin . ' ' . $acc->satuan,
              'Status' => $qty < $sumAccMin ? 'Stok Minimum' : 'Normal',
          ];
      }
  } elseif ($jenis === 'BAHAN_MASUK') {
      foreach ($penerimaanList as $pn) {
          $exportRows[] = [
              'No Nota' => $pn->no_nota,
              'Tanggal' => $pn->tgl_nota ? $pn->tgl_nota->format('d/m/Y') : '-',
              'Supplier' => $pn->supplier->nama_supplier ?? '-',
              'Nama Kain' => $pn->nama_kain,
              'Penerima' => $pn->penerima ?? '-',
              'Jml Roll' => $pn->detailBahanWarna->sum('total_roll'),
              'Total Ukuran' => number_format((float)$pn->detailBahanWarna->sum('total_ukuran'), 2, '.', ''),
          ];
      }
  } elseif ($jenis === 'MUTASI_KELUAR') {
      foreach ($riwayatList as $rw) {
          $exportRows[] = [
              'No Dokumen' => ($rw->jenis_transaksi === 'KELUAR' ? 'OUT-' : 'INB-') . str_pad((string)$rw->id_riwayat_stok, 4, '0', STR_PAD_LEFT),
              'Tanggal' => $rw->tgl_transaksi ? $rw->tgl_transaksi->format('d/m/Y H:i') : '-',
              'Jenis' => $rw->jenis_transaksi,
              'Pola & Ukuran & Warna' => ($rw->pola->nama_pola ?? '-') . ' (' . ($rw->spesifikasiUkuran->nama_ukuran ?? '-') . ' / ' . $rw->nama_warna . ')',
              'Mitra / Alasan' => ($rw->nama_mitra ? $rw->nama_mitra . ' - ' : '') . $rw->kategori_alasan,
              'Petugas' => $rw->user->username ?? '-',
              'Saldo Sebelum' => $rw->stok_sebelum,
              'Saldo Sesudah' => $rw->stok_sesudah,
              'Qty (Pcs)' => ($rw->jenis_transaksi === 'KELUAR' ? '-' : '+') . $rw->jumlah_pcs,
          ];
      }
  } elseif ($jenis === 'QC') {
      foreach ($qcList as $q) {
          $exportRows[] = [
              'No QC' => 'QC-' . str_pad((string)$q->id_qc, 4, '0', STR_PAD_LEFT),
              'Tanggal' => $q->tgl_pemeriksaan ? $q->tgl_pemeriksaan->format('d/m/Y H:i') : '-',
              'Pola / Pesanan' => $q->pesanan?->pola?->nama_pola ?? $q->pesanan?->jenis_pesanan ?? '-',
              'Tahap QC' => $q->tahap_qc,
              'Petugas QC' => $q->userQc->username ?? '-',
              'Diperiksa' => $q->jumlah_diperiksa,
              'Lolos' => $q->jumlah_lolos,
              'Cacat' => $q->jumlah_cacat,
              'Jenis Cacat' => $q->jenis_cacat,
              'Status QC' => $q->status_qc,
          ];
      }
  }
@endphp

@push('scripts')
<script>
  const exportDataRows = @json($exportRows);
  const activeJenisLaporan = @json($jenis);
  const wordMeta = {
    companyName: @json($company['nama_perusahaan'] ?? 'BONG GARMENT'),
    companyTagline: @json($company['tagline'] ?? 'KONVEKSI & MANUFAKTUR PAKAIAN CMT'),
    companyAddress: @json($company['alamat'] ?? 'Jl. Industri Konveksi Raya No. 18, Jakarta Barat'),
    companyPhone: @json($company['telepon'] ?? '(021) 5589-2410'),
    companySubtitle: @json($company['deskripsi_sistem'] ?? 'Sistem Informasi Manajemen Produksi & Inventori Konveksi Cut, Make, and Trim (CMT)'),
    cityName: @json($company['kota_pengesahan'] ?? 'Jakarta'),
    title: @json($activeReportTitle),
    periode: @json($periodeText),
    printedBy: @json($printerUsername . ' (' . $printerName . ' / ' . $printerRole . ')'),
    printedAt: @json($printedAtFormatted),
    dateShort: @json($printedDateShort),
    signerRole: @json($printerRoleLabel),
    signerUser: @json($printerUsername),
    signerName: @json($printerName)
  };

  function openCompanyProfileModal() {
    const m = document.getElementById('modal-company-profile');
    if (m) m.classList.remove('hidden');
  }

  function closeCompanyProfileModal() {
    const m = document.getElementById('modal-company-profile');
    if (m) m.classList.add('hidden');
  }

  function exportLaporanExcel() {
    if (!exportDataRows || exportDataRows.length === 0) {
      alert('Tidak ada data pada jenis laporan ini untuk diekspor ke CSV.');
      return;
    }
    const headers = Object.keys(exportDataRows[0]);
    const csvLines = [
      headers.join(','),
      ...exportDataRows.map(row => headers.map(h => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(','))
    ];
    const csvContent = '\uFEFF' + csvLines.join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Rekapan_Bong_Garment_' + activeJenisLaporan + '_' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function escapeHtmlText(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function exportLaporanWord() {
    const docRoot = document.getElementById('laporan-dokumen-resmi');
    if (!docRoot) return;

    // Extract 3 KPI summary cards into a crisp Word-compatible 3-column table
    let kpiTableHtml = '';
    const kpiGrid = docRoot.querySelector('.kpi-summary-grid');
    if (kpiGrid) {
      const kpiCells = Array.from(kpiGrid.children).map(card => {
        const ps = Array.from(card.querySelectorAll('p')).map(p => p.textContent.trim());
        return `<td style="width:33.33%; border:1px solid #cbd5e1; padding:7pt 9pt; vertical-align:top;">
          <div style="font-size:8pt; font-weight:bold; text-transform:uppercase; color:#475569;">${escapeHtmlText(ps[0] || '')}</div>
          <div style="font-size:13pt; font-weight:bold; color:#0f172a; margin:3pt 0;">${escapeHtmlText(ps[1] || '')}</div>
          <div style="font-size:8.5pt; color:#475569;">${escapeHtmlText(ps[2] || '')}</div>
        </td>`;
      }).join('');
      kpiTableHtml = `<table style="width:100%; border-collapse:separate; border-spacing:6pt 0; margin-bottom:12pt;"><tr>${kpiCells}</tr></table>`;
    }

    // Extract section headings + tables cleanly for Word (.doc) rendering
    const tables = Array.from(docRoot.querySelectorAll('table.doc-table')).map(tbl => {
      let headingHtml = '';
      const centerWrap = tbl.closest('.rekapan-sheet-block');
      const cardWrap = tbl.closest('.border.border-slate-300');
      if (centerWrap) {
        const lines = Array.from(centerWrap.querySelectorAll('h3, p')).map(el => escapeHtmlText(el.textContent.trim()));
        headingHtml = `<div style="text-align:center; font-weight:bold; font-size:10.5pt; margin:10pt 0 6pt 0;">${lines.join('<br/>')}</div>`;
      } else if (cardWrap) {
        const h3 = cardWrap.querySelector('h3');
        if (h3) {
          headingHtml = `<div style="font-weight:bold; font-size:10.5pt; color:#0f172a; margin:12pt 0 5pt 0;">${escapeHtmlText(h3.textContent.trim())}</div>`;
        }
      }

      const clone = tbl.cloneNode(true);
      clone.removeAttribute('class');
      clone.setAttribute('style', 'width:100%; border-collapse:collapse; margin-bottom:10pt; font-family:"Times New Roman", serif; font-size:9pt;');
      clone.querySelectorAll('th, td').forEach(cell => {
        const existingAlign = cell.classList.contains('text-right') ? 'text-align:right;' : (cell.classList.contains('text-center') ? 'text-align:center;' : 'text-align:left;');
        const isBold = cell.tagName === 'TH' || cell.classList.contains('font-bold') || cell.closest('tr')?.classList.contains('font-bold');
        const bg = cell.tagName === 'TH' || cell.closest('tr')?.classList.contains('bg-slate-100') ? 'background-color:#f1f5f9;' : '';
        cell.removeAttribute('class');
        cell.setAttribute('style', `border:1px solid #0f172a; padding:4pt 6pt; vertical-align:top; ${existingAlign} ${isBold ? 'font-weight:bold;' : ''} ${bg}`);
      });
      return headingHtml + clone.outerHTML;
    }).join('\n');

    const htmlDoc = `
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8" />
  <title>${escapeHtmlText(wordMeta.title)}</title>
  <style>
    @page { size: A4 landscape; margin: 1.2cm; }
    body { font-family: "Times New Roman", Times, serif; color: #0f172a; font-size: 10pt; }
    .kop-title { font-size: 15pt; font-weight: bold; color: #1e1b4b; margin: 0; }
    .kop-sub { font-size: 9.5pt; color: #475569; margin: 2pt 0 0 0; }
    .meta-box { border: 1px solid #cbd5e1; background: #f8fafc; padding: 8pt 10pt; margin: 10pt 0; font-size: 9.5pt; }
    .divider { border-top: 2px solid #0f172a; margin: 8pt 0 12pt 0; }
    .sig-wrap { width: 100%; border-collapse: collapse; margin-top: 18pt; }
    .sig-wrap td { border: none; vertical-align: top; }
  </style>
</head>
<body>
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:48pt; height:48pt; vertical-align:middle; text-align:center; background:#4f46e5; color:#ffffff; padding:6pt;">
        <div style="font-size:20pt; line-height:1; color:#ffffff;">&#9986;</div>
      </td>
      <td style="padding-left:10pt; vertical-align:middle;">
        <p class="kop-title">${escapeHtmlText(wordMeta.companyName)} <span style="font-size:9pt; color:#be123c;">${escapeHtmlText(wordMeta.companyTagline)}</span></p>
        <p class="kop-sub">${escapeHtmlText(wordMeta.companyAddress)} | Telp: ${escapeHtmlText(wordMeta.companyPhone)}</p>
        <p class="kop-sub">${escapeHtmlText(wordMeta.companySubtitle)}</p>
      </td>
    </tr>
  </table>

  <div class="meta-box">
    <div><b>Judul Laporan:</b> ${escapeHtmlText(wordMeta.title)}</div>
    <div><b>Periode:</b> ${escapeHtmlText(wordMeta.periode)}</div>
    <div><b>Diotorisasi / Dicetak Oleh:</b> ${escapeHtmlText(wordMeta.printedBy)}</div>
    <div><b>Waktu Cetak:</b> ${escapeHtmlText(wordMeta.printedAt)}</div>
  </div>
  <div class="divider"></div>

  ${kpiTableHtml}
  ${tables}

  <table class="sig-wrap">
    <tr>
      <td style="width:58%;"></td>
      <td style="width:42%; text-align:center; font-size:9.5pt;">
        <div>${escapeHtmlText(wordMeta.cityName)}, ${escapeHtmlText(wordMeta.dateShort)}</div>
        <div><b>Diotorisasi Oleh &mdash; ${escapeHtmlText(wordMeta.signerRole)}</b></div>
        <br/><br/><br/>
        <div><b><u>${escapeHtmlText(wordMeta.signerUser.toUpperCase())}</u></b></div>
        <div style="font-size:8.5pt; color:#475569;">${escapeHtmlText(wordMeta.signerName)} &bull; ${escapeHtmlText(wordMeta.signerRole)}</div>
      </td>
    </tr>
  </table>
</body>
</html>`;

    const blob = new Blob(['\ufeff', htmlDoc], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Rekapan_Bong_Garment_' + activeJenisLaporan + '_' + new Date().toISOString().slice(0, 10) + '.doc';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
</script>
@endpush
@endsection
