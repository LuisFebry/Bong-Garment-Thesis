@extends('layouts.app')

@section('title', 'Kelola Pesanan & Pola - Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pesanan & Master Pola</h2>
      <p class="text-xs text-slate-500 mt-1">Inisiasi pesanan produksi, pendaftaran master pola pakaian (11-metrik ukuran), penugasan nota bahan kain, dan penjadwalan EDF</p>
    </div>
  </div>

  <!-- Form Inisiasi Pesanan Baru + Opsi Daftarkan Pola Baru -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-100 mb-5">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Input Pesanan Produksi Baru & Pemilihan Pola</h3>
          <p class="text-[11px] text-slate-500">Pilih pola terdaftar atau daftarkan pola pakaian baru beserta spesifikasi ukuran 11-metrik (<code class="font-mono text-indigo-600">Pesanan</code> &amp; <code class="font-mono text-indigo-600">Pola</code>)</p>
        </div>
      </div>

      <!-- Toggle Mode Pola -->
      <div class="inline-flex items-center p-1 bg-slate-100 rounded-lg border border-slate-200">
        <button type="button" id="btn-mode-existing" onclick="setPolaMode('existing')" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all bg-white text-indigo-700 shadow-sm inline-flex items-center gap-1.5">
          <i data-lucide="list" class="w-3.5 h-3.5"></i>
          <span>Pilih Master Pola Ada</span>
        </button>
        <button type="button" id="btn-mode-new" onclick="setPolaMode('new')" class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5">
          <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
          <span>+ Daftarkan Pola Baru</span>
        </button>
      </div>
    </div>

    <form id="form-pesanan" class="space-y-5">
      @csrf
      <input type="hidden" name="pola_mode" id="inp-pola-mode" value="existing">

      <!-- Baris 1: Sumber, Mitra, Tanggal Order, Tanggal Deadline Awal -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Sumber Inisiasi <span class="text-rose-500">*</span></label>
            <button type="button" onclick="openModalSumber()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Sumber / Edit</button>
          </div>
          <select name="id_sumber" id="pesanan-sumber" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            @foreach($sumber ?? [] as $s)
              <option value="{{ $s->id_sumber }}">{{ $s->nama_sumber }}</option>
            @endforeach
          </select>
        </div>

        <div id="field-nama-mitra">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Mitra / Klien</label>
          <input type="text" name="nama_mitra" id="input-nama-mitra" placeholder="Contoh: PT Busana Mandiri" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Order <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" name="tgl_order" id="inp-tgl-order" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Batas Waktu / Deadline Awal <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" name="deadline_awal" id="inp-deadline-awal" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>
      </div>

      <!-- Baris 2: Jenis/Keterangan Pesanan & Nota Bahan Kain -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis / Keterangan Pesanan <span class="text-rose-500">*</span></label>
          <input type="text" name="jenis_pesanan" id="inp-jenis-pesanan" placeholder="Misal: Kaos Event Sablon Depan Belakang 200 Pcs" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Bahan Kain (Nota Penerimaan Gudang)</label>
          <select name="id_penerimaan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="">-- Pilih Nota Kain (Opsional, Dapat Diisi Nanti Saat Kain Tiba) --</option>
            @foreach($penerimaan ?? [] as $pn)
              <option value="{{ $pn->id_penerimaan }}">{{ $pn->no_nota }} - {{ $pn->nama_kain }} (Tgl: {{ $pn->tgl_nota }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- Bagian A: Pilih Master Pola Ada -->
      <div id="section-pola-existing" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Master Pola Pakaian Terdaftar <span class="text-rose-500">*</span></label>
            <select name="id_pola" id="sel-pola-existing" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white font-medium">
              @foreach($pola ?? [] as $p)
                <option value="{{ $p->id_pola }}">{{ $p->kode_pola }} - {{ $p->nama_pola }} ({{ $p->kategori->nama_kategori ?? 'Umum' }})</option>
              @endforeach
            </select>
          </div>
          <div>
            <button type="button" onclick="setPolaMode('new')" class="w-full py-2.5 px-3 bg-white hover:bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg text-xs font-semibold transition-colors inline-flex items-center justify-center gap-1.5">
              <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
              <span>+ Daftarkan Pola Baru</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Bagian B: Form Pendaftaran Master Pola Baru (11-Metrik & Aksesoris) -->
      <div id="section-pola-new" class="hidden p-5 rounded-xl bg-indigo-50/40 border border-indigo-200 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-indigo-100">
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-600 text-white">Pola Baru</span>
            <h4 class="font-bold text-slate-900 text-xs">Pendaftaran Master Pola Pakaian & Spesifikasi Ukuran 11-Metrik (<code class="font-mono text-indigo-700">Pola</code> &amp; <code class="font-mono text-indigo-700">Spesifikasi_Ukuran</code>)</h4>
          </div>
          <button type="button" onclick="saveNewPolaStandalone()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-semibold shadow-sm inline-flex items-center gap-1 transition-colors">
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
            <span>Simpan Master Pola Ini Saja</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700">Kategori Pola <span class="text-rose-500">*</span></label>
              <button type="button" onclick="openModalKategori()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Kategori Baru / Edit</button>
            </div>
            <select id="new-pola-kategori" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
              @foreach($kategori ?? [] as $kat)
                <option value="{{ $kat->id_kategori }}">{{ $kat->nama_kategori }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pola Unik <span class="text-rose-500">*</span></label>
            <input type="text" id="new-pola-kode" value="POLA-NEW-0{{ count($pola ?? []) + 1 }}" placeholder="Contoh: POLA-FLN-05" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white font-mono uppercase focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Master Pola Pakaian <span class="text-rose-500">*</span></label>
            <input type="text" id="new-pola-nama" placeholder="Contoh: Kemeja Flanel Lengan Panjang" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        <!-- Tabel Input 11-Metrik Spesifikasi Ukuran (S, M, L, XL) -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-slate-800">Tabel Spesifikasi Ukuran 11-Metrik (cm) - <code class="font-mono text-indigo-600">Spesifikasi_Ukuran</code></label>
            <button type="button" onclick="addNewPolaSizeRow()" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-indigo-600 border border-indigo-200 rounded-md text-[11px] font-semibold inline-flex items-center gap-1">
              <i data-lucide="plus" class="w-3 h-3"></i> Tambah Baris Ukuran
            </button>
          </div>
          <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-[11px]">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th class="py-2 px-2 text-left w-16">Ukuran</th>
                  <th class="py-2 px-1.5 text-center">L. Dada</th>
                  <th class="py-2 px-1.5 text-center">P. Baju/Cln</th>
                  <th class="py-2 px-1.5 text-center">L. Baju</th>
                  <th class="py-2 px-1.5 text-center">P. Pundak</th>
                  <th class="py-2 px-1.5 text-center">P. Tangan</th>
                  <th class="py-2 px-1.5 text-center">L. Lengan</th>
                  <th class="py-2 px-1.5 text-center">D. Leher</th>
                  <th class="py-2 px-1.5 text-center">L. Pinggang</th>
                  <th class="py-2 px-1.5 text-center">L. Selangkang</th>
                  <th class="py-2 px-1.5 text-center">L. Paha</th>
                  <th class="py-2 px-1.5 text-center">L. Kaki</th>
                  <th class="py-2 px-2 text-center w-10">Aksi</th>
                </tr>
              </thead>
              <tbody id="tbody-new-pola-specs" class="divide-y divide-slate-100">
                <!-- Populated by JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Kebutuhan Aksesoris per Pola (Detail_Aksesoris_Pola) -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-slate-800">Kebutuhan Aksesoris per 1 Potong Pakaian (Opsional) - <code class="font-mono text-indigo-600">Detail_Aksesoris_Pola</code></label>
            <button type="button" onclick="addNewPolaAccRow()" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-indigo-600 border border-indigo-200 rounded-md text-[11px] font-semibold inline-flex items-center gap-1">
              <i data-lucide="plus" class="w-3 h-3"></i> Tambah Aksesoris Pola
            </button>
          </div>
          <div id="container-new-pola-acc" class="space-y-2">
            <!-- Populated by JS -->
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors inline-flex items-center gap-1.5">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span>Simpan & Inisiasi Pesanan</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Daftar Pesanan -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Daftar Pesanan & Status Produksi</h3>
        <p class="text-xs text-slate-500 mt-0.5">Urutan otomatis berbasis prioritas batas waktu terdekat (Earliest Deadline First)</p>
      </div>
      <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full border border-indigo-100">
        Total: {{ count($pesanan ?? []) }} Pesanan
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">ID</th>
            <th class="pb-2">Sumber / Mitra</th>
            <th class="pb-2">Pola & Keterangan</th>
            <th class="pb-2">Nota Bahan Kain</th>
            <th class="pb-2">Tgl Order</th>
            <th class="pb-2">Deadline Awal</th>
            <th class="pb-2">Deadline Terbaru</th>
            <th class="pb-2">Status</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($pesanan ?? [] as $item)
          @php
            $dlAwalFormatted = $item->deadline_awal ? (is_string($item->deadline_awal) ? substr($item->deadline_awal, 0, 10) : $item->deadline_awal->format('Y-m-d')) : '-';
            $dlTerbaruFormatted = $item->deadline_terbaru ? (is_string($item->deadline_terbaru) ? substr($item->deadline_terbaru, 0, 10) : $item->deadline_terbaru->format('Y-m-d')) : $dlAwalFormatted;
            $isRevised = ($dlTerbaruFormatted !== '-' && $dlAwalFormatted !== '-' && $dlTerbaruFormatted !== $dlAwalFormatted);
          @endphp
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $item->id_pesanan }}</td>
            <td class="py-3">
              <span class="font-medium text-slate-900">{{ $item->nama_mitra ?? 'Non-Mitra' }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $item->sumberInisiasi->nama_sumber ?? '-' }}</span>
            </td>
            <td class="py-3">
              <span class="font-medium text-slate-800">{{ $item->pola->nama_pola ?? '-' }}</span>
              <span class="text-[10px] text-slate-400 block">{{ $item->jenis_pesanan }}</span>
            </td>
            <td class="py-3">
              @if($item->penerimaanBahan)
                <div class="inline-flex items-center gap-1 font-mono text-[11px] font-semibold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                  <i data-lucide="file-text" class="w-3 h-3 text-indigo-500"></i>
                  {{ $item->penerimaanBahan->no_nota }}
                </div>
                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $item->penerimaanBahan->nama_kain }}</span>
              @else
                <span class="inline-flex items-center gap-1 text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-medium">
                  <i data-lucide="alert-circle" class="w-3 h-3 text-amber-500"></i> Belum Ditautkan
                </span>
              @endif
            </td>
            <td class="py-3 font-mono text-slate-500">
              {{ $item->tgl_order ? (is_string($item->tgl_order) ? substr($item->tgl_order, 0, 10) : $item->tgl_order->format('Y-m-d')) : '-' }}
            </td>
            <td class="py-3 font-mono text-slate-500">{{ $dlAwalFormatted }}</td>
            <td class="py-3 font-mono">
              @if($isRevised)
                <span class="text-amber-600 font-semibold">{{ $dlTerbaruFormatted }}</span>
                <span class="text-[9px] text-amber-500 block font-sans">(Revisi)</span>
              @else
                <span class="text-slate-600">{{ $dlTerbaruFormatted }}</span>
              @endif
            </td>
            <td class="py-3">
              @php
                $statusColors = [
                  'Antrean' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                  'Pemotongan' => 'bg-amber-50 text-amber-700 border-amber-100',
                  'Dalam QC' => 'bg-rose-50 text-rose-700 border-rose-100',
                  'Jahit' => 'bg-purple-50 text-purple-700 border-purple-100',
                  'Finishing' => 'bg-blue-50 text-blue-700 border-blue-100',
                  'Selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                  'Dibatalkan' => 'bg-rose-50 text-rose-700 border-rose-100',
                ];
                $badgeStyle = $statusColors[$item->status_pesanan] ?? 'bg-slate-100 text-slate-700 border-slate-200';
              @endphp
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeStyle }}">
                {{ $item->status_pesanan }}
              </span>
            </td>
            <td class="py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button type="button" onclick='openEditPesananModal(@json($item))' class="px-2.5 py-1 text-[11px] bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-md font-semibold inline-flex items-center gap-1 transition-colors">
                  <i data-lucide="edit-3" class="w-3 h-3"></i> Edit
                </button>
                <button type="button" onclick="openReviseDeadlineModal({{ $item->id_pesanan }}, '{{ $dlAwalFormatted }}', '{{ $dlTerbaruFormatted }}')" class="px-2.5 py-1 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-medium transition-colors">
                  Revisi
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="py-8 text-center text-slate-400">Belum ada data pesanan.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Katalog Master Pola & Spesifikasi Ukuran 11-Metrik -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Katalog Master Pola Pakaian & Spesifikasi 11-Metrik</h3>
        <p class="text-xs text-slate-500 mt-0.5">Daftar pola terdaftar beserta rincian ukuran S/M/L/XL (<code class="font-mono text-indigo-600">Spesifikasi_Ukuran</code>) dan kebutuhan aksesoris (<code class="font-mono text-indigo-600">Detail_Aksesoris_Pola</code>)</p>
      </div>
      <span class="text-xs font-semibold bg-slate-100 text-slate-700 px-3 py-1 rounded-full">
        {{ count($pola ?? []) }} Master Pola
      </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      @foreach($pola ?? [] as $p)
      @php
        $firstSpec = $p->spesifikasiUkuran->first();
      @endphp
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
        <div class="flex items-start justify-between gap-2">
          <div>
            <span class="font-mono text-xs font-bold text-indigo-600">{{ $p->kode_pola }}</span>
            <h4 class="font-bold text-slate-900 text-sm">{{ $p->nama_pola }}</h4>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-600">
              {{ $p->kategori->nama_kategori ?? 'Umum' }}
            </span>
            <button type="button"
              onclick="openEditPolaModal({{ json_encode([
                'id_pola' => $p->id_pola,
                'id_kategori' => $p->id_kategori,
                'kode_pola' => $p->kode_pola,
                'nama_pola' => $p->nama_pola,
                'nama_ukuran' => $firstSpec?->nama_ukuran ?? 'L',
                'lingkar_dada' => $firstSpec?->lingkar_dada,
                'panjang_baju_celana' => $firstSpec?->panjang_baju_celana,
                'panjang_pundak' => $firstSpec?->panjang_pundak,
                'panjang_tangan' => $firstSpec?->panjang_tangan,
              ]) }})"
              class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 inline-flex items-center gap-1 transition-colors">
              <i data-lucide="edit-3" class="w-3 h-3"></i> Edit Pola
            </button>
          </div>
        </div>

        <!-- Tabel ringkas ukuran -->
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
          <table class="w-full text-[10px] text-left">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold">
              <tr>
                <th class="py-1.5 px-2">Ukuran</th>
                <th class="py-1.5 px-2">L. Dada</th>
                <th class="py-1.5 px-2">P. Baju/Cln</th>
                <th class="py-1.5 px-2">P. Pundak</th>
                <th class="py-1.5 px-2">P. Tangan</th>
                <th class="py-1.5 px-2">L. Pinggang</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
              @forelse($p->spesifikasiUkuran ?? [] as $sp)
              <tr>
                <td class="py-1 px-2 font-bold text-indigo-600">{{ $sp->nama_ukuran }}</td>
                <td class="py-1 px-2">{{ $sp->lingkar_dada ?? '-' }}</td>
                <td class="py-1 px-2">{{ $sp->panjang_baju_celana ?? '-' }}</td>
                <td class="py-1 px-2">{{ $sp->panjang_pundak ?? '-' }}</td>
                <td class="py-1 px-2">{{ $sp->panjang_tangan ?? '-' }}</td>
                <td class="py-1 px-2">{{ $sp->lingkar_pinggang ?? '-' }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="py-2 text-center text-slate-400">Belum ada spesifikasi ukuran.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Kebutuhan Aksesoris -->
        <div class="flex flex-wrap items-center gap-1.5 pt-1">
          <span class="text-[10px] font-semibold text-slate-500">Aksesoris / Pcs:</span>
          @forelse($p->detailAksesorisPola ?? [] as $dap)
            <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-medium text-slate-700">
              {{ $dap->aksesoris->nama_aksesoris ?? 'Aksesoris #'.$dap->id_aksesoris }}: <b class="font-mono text-indigo-600">{{ $dap->jumlah_digunakan }} {{ $dap->aksesoris->satuan ?? 'pcs' }}</b>
            </span>
          @empty
            <span class="text-[10px] text-slate-400 italic">Tidak ada aksesoris khusus</span>
          @endforelse
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Modal Edit Pesanan & Assign Nota Bahan -->
<div id="modal-edit-pesanan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base" id="modal-edit-title">Edit Pesanan</h3>
        <p class="text-xs text-slate-500">Perbarui spesifikasi pesanan, tanggal order, penetapan nota bahan kain, dan status produksi</p>
      </div>
      <button type="button" onclick="closeEditPesananModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg leading-none transition-colors">&times;</button>
    </div>

    <form id="form-edit-pesanan" class="space-y-4">
      @csrf
      <input type="hidden" name="id_pesanan" id="edit-id-pesanan">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Sumber Inisiasi</label>
            <button type="button" onclick="openModalSumber()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Sumber / Edit</button>
          </div>
          <select name="id_sumber" id="edit-id-sumber" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
            @foreach($sumber ?? [] as $s)
              <option value="{{ $s->id_sumber }}">{{ $s->nama_sumber }}</option>
            @endforeach
          </select>
        </div>

        <div id="edit-field-nama-mitra">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Mitra / Klien</label>
          <input type="text" name="nama_mitra" id="edit-nama-mitra" placeholder="Contoh: PT Busana Mandiri" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pola Pakaian</label>
          <select name="id_pola" id="edit-id-pola" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
            @foreach($pola ?? [] as $p)
              <option value="{{ $p->id_pola }}">{{ $p->kode_pola }} - {{ $p->nama_pola }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis / Keterangan Pesanan</label>
          <input type="text" name="jenis_pesanan" id="edit-jenis-pesanan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Bahan Kain (Nota Penerimaan Gudang)
            <span class="text-[11px] text-indigo-600 font-normal ml-1">- Dapat ditautkan saat kain tiba di gudang</span>
          </label>
          <select name="id_penerimaan" id="edit-id-penerimaan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tanpa Nota / Belum Ada Bahan Kain (Menunggu Kedatangan) --</option>
            @foreach($penerimaan ?? [] as $pn)
              <option value="{{ $pn->id_penerimaan }}">{{ $pn->no_nota }} - {{ $pn->nama_kain }} (Tgl: {{ $pn->tgl_nota }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Order <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" name="tgl_order" id="edit-tgl-order" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Deadline Awal <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" name="deadline_awal" id="edit-deadline-awal" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Deadline Terbaru (Revisi)
          </label>
          <input type="date" name="deadline_terbaru" id="edit-deadline-terbaru" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Status Produksi</label>
          <select name="status_pesanan" id="edit-status-pesanan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
            <option value="Antrean">Antrean</option>
            <option value="Pemotongan">Pemotongan</option>
            <option value="Dalam QC">Dalam QC</option>
            <option value="Jahit">Jahit</option>
            <option value="Finishing">Finishing</option>
            <option value="Selesai">Selesai</option>
            <option value="Dibatalkan">Dibatalkan</option>
          </select>
        </div>

        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Perubahan Deadline (Opsional)</label>
          <input type="text" name="alasan_perubahan_deadline" id="edit-alasan-perubahan" placeholder="Contoh: Percepatan jadwal mitra" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
        <button type="button" onclick="closeEditPesananModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Master Pola -->
<div id="modal-edit-pola" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Master Pola Pakaian</h3>
        <p class="text-xs text-slate-500">Revisi identitas pola & parameter ukuran (<code class="font-mono text-indigo-600">Pola</code> & <code class="font-mono text-indigo-600">Spesifikasi_Ukuran</code>)</p>
      </div>
      <button type="button" onclick="closeEditPolaModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg leading-none">&times;</button>
    </div>

    <form id="form-edit-pola" class="space-y-4">
      <input type="hidden" id="edit-pola-id">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Kategori Pola <span class="text-rose-500">*</span></label>
            <button type="button" onclick="openModalKategori()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Kategori Baru / Edit</button>
          </div>
          <select id="edit-pola-kategori" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white">
            @foreach($kategori ?? [] as $kat)
              <option value="{{ $kat->id_kategori }}">{{ $kat->nama_kategori }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pola Unik <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-pola-kode" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono uppercase">
        </div>
        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Master Pola Pakaian <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-pola-nama" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran Utama (S/M/L/XL)</label>
          <input type="text" id="edit-pola-ukuran" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-indigo-700">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Lingkar Dada (cm)</label>
          <input type="number" step="0.1" id="edit-pola-dada" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Panjang Baju / Celana (cm)</label>
          <input type="number" step="0.1" id="edit-pola-panjang" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Panjang Pundak (cm)</label>
          <input type="number" step="0.1" id="edit-pola-pundak" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeEditPolaModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm">Simpan Perubahan Pola</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah & Kelola Kategori Pola (Kategori_Pola) -->
<div id="modal-kelola-kategori" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Tambah & Kelola Kategori Pola</h3>
        <p class="text-xs text-slate-500">Tabel <code class="font-mono text-indigo-600">Kategori_Pola</code> (Klasifikasi Jenis Pakaian)</p>
      </div>
      <button type="button" onclick="closeModalKategori()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg leading-none">&times;</button>
    </div>

    <form id="form-manage-kategori" class="space-y-3 mb-4">
      <input type="hidden" id="kat-edit-id" value="">
      <div>
        <label id="kat-form-label" class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Pola Baru <span class="text-rose-500">*</span></label>
        <div class="flex gap-2">
          <input type="text" id="kat-nama-input" required maxlength="50" placeholder="Contoh: Jaket & Outerwear, Seragam, Gamis..." class="flex-1 text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <button type="submit" id="kat-submit-btn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm whitespace-nowrap">+ Tambah</button>
          <button type="button" id="kat-cancel-edit-btn" onclick="resetKategoriForm()" class="hidden px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg">Batal</button>
        </div>
      </div>
    </form>

    <div class="border-t border-slate-100 pt-3">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Kategori Terdaftar</p>
      <div id="kategori-modal-list" class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
        @foreach($kategori ?? [] as $kat)
        <div id="kat-row-{{ $kat->id_kategori }}" class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
          <span class="font-medium text-slate-800">
            <span class="font-mono text-slate-400 mr-1">#{{ $kat->id_kategori }}</span>
            <span id="kat-name-text-{{ $kat->id_kategori }}">{{ $kat->nama_kategori }}</span>
          </span>
          <div class="flex items-center gap-1">
            <button type="button" onclick="startEditKategori({{ $kat->id_kategori }}, '{{ addslashes($kat->nama_kategori) }}')" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteKategoriModal({{ $kat->id_kategori }})" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="flex justify-end pt-4 mt-3 border-t border-slate-100">
      <button type="button" onclick="closeModalKategori()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Tambah & Kelola Sumber Inisiasi (Sumber_Inisiasi) -->
<div id="modal-kelola-sumber" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Tambah & Kelola Sumber Inisiasi</h3>
        <p class="text-xs text-slate-500">Tabel <code class="font-mono text-indigo-600">Sumber_Inisiasi</code> (Asal Inisiasi Pesanan)</p>
      </div>
      <button type="button" onclick="closeModalSumber()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg leading-none">&times;</button>
    </div>

    <form id="form-manage-sumber" class="space-y-3 mb-4">
      <input type="hidden" id="sum-edit-id" value="">
      <div>
        <label id="sum-form-label" class="block text-xs font-semibold text-slate-700 mb-1">Nama Sumber Inisiasi Baru <span class="text-rose-500">*</span></label>
        <div class="flex gap-2">
          <input type="text" id="sum-nama-input" required maxlength="50" placeholder="Contoh: Request Mitra, Produk Abadi, Pre-Order..." class="flex-1 text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <button type="submit" id="sum-submit-btn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm whitespace-nowrap">+ Tambah</button>
          <button type="button" id="sum-cancel-edit-btn" onclick="resetSumberForm()" class="hidden px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-lg">Batal</button>
        </div>
      </div>
    </form>

    <div class="border-t border-slate-100 pt-3">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Sumber Inisiasi Terdaftar</p>
      <div id="sumber-modal-list" class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
        @foreach($sumber ?? [] as $s)
        <div id="sum-row-{{ $s->id_sumber }}" class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
          <span class="font-medium text-slate-800">
            <span class="font-mono text-slate-400 mr-1">#{{ $s->id_sumber }}</span>
            <span id="sum-name-text-{{ $s->id_sumber }}">{{ $s->nama_sumber }}</span>
          </span>
          <div class="flex items-center gap-1">
            <button type="button" onclick="startEditSumber({{ $s->id_sumber }}, '{{ addslashes($s->nama_sumber) }}')" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteSumberModal({{ $s->id_sumber }})" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="flex justify-end pt-4 mt-3 border-t border-slate-100">
      <button type="button" onclick="closeModalSumber()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg">Tutup</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const availableAksesoris = @json($aksesoris ?? []);

  // Kategori Pola Modal & Dynamic Dropdown Handlers
  function openModalKategori() {
    resetKategoriForm();
    const m = document.getElementById('modal-kelola-kategori');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('kat-nama-input').focus();
  }
  function closeModalKategori() {
    const m = document.getElementById('modal-kelola-kategori');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }
  function resetKategoriForm() {
    document.getElementById('kat-edit-id').value = '';
    document.getElementById('kat-nama-input').value = '';
    document.getElementById('kat-form-label').innerHTML = 'Nama Kategori Pola Baru <span class="text-rose-500">*</span>';
    document.getElementById('kat-submit-btn').textContent = '+ Tambah';
    document.getElementById('kat-cancel-edit-btn').classList.add('hidden');
  }
  function startEditKategori(id, currentName) {
    document.getElementById('kat-edit-id').value = id;
    document.getElementById('kat-nama-input').value = currentName;
    document.getElementById('kat-form-label').innerHTML = 'Edit Kategori Pola <span class="font-mono text-indigo-600">#' + id + '</span>';
    document.getElementById('kat-submit-btn').textContent = 'Simpan Perubahan';
    document.getElementById('kat-cancel-edit-btn').classList.remove('hidden');
    document.getElementById('kat-nama-input').focus();
  }

  document.getElementById('form-manage-kategori').addEventListener('submit', async function (e) {
    e.preventDefault();
    const editId = document.getElementById('kat-edit-id').value;
    const nama = document.getElementById('kat-nama-input').value.trim();
    if (!nama) return;

    const url = editId ? ('/kategori/' + editId + '/update') : '/kategori';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ nama_kategori: nama })
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menyimpan kategori pola.');
        return;
      }
      const kat = json.data;
      if (editId) {
        // Update existing dropdown options & modal list item
        ['new-pola-kategori', 'edit-pola-kategori'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = sel.querySelector('option[value="' + kat.id_kategori + '"]');
            if (opt) opt.textContent = kat.nama_kategori;
          }
        });
        const txt = document.getElementById('kat-name-text-' + kat.id_kategori);
        if (txt) txt.textContent = kat.nama_kategori;
        resetKategoriForm();
      } else {
        // Append new option to dropdowns and select it
        ['new-pola-kategori', 'edit-pola-kategori'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = document.createElement('option');
            opt.value = kat.id_kategori;
            opt.textContent = kat.nama_kategori;
            sel.appendChild(opt);
            sel.value = kat.id_kategori;
          }
        });
        const list = document.getElementById('kategori-modal-list');
        if (list) {
          const row = document.createElement('div');
          row.id = 'kat-row-' + kat.id_kategori;
          row.className = 'flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs';
          const safeName = kat.nama_kategori.replace(/'/g, "\\'");
          row.innerHTML = '<span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#' + kat.id_kategori + '</span><span id="kat-name-text-' + kat.id_kategori + '">' + kat.nama_kategori + '</span></span>' +
            '<div class="flex items-center gap-1">' +
            '<button type="button" onclick="startEditKategori(' + kat.id_kategori + ', \'' + safeName + '\')" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>' +
            '<button type="button" onclick="deleteKategoriModal(' + kat.id_kategori + ')" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>' +
            '</div>';
          list.appendChild(row);
        }
        resetKategoriForm();
      }
    } catch (err) {
      alert('Terjadi kesalahan jaringan saat menyimpan kategori.');
    }
  });

  async function deleteKategoriModal(id) {
    if (!confirm('Hapus kategori pola ini?')) return;
    try {
      const res = await fetch('/kategori/' + id + '/delete', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menghapus kategori.');
        return;
      }
      ['new-pola-kategori', 'edit-pola-kategori'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (sel) {
          const opt = sel.querySelector('option[value="' + id + '"]');
          if (opt) opt.remove();
        }
      });
      const row = document.getElementById('kat-row-' + id);
      if (row) row.remove();
    } catch (err) {
      alert('Terjadi kesalahan jaringan.');
    }
  }

  // Sumber Inisiasi Modal & Dynamic Dropdown Handlers
  function openModalSumber() {
    resetSumberForm();
    const m = document.getElementById('modal-kelola-sumber');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('sum-nama-input').focus();
  }
  function closeModalSumber() {
    const m = document.getElementById('modal-kelola-sumber');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }
  function resetSumberForm() {
    document.getElementById('sum-edit-id').value = '';
    document.getElementById('sum-nama-input').value = '';
    document.getElementById('sum-form-label').innerHTML = 'Nama Sumber Inisiasi Baru <span class="text-rose-500">*</span>';
    document.getElementById('sum-submit-btn').textContent = '+ Tambah';
    document.getElementById('sum-cancel-edit-btn').classList.add('hidden');
  }
  function startEditSumber(id, currentName) {
    document.getElementById('sum-edit-id').value = id;
    document.getElementById('sum-nama-input').value = currentName;
    document.getElementById('sum-form-label').innerHTML = 'Edit Sumber Inisiasi <span class="font-mono text-indigo-600">#' + id + '</span>';
    document.getElementById('sum-submit-btn').textContent = 'Simpan Perubahan';
    document.getElementById('sum-cancel-edit-btn').classList.remove('hidden');
    document.getElementById('sum-nama-input').focus();
  }

  document.getElementById('form-manage-sumber').addEventListener('submit', async function (e) {
    e.preventDefault();
    const editId = document.getElementById('sum-edit-id').value;
    const nama = document.getElementById('sum-nama-input').value.trim();
    if (!nama) return;

    const url = editId ? ('/sumber/' + editId + '/update') : '/sumber';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ nama_sumber: nama })
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menyimpan sumber inisiasi.');
        return;
      }
      const sum = json.data;
      if (editId) {
        ['pesanan-sumber', 'edit-id-sumber'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = sel.querySelector('option[value="' + sum.id_sumber + '"]');
            if (opt) opt.textContent = sum.nama_sumber;
          }
        });
        const txt = document.getElementById('sum-name-text-' + sum.id_sumber);
        if (txt) txt.textContent = sum.nama_sumber;
        resetSumberForm();
      } else {
        ['pesanan-sumber', 'edit-id-sumber'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = document.createElement('option');
            opt.value = sum.id_sumber;
            opt.textContent = sum.nama_sumber;
            sel.appendChild(opt);
            sel.value = sum.id_sumber;
          }
        });
        const list = document.getElementById('sumber-modal-list');
        if (list) {
          const row = document.createElement('div');
          row.id = 'sum-row-' + sum.id_sumber;
          row.className = 'flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs';
          const safeName = sum.nama_sumber.replace(/'/g, "\\'");
          row.innerHTML = '<span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#' + sum.id_sumber + '</span><span id="sum-name-text-' + sum.id_sumber + '">' + sum.nama_sumber + '</span></span>' +
            '<div class="flex items-center gap-1">' +
            '<button type="button" onclick="startEditSumber(' + sum.id_sumber + ', \'' + safeName + '\')" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>' +
            '<button type="button" onclick="deleteSumberModal(' + sum.id_sumber + ')" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>' +
            '</div>';
          list.appendChild(row);
        }
        resetSumberForm();
      }
    } catch (err) {
      alert('Terjadi kesalahan jaringan saat menyimpan sumber inisiasi.');
    }
  });

  async function deleteSumberModal(id) {
    if (!confirm('Hapus sumber inisiasi ini?')) return;
    try {
      const res = await fetch('/sumber/' + id + '/delete', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menghapus sumber inisiasi.');
        return;
      }
      ['pesanan-sumber', 'edit-id-sumber'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (sel) {
          const opt = sel.querySelector('option[value="' + id + '"]');
          if (opt) opt.remove();
        }
      });
      const row = document.getElementById('sum-row-' + id);
      if (row) row.remove();
    } catch (err) {
      alert('Terjadi kesalahan jaringan.');
    }
  }

  // Default 4 size rows (S, M, L, XL) for New Pola registration
  let newPolaSpecs = [
    { nama_ukuran: 'S', lingkar_dada: 96, panjang_baju_celana: 68, lebar_baju: 48, panjang_pundak: 13.5, panjang_tangan: 21, lingkar_lengan: 42, diameter_leher: 17.5, lingkar_pinggang: 94, lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' },
    { nama_ukuran: 'M', lingkar_dada: 102, panjang_baju_celana: 70, lebar_baju: 51, panjang_pundak: 14.5, panjang_tangan: 22, lingkar_lengan: 44, diameter_leher: 18.0, lingkar_pinggang: 100, lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' },
    { nama_ukuran: 'L', lingkar_dada: 108, panjang_baju_celana: 72, lebar_baju: 54, panjang_pundak: 15.5, panjang_tangan: 23.5, lingkar_lengan: 46, diameter_leher: 18.5, lingkar_pinggang: 106, lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' },
    { nama_ukuran: 'XL', lingkar_dada: 114, panjang_baju_celana: 74, lebar_baju: 57, panjang_pundak: 16.5, panjang_tangan: 25, lingkar_lengan: 48, diameter_leher: 19.0, lingkar_pinggang: 112, lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' }
  ];

  let newPolaAccs = [
    { id_aksesoris: availableAksesoris[0] ? availableAksesoris[0].id_aksesoris : 1, jumlah_digunakan: 1 }
  ];

  window.setPolaMode = function (mode) {
    document.getElementById('inp-pola-mode').value = mode;
    const secExisting = document.getElementById('section-pola-existing');
    const secNew = document.getElementById('section-pola-new');
    const btnExisting = document.getElementById('btn-mode-existing');
    const btnNew = document.getElementById('btn-mode-new');

    if (mode === 'new') {
      secExisting.classList.add('hidden');
      secNew.classList.remove('hidden');
      btnNew.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all bg-white text-indigo-700 shadow-sm inline-flex items-center gap-1.5';
      btnExisting.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5';
      renderNewPolaSpecs();
      renderNewPolaAccs();
    } else {
      secNew.classList.add('hidden');
      secExisting.classList.remove('hidden');
      btnExisting.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all bg-white text-indigo-700 shadow-sm inline-flex items-center gap-1.5';
      btnNew.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5';
    }
    if (window.lucide) lucide.createIcons();
  };

  function renderNewPolaSpecs() {
    const tbody = document.getElementById('tbody-new-pola-specs');
    if (!tbody) return;
    const fields = ['lingkar_dada', 'panjang_baju_celana', 'lebar_baju', 'panjang_pundak', 'panjang_tangan', 'lingkar_lengan', 'diameter_leher', 'lingkar_pinggang', 'lingkar_selangkang', 'lingkar_paha', 'lebar_kaki'];

    tbody.innerHTML = newPolaSpecs.map(function (sp, idx) {
      const metricInputs = fields.map(function (f) {
        return `<td class="p-1"><input type="number" step="0.1" value="${sp[f] !== undefined && sp[f] !== null ? sp[f] : ''}" data-idx="${idx}" data-field="${f}" class="inp-spec-metric w-14 text-center font-mono text-[11px] p-1 border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500" placeholder="-"></td>`;
      }).join('');

      return `
        <tr>
          <td class="p-1.5">
            <input type="text" value="${sp.nama_ukuran}" data-idx="${idx}" data-field="nama_ukuran" class="inp-spec-metric w-14 font-mono font-bold text-indigo-700 text-center text-[11px] p-1 border border-slate-200 rounded bg-indigo-50/40">
          </td>
          ${metricInputs}
          <td class="p-1 text-center">
            <button type="button" onclick="removeNewPolaSizeRow(${idx})" class="text-slate-400 hover:text-rose-600 p-1" ${newPolaSpecs.length <= 1 ? 'disabled' : ''}>
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    tbody.querySelectorAll('.inp-spec-metric').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const idx = Number(this.dataset.idx);
        const f = this.dataset.field;
        if (newPolaSpecs[idx]) {
          newPolaSpecs[idx][f] = f === 'nama_ukuran' ? this.value : (this.value !== '' ? parseFloat(this.value) : null);
        }
      });
    });
    if (window.lucide) lucide.createIcons();
  }

  window.addNewPolaSizeRow = function () {
    newPolaSpecs.push({ nama_ukuran: 'XXL', lingkar_dada: 120, panjang_baju_celana: 76, lebar_baju: 60, panjang_pundak: 17.5, panjang_tangan: 26, lingkar_lengan: 50, diameter_leher: 19.5, lingkar_pinggang: 118, lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' });
    renderNewPolaSpecs();
  };

  window.removeNewPolaSizeRow = function (idx) {
    if (newPolaSpecs.length <= 1) return;
    newPolaSpecs.splice(idx, 1);
    renderNewPolaSpecs();
  };

  function renderNewPolaAccs() {
    const container = document.getElementById('container-new-pola-acc');
    if (!container) return;

    container.innerHTML = newPolaAccs.map(function (item, idx) {
      const opts = availableAksesoris.map(function (a) {
        return `<option value="${a.id_aksesoris}" ${Number(item.id_aksesoris) === Number(a.id_aksesoris) ? 'selected' : ''}>${a.nama_aksesoris} (${a.ukuran || '-'}) - Stok: ${a.stok} ${a.satuan}</option>`;
      }).join('');

      return `
        <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200">
          <select data-idx="${idx}" class="sel-new-acc flex-1 text-xs p-1.5 border border-slate-200 rounded">
            ${opts}
          </select>
          <div class="flex items-center gap-1.5">
            <span class="text-[11px] text-slate-500">Kebutuhan/Pcs:</span>
            <input type="number" min="1" value="${item.jumlah_digunakan || 1}" data-idx="${idx}" class="inp-new-acc-qty w-16 text-xs font-mono p-1.5 border border-slate-200 rounded text-center">
          </div>
          <button type="button" onclick="removeNewPolaAccRow(${idx})" class="p-1.5 text-slate-400 hover:text-rose-600">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
          </button>
        </div>
      `;
    }).join('');

    container.querySelectorAll('.sel-new-acc').forEach(function (sel) {
      sel.addEventListener('change', function () {
        const idx = Number(this.dataset.idx);
        if (newPolaAccs[idx]) newPolaAccs[idx].id_aksesoris = Number(this.value);
      });
    });
    container.querySelectorAll('.inp-new-acc-qty').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const idx = Number(this.dataset.idx);
        if (newPolaAccs[idx]) newPolaAccs[idx].jumlah_digunakan = parseInt(this.value) || 1;
      });
    });
    if (window.lucide) lucide.createIcons();
  }

  window.addNewPolaAccRow = function () {
    if (!availableAksesoris.length) return;
    newPolaAccs.push({ id_aksesoris: availableAksesoris[0].id_aksesoris, jumlah_digunakan: 1 });
    renderNewPolaAccs();
  };

  window.removeNewPolaAccRow = function (idx) {
    newPolaAccs.splice(idx, 1);
    renderNewPolaAccs();
  };

  window.saveNewPolaStandalone = function () {
    const idKategori = Number(document.getElementById('new-pola-kategori').value);
    const kodePola = document.getElementById('new-pola-kode').value.trim();
    const namaPola = document.getElementById('new-pola-nama').value.trim();

    if (!kodePola || !namaPola) {
      alert('Harap isi Kode Pola Unik dan Nama Master Pola Pakaian terlebih dahulu.');
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch("{{ route('pola.store', [], false) }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        id_kategori: idKategori,
        kode_pola: kodePola,
        nama_pola: namaPola,
        spesifikasi: newPolaSpecs,
        aksesoris: newPolaAccs
      })
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        alert(data.message || 'Master Pola baru berhasil didaftarkan!');
        window.location.reload();
      } else {
        alert('Gagal menyimpan pola: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan master pola.');
    });
  };

  // Toggle Nama Mitra based on Sumber Inisiasi in Create Form
  const sumberSelect = document.getElementById('pesanan-sumber');
  const fieldMitra = document.getElementById('field-nama-mitra');
  const inputMitra = document.getElementById('input-nama-mitra');

  function syncCreateMitraField() {
    if (!sumberSelect || !fieldMitra) return;
    const isMitra = (sumberSelect.value === '1');
    fieldMitra.style.display = isMitra ? '' : 'none';
    if (!isMitra && inputMitra) inputMitra.value = '';
  }
  if (sumberSelect) {
    sumberSelect.addEventListener('change', syncCreateMitraField);
    syncCreateMitraField();
  }

  // Set default deadline_awal to +14 days
  const dlAwalInput = document.getElementById('inp-deadline-awal');
  if (dlAwalInput && !dlAwalInput.value) {
    const d = new Date();
    d.setDate(d.getDate() + 14);
    dlAwalInput.value = d.toISOString().split('T')[0];
  }

  // Toggle Nama Mitra based on Sumber Inisiasi in Edit Form
  const editSumberSelect = document.getElementById('edit-id-sumber');
  const editFieldMitra = document.getElementById('edit-field-nama-mitra');
  const editInputMitra = document.getElementById('edit-nama-mitra');

  function syncEditMitraField() {
    if (!editSumberSelect || !editFieldMitra) return;
    const isMitra = (editSumberSelect.value === '1');
    editFieldMitra.style.display = isMitra ? '' : 'none';
    if (!isMitra && editInputMitra) editInputMitra.value = '';
  }
  if (editSumberSelect) {
    editSumberSelect.addEventListener('change', syncEditMitraField);
  }

  function formatDateVal(val) {
    if (!val) return '';
    return String(val).substring(0, 10);
  }

  // Open Edit Pesanan Modal
  window.openEditPesananModal = function (order) {
    if (!order) return;
    document.getElementById('modal-edit-title').textContent = 'Edit Pesanan #' + order.id_pesanan;
    document.getElementById('edit-id-pesanan').value = order.id_pesanan;
    document.getElementById('edit-id-sumber').value = order.id_sumber || '1';
    document.getElementById('edit-nama-mitra').value = order.nama_mitra || '';
    document.getElementById('edit-id-pola').value = order.id_pola || '';
    document.getElementById('edit-jenis-pesanan').value = order.jenis_pesanan || '';
    document.getElementById('edit-id-penerimaan').value = order.id_penerimaan || '';
    document.getElementById('edit-tgl-order').value = formatDateVal(order.tgl_order) || new Date().toISOString().split('T')[0];
    document.getElementById('edit-deadline-awal').value = formatDateVal(order.deadline_awal);
    document.getElementById('edit-deadline-terbaru').value = formatDateVal(order.deadline_terbaru || order.deadline_awal);
    document.getElementById('edit-status-pesanan').value = order.status_pesanan || 'Antrean';
    document.getElementById('edit-alasan-perubahan').value = order.alasan_perubahan_deadline || '';

    syncEditMitraField();

    const modal = document.getElementById('modal-edit-pesanan');
    if (modal) modal.classList.remove('hidden');

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  };

  window.closeEditPesananModal = function () {
    const modal = document.getElementById('modal-edit-pesanan');
    if (modal) modal.classList.add('hidden');
  };

  document.getElementById('modal-edit-pesanan')?.addEventListener('click', function (e) {
    if (e.target === this) {
      closeEditPesananModal();
    }
  });

  // Handle Edit Form Submission
  document.getElementById('form-edit-pesanan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const idPesanan = formData.get('id_pesanan');

    const payload = {
      id_sumber: Number(formData.get('id_sumber')),
      nama_mitra: formData.get('nama_mitra') || null,
      id_pola: Number(formData.get('id_pola')),
      jenis_pesanan: formData.get('jenis_pesanan'),
      id_penerimaan: formData.get('id_penerimaan') ? Number(formData.get('id_penerimaan')) : null,
      tgl_order: formData.get('tgl_order'),
      deadline_awal: formData.get('deadline_awal'),
      deadline_terbaru: formData.get('deadline_terbaru') || formData.get('deadline_awal'),
      status_pesanan: formData.get('status_pesanan'),
      alasan_perubahan_deadline: formData.get('alasan_perubahan_deadline') || null
    };

    if (!payload.deadline_awal) {
      alert('Tanggal Deadline Awal wajib diisi.');
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/pesanan/' + idPesanan + '/update', {
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
        alert(data.message || 'Pesanan berhasil diperbarui!');
        window.location.reload();
      } else {
        alert('Gagal memperbarui pesanan: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat memproses pembaruan pesanan.');
    });
  });

  // Handle Create Form Submission
  document.getElementById('form-pesanan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const polaMode = document.getElementById('inp-pola-mode').value;

    const deadlineAwal = formData.get('deadline_awal');
    const tglOrder = formData.get('tgl_order') || new Date().toISOString().split('T')[0];

    if (!deadlineAwal) {
      alert('Tanggal Deadline Awal wajib diisi untuk penjadwalan antrean produksi.');
      return;
    }

    const payload = {
      pola_mode: polaMode,
      id_sumber: Number(formData.get('id_sumber')),
      nama_mitra: formData.get('nama_mitra') || null,
      id_pola: Number(formData.get('id_pola')),
      jenis_pesanan: formData.get('jenis_pesanan'),
      id_penerimaan: formData.get('id_penerimaan') ? Number(formData.get('id_penerimaan')) : null,
      id_user_admin: 1,
      tgl_order: tglOrder,
      deadline_awal: deadlineAwal
    };

    if (polaMode === 'new') {
      const kodePola = document.getElementById('new-pola-kode').value.trim();
      const namaPola = document.getElementById('new-pola-nama').value.trim();
      if (!kodePola || !namaPola) {
        alert('Harap isi Kode Pola Unik dan Nama Master Pola pada form Pola Baru.');
        return;
      }
      payload.new_pola = {
        id_kategori: Number(document.getElementById('new-pola-kategori').value),
        kode_pola: kodePola,
        nama_pola: namaPola,
        spesifikasi: newPolaSpecs,
        aksesoris: newPolaAccs
      };
    }

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

  window.openEditPolaModal = function (pola) {
    document.getElementById('edit-pola-id').value = pola.id_pola;
    document.getElementById('edit-pola-kategori').value = pola.id_kategori;
    document.getElementById('edit-pola-kode').value = pola.kode_pola || '';
    document.getElementById('edit-pola-nama').value = pola.nama_pola || '';
    document.getElementById('edit-pola-ukuran').value = pola.nama_ukuran || 'L';
    document.getElementById('edit-pola-dada').value = pola.lingkar_dada ?? '';
    document.getElementById('edit-pola-panjang').value = pola.panjang_baju_celana ?? '';
    document.getElementById('edit-pola-pundak').value = pola.panjang_pundak ?? '';
    document.getElementById('modal-edit-pola').classList.remove('hidden');
  };

  window.closeEditPolaModal = function () {
    document.getElementById('modal-edit-pola').classList.add('hidden');
  };

  document.getElementById('form-edit-pola')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('edit-pola-id').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      id_kategori: Number(document.getElementById('edit-pola-kategori').value),
      kode_pola: document.getElementById('edit-pola-kode').value.trim(),
      nama_pola: document.getElementById('edit-pola-nama').value.trim(),
      nama_ukuran: document.getElementById('edit-pola-ukuran').value.trim() || 'L',
      lingkar_dada: document.getElementById('edit-pola-dada').value !== '' ? parseFloat(document.getElementById('edit-pola-dada').value) : null,
      panjang_baju_celana: document.getElementById('edit-pola-panjang').value !== '' ? parseFloat(document.getElementById('edit-pola-panjang').value) : null,
      panjang_pundak: document.getElementById('edit-pola-pundak').value !== '' ? parseFloat(document.getElementById('edit-pola-pundak').value) : null
    };

    fetch('/pola/' + id + '/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Master Pola berhasil diperbarui.');
        window.location.reload();
      } else {
        alert('Gagal memperbarui pola: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan perubahan master pola.');
    });
  });
</script>
@endpush
@endsection
