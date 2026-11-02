@extends('layouts.app')

@section('title', 'Quality Control & Cacat — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Quality Control & Kerusakan</h2>
      <p class="text-xs text-slate-500 mt-1">Pemeriksaan berjenjang (Prototipe, Potong, Jahit Akhir), pencatatan cacat, dan penambahan stok barang jadi otomatis</p>
    </div>
  </div>

  <!-- Form QC -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Input Hasil Inspeksi QC</h3>
    <form id="form-qc" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">ID Pesanan</label>
        <input type="number" name="id_pesanan" placeholder="Misal: 1" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahap QC</label>
        <select name="tahap_qc" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="Prototipe">Prototipe</option>
          <option value="Potong">Potong</option>
          <option value="Jahit Akhir">Jahit Akhir (Tambah Stok Jadi)</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Diperiksa (Pcs)</label>
        <input type="number" name="jumlah_diperiksa" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Lolos (Pcs)</label>
        <input type="number" name="jumlah_lolos" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Cacat (Pcs)</label>
        <input type="number" name="jumlah_cacat" value="0" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Cacat</label>
        <select name="jenis_cacat" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="Tidak Ada">Tidak Ada</option>
          <option value="Cacat Potong">Cacat Potong</option>
          <option value="Cacat Jahit">Cacat Jahit</option>
          <option value="Cacat Bahan">Cacat Bahan</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Status QC</label>
        <select name="status_qc" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="Lolos QC">Lolos QC</option>
          <option value="Need Rework">Need Rework</option>
          <option value="Rework Selesai">Rework Selesai</option>
          <option value="Scrapped">Scrapped</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Tenggat Perbaikan</label>
        <input type="date" name="tgl_target_perbaikan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan</label>
        <input type="text" name="catatan_qc" placeholder="Detail temuan cacat atau area perbaikan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="col-span-full flex justify-end gap-2 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Inspeksi QC
        </button>
      </div>
    </form>
  </div>

  <!-- Riwayat QC -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Catatan Log Inspeksi Mutu & Rework</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">ID</th>
            <th class="pb-2">Pesanan</th>
            <th class="pb-2">Tahap</th>
            <th class="pb-2">Lolos / Cacat</th>
            <th class="pb-2">Jenis Cacat</th>
            <th class="pb-2">Status QC</th>
            <th class="pb-2">Target Perbaikan</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($qcRecords ?? [] as $qc)
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $qc->id_qc }}</td>
            <td class="py-3 font-medium text-slate-800">Pesanan #{{ $qc->id_pesanan }}</td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded font-mono font-semibold text-[10px] bg-slate-100 text-slate-700">{{ $qc->tahap_qc }}</span>
            </td>
            <td class="py-3 font-mono">
              <span class="text-emerald-600 font-bold">{{ $qc->jumlah_lolos }}</span> / <span class="text-rose-600 font-bold">{{ $qc->jumlah_cacat }}</span>
            </td>
            <td class="py-3 text-slate-600">{{ $qc->jenis_cacat }}</td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $qc->status_qc === 'Lolos QC' ? 'bg-emerald-100 text-emerald-800' : ($qc->status_qc === 'Need Rework' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                {{ $qc->status_qc }}
              </span>
            </td>
            <td class="py-3 font-mono text-slate-500">{{ $qc->tgl_target_perbaikan ?? '-' }}</td>
            <td class="py-3 text-right">
              @if($qc->status_qc === 'Need Rework')
                <button onclick="completeRework({{ $qc->id_qc }})" class="px-2.5 py-1 text-[11px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-md font-medium">
                  Selesaikan Rework
                </button>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="py-6 text-center text-slate-400">Belum ada data inspeksi QC.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

