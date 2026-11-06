@extends('layouts.app')

@section('title', 'Quality Control & Cacat — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Quality Control & Kerusakan</h2>
      <p class="text-xs text-slate-500 mt-1">Pemeriksaan berjenjang (Prototipe, Potong, Jahit Akhir), disposisi PIC rework, dan integrasi stok barang jadi otomatis</p>
    </div>
  </div>

  <!-- Form QC Multi-Section -->
  <form id="form-qc" class="space-y-6">
    @csrf

    <!-- Section 1: Waktu Pemeriksaan, Penugasan Staf & Kuantitas -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">1. Waktu Pemeriksaan, Penugasan Staf & Pemilihan Pesanan (<code class="font-mono text-indigo-600">QC</code>)</h3>
          <p class="text-[11px] text-slate-500">Pilih pesanan produksi dari daftar aktif, waktu pelaksanaan inspeksi, petugas penguji QC, dan kuantitas sampel</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pemilihan Pesanan Target (<code class="font-mono">id_pesanan</code>) <span class="text-rose-500">*</span></label>
          <select name="id_pesanan" id="sel-qc-pesanan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="" disabled selected>-- Pilih Pesanan Produksi --</option>
            @foreach($orders ?? [] as $ord)
              @php
                $defaultWarna = $ord->penerimaanBahan?->detailBahanWarna?->first()?->nama_warna ?? 'Hitam Reaktif';
              @endphp
              <option value="{{ $ord->id_pesanan }}" data-pola-id="{{ $ord->id_pola }}" data-pola-nama="{{ $ord->pola->nama_pola ?? '-' }}" data-warna="{{ $defaultWarna }}">
                Pesanan #{{ $ord->id_pesanan }} — {{ $ord->pola->nama_pola ?? $ord->jenis_pesanan }} ({{ $ord->nama_mitra ?? 'Internal Produk Abadi' }} · Status: {{ $ord->status_pesanan }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal & Waktu Pemeriksaan (<code class="font-mono">tgl_pemeriksaan</code>) <span class="text-rose-500">*</span></label>
          <input type="datetime-local" name="tgl_pemeriksaan" id="inp-tgl-pemeriksaan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Petugas / Penguji QC (<code class="font-mono">id_user_qc</code>) <span class="text-rose-500">*</span></label>
          <select name="id_user_qc" id="sel-user-qc" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            @php
              $activeCookieUserId = request()->cookie('bong_garment_user_id');
            @endphp
            @foreach($qcUsers ?? [] as $usr)
              <option value="{{ $usr->id_user }}" {{ (string)$activeCookieUserId === (string)$usr->id_user ? 'selected' : '' }}>
                {{ $usr->nama_user }} ({{ $usr->role }} — @ {{ $usr->username }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tahap QC <span class="text-rose-500">*</span></label>
          <select name="tahap_qc" id="sel-tahap-qc" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white font-semibold">
            <option value="Prototipe">Prototipe (Sampel Awal)</option>
            <option value="Potong">Potong (Panel Kain)</option>
            <option value="Jahit Akhir">Jahit Akhir (+ Tambah Stok Barang Jadi)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Diperiksa (Pcs) <span class="text-rose-500">*</span></label>
          <input type="number" name="jumlah_diperiksa" id="inp-qc-diperiksa" min="1" value="100" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Lolos (Pcs) <span class="text-rose-500">*</span></label>
          <input type="number" name="jumlah_lolos" id="inp-qc-lolos" min="0" value="100" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Cacat (Pcs) <span class="text-rose-500">*</span></label>
          <input type="number" name="jumlah_cacat" id="inp-qc-cacat" min="0" value="0" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-rose-600 focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    <!-- Section 2: Disposisi Tindak Lanjut Rework & Kategori Kerusakan -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
          <i data-lucide="wrench" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">2. Disposisi Tindak Lanjut Rework & Kategori Cacat</h3>
          <p class="text-[11px] text-slate-500">Penentuan jenis kerusakan, status kelulusan batch, alokasi PIC penanggung jawab perbaikan, dan tenggat rework</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Cacat</label>
          <select name="jenis_cacat" id="sel-jenis-cacat" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="Tidak Ada">Tidak Ada</option>
            <option value="Cacat Potong">Cacat Potong (Salah Pola / Miring)</option>
            <option value="Cacat Jahit">Cacat Jahit (Loncat / Kerut / Ukuran)</option>
            <option value="Cacat Bahan">Cacat Bahan (Fabric Stain / Bolong Supplier)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Status Keputusan QC</label>
          <select name="status_qc" id="sel-status-qc" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white font-semibold">
            <option value="Lolos QC">Lolos QC</option>
            <option value="Need Rework">Need Rework (Perlu Perbaikan)</option>
            <option value="Rework Selesai">Rework Selesai</option>
            <option value="Scrapped">Scrapped (Afkir Total)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alokasi PIC / Disposisi Rework</label>
          <select name="pic_rework" id="sel-pic-rework" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="">-- Tanpa Disposisi Rework --</option>
            <option value="Tim Pemotong (Stasiun Cutting)">Tim Pemotong (Stasiun Cutting)</option>
            <option value="Mitra Penjahit CMT">Mitra Penjahit CMT</option>
            <option value="Tim Finishing & Steam">Tim Finishing & Steam</option>
            <option value="Gudang Bahan (Klaim Retur Supplier)">Gudang Bahan (Klaim Retur Supplier)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target Tenggat Perbaikan (<code class="font-mono">tgl_target_perbaikan</code>)</label>
          <input type="date" name="tgl_target_perbaikan" id="inp-target-perbaikan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan Inspeksi (<code class="font-mono">catatan_qc</code>)</label>
          <input type="text" name="catatan_qc" placeholder="Detail temuan cacat, instruksi perbaikan untuk PIC, atau nomor klaim retur bahan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    <!-- Section 3: Integrasi ke Stok_Barang_Jadi -->
    <div id="box-integrasi-stok" class="bg-emerald-50/70 rounded-xl border-2 border-emerald-200 shadow-sm p-6 transition-all">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-emerald-200/70 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-sm">
            <i data-lucide="package-check" class="w-4 h-4"></i>
          </div>
          <div>
            <h3 class="font-bold text-emerald-950 text-sm">3. Integrasi Otomatis ke Gudang Barang Jadi (<code class="font-mono text-emerald-700">Stok_Barang_Jadi</code>)</h3>
            <p class="text-[11px] text-emerald-700">Saat <b>Tahap QC = Jahit Akhir</b>, jumlah unit yang <b>Lolos QC</b> otomatis ditambahkan ke stok gudang sesuai ukuran & warna</p>
          </div>
        </div>

        <span id="badge-indikator-stok" class="px-3 py-1.5 rounded-full text-xs font-mono font-bold bg-slate-200 text-slate-700">
          Pilih Tahap "Jahit Akhir" untuk Alokasi Stok Gudang
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-emerald-900 mb-1">Pola Target Terkait</label>
          <input type="text" id="inp-display-pola" readonly value="Pilih pesanan terlebih dahulu" class="w-full text-xs p-2.5 bg-white/80 border border-emerald-200 rounded-lg text-slate-700 font-semibold">
        </div>

        <div>
          <label class="block text-xs font-semibold text-emerald-900 mb-1">Alokasi Spesifikasi Ukuran (S/M/L/XL) (<code class="font-mono">id_spesifikasi</code>)</label>
          <select name="id_spesifikasi" id="sel-qc-spesifikasi" class="w-full text-xs p-2.5 border border-emerald-300 rounded-lg bg-white font-semibold focus:ring-2 focus:ring-emerald-500">
            <option value="">-- Otomatis Ukuran Default Pola --</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-emerald-900 mb-1">Variasi Warna Barang Jadi (<code class="font-mono">nama_warna</code>)</label>
          <input type="text" name="nama_warna" id="inp-qc-warna" value="Hitam Reaktif" placeholder="Contoh: Hitam Reaktif" class="w-full text-xs p-2.5 border border-emerald-300 rounded-lg bg-white font-semibold focus:ring-2 focus:ring-emerald-500">
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-5 mt-4 border-t border-emerald-200/70">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors inline-flex items-center gap-1.5">
          <i data-lucide="check-circle-2" class="w-4 h-4"></i>
          Simpan Hasil Inspeksi QC
        </button>
      </div>
    </div>
  </form>

  <!-- Riwayat QC -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Catatan Log Inspeksi Mutu, Disposisi PIC & Tanggal Selesai Rework</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">ID</th>
            <th class="pb-2">Waktu & Penguji QC</th>
            <th class="pb-2">Pesanan & Pola</th>
            <th class="pb-2">Tahap</th>
            <th class="pb-2">Periksa / Lolos / Cacat</th>
            <th class="pb-2">Jenis Cacat & PIC Rework</th>
            <th class="pb-2">Status QC</th>
            <th class="pb-2">Target & Selesai Rework</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($qcRecords ?? [] as $qc)
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $qc->id_qc }}</td>
            <td class="py-3">
              <span class="font-mono text-slate-700 block">{{ $qc->tgl_pemeriksaan }}</span>
              <span class="text-[10px] text-slate-500 font-semibold">Oleh: {{ $qc->userQc->nama_user ?? ('User #' . $qc->id_user_qc) }}</span>
            </td>
            <td class="py-3">
              <span class="font-semibold text-slate-800">Pesanan #{{ $qc->id_pesanan }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $qc->pesanan->pola->nama_pola ?? '-' }}</span>
            </td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded font-mono font-semibold text-[10px] {{ $qc->tahap_qc === 'Jahit Akhir' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700' }}">
                {{ $qc->tahap_qc }}
              </span>
              @if($qc->tahap_qc === 'Jahit Akhir' && $qc->jumlah_lolos > 0)
                <span class="block text-[10px] text-emerald-600 font-semibold mt-0.5">+{{ $qc->jumlah_lolos }} Pcs ke Stok Jadi</span>
              @endif
            </td>
            <td class="py-3 font-mono">
              <span class="text-slate-600">{{ $qc->jumlah_diperiksa }}</span> /
              <span class="text-emerald-600 font-bold">{{ $qc->jumlah_lolos }}</span> /
              <span class="text-rose-600 font-bold">{{ $qc->jumlah_cacat }}</span>
            </td>
            <td class="py-3">
              <span class="font-semibold text-slate-700">{{ $qc->jenis_cacat }}</span>
              @if($qc->catatan_qc)
                <span class="block text-[10px] text-slate-500 mt-0.5">{{ $qc->catatan_qc }}</span>
              @endif
            </td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $qc->status_qc === 'Lolos QC' ? 'bg-emerald-100 text-emerald-800' : ($qc->status_qc === 'Need Rework' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                {{ $qc->status_qc }}
              </span>
            </td>
            <td class="py-3 font-mono text-[11px]">
              <div class="text-slate-500">Target: {{ $qc->tgl_target_perbaikan ?? '-' }}</div>
              @if($qc->tgl_selesai_perbaikan)
                <div class="text-emerald-700 font-bold mt-0.5">Selesai: {{ $qc->tgl_selesai_perbaikan }}</div>
              @endif
            </td>
            <td class="py-3 text-right">
              @if($qc->status_qc === 'Need Rework')
                <button type="button" onclick="openReworkModal({{ $qc->id_qc }})" class="px-2.5 py-1.5 text-[11px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow-sm transition-colors">
                  Selesaikan Rework
                </button>
              @else
                <span class="text-[10px] text-slate-400">—</span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="py-6 text-center text-slate-400">Belum ada data inspeksi QC.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Selesaikan Rework (Pencatatan tgl_selesai_perbaikan) -->
<div id="modal-complete-rework" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Konfirmasi Penyelesaian Rework</h3>
        <p class="text-xs text-slate-500">Catat tanggal & waktu aktual perbaikan selesai (<code class="font-mono text-indigo-600">tgl_selesai_perbaikan</code>)</p>
      </div>
      <button type="button" onclick="closeReworkModal()" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-complete-rework" class="space-y-4">
      <input type="hidden" id="rework-qc-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal & Waktu Aktual Rework Selesai (<code class="font-mono">tgl_selesai_perbaikan</code>) <span class="text-rose-500">*</span></label>
        <input type="datetime-local" id="rework-tgl-selesai" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-emerald-500">
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeReworkModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm">Konfirmasi Rework Selesai</button>
      </div>
    </form>
  </div>
</div>

@php
  $ordersSpecsMap = [];
  foreach (($orders ?? []) as $o) {
    $ordersSpecsMap[$o->id_pesanan] = [
      'nama_pola' => $o->pola->nama_pola ?? '-',
      'warna' => $o->penerimaanBahan?->detailBahanWarna?->first()?->nama_warna ?? 'Hitam Reaktif',
      'specs' => ($o->pola->spesifikasiUkuran ?? collect())->map(function($s) {
        return [
          'id_spesifikasi' => $s->id_spesifikasi,
          'ukuran' => $s->ukuran,
        ];
      })->values(),
    ];
  }
@endphp

@push('scripts')
<script>
  const ordersSpecsMap = @json($ordersSpecsMap);

  function nowLocalISO() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
  }

  function openReworkModal(idQc) {
    document.getElementById('rework-qc-id').value = idQc;
    document.getElementById('rework-tgl-selesai').value = nowLocalISO();
    const m = document.getElementById('modal-complete-rework');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function closeReworkModal() {
    const m = document.getElementById('modal-complete-rework');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  document.getElementById('form-complete-rework')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const idQc = document.getElementById('rework-qc-id').value;
    const tglSelesai = document.getElementById('rework-tgl-selesai').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/qc/' + idQc + '/complete-rework', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({ tgl_selesai_perbaikan: tglSelesai })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Rework berhasil diselesaikan!');
        window.location.reload();
      } else {
        alert('Gagal menyelesaikan rework: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan koneksi.');
    });
  });

  function updateStokIndicator() {
    const tahap = document.getElementById('sel-tahap-qc')?.value;
    const lolos = parseInt(document.getElementById('inp-qc-lolos')?.value, 10) || 0;
    const badge = document.getElementById('badge-indikator-stok');
    const warna = document.getElementById('inp-qc-warna')?.value || 'Hitam Reaktif';
    const specSel = document.getElementById('sel-qc-spesifikasi');
    const specText = specSel?.options[specSel.selectedIndex]?.text || 'Ukuran Default';

    if (!badge) return;
    if (tahap === 'Jahit Akhir' && lolos > 0) {
      badge.className = 'px-3 py-1.5 rounded-full text-xs font-mono font-bold bg-emerald-600 text-white shadow-sm';
      badge.textContent = `✓ AKTIF: +${lolos} Pcs Masuk Stok_Barang_Jadi (${specText} · ${warna})`;
    } else {
      badge.className = 'px-3 py-1.5 rounded-full text-xs font-mono font-semibold bg-slate-200 text-slate-600';
      badge.textContent = 'Non-Aktif (Hanya Tahap "Jahit Akhir" yang menambah Stok_Barang_Jadi)';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const dtInput = document.getElementById('inp-tgl-pemeriksaan');
    if (dtInput && !dtInput.value) {
      dtInput.value = nowLocalISO();
    }

    const inDiperiksa = document.getElementById('inp-qc-diperiksa');
    const inLolos = document.getElementById('inp-qc-lolos');
    const inCacat = document.getElementById('inp-qc-cacat');
    const selJenisCacat = document.getElementById('sel-jenis-cacat');
    const selStatusQc = document.getElementById('sel-status-qc');
    const selPicRework = document.getElementById('sel-pic-rework');

    function autoCalc() {
      const d = parseInt(inDiperiksa.value, 10) || 0;
      const c = parseInt(inCacat.value, 10) || 0;
      if (d >= c) {
        inLolos.value = d - c;
      }
      if (c > 0 && selStatusQc.value === 'Lolos QC') {
        selStatusQc.value = 'Need Rework';
      } else if (c === 0 && selStatusQc.value === 'Need Rework') {
        selStatusQc.value = 'Lolos QC';
      }
      updateStokIndicator();
    }

    inDiperiksa?.addEventListener('input', autoCalc);
    inCacat?.addEventListener('input', autoCalc);
    inLolos?.addEventListener('input', updateStokIndicator);
    document.getElementById('sel-tahap-qc')?.addEventListener('change', updateStokIndicator);
    document.getElementById('inp-qc-warna')?.addEventListener('input', updateStokIndicator);
    document.getElementById('sel-qc-spesifikasi')?.addEventListener('change', updateStokIndicator);

    // Auto-suggest PIC Rework based on Jenis Cacat
    selJenisCacat?.addEventListener('change', function () {
      if (this.value === 'Cacat Potong') {
        selPicRework.value = 'Tim Pemotong (Stasiun Cutting)';
      } else if (this.value === 'Cacat Jahit') {
        selPicRework.value = 'Mitra Penjahit CMT';
      } else if (this.value === 'Cacat Bahan') {
        selPicRework.value = 'Gudang Bahan (Klaim Retur Supplier)';
      } else {
        selPicRework.value = '';
      }
    });

    // Populate Spesifikasi Ukuran & Warna when Pesanan Target changes
    document.getElementById('sel-qc-pesanan')?.addEventListener('change', function () {
      const idPesanan = this.value;
      const info = ordersSpecsMap[idPesanan];
      const dispPola = document.getElementById('inp-display-pola');
      const specSel = document.getElementById('sel-qc-spesifikasi');
      const warnaInp = document.getElementById('inp-qc-warna');

      if (info) {
        if (dispPola) dispPola.value = info.nama_pola;
        if (warnaInp && info.warna) warnaInp.value = info.warna;
        if (specSel) {
          specSel.innerHTML = info.specs.map(function (s) {
            return `<option value="${s.id_spesifikasi}">Ukuran ${s.ukuran} (#id_spesifikasi: ${s.id_spesifikasi})</option>`;
          }).join('') || '<option value="">-- Ukuran Default --</option>';
        }
      }
      updateStokIndicator();
    });

    updateStokIndicator();
  });

  document.getElementById('form-qc')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const payload = {
      id_pesanan: Number(formData.get('id_pesanan')),
      id_user_qc: Number(formData.get('id_user_qc')),
      tahap_qc: formData.get('tahap_qc'),
      tgl_pemeriksaan: formData.get('tgl_pemeriksaan'),
      jumlah_diperiksa: Number(formData.get('jumlah_diperiksa')),
      jumlah_lolos: Number(formData.get('jumlah_lolos')),
      jumlah_cacat: Number(formData.get('jumlah_cacat') || 0),
      jenis_cacat: formData.get('jenis_cacat'),
      status_qc: formData.get('status_qc'),
      pic_rework: formData.get('pic_rework') || null,
      tgl_target_perbaikan: formData.get('tgl_target_perbaikan') || null,
      catatan_qc: formData.get('catatan_qc') || null,
      id_spesifikasi: formData.get('id_spesifikasi') ? Number(formData.get('id_spesifikasi')) : null,
      nama_warna: formData.get('nama_warna') || null
    };

    if (!payload.id_pesanan) {
      alert('Silakan pilih Pesanan Target terlebih dahulu.');
      return;
    }

    if ((payload.jumlah_lolos + payload.jumlah_cacat) !== payload.jumlah_diperiksa) {
      alert("Validasi Mutu Gagal: Jumlah Lolos (" + payload.jumlah_lolos + ") + Jumlah Cacat (" + payload.jumlah_cacat + ") harus sama dengan Jumlah Diperiksa (" + payload.jumlah_diperiksa + ")!");
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch("{{ route('qc.store', [], false) }}", {
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
        alert(data.message || 'Inspeksi QC berhasil disimpan!');
        window.location.reload();
      } else {
        alert('Gagal menyimpan QC: ' + (data.message || 'Validasi gagal'));
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
