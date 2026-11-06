@extends('layouts.app')

@section('title', 'Laporan Eksekutif - Bong Garment')

@section('content')
@php
  $filters = $reports['filters'] ?? [];
  $summary = $reports['summary'] ?? [];
  $jenis = $filters['jenis_laporan'] ?? 'KONSOLIDASI';
@endphp

<div class="space-y-6">
  <!-- Header & Fitur Ekspor Data -->
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Eksekutif & Analitik Produksi</h2>
      <p class="text-xs text-slate-500 mt-1">Konsolidasi pesanan, konsumsi roll fisik, rata-rata yield potong, stok barang jadi, dan rekapitulasi mutu QC</p>
    </div>

    <div class="flex items-center gap-2 print:hidden">
      <button type="button" onclick="exportLaporanExcel()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
        <span>Ekspor Data (Excel)</span>
      </button>
      <button type="button" onclick="window.print()" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="printer" class="w-4 h-4"></i>
        <span>Cetak Laporan (PDF)</span>
      </button>
    </div>
  </div>

  <!-- 1. Panel Filter Parameter -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 print:hidden">
    <form method="GET" action="{{ route('laporan.index', [], false) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Laporan</label>
        <select name="jenis_laporan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
          <option value="KONSOLIDASI" {{ $jenis === 'KONSOLIDASI' ? 'selected' : '' }}>Konsolidasi Produksi & Inventori (Semua)</option>
          <option value="STOK" {{ $jenis === 'STOK' ? 'selected' : '' }}>Stok Bahan & Barang Jadi</option>
          <option value="QC" {{ $jenis === 'QC' ? 'selected' : '' }}>Rekapitulasi QC & Kerusakan</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Inisiasi</label>
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
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai (<code class="font-mono">tgl_order</code>)</label>
        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai (<code class="font-mono">tgl_order</code>)</label>
        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="flex items-center gap-2">
        <button type="submit" class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center justify-center gap-1.5 transition-colors">
          <i data-lucide="filter" class="w-3.5 h-3.5"></i>
          <span>Terapkan Filter</span>
        </button>
        <a href="{{ route('laporan.index', [], false) }}" class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Reset
        </a>
      </div>
    </form>
  </div>

  <!-- 2. Kartu Ringkasan Metrik (KPI Cards) -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pesanan Produksi</span>
        <i data-lucide="clipboard-list" class="w-4 h-4 text-indigo-500"></i>
      </div>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ $summary['total_pesanan'] ?? 0 }} Pesanan</p>
      <p class="text-[11px] text-slate-500 mt-1">
        <b class="text-emerald-600">{{ $summary['pesanan_selesai'] ?? 0 }} Selesai</b> &bull;
        <b class="text-amber-600">{{ $summary['pesanan_proses'] ?? 0 }} Dalam Proses</b>
      </p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Roll Kain Dikonsumsi</span>
        <i data-lucide="cylinder" class="w-4 h-4 text-indigo-500"></i>
      </div>
      <p class="text-2xl font-bold text-indigo-700 mt-2">{{ $summary['total_roll_dikonsumsi'] ?? 0 }} Roll Fisik</p>
      <p class="text-[11px] text-slate-500 mt-1">
        Total kain terpakai: <b class="font-mono text-slate-800">{{ number_format($summary['total_kain_terpakai'] ?? 0, 2) }} Kg/Yd</b>
      </p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Yield Potong</span>
        <i data-lucide="scissors" class="w-4 h-4 text-indigo-500"></i>
      </div>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($summary['avg_yield_per_roll'] ?? 0, 1) }} <span class="text-sm font-normal text-slate-500">Pcs / Roll</span></p>
      <p class="text-[11px] text-slate-500 mt-1">
        Total output potong: <b class="font-mono text-indigo-600">{{ number_format($summary['total_lembar_potong'] ?? 0) }} Lembar</b>
      </p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Stok Barang Jadi</span>
        <i data-lucide="package-check" class="w-4 h-4 text-emerald-600"></i>
      </div>
      <p class="text-2xl font-bold text-emerald-700 mt-2">{{ number_format($summary['total_stok_jadi'] ?? 0) }} Pcs</p>
      <p class="text-[11px] text-slate-500 mt-1">Tersedia di Gudang (<code class="font-mono">Stok_Barang_Jadi</code>)</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tingkat Kelolosan QC (Pass Rate)</span>
        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
      </div>
      <p class="text-2xl font-bold text-emerald-600 mt-2">{{ $summary['pass_rate_pct'] ?? 0 }}%</p>
      <p class="text-[11px] text-slate-400 mt-1">Standar mutu minimum: &gt; 95.0%</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rasio Cacat (Defect Rate)</span>
        <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600"></i>
      </div>
      <p class="text-2xl font-bold text-rose-600 mt-2">{{ $summary['defect_rate_pct'] ?? 0 }}%</p>
      <p class="text-[11px] text-slate-400 mt-1">Akumulasi Cacat Potong, Jahit & Bahan</p>
    </div>
  </div>

  <!-- 3. Analisis Cacat (Termasuk Cacat Bahan / Fabric Stain) & Tahapan QC -->
  @if($jenis === 'KONSOLIDASI' || $jenis === 'QC')
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 class="font-bold text-slate-900 text-sm mb-1">Sebaran Kategori Kerusakan (Defect Breakdown)</h3>
      <p class="text-[11px] text-slate-500 mb-4">Mencakup Cacat Potong, Cacat Jahit, dan Cacat Bahan / Fabric Stain (Klaim Retur Supplier)</p>
      <div class="space-y-4">
        @foreach($reports['defectBreakdown'] ?? [] as $df)
        <div>
          <div class="flex justify-between text-xs mb-1 font-medium">
            <span class="text-slate-700 font-semibold">{{ $df->label_lengkap ?? $df->jenis_cacat }}</span>
            <span class="font-mono text-slate-900 font-bold">{{ $df->total_cacat }} pcs</span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2.5">
            <div class="{{ $df->jenis_cacat === 'Cacat Bahan' ? 'bg-amber-500' : 'bg-rose-500' }} h-2.5 rounded-full" style="width: {{ max(4, min(100, $df->total_cacat * 6)) }}%"></div>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 class="font-bold text-slate-900 text-sm mb-1">Performa Pemeriksaan per Tahapan QC</h3>
      <p class="text-[11px] text-slate-500 mb-4">Rekapitulasi sampel diperiksa, lolos, dan cacat pada Prototipe, Potong, dan Jahit Akhir</p>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">Tahap QC</th>
              <th class="pb-2">Diperiksa</th>
              <th class="pb-2">Lolos</th>
              <th class="pb-2">Cacat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($reports['stageBreakdown'] ?? [] as $st)
            <tr class="hover:bg-slate-50">
              <td class="py-3 font-semibold text-slate-800">{{ $st->tahap_qc }}</td>
              <td class="py-3 font-mono">{{ $st->total_diperiksa }} Pcs</td>
              <td class="py-3 font-mono text-emerald-600 font-semibold">{{ $st->total_lolos }} Pcs</td>
              <td class="py-3 font-mono text-rose-600 font-semibold">{{ $st->total_cacat }} Pcs</td>
            </tr>
            @empty
            <tr>
              <td colspan="4" class="py-6 text-center text-slate-400">Belum ada data tahapan QC.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <!-- 4. Tabel Rincian Data Konsolidasi (Tabel Detail Operasional Pesanan) -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Tabel Detail Operasional Pesanan (Konsolidasi End-to-End)</h3>
        <p class="text-xs text-slate-500">Rekapitulasi per pesanan dari inisiasi pola, pemakaian roll kain fisik, actual yield potong, hingga hasil akhir QC</p>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
        {{ count($reports['consolidatedOrders'] ?? []) }} Baris Pesanan
      </span>
    </div>

    <div class="overflow-x-auto">
      <table id="tbl-konsolidasi-laporan" class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-200 text-slate-500 uppercase font-semibold bg-slate-50">
            <th class="py-2.5 px-3">ID Pesanan & Nama Pola</th>
            <th class="py-2.5 px-3">Sumber Inisiasi & Mitra</th>
            <th class="py-2.5 px-3">Tgl Order & Deadline</th>
            <th class="py-2.5 px-3">Roll Terpakai (<code class="font-mono">#id_roll</code>)</th>
            <th class="py-2.5 px-3">Actual Yield (Potong)</th>
            <th class="py-2.5 px-3">Hasil QC (Lolos / Cacat)</th>
            <th class="py-2.5 px-3 text-right">Status Pesanan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($reports['consolidatedOrders'] ?? [] as $row)
          <tr class="hover:bg-slate-50">
            <td class="py-3 px-3">
              <span class="font-mono font-bold text-indigo-600">#{{ $row->id_pesanan }}</span>
              <span class="font-semibold text-slate-800 ml-1">{{ $row->nama_pola }}</span>
              <span class="block text-[10px] font-mono text-slate-400">{{ $row->kode_pola }}</span>
            </td>
            <td class="py-3 px-3">
              <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $row->nama_sumber === 'Request Mitra' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                {{ $row->nama_sumber }}
              </span>
              <span class="block text-[11px] text-slate-600 font-medium mt-0.5">{{ $row->nama_mitra }}</span>
            </td>
            <td class="py-3 px-3 font-mono text-[11px]">
              <div class="text-slate-600">Order: {{ \Carbon\Carbon::parse($row->tgl_order)->format('Y-m-d') }}</div>
              <div class="text-amber-600 font-bold">EDF: {{ \Carbon\Carbon::parse($row->deadline_terbaru)->format('Y-m-d') }}</div>
            </td>
            <td class="py-3 px-3 font-mono text-xs">
              <span class="font-semibold text-slate-800">{{ $row->rolls_used }}</span>
              @if($row->kain_terpakai > 0)
                <span class="block text-[10px] text-slate-500">{{ number_format($row->kain_terpakai, 2) }} Kg/Yd ({{ $row->rolls_count }} Roll)</span>
              @endif
            </td>
            <td class="py-3 px-3 font-mono font-bold text-indigo-700">
              {{ number_format($row->actual_yield) }} Lembar
            </td>
            <td class="py-3 px-3 font-mono">
              <span class="text-emerald-600 font-bold">{{ $row->qc_lolos }} Lolos</span>
              <span class="text-slate-300">/</span>
              <span class="text-rose-600 font-bold">{{ $row->qc_cacat }} Cacat</span>
            </td>
            <td class="py-3 px-3 text-right">
              <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold
                {{ $row->status_pesanan === 'Selesai' ? 'bg-emerald-100 text-emerald-800' : ($row->status_pesanan === 'Dalam QC' ? 'bg-amber-100 text-amber-800' : ($row->status_pesanan === 'Pemotongan' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700')) }}">
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
      </table>
    </div>
  </div>
</div>

@php
  $csvRows = [];
  foreach (($reports['consolidatedOrders'] ?? []) as $r) {
    $csvRows[] = [
      'ID Pesanan' => '#' . $r->id_pesanan,
      'Kode Pola' => $r->kode_pola,
      'Nama Pola' => $r->nama_pola,
      'Sumber Inisiasi' => $r->nama_sumber,
      'Mitra' => $r->nama_mitra,
      'Tgl Order' => \Carbon\Carbon::parse($r->tgl_order)->format('Y-m-d'),
      'Deadline EDF' => \Carbon\Carbon::parse($r->deadline_terbaru)->format('Y-m-d'),
      'Roll Terpakai' => $r->rolls_used,
      'Kain Terpakai' => $r->kain_terpakai,
      'Actual Yield (Lembar)' => $r->actual_yield,
      'QC Lolos (Pcs)' => $r->qc_lolos,
      'QC Cacat (Pcs)' => $r->qc_cacat,
      'Status Pesanan' => $r->status_pesanan,
    ];
  }
@endphp

@push('scripts')
<script>
  const exportDataRows = @json($csvRows);

  function exportLaporanExcel() {
    if (!exportDataRows || exportDataRows.length === 0) {
      alert('Tidak ada data untuk diekspor.');
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
    a.download = 'Laporan_Eksekutif_Bong_Garment_' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
</script>
@endpush
@endsection
