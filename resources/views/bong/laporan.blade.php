@extends('layouts.app')

@section('title', 'Laporan Eksekutif — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Laporan Eksekutif & Analitik Produksi</h2>
      <p class="text-xs text-slate-500 mt-1">Audit efisiensi produksi, rasio kelolosan mutu (Quality Pass Rate), dan sebaran jenis cacat</p>
    </div>
  </div>

  <!-- KPI Eksekutif -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pesanan</span>
      <p class="text-2xl font-bold text-slate-900 mt-2">{{ $reports['summary']['total_pesanan'] ?? 0 }}</p>
      <p class="text-[11px] text-slate-400 mt-1">{{ $reports['summary']['pesanan_proses'] ?? 0 }} dalam pengerjaan</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tingkat Kelolosan QC</span>
      <p class="text-2xl font-bold text-emerald-600 mt-2">{{ $reports['summary']['pass_rate_pct'] ?? 0 }}%</p>
      <p class="text-[11px] text-slate-400 mt-1">Standar target: > 95%</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rasio Cacat (Defect Rate)</span>
      <p class="text-2xl font-bold text-rose-600 mt-2">{{ $reports['summary']['defect_rate_pct'] ?? 0 }}%</p>
      <p class="text-[11px] text-slate-400 mt-1">Total lembar/pcs bermasalah</p>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Hasil Potong</span>
      <p class="text-2xl font-bold text-indigo-600 mt-2">{{ number_format($reports['summary']['total_lembar_potong'] ?? 0) }}</p>
      <p class="text-[11px] text-slate-400 mt-1">Lembar kain siap jahit</p>
    </div>
  </div>

  <!-- Analisis Cacat & Tahapan -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 class="font-bold text-slate-900 text-sm mb-4">Sebaran Kategori Kerusakan (Defect Breakdown)</h3>
      <div class="space-y-3">
        @forelse($reports['defectBreakdown'] ?? [] as $df)
        <div>
          <div class="flex justify-between text-xs mb-1 font-medium">
            <span class="text-slate-700">{{ $df->jenis_cacat }}</span>
            <span class="font-mono text-slate-900 font-bold">{{ $df->total_cacat }} pcs</span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2">
            <div class="bg-rose-500 h-2 rounded-full" style="width: {{ min(100, $df->total_cacat * 5) }}%"></div>
          </div>
        </div>
        @empty
        <p class="text-slate-400 text-xs py-4 text-center">Belum ada cacat tercatat.</p>
        @endforelse
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 class="font-bold text-slate-900 text-sm mb-4">Performa Pemeriksaan per Tahapan QC</h3>
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
              <td class="py-3 font-mono">{{ $st->total_diperiksa }}</td>
              <td class="py-3 font-mono text-emerald-600 font-semibold">{{ $st->total_lolos }}</td>
              <td class="py-3 font-mono text-rose-600 font-semibold">{{ $st->total_cacat }}</td>
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
</div>
@endsection

