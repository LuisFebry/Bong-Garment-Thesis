@extends('layouts.app')

@section('title', 'Pemotongan Seri & Gelaran — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Pemotongan Seri & Gelaran</h2>
      <p class="text-xs text-slate-500 mt-1">Antrean prioritas EDF, alokasi warna & rencana potong, parameter gelaran roll fisik, serta pembaruan saldo roll otomatis</p>
    </div>
  </div>

  <!-- Antrean Prioritas Pemotongan (Algoritma EDF - Earliest Deadline First) -->
  <div class="bg-white rounded-xl border border-indigo-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-sm">
          <i data-lucide="clock" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Antrean Prioritas Pemotongan (Algoritma EDF — Earliest Deadline First)</h3>
          <p class="text-[11px] text-slate-500">Urutan eksekusi potong otomatis berdasarkan <code class="font-mono text-indigo-600">COALESCE(deadline_terbaru, deadline_awal) ASC</code></p>
        </div>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
        {{ count($orders ?? []) }} Pesanan Terjadwal
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">Prioritas EDF</th>
            <th class="pb-2">ID & Mitra / Sumber</th>
            <th class="pb-2">Pola Pakaian</th>
            <th class="pb-2">Nota Kain & Warna</th>
            <th class="pb-2">Deadline Awal</th>
            <th class="pb-2">Deadline Terbaru (EDF)</th>
            <th class="pb-2">Status</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($orders ?? [] as $idx => $ord)
          @php
            $firstWarnaId = optional(optional($ord->penerimaanBahan)->detailBahanWarna->first())->id_detail_warna;
            $firstRollId = optional(optional(optional($ord->penerimaanBahan)->detailBahanWarna->first())->detailRollBahan->where('status_roll', '!=', 'Habis')->first())->id_roll;
          @endphp
          <tr class="hover:bg-indigo-50/40 transition-colors">
            <td class="py-3">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold {{ $idx === 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : ($idx === 1 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700') }}">
                #{{ $idx + 1 }} {{ $idx === 0 ? 'PRIORITAS UTAMA' : 'ANTREAN' }}
              </span>
            </td>
            <td class="py-3">
              <span class="font-mono font-bold text-indigo-600">#{{ $ord->id_pesanan }}</span>
              <span class="font-semibold text-slate-800 ml-1">{{ $ord->nama_mitra ?? 'Internal (Produk Abadi)' }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $ord->sumberInisiasi->nama_sumber ?? '-' }} &bull; {{ $ord->jenis_pesanan }}</span>
            </td>
            <td class="py-3 text-slate-700 font-medium">
              {{ $ord->pola->nama_pola ?? '-' }}
              <span class="text-[10px] font-mono text-slate-400 block">{{ $ord->pola->kode_pola ?? '' }}</span>
            </td>
            <td class="py-3 text-[11px] text-slate-600">
              @if($ord->penerimaanBahan)
                <span class="font-mono font-semibold text-indigo-700">{{ $ord->penerimaanBahan->no_nota }}</span>
                <span class="block text-[10px] text-slate-400">
                  {{ $ord->penerimaanBahan->detailBahanWarna->pluck('nama_warna')->join(', ') ?: $ord->penerimaanBahan->nama_kain }}
                </span>
              @else
                <span class="text-slate-400 italic">Belum terhubung nota</span>
              @endif
            </td>
            <td class="py-3 font-mono text-slate-500">{{ \Carbon\Carbon::parse($ord->deadline_awal)->format('Y-m-d') }}</td>
            <td class="py-3 font-mono font-bold text-amber-600">
              {{ \Carbon\Carbon::parse($ord->deadline_terbaru ?? $ord->deadline_awal)->format('Y-m-d') }}
              @if($ord->alasan_perubahan_deadline)
                <span class="block text-[10px] font-sans font-normal text-slate-400">Revisi: {{ $ord->alasan_perubahan_deadline }}</span>
              @endif
            </td>
            <td class="py-3">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                {{ $ord->status_pesanan === 'Selesai' ? 'bg-emerald-100 text-emerald-800' : ($ord->status_pesanan === 'Pemotongan' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700') }}">
                {{ $ord->status_pesanan }}
              </span>
            </td>
            <td class="py-3 text-right">
              <button type="button"
                onclick="selectOrderFromQueue({{ $ord->id_pesanan }}, {{ $firstWarnaId ?: 'null' }}, {{ $firstRollId ?: 'null' }})"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-semibold shadow-sm transition-colors inline-flex items-center gap-1">
                <i data-lucide="scissors" class="w-3 h-3"></i> Pilih Potong
              </button>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="8" class="py-6 text-center text-slate-400">Belum ada antrean pesanan untuk dipotong.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form Pemotongan Seri Multi-Section -->
  <form id="form-pemotongan" class="space-y-6">
    @csrf

    <!-- Section 1: Identitas Seri, Alokasi Warna & Rencana Potong (Detail_Pemotongan_Warna) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="palette" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">1. Alokasi Warna & Rencana Potong (<code class="font-mono text-indigo-600">Pemotongan_Seri</code> & <code class="font-mono text-indigo-600">Detail_Pemotongan_Warna</code>)</h3>
          <p class="text-[11px] text-slate-500">Pilih pesanan target, warna bahan kain yang akan dipotong, dan target lembar direncanakan</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pesanan Target <span class="text-rose-500">*</span></label>
          <select name="id_pesanan" id="sel-pesanan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="" disabled selected>-- Pilih Pesanan Target --</option>
            @foreach($orders ?? [] as $idx => $ord)
              @php
                $wId = optional(optional($ord->penerimaanBahan)->detailBahanWarna->first())->id_detail_warna;
              @endphp
              <option value="{{ $ord->id_pesanan }}" data-penerimaan="{{ $ord->id_penerimaan }}" data-default-warna="{{ $wId }}">
                [Prioritas #{{ $idx + 1 }}] Pesanan #{{ $ord->id_pesanan }} — {{ $ord->pola->nama_pola ?? $ord->jenis_pesanan }} ({{ $ord->nama_mitra ?? 'Internal' }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Operator Pemotong (Cutter) <span class="text-rose-500">*</span></label>
          <select name="id_user_pemotong" id="sel-cutter" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            @foreach($users ?? [] as $usr)
              <option value="{{ $usr->id_user }}">{{ $usr->nama_user }} ({{ $usr->role }} — @ {{ $usr->username }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Seri Potong <span class="text-rose-500">*</span></label>
          <input type="text" name="kode_seri" id="inp-kode-seri" placeholder="Contoh: SERI-2026-002" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Warna Bahan (<code class="font-mono">id_detail_warna</code>) <span class="text-rose-500">*</span></label>
          <select name="id_detail_warna" id="sel-warna-bahan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            @foreach($warnaList ?? [] as $w)
              <option value="{{ $w->id_detail_warna }}" data-penerimaan="{{ $w->id_penerimaan }}" data-satuan="{{ $w->satuan_ukur }}">
                #{{ $w->id_detail_warna }} — {{ $w->nama_warna }} ({{ $w->penerimaanBahan->no_nota ?? 'Nota' }} · {{ $w->penerimaanBahan->nama_kain ?? '' }} · {{ $w->total_ukuran }} {{ $w->satuan_ukur }})
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Target Lembar Direncanakan (<code class="font-mono">total_lembar_direncanakan</code>) <span class="text-rose-500">*</span></label>
          <input type="number" name="total_lembar_direncanakan" id="inp-target-lembar" min="1" value="300" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Variasi Potongan</label>
          <input type="text" name="jenis_variasi" id="inp-jenis-variasi" placeholder="Misal: Badan Utama + Lengan Pendek + Rib Leher" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    <!-- Section 2: Parameter Gelaran & Pelacakan Roll (Detail_Gelaran_Kain) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i data-lucide="layers" class="w-4 h-4"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">2. Parameter Gelaran & Pelacakan Roll (<code class="font-mono text-indigo-600">Detail_Gelaran_Kain</code>)</h3>
            <p class="text-[11px] text-slate-500">Pilih gulungan kain fisik aktif yang digelar di meja potong, panjang bentangan, jumlah tumpukan (ply), dan kalkulasi lembar</p>
          </div>
        </div>

        <button type="button" onclick="addGelaranRow()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg inline-flex items-center gap-1.5 shadow-sm transition-colors">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i>
          + Tambah Gelaran Roll
        </button>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
            <tr>
              <th class="py-2.5 pl-4 text-left">Pilih Roll Fisik (<code class="font-mono">id_roll</code>) *</th>
              <th class="py-2.5 px-3 text-left w-40">Panjang Gelaran (<code class="font-mono">panjang_gelar</code>) *</th>
              <th class="py-2.5 px-3 text-left w-40">Jumlah Tumpukan / Ply (<code class="font-mono">jumlah_tumpuk</code>) *</th>
              <th class="py-2.5 px-3 text-left w-44">Kalkulasi Lembar Gelar (<code class="font-mono">total_lembar_gelar</code>) *</th>
              <th class="py-2.5 px-3 text-left w-48">Pembaruan Status Roll *</th>
              <th class="py-2.5 pr-4 text-center w-14">Aksi</th>
            </tr>
          </thead>
          <tbody id="gelaran-rows-body" class="divide-y divide-slate-100 bg-white">
            <!-- Rendered by JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Section 3: Waktu & Pembaruan Status Stok Roll -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="calendar-clock" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">3. Waktu Potong & Pembaruan Kuantitas Stok Roll</h3>
          <p class="text-[11px] text-slate-500">Pencatatan waktu pengerjaan potong (<code class="font-mono text-indigo-600">tgl_potong</code>), konsumsi kain (<code class="font-mono text-indigo-600">kain_terpakai</code>), dan hasil lembar aktual</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu Potong (<code class="font-mono">tgl_potong</code>) <span class="text-rose-500">*</span></label>
          <input type="datetime-local" name="tgl_potong" id="inp-tgl-potong" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kuantitas Kain Terpakai (<code class="font-mono">kain_terpakai</code>) <span class="text-rose-500">*</span></label>
          <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 bg-white">
            <input type="number" step="0.01" min="0.01" name="kain_terpakai" id="inp-kain-terpakai" value="45.00" required class="flex-1 min-w-0 text-xs font-mono p-2.5 border-0 focus:outline-none">
            <span id="badge-satuan-kain" class="px-3 bg-slate-50 border-l border-slate-200 text-xs font-mono font-semibold text-slate-600 flex items-center">Kg</span>
          </div>
          <p class="text-[10px] text-slate-400 mt-1">Otomatis memotong <code class="font-mono">ukuran_sisa</code> pada roll fisik yang dipilih.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Total Lembar Hasil Aktual (<code class="font-mono">total_lembar_hasil</code>) <span class="text-rose-500">*</span></label>
          <input type="number" name="total_lembar_hasil" id="inp-total-hasil" min="1" value="300" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-500">
          <p class="text-[10px] text-slate-400 mt-1">Tersinkronisasi otomatis dengan jumlah kalkulasi lembar gelar di atas.</p>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-5 mt-4 border-t border-slate-100">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors inline-flex items-center gap-1.5">
          <i data-lucide="scissors" class="w-4 h-4"></i>
          Simpan Pemotongan & Perbarui Stok Roll
        </button>
      </div>
    </div>
  </form>

  <!-- Riwayat Pemotongan Seri -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 text-sm mb-4">Riwayat Pemotongan Seri & Detail Gelaran Roll</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">Kode Seri</th>
            <th class="pb-2">Pesanan Target</th>
            <th class="pb-2">Pemotong</th>
            <th class="pb-2">Rencana / Hasil (Lembar)</th>
            <th class="pb-2">Waktu Potong</th>
            <th class="pb-2">Warna & Kain Terpakai</th>
            <th class="pb-2">Detail Gelaran & Roll (<code class="font-mono">Detail_Gelaran_Kain</code>)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($pemotongan ?? [] as $pm)
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-bold text-indigo-600">
              {{ $pm->kode_seri }}
              @if($pm->jenis_variasi)
                <span class="block text-[10px] font-sans font-normal text-slate-400">{{ $pm->jenis_variasi }}</span>
              @endif
            </td>
            <td class="py-3">
              <span class="font-medium text-slate-800">Pesanan #{{ $pm->id_pesanan }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $pm->pesanan->pola->nama_pola ?? '-' }}</span>
            </td>
            <td class="py-3 text-slate-600">{{ $pm->userPemotong->nama_user ?? '-' }}</td>
            <td class="py-3 font-mono">
              @php
                $totalRencana = $pm->detailPemotonganWarna->sum('total_lembar_direncanakan');
              @endphp
              <span class="text-slate-500">{{ number_format($totalRencana ?: $pm->total_lembar_hasil) }}</span>
              <span class="text-slate-400">/</span>
              <span class="font-bold text-emerald-700">{{ number_format($pm->total_lembar_hasil) }} Pcs</span>
            </td>
            <td class="py-3 font-mono text-slate-500">{{ $pm->tgl_potong }}</td>
            <td class="py-3 text-[11px] text-slate-600">
              @foreach($pm->detailPemotonganWarna ?? [] as $pw)
                <div>
                  <span class="font-semibold">{{ $pw->detailBahanWarna->nama_warna ?? '-' }}</span>:
                  <span class="font-mono font-bold text-indigo-700">{{ $pw->kain_terpakai }} {{ $pw->detailBahanWarna->satuan_ukur ?? 'Kg' }}</span>
                </div>
              @endforeach
            </td>
            <td class="py-3 text-[11px] text-slate-600 space-y-1">
              @foreach($pm->detailPemotonganWarna ?? [] as $pw)
                @foreach($pw->detailGelaranKain ?? [] as $gk)
                  <div class="bg-slate-50 border border-slate-200 rounded px-2 py-1 font-mono text-[10px]">
                    <span class="font-bold text-indigo-700">Roll #{{ $gk->detailRollBahan->no_roll_ke ?? '?' }} (#{{ $gk->id_roll }})</span>
                    &bull; Gelar: <b>{{ $gk->panjang_gelar }}m</b>
                    &bull; Ply: <b>{{ $gk->jumlah_tumpuk }} lapis</b>
                    &bull; Hasil: <b>{{ $gk->total_lembar_gelar }} lbr</b>
                    @if($gk->detailRollBahan)
                      <span class="ml-1 px-1.5 py-0.2 rounded {{ $gk->detailRollBahan->status_roll === 'Habis' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                        {{ $gk->detailRollBahan->status_roll }} (Sisa: {{ $gk->detailRollBahan->ukuran_sisa }})
                      </span>
                    @endif
                  </div>
                @endforeach
              @endforeach
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="py-6 text-center text-slate-400">Belum ada catatan pemotongan seri.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@php
  $rollsJson = ($rolls ?? collect())->map(function($r) {
    return [
      'id_roll' => $r->id_roll,
      'id_detail_warna' => $r->id_detail_warna,
      'no_roll_ke' => $r->no_roll_ke,
      'ukuran_awal' => $r->ukuran_awal,
      'ukuran_sisa' => $r->ukuran_sisa,
      'satuan' => $r->satuan,
      'status_roll' => $r->status_roll,
      'nama_warna' => $r->detailBahanWarna->nama_warna ?? '-',
      'no_nota' => $r->detailBahanWarna->penerimaanBahan->no_nota ?? '-',
    ];
  })->values();
@endphp

@push('scripts')
<script>
  const availableRolls = @json($rollsJson);

  let gelaranRows = [
    {
      id_roll: availableRolls.length > 0 ? availableRolls[0].id_roll : '',
      panjang_gelar: 2.50,
      jumlah_tumpuk: 50,
      total_lembar_gelar: 300,
      status_roll_baru: 'Dipotong'
    }
  ];

  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  function renderGelaranRows() {
    const tbody = document.getElementById('gelaran-rows-body');
    if (!tbody) return;

    const selectedWarnaId = Number(document.getElementById('sel-warna-bahan')?.value || 0);

    tbody.innerHTML = gelaranRows.map(function (row, idx) {
      const rollOptions = availableRolls.map(function (r) {
        const isMatchWarna = Number(r.id_detail_warna) === selectedWarnaId;
        const prefix = isMatchWarna ? '★ ' : '';
        const selected = Number(row.id_roll) === Number(r.id_roll) ? 'selected' : '';
        return `<option value="${r.id_roll}" ${selected}>${prefix}#id_roll: ${r.id_roll} — Roll #${r.no_roll_ke} (${r.nama_warna} · Sisa: ${r.ukuran_sisa}/${r.ukuran_awal} ${r.satuan}) [${r.status_roll}]</option>`;
      }).join('');

      return `
        <tr class="hover:bg-slate-50/70">
          <td class="py-2.5 pl-4">
            <select class="sel-gelar-roll w-full text-xs p-2 border border-slate-200 rounded-lg font-mono bg-white" data-idx="${idx}" required>
              ${rollOptions || '<option value="">-- Tidak ada roll aktif --</option>'}
            </select>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" step="0.01" min="0.1" class="inp-panjang-gelar flex-1 min-w-0 text-xs font-mono p-2 border-0 focus:outline-none" data-idx="${idx}" value="${row.panjang_gelar}">
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">m/yd</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" min="1" class="inp-jumlah-tumpuk flex-1 min-w-0 text-xs font-mono p-2 border-0 focus:outline-none" data-idx="${idx}" value="${row.jumlah_tumpuk}">
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">Ply</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" min="1" class="inp-lembar-gelar flex-1 min-w-0 text-xs font-mono font-bold text-indigo-700 p-2 border-0 focus:outline-none" data-idx="${idx}" value="${row.total_lembar_gelar}">
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">Lembar</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <select class="sel-status-roll w-full text-xs p-2 border border-slate-200 rounded-lg bg-white font-semibold" data-idx="${idx}">
              <option value="Dipotong" ${row.status_roll_baru === 'Dipotong' ? 'selected' : ''}>Dipotong (Masih Tersisa)</option>
              <option value="Habis" ${row.status_roll_baru === 'Habis' ? 'selected' : ''}>Habis (Tuntas 0.00)</option>
            </select>
          </td>
          <td class="py-2.5 pr-4 text-center">
            <button type="button" onclick="removeGelaranRow(${idx})" class="w-7 h-7 text-slate-400 hover:text-rose-600 rounded inline-flex items-center justify-center ${gelaranRows.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''}" ${gelaranRows.length <= 1 ? 'disabled' : ''}>
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    tbody.querySelectorAll('.sel-gelar-roll').forEach(function (sel) {
      sel.addEventListener('change', function () {
        const i = Number(this.dataset.idx);
        gelaranRows[i].id_roll = Number(this.value);
      });
    });

    tbody.querySelectorAll('.inp-panjang-gelar').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const i = Number(this.dataset.idx);
        gelaranRows[i].panjang_gelar = parseFloat(this.value) || 0;
        recalcRowAndTotals(i);
      });
    });

    tbody.querySelectorAll('.inp-jumlah-tumpuk').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const i = Number(this.dataset.idx);
        gelaranRows[i].jumlah_tumpuk = parseInt(this.value, 10) || 1;
        recalcRowAndTotals(i);
      });
    });

    tbody.querySelectorAll('.inp-lembar-gelar').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const i = Number(this.dataset.idx);
        gelaranRows[i].total_lembar_gelar = parseInt(this.value, 10) || 1;
        syncTotalHasil();
      });
    });

    tbody.querySelectorAll('.sel-status-roll').forEach(function (sel) {
      sel.addEventListener('change', function () {
        const i = Number(this.dataset.idx);
        gelaranRows[i].status_roll_baru = this.value;
      });
    });

    syncTotalHasil();
    refreshIcons();
  }

  function recalcRowAndTotals(idx) {
    const r = gelaranRows[idx];
    if (!r) return;
    // Estimate sheets: e.g. each 1m of spread yields ~2.4 garment panels * ply count
    const estimatedLembar = Math.max(1, Math.round(r.panjang_gelar * r.jumlah_tumpuk * 2.4));
    r.total_lembar_gelar = estimatedLembar;
    const lembarInp = document.querySelector(`.inp-lembar-gelar[data-idx="${idx}"]`);
    if (lembarInp) lembarInp.value = estimatedLembar;
    syncTotalHasil();
  }

  function syncTotalHasil() {
    let totalLembar = 0;
    let totalKainEst = 0;
    gelaranRows.forEach(function (r) {
      totalLembar += (parseInt(r.total_lembar_gelar, 10) || 0);
      totalKainEst += (parseFloat(r.panjang_gelar) || 0) * (parseInt(r.jumlah_tumpuk, 10) || 0) * 0.36;
    });
    const hasilInp = document.getElementById('inp-total-hasil');
    if (hasilInp && totalLembar > 0) hasilInp.value = totalLembar;
    const kainInp = document.getElementById('inp-kain-terpakai');
    if (kainInp && totalKainEst > 0) kainInp.value = totalKainEst.toFixed(2);
  }

  function addGelaranRow() {
    const defaultRoll = availableRolls.length > 0 ? availableRolls[0].id_roll : '';
    gelaranRows.push({
      id_roll: defaultRoll,
      panjang_gelar: 2.50,
      jumlah_tumpuk: 50,
      total_lembar_gelar: 300,
      status_roll_baru: 'Dipotong'
    });
    renderGelaranRows();
  }

  function removeGelaranRow(idx) {
    if (gelaranRows.length <= 1) return;
    gelaranRows.splice(idx, 1);
    renderGelaranRows();
  }

  function selectOrderFromQueue(idPesanan, idDetailWarna, idRoll) {
    const selPesanan = document.getElementById('sel-pesanan');
    if (selPesanan) {
      selPesanan.value = idPesanan;
    }
    if (idDetailWarna) {
      const selWarna = document.getElementById('sel-warna-bahan');
      if (selWarna) {
        selWarna.value = idDetailWarna;
        updateUnitBadgeFromWarna();
      }
    }
    if (idRoll && gelaranRows.length > 0) {
      gelaranRows[0].id_roll = idRoll;
    }
    renderGelaranRows();
    document.getElementById('form-pemotongan')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function updateUnitBadgeFromWarna() {
    const selWarna = document.getElementById('sel-warna-bahan');
    const opt = selWarna?.options[selWarna.selectedIndex];
    const satuan = opt?.getAttribute('data-satuan') || 'Kg';
    const badge = document.getElementById('badge-satuan-kain');
    if (badge) badge.textContent = satuan;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Default datetime-local to current local time
    const dtInput = document.getElementById('inp-tgl-potong');
    if (dtInput && !dtInput.value) {
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      dtInput.value = now.toISOString().slice(0, 16);
    }

    const seriInput = document.getElementById('inp-kode-seri');
    if (seriInput && !seriInput.value) {
      seriInput.value = 'SERI-' + new Date().getFullYear() + '-' + Math.floor(100 + Math.random() * 900);
    }

    document.getElementById('sel-pesanan')?.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      const defaultWarna = opt?.getAttribute('data-default-warna');
      if (defaultWarna) {
        const selWarna = document.getElementById('sel-warna-bahan');
        if (selWarna) {
          selWarna.value = defaultWarna;
          updateUnitBadgeFromWarna();
        }
        const matchingRoll = availableRolls.find(r => Number(r.id_detail_warna) === Number(defaultWarna));
        if (matchingRoll && gelaranRows.length > 0) {
          gelaranRows[0].id_roll = matchingRoll.id_roll;
        }
      }
      renderGelaranRows();
    });

    document.getElementById('sel-warna-bahan')?.addEventListener('change', function () {
      updateUnitBadgeFromWarna();
      const selectedW = Number(this.value);
      const matchingRoll = availableRolls.find(r => Number(r.id_detail_warna) === selectedW);
      if (matchingRoll && gelaranRows.length > 0) {
        gelaranRows[0].id_roll = matchingRoll.id_roll;
      }
      renderGelaranRows();
    });

    updateUnitBadgeFromWarna();
    renderGelaranRows();
  });

  document.getElementById('form-pemotongan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const idPesanan = Number(formData.get('id_pesanan'));
    const idUserPemotong = Number(formData.get('id_user_pemotong'));
    const kodeSeri = formData.get('kode_seri');
    const jenisVariasi = formData.get('jenis_variasi') || null;
    const tglPotong = formData.get('tgl_potong');
    const totalLembarHasil = Number(formData.get('total_lembar_hasil'));
    const idDetailWarna = Number(formData.get('id_detail_warna'));
    const targetLembar = Number(formData.get('total_lembar_direncanakan'));
    const kainTerpakai = parseFloat(formData.get('kain_terpakai'));

    if (!idPesanan) {
      alert('Silakan pilih Pesanan Target terlebih dahulu.');
      return;
    }

    const payload = {
      id_pesanan: idPesanan,
      id_user_pemotong: idUserPemotong,
      kode_seri: kodeSeri,
      jenis_variasi: jenisVariasi,
      tgl_potong: tglPotong,
      total_lembar_hasil: totalLembarHasil,
      potong_warna: [
        {
          id_detail_warna: idDetailWarna,
          total_lembar_direncanakan: targetLembar,
          kain_terpakai: kainTerpakai,
          gelaran: gelaranRows.map(function (r) {
            return {
              id_roll: Number(r.id_roll),
              panjang_gelar: parseFloat(r.panjang_gelar),
              jumlah_tumpuk: parseInt(r.jumlah_tumpuk, 10),
              total_lembar_gelar: parseInt(r.total_lembar_gelar, 10),
              status_roll_baru: r.status_roll_baru
            };
          })
        }
      ]
    };

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch("{{ route('pemotongan.store', [], false) }}", {
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
