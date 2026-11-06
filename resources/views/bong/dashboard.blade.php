@extends('layouts.app')

@section('title', 'Dashboard Produksi — Bong Garment')

@section('content')
<div class="space-y-6">
  <!-- Page Header -->
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Produksi CMT</h2>
      <p class="text-xs text-slate-500 mt-1">Sistem Informasi Manajemen Produksi & Inventori Bong Garment</p>
    </div>
    <div class="flex items-center gap-2">
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">
        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
        <span>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</span>
      </div>
    </div>
  </div>

  <!-- KPI Cards Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Active Orders -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pesanan Berjalan</span>
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ $kpi['total_pesanan_aktif'] ?? 0 }}</p>
      <p class="text-[11px] text-slate-400 mt-1">Antrean, potong, jahit & finishing</p>
    </div>

    <!-- Rolls Available -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Roll Kain Siap</span>
        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <i data-lucide="package-check" class="w-4 h-4"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ $kpi['total_roll_tersedia'] ?? 0 }}</p>
      <p class="text-[11px] text-slate-400 mt-1">Status Tersedia di gudang bahan</p>
    </div>

    <!-- Finished Goods -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Stok Barang Jadi</span>
        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
          <i data-lucide="boxes" class="w-4 h-4"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($kpi['total_stok_jadi_pcs'] ?? 0) }} <span class="text-sm font-normal text-slate-500">pcs</span></p>
      <p class="text-[11px] text-slate-400 mt-1">Tercatat di gudang barang jadi</p>
    </div>

    <!-- QC Need Rework -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Perbaikan QC</span>
        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
          <i data-lucide="alert-triangle" class="w-4 h-4"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-amber-600 mt-2">{{ $kpi['total_qc_need_rework'] ?? 0 }}</p>
      <p class="text-[11px] text-slate-400 mt-1">Memerlukan penanganan rework</p>
    </div>
  </div>

  <!-- Main Grid: EDF Queue & Recent QC -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- EDF Queue (2 Cols) -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Antrean Pemotongan Pesanan</h3>
          <p class="text-xs text-slate-400">Prioritas pengerjaan meja potong berbasis batas waktu terdekat (Earliest Deadline First)</p>
        </div>
        <span class="text-xs font-mono bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md">
          {{ count($edfQueue ?? []) }} Pesanan Aktif
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">ID</th>
              <th class="pb-2">Pesanan / Mitra</th>
              <th class="pb-2">Pola Pakaian</th>
              <th class="pb-2">Batas Waktu (Deadline)</th>
              <th class="pb-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($edfQueue ?? [] as $q)
            <tr class="hover:bg-slate-50">
              <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $q->id_pesanan }}</td>
              <td class="py-3">
                <p class="font-medium text-slate-800">{{ $q->nama_mitra ?? 'Non-Mitra (Produk Abadi/Tren)' }}</p>
                <p class="text-[11px] text-slate-400">{{ $q->jenis_pesanan }}</p>
              </td>
              <td class="py-3 text-slate-600">{{ $q->pola->nama_pola ?? '-' }}</td>
              <td class="py-3 font-mono">
                @php
                  $dAwal = $q->deadline_awal ? (is_string($q->deadline_awal) ? substr($q->deadline_awal, 0, 10) : $q->deadline_awal->format('Y-m-d')) : '-';
                  $dBaru = $q->deadline_terbaru ? (is_string($q->deadline_terbaru) ? substr($q->deadline_terbaru, 0, 10) : $q->deadline_terbaru->format('Y-m-d')) : $dAwal;
                @endphp
                @if($dBaru !== $dAwal)
                  <span class="text-amber-600 font-semibold">{{ $dBaru }}</span>
                  <span class="text-[10px] text-slate-400 block">(Revisi dari {{ $dAwal }})</span>
                @else
                  <span class="text-slate-600">{{ $dBaru }}</span>
                @endif
              </td>
              <td class="py-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $q->status_pesanan === 'Pemotongan' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                  {{ $q->status_pesanan }}
                </span>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada antrean pesanan aktif saat ini.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent QC (1 Col) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-slate-900 text-sm">Inspeksi QC Terkini</h3>
        <i data-lucide="shield-check" class="w-4 h-4 text-slate-400"></i>
      </div>

      <div class="space-y-3">
        @forelse($recentQc ?? [] as $qc)
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs">
          <div class="flex items-center justify-between">
            <span class="font-semibold text-slate-800">Pesanan #{{ $qc->id_pesanan }} ({{ $qc->tahap_qc }})</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold {{ $qc->status_qc === 'Lolos QC' ? 'bg-emerald-100 text-emerald-800' : ($qc->status_qc === 'Need Rework' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
              {{ $qc->status_qc }}
            </span>
          </div>
          <div class="flex items-center justify-between mt-2 text-slate-500 text-[11px]">
            <span>Lolos: <b class="text-slate-700">{{ $qc->jumlah_lolos }}</b> / Cacat: <b class="text-rose-600">{{ $qc->jumlah_cacat }}</b></span>
            <span class="font-mono text-[10px]">{{ substr($qc->tgl_pemeriksaan, 0, 10) }}</span>
          </div>
        </div>
        @empty
        <p class="text-center text-slate-400 text-xs py-4">Belum ada catatan inspeksi QC.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

