@extends('layouts.app')

@section('title', 'Pemotongan Seri & Gelaran — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Pemotongan Seri & Gelaran</h2>
      <p class="text-xs text-slate-500 mt-1">Eksekusi pemotongan seri, input parameter gelaran kain, dan pemotongan saldo roll otomatis</p>
    </div>
  </div>

  <!-- Form Pemotongan Seri -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Input Pemotongan Seri & Gelaran Kain</h3>
    <form id="form-pemotongan" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pesanan Target</label>
        <select name="id_pesanan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          <option value="" disabled selected>-- Pilih Pesanan Target --</option>
          @foreach($orders ?? [] as $ord)
            <option value="{{ $ord->id_pesanan }}">
              Pesanan #{{ $ord->id_pesanan }} - {{ $ord->pola->nama_pola ?? $ord->jenis_pesanan }} [{{ $ord->status_pesanan }}]
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Operator Pemotong (Cutter)</label>
        <select name="id_user_pemotong" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          <option value="" disabled selected>-- Pilih Operator Pemotong --</option>
          @foreach($users ?? [] as $usr)
            <option value="{{ $usr->id_user }}">{{ $usr->nama_user }} (@ {{ $usr->username }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Seri Potong</label>
        <input type="text" name="kode_seri" placeholder="Contoh: SERI-2026-002" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Variasi Potongan</label>
        <input type="text" name="jenis_variasi" placeholder="Misal: Kombinasi Rib Leher" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Lembar Hasil</label>
        <input type="number" name="total_lembar_hasil" placeholder="Contoh: 300" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="col-span-full flex justify-end gap-2 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Pemotongan & Kurangi Stok Roll
        </button>
      </div>
    </form>
  </div>

  <!-- Riwayat Pemotongan Seri -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Riwayat Pemotongan Seri & Detail Gelaran</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">Kode Seri</th>
            <th class="pb-2">Pesanan Target</th>
            <th class="pb-2">Pemotong</th>
            <th class="pb-2">Hasil (Lembar)</th>
            <th class="pb-2">Tgl Potong</th>
            <th class="pb-2">Rincian Kain Terpakai</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($pemotongan ?? [] as $pm)
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-bold text-indigo-600">{{ $pm->kode_seri }}</td>
            <td class="py-3">
              <span class="font-medium text-slate-800">Pesanan #{{ $pm->id_pesanan }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $pm->pesanan->pola->nama_pola ?? '-' }}</span>
            </td>
            <td class="py-3 text-slate-600">{{ $pm->userPemotong->nama_user ?? '-' }}</td>
            <td class="py-3 font-mono font-semibold text-slate-800">{{ number_format($pm->total_lembar_hasil) }}</td>
            <td class="py-3 font-mono text-slate-500">{{ $pm->tgl_potong }}</td>
            <td class="py-3 text-[11px] text-slate-500">
              @foreach($pm->detailPemotonganWarna ?? [] as $pw)
                <span>{{ $pw->detailBahanWarna->nama_warna ?? '-' }}: <b>{{ $pw->kain_terpakai }}</b> {{ $pw->detailBahanWarna->satuan_ukur ?? 'Kg' }}</span><br>
              @endforeach
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada catatan pemotongan seri.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.getElementById('form-pemotongan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const payload = {
      id_pesanan: Number(formData.get('id_pesanan')),
      id_user_pemotong: Number(formData.get('id_user_pemotong')),
      kode_seri: formData.get('kode_seri'),
      jenis_variasi: formData.get('jenis_variasi') || null,
      total_lembar_hasil: Number(formData.get('total_lembar_hasil'))
    };

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch("{{ route('pemotongan.store') }}", {
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
        alert(data.message || 'Pemotongan seri berhasil disimpan!');
        window.location.reload();
      } else {
        alert('Gagal menyimpan pemotongan: ' + (data.message || 'Validasi gagal'));
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