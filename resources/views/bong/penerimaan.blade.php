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

  <!-- Form Penerimaan Bahan Multi-Roll -->
  <form id="form-penerimaan" class="space-y-6">
    @csrf

    <!-- Card 1: Data Nota & Informasi Supplier -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i data-lucide="file-text" class="w-4 h-4"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">1. Data Nota & Informasi Supplier</h3>
            <p class="text-[11px] text-slate-500">Pencatatan identitas surat jalan dan supplier kain (<code class="font-mono text-indigo-600">Penerimaan_Bahan</code>)</p>
          </div>
        </div>
        <button type="button" onclick="openSupplierModal()" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold rounded-lg inline-flex items-center gap-1.5 transition-colors">
          <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
          + Tambah / Edit Supplier
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Supplier Kain <span class="text-rose-500">*</span></label>
            <div class="flex items-center gap-2">
              <button type="button" onclick="openSupplierModal()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Supplier Baru</button>
              <span class="text-slate-300">|</span>
              <button type="button" onclick="editCurrentSelectedSupplier('sel-supplier')" class="text-[11px] font-semibold text-amber-600 hover:underline">Edit Supplier</button>
            </div>
          </div>
          <select name="id_supplier" id="sel-supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
            @foreach($suppliers ?? [] as $sp)
              <option value="{{ $sp->id_supplier }}" data-kontak="{{ $sp->kontak ?? '' }}" data-alamat="{{ $sp->alamat ?? '' }}">{{ $sp->nama_supplier }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Nota / Surat Jalan <span class="text-rose-500">*</span></label>
          <input type="text" name="no_nota" id="inp-no-nota" placeholder="Contoh: NOTA-2026-99" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Nota <span class="text-rose-500">*</span></label>
          <input type="date" name="tgl_nota" id="inp-tgl-nota" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama / Jenis Kain <span class="text-rose-500">*</span></label>
          <input type="text" name="nama_kain" id="inp-nama-kain" placeholder="Contoh: Cotton Combed 30s Reaktif" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Lebar / Gramasi</label>
          <input type="text" name="lebar_gramasi" id="inp-lebar-gramasi" placeholder="Gramasi 145 / Lebar 42 inch" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Kendaraan</label>
          <input type="text" name="no_kendaraan" id="inp-no-kendaraan" placeholder="B 1234 XYZ" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="col-span-full md:col-span-1">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Petugas Penerima Gudang</label>
          <input type="text" name="penerima" id="inp-penerima" placeholder="Contoh: Dedi Kurniawan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    <!-- Card 2: Kelompok Variasi Warna Kain -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i data-lucide="palette" class="w-4 h-4"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">2. Kelompok Variasi Warna Kain</h3>
            <p class="text-[11px] text-slate-500">Satu nota penerimaan dapat memuat satu atau lebih variasi warna (<code class="font-mono text-indigo-600">Detail_Bahan_Warna</code>)</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="p-2 px-3.5 rounded-lg bg-indigo-50/70 border border-indigo-100 flex items-center gap-2.5">
            <span class="text-[10px] font-semibold tracking-wider uppercase text-indigo-700">RINGKASAN FISIK:</span>
            <span id="summary-rolls-count" class="font-mono text-xs font-bold text-slate-900">0 Roll Terdaftar</span>
            <span class="text-slate-300">·</span>
            <span id="summary-rolls-weight" class="font-mono text-xs font-bold text-indigo-700">Total: 0.00 Kg</span>
          </div>
          <button type="button" id="btn-add-color-group" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            Tambah Kelompok Warna
          </button>
        </div>
      </div>

      <div id="color-groups-container" class="space-y-3">
        <!-- Rendered dynamically by JavaScript -->
      </div>
    </div>

    <!-- Card 3: Rincian Gulungan Kain Fisik (Individual Roll Tracking) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i data-lucide="cylinder" class="w-4 h-4"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">3. Rincian Gulungan Kain Fisik (Individual Roll Tracking)</h3>
            <p class="text-[11px] text-slate-500">Setiap baris mencatat 1 roll fisik beserta ukuran aktual (<code class="font-mono text-indigo-600">Detail_Roll_Bahan</code>)</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button type="button" id="btn-add-5-rolls" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="copy-plus" class="w-3.5 h-3.5"></i>
            + Tambah 5 Roll Sekaligus
          </button>
          <button type="button" id="btn-add-roll-row" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg inline-flex items-center gap-1.5 shadow-sm transition-colors">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            + Tambah Roll Baru
          </button>
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
            <tr>
              <th class="py-2.5 pl-4 text-left w-28">No. Roll Ke</th>
              <th class="py-2.5 px-3 text-left w-36">Integer PK (Auto)</th>
              <th class="py-2.5 px-3 text-left">Kelompok Warna</th>
              <th class="py-2.5 px-3 text-left w-56">Ukuran Roll *</th>
              <th class="py-2.5 px-3 text-center w-28">Status Roll</th>
              <th class="py-2.5 pr-4 text-center w-16">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbl-draft-rolls-body" class="divide-y divide-slate-100 bg-white">
            <!-- Rendered dynamically by JavaScript -->
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <button type="button" id="btn-add-roll-row-bottom" class="w-full py-3 rounded-lg border-2 border-dashed border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/30 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition-colors flex items-center justify-center gap-2">
          <i data-lucide="plus-circle" class="w-4 h-4"></i>
          + Tambah Roll Kain Baru (Add Row)
        </button>
      </div>
    </div>

    <!-- Bottom Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button type="button" id="btn-reset-form" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors inline-flex items-center gap-1.5">
        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
        Reset Form
      </button>
      <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors inline-flex items-center gap-1.5">
        <i data-lucide="save" class="w-4 h-4"></i>
        Simpan Penerimaan & Registrasi Roll
      </button>
    </div>
  </form>

  <!-- Daftar Nota Penerimaan & Roll -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Daftar Penerimaan Kain & Roll Bahan Fisik</h3>
        <p class="text-xs text-slate-500 mt-0.5">Seluruh roll kain fisik individu terdaftar dalam database (<code class="font-mono text-indigo-600">Detail_Roll_Bahan</code>)</p>
      </div>
      <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full border border-indigo-100">
        Total: {{ count($penerimaan ?? []) }} Nota Masuk
      </span>
    </div>

    <div class="space-y-4">
      @forelse($penerimaan ?? [] as $pn)
      @php
        $tglNotaFormatted = $pn->tgl_nota ? (is_string($pn->tgl_nota) ? substr($pn->tgl_nota, 0, 10) : $pn->tgl_nota->format('Y-m-d')) : '';
      @endphp
      <div class="searchable-card p-4 bg-slate-50 rounded-xl border border-slate-200 transition-all">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <span class="font-mono font-bold text-sm text-indigo-600">{{ $pn->no_nota }}</span>
            <span class="text-xs font-semibold text-slate-700 ml-2">{{ $pn->nama_kain }}</span>
            @if($pn->lebar_gramasi)
              <span class="text-[11px] text-slate-500 ml-1">({{ $pn->lebar_gramasi }})</span>
            @endif
            <span class="text-[11px] text-slate-400 block">
              {{ $pn->supplier->nama_supplier ?? '-' }} &bull; {{ $tglNotaFormatted }}
              @if($pn->penerima) &bull; Penerima: {{ $pn->penerima }} @endif
            </span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-mono bg-white px-2.5 py-1 rounded-md border border-slate-200 text-slate-600">
              Kendaraan: {{ $pn->no_kendaraan ?? '-' }}
            </span>
            <button type="button"
              onclick="openEditNotaModal({
                id_penerimaan: {{ $pn->id_penerimaan }},
                id_supplier: {{ $pn->id_supplier }},
                no_nota: '{{ addslashes($pn->no_nota) }}',
                tgl_nota: '{{ $tglNotaFormatted }}',
                nama_kain: '{{ addslashes($pn->nama_kain) }}',
                lebar_gramasi: '{{ addslashes($pn->lebar_gramasi ?? '') }}',
                no_kendaraan: '{{ addslashes($pn->no_kendaraan ?? '') }}',
                penerima: '{{ addslashes($pn->penerima ?? '') }}'
              })"
              class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md text-xs font-semibold inline-flex items-center gap-1 transition-colors">
              <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Nota
            </button>
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @foreach($pn->detailBahanWarna ?? [] as $bw)
          <div class="bg-white p-3 rounded-lg border border-slate-200 text-xs">
            <div class="flex items-center justify-between gap-1">
              <div>
                <span class="font-semibold text-slate-800">{{ $bw->nama_warna }}</span>
                @if($bw->kode_warna)
                  <span class="text-[10px] font-mono text-slate-400 ml-1">({{ $bw->kode_warna }})</span>
                @endif
              </div>
              <div class="flex items-center gap-1">
                <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">{{ $bw->satuan_ukur }}</span>
                <button type="button"
                  onclick="openEditWarnaModal({
                    id_detail_warna: {{ $bw->id_detail_warna }},
                    nama_warna: '{{ addslashes($bw->nama_warna) }}',
                    kode_warna: '{{ addslashes($bw->kode_warna ?? '') }}',
                    satuan_ukur: '{{ $bw->satuan_ukur }}'
                  })"
                  class="px-1.5 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 rounded text-[10px] font-semibold" title="Edit kelompok warna">
                  Edit Warna
                </button>
                <button type="button"
                  onclick="openAddRollExistingModal({{ $bw->id_detail_warna }}, '{{ addslashes($bw->nama_warna) }}', '{{ $bw->satuan_ukur }}')"
                  class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[10px] font-semibold" title="Tambah roll ke warna ini">
                  + Roll
                </button>
              </div>
            </div>
            <div class="mt-1.5 text-[11px] text-slate-500">
              Total: {{ $bw->total_roll }} roll &bull; {{ $bw->total_ukuran }} {{ $bw->satuan_ukur }}
            </div>
            <div class="mt-2 space-y-1">
              @foreach($bw->detailRollBahan ?? [] as $roll)
              <div class="flex items-center justify-between text-[10px] font-mono bg-slate-50 p-1.5 rounded border border-slate-100">
                <span>Roll #{{ $roll->no_roll_ke }} (#{{ $roll->id_roll }})</span>
                <span>Sisa: <b>{{ $roll->ukuran_sisa }}</b> / {{ $roll->ukuran_awal }} {{ $roll->satuan }}</span>
                <div class="flex items-center gap-1">
                  <span class="px-1.5 py-0.5 rounded font-semibold {{ $roll->status_roll === 'Tersedia' ? 'bg-emerald-100 text-emerald-800' : ($roll->status_roll === 'Dipotong' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ $roll->status_roll }}
                  </span>
                  <button type="button"
                    onclick="openEditRollModal({
                      id_roll: {{ $roll->id_roll }},
                      no_roll_ke: {{ $roll->no_roll_ke }},
                      ukuran_awal: {{ $roll->ukuran_awal }},
                      ukuran_sisa: {{ $roll->ukuran_sisa }},
                      satuan: '{{ $roll->satuan }}',
                      status_roll: '{{ $roll->status_roll }}'
                    })"
                    class="px-1.5 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded font-sans font-semibold" title="Edit Roll #{{ $roll->no_roll_ke }}">
                    Edit
                  </button>
                </div>
              </div>
              @endforeach
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @empty
      <p class="text-center text-slate-400 text-xs py-8">Belum ada nota penerimaan bahan terdaftar.</p>
      @endforelse
    </div>
  </div>
</div>

<!-- Modal Edit Nota Penerimaan -->
<div id="modal-edit-nota" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Nota Penerimaan Bahan</h3>
        <p class="text-xs text-slate-500">Revisi informasi surat jalan, supplier, atau jenis kain (<code class="font-mono text-indigo-600">Penerimaan_Bahan</code>)</p>
      </div>
      <button type="button" onclick="closeModal('modal-edit-nota')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-edit-nota" class="space-y-4">
      <input type="hidden" id="edit-nota-id">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Supplier Kain <span class="text-rose-500">*</span></label>
            <div class="flex items-center gap-1.5">
              <button type="button" onclick="openSupplierModal()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Baru</button>
              <span class="text-slate-300">|</span>
              <button type="button" onclick="editCurrentSelectedSupplier('edit-nota-supplier')" class="text-[11px] font-semibold text-amber-600 hover:underline">Edit</button>
            </div>
          </div>
          <select id="edit-nota-supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white">
            @foreach($suppliers ?? [] as $sp)
              <option value="{{ $sp->id_supplier }}" data-kontak="{{ $sp->kontak ?? '' }}" data-alamat="{{ $sp->alamat ?? '' }}">{{ $sp->nama_supplier }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Nota / Surat Jalan <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-nota-no" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Nota <span class="text-rose-500">*</span></label>
          <input type="date" id="edit-nota-tgl" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama / Jenis Kain <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-nota-kain" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Lebar / Gramasi</label>
          <input type="text" id="edit-nota-gramasi" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Kendaraan</label>
          <input type="text" id="edit-nota-kendaraan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Petugas Penerima Gudang</label>
          <input type="text" id="edit-nota-penerima" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-nota')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Perubahan Nota</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Kelompok Warna (Detail_Bahan_Warna) -->
<div id="modal-edit-warna" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Kelompok Warna Kain</h3>
        <p class="text-xs text-slate-500">Tabel <code class="font-mono text-indigo-600">Detail_Bahan_Warna</code></p>
      </div>
      <button type="button" onclick="closeModal('modal-edit-warna')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-edit-warna" class="space-y-4">
      <input type="hidden" id="edit-warna-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Warna Kain <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-warna-nama" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Lab / Lot Warna</label>
        <input type="text" id="edit-warna-kode" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono uppercase">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Ukur <span class="text-rose-500">*</span></label>
        <select id="edit-warna-satuan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white">
          <option value="Kg">Kg (Kilogram)</option>
          <option value="Yard">Yard</option>
          <option value="Meter">Meter</option>
        </select>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-warna')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Warna</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Roll Fisik (Detail_Roll_Bahan) -->
<div id="modal-edit-roll" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Data Roll Kain Fisik</h3>
        <p class="text-xs text-slate-500">Revisi ukuran awal, ukuran sisa, satuan, atau status roll (<code class="font-mono text-indigo-600">Detail_Roll_Bahan</code>)</p>
      </div>
      <button type="button" onclick="closeModal('modal-edit-roll')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-edit-roll" class="space-y-4">
      <input type="hidden" id="edit-roll-id">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Urut Roll <span class="text-rose-500">*</span></label>
          <input type="number" id="edit-roll-no" min="1" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Ukur <span class="text-rose-500">*</span></label>
          <select id="edit-roll-satuan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white font-mono">
            <option value="Kg">Kg</option>
            <option value="Yard">Yard</option>
            <option value="Meter">Meter</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran Awal <span class="text-rose-500">*</span></label>
          <input type="number" step="0.01" min="0.1" id="edit-roll-awal" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran Sisa Aktual <span class="text-rose-500">*</span></label>
          <input type="number" step="0.01" min="0" id="edit-roll-sisa" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-indigo-700">
        </div>
        <div class="col-span-full">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Status Roll <span class="text-rose-500">*</span></label>
          <select id="edit-roll-status" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white font-semibold">
            <option value="Tersedia">Tersedia (Utuh)</option>
            <option value="Dipotong">Dipotong (Masih Ada Sisa)</option>
            <option value="Habis">Habis (0.00)</option>
          </select>
        </div>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-roll')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Perubahan Roll</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah Roll ke Warna yang Sudah Ada -->
<div id="modal-add-roll-existing" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Tambah Roll Baru ke Kelompok Warna</h3>
        <p id="add-roll-existing-subtitle" class="text-xs text-slate-500">Tambahkan gulungan fisik baru ke nota ini</p>
      </div>
      <button type="button" onclick="closeModal('modal-add-roll-existing')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-add-roll-existing" class="space-y-4">
      <input type="hidden" id="add-roll-warna-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran Awal Roll Baru <span class="text-rose-500">*</span></label>
        <input type="number" step="0.01" min="0.1" id="add-roll-ukuran-awal" value="25.00" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-add-roll-existing')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Tambahkan Roll</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah & Kelola Supplier -->
<div id="modal-add-supplier" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 id="sup-modal-title" class="font-bold text-slate-900 text-base">Tambah & Kelola Supplier</h3>
        <p class="text-xs text-slate-500">Registrasi dan revisi data supplier pada tabel <code class="font-mono text-indigo-600">Supplier</code></p>
      </div>
      <button type="button" onclick="closeSupplierModal()" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-add-supplier" class="space-y-3 mb-4">
      <input type="hidden" id="sup-edit-id" value="">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label id="sup-nama-label" class="block text-xs font-semibold text-slate-700 mb-1">Nama Supplier <span class="text-rose-500">*</span></label>
          <input type="text" id="sup-nama" required placeholder="Contoh: PT Kahatex Tekstil Prima" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak / No. Telepon</label>
          <input type="text" id="sup-kontak" placeholder="Contoh: 021-5551234 / 08123456789" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Supplier</label>
        <textarea id="sup-alamat" rows="2" placeholder="Contoh: Jl. Raya Rancaekek KM 23, Bandung" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="sup-cancel-edit-btn" onclick="resetSupplierForm()" class="hidden px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Batal Edit</button>
        <button type="submit" id="sup-submit-btn" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">+ Simpan Supplier Baru</button>
      </div>
    </form>

    <div class="border-t border-slate-100 pt-3">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Supplier Terdaftar (Klik Edit untuk Revisi)</p>
      <div id="supplier-modal-list" class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
        @foreach($suppliers ?? [] as $sp)
        <div id="sup-row-{{ $sp->id_supplier }}" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
          <div>
            <p id="sup-row-nama-{{ $sp->id_supplier }}" class="font-semibold text-slate-800">{{ $sp->nama_supplier }}</p>
            <p id="sup-row-meta-{{ $sp->id_supplier }}" class="text-[11px] text-slate-500 font-mono">{{ $sp->kontak ?? 'Tanpa kontak' }} {{ $sp->alamat ? '• ' . $sp->alamat : '' }}</p>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <button type="button" onclick="startEditSupplier({{ $sp->id_supplier }}, '{{ addslashes($sp->nama_supplier) }}', '{{ addslashes($sp->kontak ?? '') }}', '{{ addslashes($sp->alamat ?? '') }}')" class="px-2 py-1 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteSupplierItem({{ $sp->id_supplier }})" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="flex justify-end pt-3 mt-3 border-t border-slate-100">
      <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Tutup</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // Supplier Modal Functions (Add, Edit & Delete)
  function openSupplierModal() {
    resetSupplierForm();
    const m = document.getElementById('modal-add-supplier');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }
  function closeSupplierModal() {
    const m = document.getElementById('modal-add-supplier');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }
  function resetSupplierForm() {
    document.getElementById('sup-edit-id').value = '';
    document.getElementById('sup-nama').value = '';
    document.getElementById('sup-kontak').value = '';
    document.getElementById('sup-alamat').value = '';
    document.getElementById('sup-modal-title').textContent = 'Tambah & Kelola Supplier';
    document.getElementById('sup-nama-label').innerHTML = 'Nama Supplier <span class="text-rose-500">*</span>';
    document.getElementById('sup-submit-btn').textContent = '+ Simpan Supplier Baru';
    document.getElementById('sup-cancel-edit-btn').classList.add('hidden');
  }
  function startEditSupplier(id, nama, kontak, alamat) {
    document.getElementById('sup-edit-id').value = id;
    document.getElementById('sup-nama').value = nama || '';
    document.getElementById('sup-kontak').value = kontak || '';
    document.getElementById('sup-alamat').value = alamat || '';
    document.getElementById('sup-modal-title').textContent = 'Edit Data Supplier #' + id;
    document.getElementById('sup-nama-label').innerHTML = 'Edit Nama Supplier <span class="font-mono text-indigo-600">#' + id + '</span> <span class="text-rose-500">*</span>';
    document.getElementById('sup-submit-btn').textContent = 'Simpan Perubahan Supplier';
    document.getElementById('sup-cancel-edit-btn').classList.remove('hidden');
    const m = document.getElementById('modal-add-supplier');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('sup-nama').focus();
  }
  function editCurrentSelectedSupplier(selectId) {
    const sel = document.getElementById(selectId);
    if (!sel || !sel.value) {
      openSupplierModal();
      return;
    }
    const opt = sel.options[sel.selectedIndex];
    const id = sel.value;
    const nama = opt ? opt.textContent.trim() : '';
    const kontak = opt ? (opt.getAttribute('data-kontak') || '') : '';
    const alamat = opt ? (opt.getAttribute('data-alamat') || '') : '';
    startEditSupplier(id, nama, kontak, alamat);
  }
  async function deleteSupplierItem(id) {
    if (!confirm('Hapus supplier ini?')) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
      const res = await fetch('/supplier/' + id + '/delete', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menghapus supplier.');
        return;
      }
      ['sel-supplier', 'edit-nota-supplier'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (sel) {
          const opt = sel.querySelector('option[value="' + id + '"]');
          if (opt) opt.remove();
        }
      });
      const row = document.getElementById('sup-row-' + id);
      if (row) row.remove();
    } catch (e) {
      alert('Terjadi kesalahan jaringan saat menghapus supplier.');
    }
  }

  function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) {
      m.classList.add('hidden');
      m.classList.remove('flex');
    }
  }

  function openEditNotaModal(nota) {
    document.getElementById('edit-nota-id').value = nota.id_penerimaan;
    document.getElementById('edit-nota-supplier').value = nota.id_supplier;
    document.getElementById('edit-nota-no').value = nota.no_nota || '';
    document.getElementById('edit-nota-tgl').value = nota.tgl_nota ? String(nota.tgl_nota).split('T')[0].split(' ')[0] : '';
    document.getElementById('edit-nota-kain').value = nota.nama_kain || '';
    document.getElementById('edit-nota-gramasi').value = nota.lebar_gramasi || '';
    document.getElementById('edit-nota-kendaraan').value = nota.no_kendaraan || '';
    document.getElementById('edit-nota-penerima').value = nota.penerima || '';
    const m = document.getElementById('modal-edit-nota');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function openEditWarnaModal(warna) {
    document.getElementById('edit-warna-id').value = warna.id_detail_warna;
    document.getElementById('edit-warna-nama').value = warna.nama_warna || '';
    document.getElementById('edit-warna-kode').value = warna.kode_warna || '';
    document.getElementById('edit-warna-satuan').value = warna.satuan || 'Kg';
    const m = document.getElementById('modal-edit-warna');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function openEditRollModal(roll) {
    document.getElementById('edit-roll-id').value = roll.id_roll;
    document.getElementById('edit-roll-no').value = roll.no_roll_ke;
    document.getElementById('edit-roll-satuan').value = roll.satuan || 'Kg';
    document.getElementById('edit-roll-awal').value = Number(roll.ukuran_awal || 0).toFixed(2);
    document.getElementById('edit-roll-sisa').value = Number(roll.ukuran_sisa || 0).toFixed(2);
    document.getElementById('edit-roll-status').value = roll.status_roll || 'Tersedia';
    const m = document.getElementById('modal-edit-roll');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function openAddRollExistingModal(idWarna, namaWarna, satuan) {
    document.getElementById('add-roll-warna-id').value = idWarna;
    document.getElementById('add-roll-existing-subtitle').textContent = 'Warna: ' + namaWarna + ' (' + (satuan || 'Kg') + ')';
    const m = document.getElementById('modal-add-roll-existing');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  document.getElementById('form-edit-nota')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('edit-nota-id').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      id_supplier: Number(document.getElementById('edit-nota-supplier').value),
      no_nota: document.getElementById('edit-nota-no').value,
      tgl_nota: document.getElementById('edit-nota-tgl').value,
      nama_kain: document.getElementById('edit-nota-kain').value,
      lebar_gramasi: document.getElementById('edit-nota-gramasi').value || null,
      no_kendaraan: document.getElementById('edit-nota-kendaraan').value || null,
      penerima: document.getElementById('edit-nota-penerima').value || null
    };
    fetch(`/penerimaan/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Nota penerimaan berhasil diperbarui.');
        window.location.reload();
      } else {
        alert('Gagal memperbarui nota: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan perubahan nota.');
    });
  });

  document.getElementById('form-edit-warna')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('edit-warna-id').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      nama_warna: document.getElementById('edit-warna-nama').value,
      kode_warna: document.getElementById('edit-warna-kode').value || null,
      satuan_ukur: document.getElementById('edit-warna-satuan').value
    };
    fetch(`/penerimaan/warna/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Kelompok warna berhasil diperbarui.');
        window.location.reload();
      } else {
        alert('Gagal memperbarui warna: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan perubahan kelompok warna.');
    });
  });

  document.getElementById('form-edit-roll')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('edit-roll-id').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      no_roll_ke: Number(document.getElementById('edit-roll-no').value),
      satuan: document.getElementById('edit-roll-satuan').value,
      ukuran_awal: parseFloat(document.getElementById('edit-roll-awal').value),
      ukuran_sisa: parseFloat(document.getElementById('edit-roll-sisa').value),
      status_roll: document.getElementById('edit-roll-status').value
    };
    fetch(`/penerimaan/roll/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Data roll berhasil diperbarui.');
        window.location.reload();
      } else {
        alert('Gagal memperbarui roll: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan perubahan roll.');
    });
  });

  document.getElementById('form-add-roll-existing')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const idWarna = document.getElementById('add-roll-warna-id').value;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const payload = {
      ukuran_awal: parseFloat(document.getElementById('add-roll-ukuran-awal').value)
    };
    fetch(`/penerimaan/warna/${idWarna}/add-roll`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Roll baru berhasil ditambahkan.');
        window.location.reload();
      } else {
        alert('Gagal menambah roll: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menambahkan roll baru.');
    });
  });

  document.getElementById('form-add-supplier')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const editId = document.getElementById('sup-edit-id').value;
    const payload = {
      nama_supplier: document.getElementById('sup-nama').value.trim(),
      kontak: document.getElementById('sup-kontak').value.trim() || null,
      alamat: document.getElementById('sup-alamat').value.trim() || null
    };
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const url = editId ? (`/supplier/${editId}/update`) : "{{ route('supplier.store', [], false) }}";

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.data) {
        const sp = data.data;
        if (editId) {
          ['sel-supplier', 'edit-nota-supplier'].forEach(selId => {
            const sel = document.getElementById(selId);
            if (sel) {
              const opt = sel.querySelector('option[value="' + sp.id_supplier + '"]');
              if (opt) {
                opt.textContent = sp.nama_supplier;
                opt.setAttribute('data-kontak', sp.kontak || '');
                opt.setAttribute('data-alamat', sp.alamat || '');
              }
            }
          });
          const namaEl = document.getElementById('sup-row-nama-' + sp.id_supplier);
          if (namaEl) namaEl.textContent = sp.nama_supplier;
          const metaEl = document.getElementById('sup-row-meta-' + sp.id_supplier);
          if (metaEl) metaEl.textContent = (sp.kontak || 'Tanpa kontak') + (sp.alamat ? ' • ' + sp.alamat : '');
          resetSupplierForm();
          alert(data.message || 'Data supplier berhasil diperbarui!');
        } else {
          ['sel-supplier', 'edit-nota-supplier'].forEach(selId => {
            const sel = document.getElementById(selId);
            if (sel) {
              const opt = document.createElement('option');
              opt.value = sp.id_supplier;
              opt.textContent = sp.nama_supplier;
              opt.setAttribute('data-kontak', sp.kontak || '');
              opt.setAttribute('data-alamat', sp.alamat || '');
              opt.selected = true;
              sel.appendChild(opt);
            }
          });
          const list = document.getElementById('supplier-modal-list');
          if (list) {
            const row = document.createElement('div');
            row.id = 'sup-row-' + sp.id_supplier;
            row.className = 'flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs';
            const safeNama = (sp.nama_supplier || '').replace(/'/g, "\\'");
            const safeKontak = (sp.kontak || '').replace(/'/g, "\\'");
            const safeAlamat = (sp.alamat || '').replace(/'/g, "\\'");
            row.innerHTML = '<div><p id="sup-row-nama-' + sp.id_supplier + '" class="font-semibold text-slate-800">' + sp.nama_supplier + '</p>' +
              '<p id="sup-row-meta-' + sp.id_supplier + '" class="text-[11px] text-slate-500 font-mono">' + (sp.kontak || 'Tanpa kontak') + (sp.alamat ? ' • ' + sp.alamat : '') + '</p></div>' +
              '<div class="flex items-center gap-1 shrink-0">' +
              '<button type="button" onclick="startEditSupplier(' + sp.id_supplier + ', \'' + safeNama + '\', \'' + safeKontak + '\', \'' + safeAlamat + '\')" class="px-2 py-1 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>' +
              '<button type="button" onclick="deleteSupplierItem(' + sp.id_supplier + ')" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>' +
              '</div>';
            list.appendChild(row);
          }
          resetSupplierForm();
          alert(data.message || 'Supplier baru berhasil ditambahkan!');
        }
      } else {
        alert('Gagal menyimpan supplier: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan supplier.');
    });
  });

  // Initial State for Color Groups & Draft Rolls
  let colorGroups = [
    { id: 1, namaWarna: 'Hitam Reaktif', kodeWarna: 'BLK-30S-01', satuanUkur: 'Kg' }
  ];
  let nextColorGroupId = 2;

  let draftRolls = [
    { colorGroupId: 1, berat: 24.50 },
    { colorGroupId: 1, berat: 25.00 },
    { colorGroupId: 1, berat: 25.00 }
  ];

  // Refresh Lucide Icons Helper
  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  // Update Summary Counters & Color Group Badges
  function updateDraftRollsSummary() {
    const totalRolls = draftRolls.length;
    const totalsByUnit = {};

    draftRolls.forEach(function (r) {
      const cg = colorGroups.find(function (c) { return Number(c.id) === Number(r.colorGroupId); }) || colorGroups[0];
      const unit = cg ? (cg.satuanUkur || 'Kg') : 'Kg';
      totalsByUnit[unit] = (totalsByUnit[unit] || 0) + (parseFloat(r.berat) || 0);
    });

    const countEl = document.getElementById('summary-rolls-count');
    if (countEl) countEl.textContent = totalRolls + ' Roll Terdaftar';

    const weightEl = document.getElementById('summary-rolls-weight');
    if (weightEl) {
      const parts = Object.keys(totalsByUnit).map(function (u) {
        return totalsByUnit[u].toFixed(2) + ' ' + u;
      });
      const fallbackUnit = (colorGroups[0] && colorGroups[0].satuanUkur) ? colorGroups[0].satuanUkur : 'Kg';
      weightEl.textContent = 'Total: ' + (parts.length > 0 ? parts.join(' + ') : ('0.00 ' + fallbackUnit));
    }

    // Also update each color group badge in Card 2 dynamically
    colorGroups.forEach(function (cg) {
      const badgeEl = document.querySelector(`.badge-cg-summary[data-cg-id="${cg.id}"]`);
      if (badgeEl) {
        const rollsInGroup = draftRolls.filter(function (r) {
          return Number(r.colorGroupId) === Number(cg.id);
        });
        let totalSizeInGroup = 0;
        rollsInGroup.forEach(function (r) {
          totalSizeInGroup += (parseFloat(r.berat) || 0);
        });
        badgeEl.textContent = `${rollsInGroup.length} Roll (${totalSizeInGroup.toFixed(1)} ${cg.satuanUkur || 'Kg'})`;
      }
    });
  }

  // Render Color Groups in Card 2
  function renderColorGroups() {
    const container = document.getElementById('color-groups-container');
    if (!container) return;

    container.innerHTML = colorGroups.map(function (cg) {
      const rollsInGroup = draftRolls.filter(function (r) {
        return Number(r.colorGroupId) === Number(cg.id);
      });
      let totalSizeInGroup = 0;
      rollsInGroup.forEach(function (r) {
        totalSizeInGroup += (parseFloat(r.berat) || 0);
      });

      return `
        <div class="p-4 rounded-xl border border-slate-200 bg-white grid grid-cols-1 md:grid-cols-4 gap-4 items-end" data-cg-id="${cg.id}">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Warna Kain <span class="text-rose-500">*</span></label>
            <input type="text" value="${cg.namaWarna || ''}" class="inp-cg-nama w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="cth. Hitam Reaktif" data-cg-id="${cg.id}">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Lab / Lot Warna</label>
            <input type="text" value="${cg.kodeWarna || ''}" class="inp-cg-kode w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono uppercase focus:ring-2 focus:ring-indigo-500" placeholder="cth. BLK-30S-01" data-cg-id="${cg.id}">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Ukur</label>
            <select class="sel-cg-satuan w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" data-cg-id="${cg.id}">
              <option value="Kg" ${cg.satuanUkur === 'Kg' ? 'selected' : ''}>Kg (Kilogram)</option>
              <option value="Yard" ${cg.satuanUkur === 'Yard' ? 'selected' : ''}>Yard</option>
              <option value="Meter" ${cg.satuanUkur === 'Meter' ? 'selected' : ''}>Meter</option>
            </select>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="badge-cg-summary inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100" data-cg-id="${cg.id}">
              ${rollsInGroup.length} Roll (${totalSizeInGroup.toFixed(1)} ${cg.satuanUkur || 'Kg'})
            </span>
            ${colorGroups.length > 1 ? `
              <button type="button" class="btn-remove-cg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg inline-flex items-center gap-1 transition-colors" data-cg-id="${cg.id}">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
              </button>` : ''}
          </div>
        </div>
      `;
    }).join('');

    // Attach listeners for color group inputs
    container.querySelectorAll('.inp-cg-nama').forEach(function (input) {
      input.addEventListener('input', function () {
        const id = Number(this.dataset.cgId);
        const cg = colorGroups.find(function (c) { return c.id === id; });
        if (cg) cg.namaWarna = this.value;
        renderDraftRollsTable();
      });
    });

    container.querySelectorAll('.inp-cg-kode').forEach(function (input) {
      input.addEventListener('input', function () {
        const id = Number(this.dataset.cgId);
        const cg = colorGroups.find(function (c) { return c.id === id; });
        if (cg) cg.kodeWarna = this.value;
        renderDraftRollsTable();
      });
    });

    container.querySelectorAll('.sel-cg-satuan').forEach(function (sel) {
      sel.addEventListener('change', function () {
        const id = Number(this.dataset.cgId);
        const cg = colorGroups.find(function (c) { return c.id === id; });
        if (cg) cg.satuanUkur = this.value;
        renderColorGroups();
        renderDraftRollsTable();
      });
    });

    container.querySelectorAll('.btn-remove-cg').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const id = Number(this.dataset.cgId);
        removeColorGroup(id);
      });
    });

    refreshIcons();
  }

  // Render Draft Rolls Table in Card 3
  function renderDraftRollsTable() {
    const tbody = document.getElementById('tbl-draft-rolls-body');
    if (!tbody) return;

    tbody.innerHTML = draftRolls.map(function (r, i) {
      const colorOptions = colorGroups.map(function (cg) {
        const isSelected = Number(r.colorGroupId) === Number(cg.id);
        const label = (cg.namaWarna || 'Warna ' + cg.id) + (cg.kodeWarna ? ' (' + cg.kodeWarna + ')' : '');
        return `<option value="${cg.id}" ${isSelected ? 'selected' : ''}>${label}</option>`;
      }).join('');

      const activeCg = colorGroups.find(function (c) { return Number(c.id) === Number(r.colorGroupId); }) || colorGroups[0];
      const unitLabel = activeCg ? (activeCg.satuanUkur || 'Kg') : 'Kg';

      return `
        <tr class="hover:bg-slate-50/80 transition-colors" data-roll-idx="${i}">
          <td class="pl-4 py-2.5 font-bold font-mono text-slate-800">Roll #${i + 1}</td>
          <td class="px-3 py-2.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
              # id_roll: Auto
            </span>
          </td>
          <td class="px-3 py-2.5">
            <select class="sel-roll-color w-full h-8 text-xs p-1.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" data-roll-idx="${i}">
              ${colorOptions}
            </select>
          </td>
          <td class="px-3 py-2.5">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 bg-white">
              <input type="number" step="0.01" min="0.1" class="inp-roll-weight flex-1 min-w-0 h-8 text-xs font-mono px-2.5 py-1.5 border-0 focus:outline-none" data-roll-idx="${i}" value="${Number(r.berat).toFixed(2)}">
              <span class="px-2.5 bg-slate-50 border-l border-slate-200 text-[11px] text-slate-600 font-mono font-semibold flex items-center select-none">${unitLabel}</span>
            </div>
          </td>
          <td class="px-3 py-2.5 text-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
              • Tersedia
            </span>
          </td>
          <td class="pr-4 py-2.5 text-center">
            <button type="button" class="btn-remove-roll w-7 h-7 text-slate-400 hover:text-rose-600 rounded flex items-center justify-center transition-colors ${draftRolls.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''}" data-roll-idx="${i}" ${draftRolls.length <= 1 ? 'disabled' : ''} title="Hapus roll">
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    // Attach listeners for roll row changes
    tbody.querySelectorAll('.sel-roll-color').forEach(function (sel) {
      sel.addEventListener('change', function () {
        const idx = Number(this.dataset.rollIdx);
        if (draftRolls[idx]) {
          draftRolls[idx].colorGroupId = Number(this.value);
          renderColorGroups();
          renderDraftRollsTable();
        }
      });
    });

    tbody.querySelectorAll('.inp-roll-weight').forEach(function (inp) {
      inp.addEventListener('input', function () {
        const idx = Number(this.dataset.rollIdx);
        if (draftRolls[idx]) {
          draftRolls[idx].berat = parseFloat(this.value) || 0;
          updateDraftRollsSummary();
        }
      });
    });

    tbody.querySelectorAll('.btn-remove-roll').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const idx = Number(this.dataset.rollIdx);
        removeRollRow(idx);
      });
    });

    updateDraftRollsSummary();
    refreshIcons();
  }

  // Add Color Group
  function addColorGroup() {
    colorGroups.push({
      id: nextColorGroupId++,
      namaWarna: '',
      kodeWarna: '',
      satuanUkur: 'Kg'
    });
    renderColorGroups();
    renderDraftRollsTable();
  }

  // Remove Color Group
  function removeColorGroup(id) {
    if (colorGroups.length <= 1) return;
    colorGroups = colorGroups.filter(function (c) { return c.id !== id; });
    const fallbackId = colorGroups[0].id;
    draftRolls.forEach(function (r) {
      if (Number(r.colorGroupId) === Number(id)) {
        r.colorGroupId = fallbackId;
      }
    });
    renderColorGroups();
    renderDraftRollsTable();
  }

  // Add 1 Roll Row
  function addRollRow(weight) {
    const defaultColorId = colorGroups[0] ? colorGroups[0].id : 1;
    draftRolls.push({
      colorGroupId: defaultColorId,
      berat: weight !== undefined ? weight : 25.00
    });
    renderDraftRollsTable();
    renderColorGroups();
  }

  // Add 5 Rolls at Once
  function add5Rolls() {
    const defaultColorId = colorGroups[0] ? colorGroups[0].id : 1;
    for (let i = 0; i < 5; i++) {
      draftRolls.push({
        colorGroupId: defaultColorId,
        berat: 25.00
      });
    }
    renderDraftRollsTable();
    renderColorGroups();
  }

  // Remove 1 Roll Row
  function removeRollRow(index) {
    if (draftRolls.length <= 1) return;
    draftRolls.splice(index, 1);
    renderDraftRollsTable();
    renderColorGroups();
  }

  // Reset Form
  function resetPenerimaanForm() {
    colorGroups = [
      { id: 1, namaWarna: 'Hitam Reaktif', kodeWarna: 'BLK-30S-01', satuanUkur: 'Kg' }
    ];
    nextColorGroupId = 2;
    draftRolls = [
      { colorGroupId: 1, berat: 24.50 },
      { colorGroupId: 1, berat: 25.00 },
      { colorGroupId: 1, berat: 25.00 }
    ];
    document.getElementById('form-penerimaan').reset();
    setDefaultDateAndNota();
    renderColorGroups();
    renderDraftRollsTable();
  }

  // Set Default Date and Auto-Suggested Nota
  function setDefaultDateAndNota() {
    const tglInput = document.getElementById('inp-tgl-nota');
    if (tglInput && !tglInput.value) {
      tglInput.value = new Date().toISOString().split('T')[0];
    }
    const notaInput = document.getElementById('inp-no-nota');
    if (notaInput && !notaInput.value) {
      notaInput.value = 'NOTA-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000);
    }
  }

  // Initial Document Setup
  document.addEventListener('DOMContentLoaded', function () {
    setDefaultDateAndNota();
    renderColorGroups();
    renderDraftRollsTable();

    // Event Listeners for Buttons
    document.getElementById('btn-add-color-group')?.addEventListener('click', addColorGroup);
    document.getElementById('btn-add-roll-row')?.addEventListener('click', function () { addRollRow(25.00); });
    document.getElementById('btn-add-roll-row-bottom')?.addEventListener('click', function () { addRollRow(25.00); });
    document.getElementById('btn-add-5-rolls')?.addEventListener('click', add5Rolls);
    document.getElementById('btn-reset-form')?.addEventListener('click', resetPenerimaanForm);
  });

  // Handle Form Submission with Multi-Roll & Multi-Color Payload
  document.getElementById('form-penerimaan')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const idSupplier = Number(formData.get('id_supplier'));
    const noNota = formData.get('no_nota');
    const tglNota = formData.get('tgl_nota');
    const namaKain = formData.get('nama_kain');
    const lebarGramasi = formData.get('lebar_gramasi') || null;
    const noKendaraan = formData.get('no_kendaraan') || null;
    const penerima = formData.get('penerima') || null;

    if (!idSupplier || !noNota || !tglNota || !namaKain) {
      alert('Harap lengkapi semua kolom bertanda bintang (*) pada data nota.');
      return;
    }

    // Check each color group has a name
    for (let i = 0; i < colorGroups.length; i++) {
      if (!colorGroups[i].namaWarna || !colorGroups[i].namaWarna.trim()) {
        alert('Nama warna kain pada kelompok warna ke-' + (i + 1) + ' wajib diisi.');
        return;
      }
    }

    if (draftRolls.length === 0) {
      alert('Minimal harus ada 1 roll kain terdaftar.');
      return;
    }

    // Check each roll has positive size
    for (let i = 0; i < draftRolls.length; i++) {
      const w = parseFloat(draftRolls[i].berat);
      if (isNaN(w) || w <= 0) {
        alert('Ukuran roll pada baris Roll #' + (i + 1) + ' tidak valid (harus lebih dari 0).');
        return;
      }
    }

    // Group rolls by color group to match backend validation structure
    const warnaPayload = colorGroups.map(function (cg) {
      const rollsInGroup = draftRolls.filter(function (r) {
        return Number(r.colorGroupId) === Number(cg.id);
      });
      return {
        nama_warna: cg.namaWarna.trim(),
        kode_warna: cg.kodeWarna ? cg.kodeWarna.trim() : null,
        satuan_ukur: cg.satuanUkur || 'Kg',
        rolls: rollsInGroup.map(function (r) {
          return {
            ukuran_awal: parseFloat(r.berat)
          };
        })
      };
    }).filter(function (w) {
      return w.rolls.length > 0;
    });

    if (warnaPayload.length === 0) {
      alert('Tidak ada roll kain yang terdaftar pada kelompok warna.');
      return;
    }

    const payload = {
      id_supplier: idSupplier,
      no_nota: noNota,
      tgl_nota: tglNota,
      nama_kain: namaKain,
      lebar_gramasi: lebarGramasi,
      no_kendaraan: noKendaraan,
      penerima: penerima,
      warna: warnaPayload
    };

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch("{{ route('penerimaan.store', [], false) }}", {
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
        alert(data.message || 'Penerimaan kain & roll berhasil disimpan!');
        window.location.reload();
      } else {
        alert('Gagal menyimpan penerimaan: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat memproses penyimpanan data penerimaan bahan.');
    });
  });
</script>
@endpush
@endsection
