@extends('layouts.app')

@section('title', 'Gudang & Stok Barang Jadi — Bong Garment')

@section('content')
<div class="space-y-6">
  <!-- Header Title and Actions -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Gudang & Stok Barang Jadi</h2>
      <p class="text-xs text-slate-500 mt-1">Manajemen inventori perpetual barang jadi hasil produksi dan stok fisik aksesoris (kancing, ritsleting, benang)</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" onclick="openModal('modal-add-aksesoris')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm flex items-center gap-1.5 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Tambah Aksesoris Baru</span>
      </button>
      <button type="button" onclick="openModal('modal-adjust-stok')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200 shadow-sm flex items-center gap-1.5 transition-colors">
        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
        <span>Penyesuaian Stok Jadi</span>
      </button>
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
              <th class="pb-2 text-right">Aksi Cepat</th>
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
              <td class="py-3 text-right">
                <div class="inline-flex items-center gap-1.5">
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, 100)" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[11px] rounded border border-indigo-200 transition-colors" title="Tambah 100 {{ $acc->satuan }}">
                    +100
                  </button>
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, 500)" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[11px] rounded border border-indigo-200 transition-colors" title="Tambah 500 {{ $acc->satuan }}">
                    +500
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="py-6 text-center text-slate-400">Belum ada data aksesoris.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Tambah Aksesoris Baru -->
<div id="modal-add-aksesoris" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h3 class="font-bold text-slate-900 text-base">Tambah Aksesoris Baru</h3>
      <button type="button" onclick="closeModal('modal-add-aksesoris')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-add-aksesoris" class="space-y-4 mt-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aksesoris</label>
        <input type="text" name="nama_aksesoris" placeholder="Contoh: Kancing Kemeja 4 Lubang" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran / Spesifikasi</label>
          <input type="text" name="ukuran" placeholder="Contoh: 18L, No. 5" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan</label>
          <select name="satuan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="Pcs">Pcs</option>
            <option value="Gross">Gross</option>
            <option value="Meter">Meter</option>
            <option value="Yard">Yard</option>
            <option value="Roll">Roll</option>
            <option value="Lusin">Lusin</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Awal Fisik</label>
        <input type="number" name="stok" min="0" value="0" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Rekanan</label>
        <select name="id_supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          <option value="" disabled selected>-- Pilih Supplier --</option>
          @foreach($suppliers ?? [] as $sup)
            <option value="{{ $sup->id_supplier }}">{{ $sup->nama_supplier }} ({{ $sup->kontak ?? '-' }})</option>
          @endforeach
        </select>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-add-aksesoris')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Aksesoris
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Penyesuaian Stok Barang Jadi -->
<div id="modal-adjust-stok" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h3 class="font-bold text-slate-900 text-base">Penyesuaian Stok Barang Jadi</h3>
      <button type="button" onclick="closeModal('modal-adjust-stok')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-adjust-stok" class="space-y-4 mt-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Item Stok Barang Jadi</label>
        <select id="select-adjust-stok" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          <option value="" disabled selected>-- Pilih Stok Barang Jadi --</option>
          @foreach($stok ?? [] as $st)
            <option value="{{ $st->id_stok }}" 
                    data-id-pola="{{ $st->id_pola }}" 
                    data-id-spesifikasi="{{ $st->id_spesifikasi }}" 
                    data-nama-warna="{{ $st->nama_warna }}"
                    data-current-stok="{{ $st->jumlah_stok_pcs }}">
              {{ $st->pola->nama_pola ?? 'Pola #'.$st->id_pola }} ({{ $st->spesifikasiUkuran->nama_ukuran ?? '-' }}) - {{ $st->nama_warna }} [Stok: {{ number_format($st->jumlah_stok_pcs) }} pcs]
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Perubahan Jumlah Pcs (+ / -)</label>
        <input type="number" id="input-delta-pcs" name="delta_pcs" placeholder="Misal: 50 untuk penambahan, -10 untuk pengurangan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        <p class="text-[11px] text-slate-400 mt-1">Gunakan angka positif untuk menambah stok fisik dan angka negatif untuk pengurangan.</p>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-adjust-stok')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Penyesuaian
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  // 1. Submit Add Aksesoris
  document.getElementById('form-add-aksesoris')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const payload = {
      nama_aksesoris: formData.get('nama_aksesoris'),
      ukuran: formData.get('ukuran') || null,
      satuan: formData.get('satuan'),
      stok: Number(formData.get('stok')),
      id_supplier: Number(formData.get('id_supplier'))
    };

    fetch("{{ route('aksesoris.store') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        alert(data.message || 'Aksesoris berhasil didaftarkan!');
        window.location.reload();
      } else {
        alert('Gagal mendaftarkan aksesoris: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat memproses permintaan.');
    });
  });

  // 2. Submit Adjust Stok Barang Jadi
  document.getElementById('form-adjust-stok')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const sel = document.getElementById('select-adjust-stok');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset.idPola) {
      alert('Pilih item stok barang jadi terlebih dahulu.');
      return;
    }

    const delta = Number(document.getElementById('input-delta-pcs').value);
    if (isNaN(delta) || delta === 0) {
      alert('Masukkan jumlah delta pcs yang valid (bukan 0).');
      return;
    }

    const payload = {
      id_pola: Number(opt.dataset.idPola),
      id_spesifikasi: Number(opt.dataset.idSpesifikasi),
      nama_warna: opt.dataset.namaWarna,
      delta_pcs: delta
    };

    fetch("{{ route('stok.adjust') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        alert(data.message || 'Stok barang jadi berhasil disesuaikan!');
        window.location.reload();
      } else {
        alert('Gagal menyesuaikan stok: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat memproses penyesuaian stok.');
    });
  });

  // 3. Quick Stock Update (+100 / +500)
  window.quickStock = function (id, delta) {
    const sign = delta > 0 ? '+' : '';
    if (!confirm('Tambahkan ' + sign + delta + ' unit ke stok aksesoris #' + id + '?')) return;

    fetch('/aksesoris/' + id + '/stock', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ delta_stok: delta })
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        alert(data.message || 'Stok aksesoris berhasil diperbarui!');
        window.location.reload();
      } else {
        alert('Gagal memperbarui stok: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(function (err) {
      console.error(err);
      alert('Terjadi kesalahan saat memperbarui stok aksesoris.');
    });
  };

  if (window.lucide) {
    window.lucide.createIcons();
  }
</script>
@endpush
@endsection