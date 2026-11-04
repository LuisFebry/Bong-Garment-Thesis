@extends('layouts.app')

@section('title', 'Kelola Pesanan & Pola — Bong Garment')

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pesanan & Pola</h2>
      <p class="text-xs text-slate-500 mt-1">Inisiasi pesanan produksi, spesifikasi ukuran 11-matrik, penugasan nota bahan kain, dan penjadwalan EDF</p>
    </div>
  </div>

  <!-- Form Inisiasi Pesanan Baru -->
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
        <input type="text" name="nama_mitra" id="input-nama-mitra" placeholder="Contoh: PT Busana Mandiri" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
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
          <option value="">-- Pilih Nota Kain (Opsional, Dapat Diisi Nanti) --</option>
          @foreach($penerimaan ?? [] as $pn)
            <option value="{{ $pn->id_penerimaan }}">{{ $pn->no_nota }} ({{ $pn->nama_kain }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">
          Tanggal Deadline Awal <span class="text-rose-500 font-bold">*</span>
          <span class="text-[10px] text-slate-400 font-normal">(Wajib untuk Algoritma EDF)</span>
        </label>
        <input type="date" name="deadline_awal" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
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
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-sm">Daftar Pesanan & Status Produksi</h3>
        <p class="text-xs text-slate-500 mt-0.5">Urutan otomatis berbasis prioritas batas waktu (Earliest Deadline First)</p>
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
</div>

<!-- Modal Edit Pesanan & Assign Nota Bahan -->
<div id="modal-edit-pesanan" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base" id="modal-edit-title">Edit Pesanan</h3>
        <p class="text-xs text-slate-500">Perbarui spesifikasi pesanan, penetapan nota bahan kain, dan status produksi</p>
      </div>
      <button type="button" onclick="closeEditPesananModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg leading-none transition-colors">&times;</button>
    </div>

    <form id="form-edit-pesanan" class="space-y-4">
      @csrf
      <input type="hidden" name="id_pesanan" id="edit-id-pesanan">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Inisiasi</label>
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
              <option value="{{ $p->id_pola }}">{{ $p->kode_pola }} — {{ $p->nama_pola }}</option>
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
            <span class="text-[11px] text-indigo-600 font-normal ml-1">— Dapat ditautkan saat kain tiba di gudang</span>
          </label>
          <select name="id_penerimaan" id="edit-id-penerimaan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">-- Tanpa Nota / Belum Ada Bahan Kain (Menunggu Kedatangan) --</option>
            @foreach($penerimaan ?? [] as $pn)
              <option value="{{ $pn->id_penerimaan }}">{{ $pn->no_nota }} — {{ $pn->nama_kain }} (Tgl: {{ $pn->tgl_nota }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Deadline Awal <span class="text-rose-500 font-bold">*</span>
          </label>
          <input type="date" name="deadline_awal" id="edit-deadline-awal" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Tanggal Deadline Terbaru (Target EDF)
          </label>
          <input type="date" name="deadline_terbaru" id="edit-deadline-terbaru" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Status Produksi</label>
          <select name="status_pesanan" id="edit-status-pesanan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
            <option value="Antrean">Antrean</option>
            <option value="Pemotongan">Pemotongan</option>
            <option value="Jahit">Jahit</option>
            <option value="Finishing">Finishing</option>
            <option value="Selesai">Selesai</option>
            <option value="Dibatalkan">Dibatalkan</option>
          </select>
        </div>

        <div>
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

@push('scripts')
<script>
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

  // Format date helper for ISO string or date
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

  // Close Edit Pesanan Modal
  window.closeEditPesananModal = function () {
    const modal = document.getElementById('modal-edit-pesanan');
    if (modal) modal.classList.add('hidden');
  };

  // Close modal when clicking outside
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

    const deadlineAwal = formData.get('deadline_awal');
    if (!deadlineAwal) {
      alert('Tanggal Deadline Awal wajib diisi untuk algoritma penjadwalan EDF.');
      return;
    }

    const payload = {
      id_sumber: Number(formData.get('id_sumber')),
      nama_mitra: formData.get('nama_mitra') || null,
      id_pola: Number(formData.get('id_pola')),
      jenis_pesanan: formData.get('jenis_pesanan'),
      id_penerimaan: formData.get('id_penerimaan') ? Number(formData.get('id_penerimaan')) : null,
      id_user_admin: 1,
      tgl_order: new Date().toISOString().split('T')[0],
      deadline_awal: deadlineAwal
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
