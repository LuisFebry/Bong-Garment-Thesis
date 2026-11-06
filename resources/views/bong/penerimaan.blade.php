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
          + Tambah Supplier
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-semibold text-slate-700">Supplier Kain <span class="text-rose-500">*</span></label>
            <button type="button" onclick="openSupplierModal()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Supplier Baru</button>
          </div>
          <select name="id_supplier" id="sel-supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
            @foreach($suppliers ?? [] as $sp)
              <option value="{{ $sp->id_supplier }}">{{ $sp->nama_supplier }}</option>
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
      <div class="searchable-card p-4 bg-slate-50 rounded-xl border border-slate-200 transition-all">
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
                <span>Roll #{{ $roll->no_roll_ke }} (#{{ $roll->id_roll }})</span>
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
      <p class="text-center text-slate-400 text-xs py-8">Belum ada nota penerimaan bahan terdaftar.</p>
      @endforelse
    </div>
  </div>
</div>

<!-- Modal Tambah Supplier Baru -->
<div id="modal-add-supplier" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Tambah Supplier Baru</h3>
        <p class="text-xs text-slate-500">Registrasi supplier kain/aksesoris ke tabel <code class="font-mono text-indigo-600">Supplier</code></p>
      </div>
      <button type="button" onclick="closeSupplierModal()" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <form id="form-add-supplier" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Supplier <span class="text-rose-500">*</span></label>
        <input type="text" id="sup-nama" required placeholder="Contoh: PT Kahatex Tekstil Prima" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak / No. Telepon</label>
        <input type="text" id="sup-kontak" placeholder="Contoh: 021-5551234 / 08123456789" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Supplier</label>
        <textarea id="sup-alamat" rows="2" placeholder="Contoh: Jl. Raya Rancaekek KM 23, Bandung" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Supplier</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  // Supplier Modal Functions
  function openSupplierModal() {
    const m = document.getElementById('modal-add-supplier');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }
  function closeSupplierModal() {
    const m = document.getElementById('modal-add-supplier');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  document.getElementById('form-add-supplier')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const payload = {
      nama_supplier: document.getElementById('sup-nama').value,
      kontak: document.getElementById('sup-kontak').value || null,
      alamat: document.getElementById('sup-alamat').value || null
    };
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch("{{ route('supplier.store', [], false) }}", {
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
        const sel = document.getElementById('sel-supplier');
        if (sel) {
          const opt = document.createElement('option');
          opt.value = data.data.id_supplier;
          opt.textContent = data.data.nama_supplier;
          opt.selected = true;
          sel.appendChild(opt);
        }
        closeSupplierModal();
        document.getElementById('form-add-supplier').reset();
        alert(data.message || 'Supplier baru berhasil ditambahkan!');
      } else {
        alert('Gagal menambah supplier: ' + (data.message || 'Validasi gagal'));
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
