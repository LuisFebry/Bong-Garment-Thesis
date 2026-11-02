@extends('layouts.app')

@section('title', 'Penerimaan Bahan & Roll — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Penerimaan & Roll Bahan</h2>
      <p class="text-xs text-slate-500 mt-1">Pencatatan nota supplier, rincian warna kain, dan registrasi individual roll fisik (Kg/Yard/Meter)</p>
    </div>
  </div>

  <!-- Form Penerimaan Bahan -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Input Penerimaan Kain Supplier</h3>
    <form id="form-penerimaan" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
        <select name="id_supplier" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          @foreach($suppliers ?? [] as $sp)
            <option value="{{ $sp->id_supplier }}">{{ $sp->nama_supplier }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Nota / Surat Jalan</label>
        <input type="text" name="no_nota" placeholder="Contoh: NOTA-2026-99" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Nota</label>
        <input type="date" name="tgl_nota" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kain</label>
        <input type="text" name="nama_kain" placeholder="Contoh: Cotton Combed 30s Reaktif" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Lebar / Gramasi</label>
        <input type="text" name="lebar_gramasi" placeholder="Gramasi 145 / Lebar 42 inch" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Kendaraan</label>
        <input type="text" name="no_kendaraan" placeholder="B 1234 XYZ" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>
    </form>
  </div>

  <!-- Daftar Nota Penerimaan & Roll -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Daftar Penerimaan Kain & Roll Bahan Fisik</h3>
    <div class="space-y-4">
      @forelse($penerimaan ?? [] as $pn)
      <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between">
          <div>
            <span class="font-mono font-bold text-sm text-indigo-600">{{ $pn->no_nota }}</span>
            <span class="text-xs font-semibold text-slate-700 ml-2">{{ $pn->nama_kain }}</span>
            <span class="text-[11px] text-slate-400 block">{{ $pn->supplier->nama_supplier ?? '-' }} &bull; {{ $pn->tgl_nota }}</span>
          </div>
          <span class="text-xs font-mono bg-white px-2.5 py-1 rounded-md border border-slate-200 text-slate-600">
            Kendaraan: {{ $pn->no_kendaraan ?? '-' }}
          </span>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @foreach($pn->detailBahanWarna ?? [] as $bw)
          <div class="bg-white p-3 rounded-lg border border-slate-200 text-xs">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-slate-800">{{ $bw->nama_warna }}</span>
              <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $bw->satuan_ukur }}</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">
              Total: {{ $bw->total_roll }} roll &bull; {{ $bw->total_ukuran }} {{ $bw->satuan_ukur }}
            </div>
            <div class="mt-2 space-y-1">
              @foreach($bw->detailRollBahan ?? [] as $roll)
              <div class="flex items-center justify-between text-[10px] font-mono bg-slate-50 p-1 rounded">
                <span>Roll #{{ $roll->no_roll_ke }}</span>
                <span>Sisa: <b>{{ $roll->ukuran_sisa }}</b> / {{ $roll->ukuran_awal }} {{ $roll->satuan }}</span>
                <span class="px-1.5 py-0.5 rounded font-semibold {{ $roll->status_roll === 'Tersedia' ? 'bg-emerald-100 text-emerald-800' : ($roll->status_roll === 'Dipotong' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                  {{ $roll->status_roll }}
                </span>
              </div>
              @endforeach
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @empty
      <p class="text-center text-slate-400 text-xs py-6">Belum ada nota penerimaan bahan terdaftar.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection

