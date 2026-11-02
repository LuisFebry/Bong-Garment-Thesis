@extends('layouts.app')

@section('title', 'Gudang & Stok Barang Jadi — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Gudang & Stok Barang Jadi</h2>
      <p class="text-xs text-slate-500 mt-1">Manajemen inventori perpetual barang jadi hasil produksi dan stok fisik aksesoris (kancing, ritsleting, benang)</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Stok Barang Jadi -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Stok Barang Jadi (Perpetual Inventory)</h3>
          <p class="text-xs text-slate-400">Bertambah otomatis setiap inspeksi QC Jahit Akhir lolos</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">Pola / Model</th>
              <th class="pb-2">Ukuran</th>
              <th class="pb-2">Warna</th>
              <th class="pb-2">Stok (Pcs)</th>
              <th class="pb-2">Update Terakhir</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($stok ?? [] as $st)
            <tr class="hover:bg-slate-50">
              <td class="py-3 font-medium text-slate-800">{{ $st->pola->nama_pola ?? '-' }}</td>
              <td class="py-3 font-mono font-semibold text-indigo-600">{{ $st->spesifikasiUkuran->nama_ukuran ?? '-' }}</td>
              <td class="py-3">{{ $st->nama_warna }}</td>
              <td class="py-3 font-mono font-bold text-emerald-600">{{ number_format($st->jumlah_stok_pcs) }} pcs</td>
              <td class="py-3 font-mono text-[10px] text-slate-400">{{ substr($st->tgl_update_terakhir, 0, 16) }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="py-6 text-center text-slate-400">Belum ada stok barang jadi terdaftar.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Inventori Aksesoris -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Inventori Aksesoris CMT</h3>
          <p class="text-xs text-slate-400">Kancing, ritsleting, karet, benang, dan label garment</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">Nama Aksesoris</th>
              <th class="pb-2">Ukuran</th>
              <th class="pb-2">Stok</th>
              <th class="pb-2">Supplier</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($aksesoris ?? [] as $acc)
            <tr class="hover:bg-slate-50">
              <td class="py-3 font-medium text-slate-800">{{ $acc->nama_aksesoris }}</td>
              <td class="py-3 font-mono text-slate-500">{{ $acc->ukuran ?? '-' }}</td>
              <td class="py-3 font-mono font-bold {{ $acc->stok < 500 ? 'text-amber-600' : 'text-slate-800' }}">
                {{ number_format($acc->stok) }} <span class="text-[10px] text-slate-400 font-normal">{{ $acc->satuan }}</span>
              </td>
              <td class="py-3 text-slate-500">{{ $acc->supplier->nama_supplier ?? '-' }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="4" class="py-6 text-center text-slate-400">Belum ada data aksesoris.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

