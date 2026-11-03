@extends('layouts.app')

@section('title', 'Kelola Pesanan & Pola — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pesanan & Pola</h2>
      <p class="text-xs text-slate-500 mt-1">Inisiasi pesanan produksi, spesifikasi ukuran 11-matrik, dan penjadwalan EDF</p>
    </div>
  </div>

  <!-- Form Inisiasi Pesanan -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Input Pesanan Produksi Baru</h3>
    <form id="form-pesanan" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Inisiasi</label>
        <select name="id_sumber" id="pesanan-sumber" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          @foreach($sumber ?? [] as $s)
            <option value="{{ $s->id_sumber }}">{{ $s->nama_sumber }}</option>
          @endforeach
        </select>
      </div>

      <div id="field-nama-mitra">
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Mitra / Klien</label>
        <input type="text" name="nama_mitra" placeholder="Contoh: PT Busana Mandiri" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pola Pakaian</label>
        <select name="id_pola" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          @foreach($pola ?? [] as $p)
            <option value="{{ $p->id_pola }}">{{ $p->kode_pola }} — {{ $p->nama_pola }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis / Keterangan Pesanan</label>
        <input type="text" name="jenis_pesanan" placeholder="Misal: Kaos Event Sablon Depan Belakang" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Bahan Kain (Nota Penerimaan)</label>
        <select name="id_penerimaan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="">-- Pilih Nota Kain (Opsional) --</option>
          @foreach($penerimaan ?? [] as $pn)
            <option value="{{ $pn->id_penerimaan }}">{{ $pn->no_nota }} ({{ $pn->nama_kain }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Deadline Awal</label>
        <input type="date" name="deadline_awal" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="col-span-full flex justify-end gap-2 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan & Inisiasi Pesanan
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Daftar Pesanan -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Daftar Pesanan & Status Produksi</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">ID</th>
            <th class="pb-2">Sumber / Mitra</th>
            <th class="pb-2">Pola</th>
            <th class="pb-2">Tgl Order</th>
            <th class="pb-2">Deadline Awal</th>
            <th class="pb-2">Deadline Terbaru</th>
            <th class="pb-2">Status</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($pesanan ?? [] as $item)
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $item->id_pesanan }}</td>
            <td class="py-3">
              <span class="font-medium text-slate-900">{{ $item->nama_mitra ?? 'Non-Mitra' }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $item->sumberInisiasi->nama_sumber ?? '-' }}</span>
            </td>
            <td class="py-3">{{ $item->pola->nama_pola ?? '-' }}</td>
            <td class="py-3 font-mono text-slate-500">{{ $item->tgl_order }}</td>
            <td class="py-3 font-mono text-slate-500">{{ $item->deadline_awal ?? '-' }}</td>
            <td class="py-3 font-mono">
              @if($item->deadline_terbaru)
                <span class="text-amber-600 font-semibold">{{ $item->deadline_terbaru }}</span>
              @else
                <span class="text-slate-400">-</span>
              @endif
            </td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                {{ $item->status_pesanan }}
              </span>
            </td>
            <td class="py-3 text-right">
              <button onclick="openReviseDeadlineModal({{ $item->id_pesanan }}, '{{ $item->deadline_awal }}', '{{ $item->deadline_terbaru }}')" class="px-2.5 py-1 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-medium">
                Revisi Deadline
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="py-6 text-center text-slate-400">Belum ada data pesanan.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.getElementById('form-pesanan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const payload = {
      id_sumber: Number(formData.get('id_sumber')),
      nama_mitra: formData.get('nama_mitra') || null,
      id_pola: Number(formData.get('id_pola')),
      jenis_pesanan: formData.get('jenis_pesanan'),
      id_penerimaan: formData.get('id_penerimaan') ? Number(formData.get('id_penerimaan')) : null,
      id_user_admin: 1,
      tgl_order: new Date().toISOString().split('T')[0],
      deadline_awal: formData.get('deadline_awal') || null
    };

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch("{{ route('pesanan.store', [], false) }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(payload)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        alert(data.message || 'Pesanan berhasil diinisiasi!');
        window.location.reload();
      } else {
        alert('Gagal menginisiasi pesanan: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan koneksi.');
    });
  });
</script>
@endpush
@endsection

