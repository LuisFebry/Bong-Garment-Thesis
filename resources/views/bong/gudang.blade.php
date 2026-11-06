@extends('layouts.app')

@section('title', 'Gudang & Stok Barang Jadi — Bong Garment')

@section('content')
@php
  $lowStokJadi = ($stok ?? collect())->filter(fn($s) => $s->jumlah_stok_pcs <= 20);
  $lowAksesoris = ($aksesoris ?? collect())->filter(fn($a) => $a->stok < 500);
@endphp

<div class="space-y-6">
  <!-- Header Title and Actions (Penyesuaian Stok Jadi button removed from top bar as requested) -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Gudang & Stok Barang Jadi</h2>
      <p class="text-xs text-slate-500 mt-1">Manajemen inventori perpetual barang jadi hasil produksi, stok fisik aksesoris CMT, dan kebutuhan aksesoris per pola</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" onclick="openModal('modal-add-aksesoris')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm flex items-center gap-1.5 transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>+ Tambah Aksesoris Baru</span>
      </button>
    </div>
  </div>

  <!-- Red Low / Zero Stock Alert Banner -->
  @if($lowStokJadi->count() > 0 || $lowAksesoris->count() > 0)
  <div class="p-4 rounded-xl bg-rose-50 border-2 border-rose-200 flex items-start gap-3 shadow-sm">
    <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5">
      <i data-lucide="alert-triangle" class="w-4 h-4"></i>
    </div>
    <div class="text-xs text-rose-900 space-y-1 flex-1">
      <div class="font-bold text-sm text-rose-800">Peringatan Stok Rendah / Habis (Perlu Restock Segera!)</div>
      @if($lowStokJadi->count() > 0)
        <div>
          <span class="font-semibold">Stok Barang Jadi Menipis / Habis (&le; 20 Pcs):</span>
          @foreach($lowStokJadi as $ls)
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-mono font-bold text-[11px] mr-1 mt-1 border border-rose-300">
              {{ $ls->pola->nama_pola ?? 'Pola' }} ({{ $ls->spesifikasiUkuran->nama_ukuran ?? '-' }} · {{ $ls->nama_warna }}): {{ $ls->jumlah_stok_pcs }} Pcs
            </span>
          @endforeach
        </div>
      @endif
      @if($lowAksesoris->count() > 0)
        <div>
          <span class="font-semibold">Stok Aksesoris Menipis / Habis (&lt; 500 Unit):</span>
          @foreach($lowAksesoris as $la)
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-mono font-bold text-[11px] mr-1 mt-1 border border-rose-300">
              {{ $la->nama_aksesoris }}: {{ number_format($la->stok) }} {{ $la->satuan }}
            </span>
          @endforeach
        </div>
      @endif
    </div>
  </div>
  @else
  <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
    <div class="flex items-center gap-2 font-semibold">
      <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
      <span>Seluruh Stok Barang Jadi & Inventori Aksesoris berada di atas batas aman minimum.</span>
    </div>
    <span class="text-[11px] font-mono text-emerald-700">Indikator Merah aktif otomatis saat stok &le; 20 Pcs (Barang Jadi) atau &lt; 500 Unit (Aksesoris)</span>
  </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- 1. Tabel Kiri: Stok Barang Jadi (Perpetual Inventory & Outbound) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">1. Stok Barang Jadi (<code class="font-mono text-indigo-600">Stok_Barang_Jadi</code>)</h3>
          <p class="text-[11px] text-slate-500">Bertambah otomatis dari QC Jahit Akhir & berkurang saat diambil Mitra / terjual</p>
        </div>
        <button type="button" onclick="openOutboundStokModal(null)" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs rounded-lg inline-flex items-center gap-1 transition-colors">
          <i data-lucide="package-minus" class="w-3.5 h-3.5"></i>
          <span>Transaksi Keluar / Masuk</span>
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">Pola / Model</th>
              <th class="pb-2">Ukuran</th>
              <th class="pb-2">Warna</th>
              <th class="pb-2">Stok (Pcs)</th>
              <th class="pb-2">Update</th>
              <th class="pb-2 text-right">Aksi Outbound</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($stok ?? [] as $st)
            @php
              $isZero = $st->jumlah_stok_pcs <= 0;
              $isLow = $st->jumlah_stok_pcs > 0 && $st->jumlah_stok_pcs <= 20;
            @endphp
            <tr class="{{ ($isZero || $isLow) ? 'bg-rose-50/70 hover:bg-rose-100/60' : 'hover:bg-slate-50' }} transition-colors">
              <td class="py-3 font-medium text-slate-800">{{ $st->pola->nama_pola ?? '-' }}</td>
              <td class="py-3 font-mono font-semibold text-indigo-600">{{ $st->spesifikasiUkuran->nama_ukuran ?? '-' }}</td>
              <td class="py-3">{{ $st->nama_warna }}</td>
              <td class="py-3 font-mono font-bold {{ ($isZero || $isLow) ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ number_format($st->jumlah_stok_pcs) }} pcs
                @if($isZero)
                  <span class="ml-1 px-1.5 py-0.5 rounded bg-rose-600 text-white text-[9px] font-sans uppercase">HABIS (0)</span>
                @elseif($isLow)
                  <span class="ml-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-300 text-[9px] font-sans uppercase">RENDAH</span>
                @endif
              </td>
              <td class="py-3 font-mono text-[10px] text-slate-400">{{ substr($st->tgl_update_terakhir, 0, 16) }}</td>
              <td class="py-3 text-right">
                <div class="inline-flex items-center justify-end gap-1">
                  <button type="button"
                    onclick="openEditStokJadiModal({
                      id_stok: {{ $st->id_stok }},
                      id_pola: {{ $st->id_pola }},
                      id_spesifikasi: {{ $st->id_spesifikasi }},
                      nama_warna: '{{ addslashes($st->nama_warna) }}',
                      jumlah_stok_pcs: {{ $st->jumlah_stok_pcs }}
                    })"
                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-semibold text-[11px] inline-flex items-center gap-1 transition-colors">
                    <i data-lucide="edit-3" class="w-3 h-3"></i> Edit
                  </button>
                  <button type="button"
                    onclick="openOutboundStokModal({
                      id_pola: {{ $st->id_pola }},
                      id_spesifikasi: {{ $st->id_spesifikasi }},
                      nama_warna: '{{ addslashes($st->nama_warna) }}',
                      jumlah_stok_pcs: {{ $st->jumlah_stok_pcs }}
                    })"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1 transition-colors">
                    <i data-lucide="log-out" class="w-3 h-3"></i> Keluar
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="py-6 text-center text-slate-400">Belum ada stok barang jadi terdaftar.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Tabel Kanan: Inventori Aksesoris -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">2. Inventori Aksesoris CMT (<code class="font-mono text-indigo-600">Aksesoris</code>)</h3>
          <p class="text-[11px] text-slate-500">Stok fisik kancing, ritsleting, benang, label beserta tombol pemakaian (-), restock (+), edit & hapus</p>
        </div>
        <button type="button" onclick="openGudangSupplierModal()" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold rounded-lg inline-flex items-center gap-1 transition-colors">
          <i data-lucide="truck" class="w-3.5 h-3.5"></i>
          + Tambah / Edit Supplier
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
              <th class="pb-2">Nama Aksesoris</th>
              <th class="pb-2">Ukuran</th>
              <th class="pb-2">Stok Fisik</th>
              <th class="pb-2">Supplier</th>
              <th class="pb-2 text-center">Mutasi Cepat (+/-)</th>
              <th class="pb-2 text-right">Kelola</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($aksesoris ?? [] as $acc)
            @php
              $accZero = $acc->stok <= 0;
              $accLow = $acc->stok > 0 && $acc->stok < 500;
            @endphp
            <tr class="{{ ($accZero || $accLow) ? 'bg-rose-50/70 hover:bg-rose-100/60' : 'hover:bg-slate-50' }} transition-colors">
              <td class="py-3 font-medium text-slate-800">{{ $acc->nama_aksesoris }}</td>
              <td class="py-3 font-mono text-slate-500">{{ $acc->ukuran ?? '-' }}</td>
              <td class="py-3 font-mono font-bold {{ ($accZero || $accLow) ? 'text-rose-600' : 'text-slate-800' }}">
                {{ number_format($acc->stok) }} <span class="text-[10px] text-slate-400 font-normal">{{ $acc->satuan }}</span>
                @if($accZero)
                  <span class="block text-[9px] font-sans font-bold text-rose-600 uppercase">STOK HABIS (0)</span>
                @elseif($accLow)
                  <span class="block text-[9px] font-sans font-bold text-rose-600 uppercase">STOK RENDAH (&lt;500)</span>
                @endif
              </td>
              <td class="py-3 text-slate-500 text-[11px]">{{ $acc->supplier->nama_supplier ?? '-' }}</td>
              <td class="py-3 text-center">
                <div class="inline-flex items-center gap-1">
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, -500)" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-mono font-semibold text-[10px] rounded border border-rose-200 transition-colors" title="Kurangi 500 {{ $acc->satuan }} (Pemakaian Produksi)">
                    -500
                  </button>
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, -100)" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-mono font-semibold text-[10px] rounded border border-rose-200 transition-colors" title="Kurangi 100 {{ $acc->satuan }} (Pemakaian Produksi)">
                    -100
                  </button>
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, 100)" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[10px] rounded border border-indigo-200 transition-colors" title="Tambah 100 {{ $acc->satuan }}">
                    +100
                  </button>
                  <button type="button" onclick="quickStock({{ $acc->id_aksesoris }}, 500)" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[10px] rounded border border-indigo-200 transition-colors" title="Tambah 500 {{ $acc->satuan }}">
                    +500
                  </button>
                </div>
              </td>
              <td class="py-3 text-right">
                <div class="inline-flex items-center gap-1">
                  <button type="button"
                    onclick="openEditAksesorisModal({
                      id_aksesoris: {{ $acc->id_aksesoris }},
                      nama_aksesoris: '{{ addslashes($acc->nama_aksesoris) }}',
                      ukuran: '{{ addslashes($acc->ukuran ?? '') }}',
                      satuan: '{{ addslashes($acc->satuan) }}',
                      stok: {{ $acc->stok }},
                      id_supplier: {{ $acc->id_supplier }}
                    })"
                    class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">
                    Edit
                  </button>
                  <button type="button"
                    onclick="deleteAksesoris({{ $acc->id_aksesoris }}, '{{ addslashes($acc->nama_aksesoris) }}')"
                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="py-6 text-center text-slate-400">Belum ada data aksesoris.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 3. Relasi Detail_Aksesoris_Pola (Kebutuhan Aksesoris per Pola Pakaian) -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="link" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">3. Pemetaan Kebutuhan Aksesoris per Pola Pakaian (<code class="font-mono text-indigo-600">Detail_Aksesoris_Pola</code>)</h3>
          <p class="text-[11px] text-slate-500">Takaran konsumsi aksesoris (kancing, ritsleting, label) untuk setiap 1 pcs pakaian pada masing-masing Master Pola</p>
        </div>
      </div>

      <button type="button" onclick="openModal('modal-map-aksesoris-pola')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        <span>+ Hubungkan Aksesoris ke Pola</span>
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @forelse($pola ?? [] as $pl)
      <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="font-mono text-xs font-bold text-indigo-600">{{ $pl->kode_pola }}</span>
            <span class="text-[10px] px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600 font-semibold">
              {{ $pl->detailAksesorisPola->count() }} Komponen
            </span>
          </div>
          <h4 class="font-bold text-xs text-slate-900 mb-3">{{ $pl->nama_pola }}</h4>

          <div class="space-y-1.5">
            @forelse($pl->detailAksesorisPola ?? [] as $dap)
            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
              <div>
                <span class="font-semibold text-slate-800">{{ $dap->aksesoris->nama_aksesoris ?? ('Aksesoris #'.$dap->id_aksesoris) }}</span>
                <span class="block text-[10px] font-mono text-indigo-600 font-bold">
                  Kebutuhan: {{ $dap->jumlah_digunakan }} {{ $dap->aksesoris->satuan ?? 'Pcs' }} / 1 Pakaian
                </span>
              </div>
              <div class="inline-flex items-center gap-1">
                <button type="button"
                  onclick="openEditPolaMapping({{ $pl->id_pola }}, {{ $dap->id_aksesoris }}, {{ $dap->jumlah_digunakan }})"
                  class="text-slate-400 hover:text-indigo-600 p-1" title="Edit takaran kebutuhan aksesoris">
                  <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                </button>
                <button type="button"
                  onclick="deletePolaMapping({{ $pl->id_pola }}, {{ $dap->id_aksesoris }})"
                  class="text-slate-400 hover:text-rose-600 p-1" title="Hapus pemetaan ini">
                  <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
              </div>
            </div>
            @empty
            <p class="text-[11px] text-slate-400 italic py-2">Belum ada aksesoris terhubung ke pola ini.</p>
            @endforelse
          </div>
        </div>
      </div>
      @empty
      <p class="col-span-full text-center text-xs text-slate-400 py-4">Belum ada Master Pola terdaftar.</p>
      @endforelse
    </div>
  </div>

  <!-- 4. Riwayat Transaksi & Pengeluaran Stok Barang Jadi (Riwayat_Stok_Barang_Jadi) -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="history" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">4. Riwayat Transaksi & Pengeluaran Stok Barang Jadi (<code class="font-mono text-indigo-600">Riwayat_Stok_Barang_Jadi</code>)</h3>
          <p class="text-[11px] text-slate-500">Log pencatatan pengambilan barang oleh Mitra, penjualan Produk Abadi, alokasi QC Jahit Akhir, serta koreksi opname gudang</p>
        </div>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
        {{ count($riwayatStok ?? []) }} Transaksi Tercatat
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">Waktu</th>
            <th class="pb-2">Pola / Ukuran / Warna</th>
            <th class="pb-2">Jenis & Kuantitas</th>
            <th class="pb-2">Perubahan Stok</th>
            <th class="pb-2">Alasan Transaksi</th>
            <th class="pb-2">Nama Mitra / Penerima</th>
            <th class="pb-2">Keterangan / Petugas</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($riwayatStok ?? [] as $rw)
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="py-3 font-mono text-[11px] text-slate-500 whitespace-nowrap">
              {{ $rw->tgl_transaksi ? $rw->tgl_transaksi->format('Y-m-d H:i') : '-' }}
            </td>
            <td class="py-3">
              <span class="font-semibold text-slate-800 block">{{ $rw->pola->nama_pola ?? ('Pola #'.$rw->id_pola) }}</span>
              <span class="text-[11px] text-slate-500">
                Ukuran <strong class="font-mono text-indigo-600">{{ $rw->spesifikasiUkuran->nama_ukuran ?? '-' }}</strong> &bull; {{ $rw->nama_warna }}
              </span>
            </td>
            <td class="py-3 whitespace-nowrap">
              @if($rw->jenis_transaksi === 'KELUAR')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                  -{{ $rw->jumlah_pcs }} pcs (KELUAR)
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                  +{{ $rw->jumlah_pcs }} pcs (MASUK)
                </span>
              @endif
            </td>
            <td class="py-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
              {{ $rw->stok_sebelum }} &rarr; <strong class="text-slate-900">{{ $rw->stok_sesudah }} pcs</strong>
            </td>
            <td class="py-3 text-slate-700 font-medium">
              {{ $rw->kategori_alasan }}
            </td>
            <td class="py-3">
              @if($rw->nama_mitra)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 font-semibold text-[11px]">
                  <i data-lucide="building-2" class="w-3 h-3"></i>
                  {{ $rw->nama_mitra }}
                </span>
              @else
                <span class="text-slate-400 italic text-[11px]">- Non-Mitra / Internal -</span>
              @endif
            </td>
            <td class="py-3 text-[11px] text-slate-500">
              @if($rw->keterangan)
                <span class="block text-slate-700">{{ $rw->keterangan }}</span>
              @endif
              <span class="text-[10px] text-slate-400">Oleh: {{ $rw->user->nama_user ?? 'Petugas Gudang' }}</span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="py-6 text-center text-slate-400">
              Belum ada riwayat transaksi keluar/masuk stok barang jadi yang tercatat. Gunakan tombol <b>Keluar</b> atau <b>Transaksi Keluar / Masuk</b> di atas untuk mencatat serah terima Mitra atau pengeluaran gudang.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal 1: Transaksi Pengeluaran / Penyesuaian Stok Barang Jadi (Outbound & Opname) -->
<div id="modal-adjust-stok" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Pengeluaran / Penyesuaian Stok Barang Jadi</h3>
        <p class="text-xs text-slate-500">Tersimpan otomatis ke <code class="font-mono text-indigo-600">Stok_Barang_Jadi</code> &amp; <code class="font-mono text-indigo-600">Riwayat_Stok_Barang_Jadi</code></p>
      </div>
      <button type="button" onclick="closeModal('modal-adjust-stok')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-adjust-stok" class="space-y-4 mt-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">1. Pola Target (<code class="font-mono">id_pola</code>) <span class="text-rose-500">*</span></label>
          <select id="adj-id-pola" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            @foreach($pola ?? [] as $pl)
              <option value="{{ $pl->id_pola }}">{{ $pl->kode_pola }} — {{ $pl->nama_pola }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">2. Spesifikasi Ukuran (<code class="font-mono">id_spesifikasi</code>) <span class="text-rose-500">*</span></label>
          <select id="adj-id-spesifikasi" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <!-- Populated dynamically by JS -->
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">3. Variasi Warna (<code class="font-mono">nama_warna</code>) <span class="text-rose-500">*</span></label>
          <input type="text" id="adj-nama-warna" value="Hitam Reaktif" required placeholder="Contoh: Hitam Reaktif" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">4. Jenis Transaksi Stok <span class="text-rose-500">*</span></label>
          <select id="adj-jenis" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white font-semibold">
            <option value="KURANG">Pengeluaran Gudang / Outbound (-)</option>
            <option value="TAMBAH">Penambahan Stok / Inbound (+)</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">5. Jumlah (Pcs) <span class="text-rose-500">*</span></label>
          <input type="number" id="adj-jumlah-pcs" min="1" value="10" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">6. Alasan Penyesuaian / Pengeluaran <span class="text-rose-500">*</span></label>
          <select id="adj-alasan" onchange="toggleMitraFieldRequirement()" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="Diambil Mitra (Serah Terima Request Mitra)">Diambil Mitra (Serah Terima Request Mitra)</option>
            <option value="Terjual (Distribusi Produk Abadi)">Terjual (Distribusi Produk Abadi)</option>
            <option value="Koreksi Stok Opname Gudang">Koreksi Stok Opname Gudang</option>
            <option value="Sampel Display / Retur">Sampel Display / Retur</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
        <div id="adj-mitra-wrapper">
          <label id="adj-mitra-label" class="block text-xs font-semibold text-slate-700 mb-1">
            7. Nama Mitra Pengambil Barang (<code class="font-mono">nama_mitra</code>) <span id="adj-mitra-req" class="text-rose-500">*</span>
          </label>
          <input type="text" id="adj-nama-mitra" list="mitra-datalist" required placeholder="Ketik atau pilih Nama Mitra..." class="w-full text-xs p-2.5 border border-indigo-200 bg-indigo-50/30 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <datalist id="mitra-datalist">
            @foreach($mitraList ?? [] as $mName)
              <option value="{{ $mName }}"></option>
            @endforeach
          </datalist>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            8. No. Surat Jalan / Catatan (<code class="font-mono">keterangan</code>)
          </label>
          <input type="text" id="adj-keterangan" placeholder="Contoh: SJ-OUT-01 / Diambil kurir" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-adjust-stok')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Transaksi Stok
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Tambah Aksesoris Baru -->
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
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aksesoris <span class="text-rose-500">*</span></label>
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
            <option value="Cones">Cones</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Awal Fisik <span class="text-rose-500">*</span></label>
        <input type="number" name="stok" min="0" value="1000" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-semibold text-slate-700">Supplier Rekanan <span class="text-rose-500">*</span></label>
          <button type="button" onclick="openGudangSupplierModal()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Supplier Baru / Edit</button>
        </div>
        <select name="id_supplier" id="add-acc-supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
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

<!-- Modal 3: Edit Data Aksesoris -->
<div id="modal-edit-aksesoris" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h3 class="font-bold text-slate-900 text-base">Edit Data Aksesoris</h3>
      <button type="button" onclick="closeModal('modal-edit-aksesoris')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-edit-aksesoris" class="space-y-4 mt-4">
      <input type="hidden" id="edit-acc-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aksesoris <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-acc-nama" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ukuran / Spesifikasi</label>
          <input type="text" id="edit-acc-ukuran" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan</label>
          <input type="text" id="edit-acc-satuan" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Fisik Saat Ini <span class="text-rose-500">*</span></label>
        <input type="number" id="edit-acc-stok" min="0" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-semibold text-slate-700">Supplier Rekanan <span class="text-rose-500">*</span></label>
          <button type="button" onclick="openGudangSupplierModal()" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Supplier Baru / Edit</button>
        </div>
        <select id="edit-acc-supplier" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          @foreach($suppliers ?? [] as $sup)
            <option value="{{ $sup->id_supplier }}">{{ $sup->nama_supplier }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-aksesoris')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 4: Hubungkan Aksesoris ke Pola (Detail_Aksesoris_Pola) -->
<div id="modal-map-aksesoris-pola" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Pemetaan Aksesoris ke Pola Pakaian</h3>
        <p class="text-xs text-slate-500">Tabel <code class="font-mono text-indigo-600">Detail_Aksesoris_Pola</code></p>
      </div>
      <button type="button" onclick="closeModal('modal-map-aksesoris-pola')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-map-aksesoris-pola" class="space-y-4 mt-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Master Pola (<code class="font-mono">id_pola</code>) <span class="text-rose-500">*</span></label>
        <select id="map-id-pola" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          @foreach($pola ?? [] as $pl)
            <option value="{{ $pl->id_pola }}">{{ $pl->kode_pola }} — {{ $pl->nama_pola }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Item Aksesoris (<code class="font-mono">id_aksesoris</code>) <span class="text-rose-500">*</span></label>
        <select id="map-id-aksesoris" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
          @foreach($aksesoris ?? [] as $acc)
            <option value="{{ $acc->id_aksesoris }}">{{ $acc->nama_aksesoris }} (Stok: {{ number_format($acc->stok) }} {{ $acc->satuan }})</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Digunakan per 1 Pakaian (<code class="font-mono">jumlah_digunakan</code>) <span class="text-rose-500">*</span></label>
        <input type="number" id="map-jumlah-digunakan" min="1" value="1" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono focus:ring-2 focus:ring-indigo-500">
        <p class="text-[10px] text-slate-400 mt-1">Misal: Kemeja membutuhkan 7 pcs kancing per 1 baju jadi.</p>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-map-aksesoris-pola')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
          Batal
        </button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition-colors">
          Simpan Pemetaan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 5: Edit Langsung Stok Barang Jadi (Stok_Barang_Jadi) -->
<div id="modal-edit-stok-jadi" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Data Stok Barang Jadi</h3>
        <p class="text-xs text-slate-500">Revisi pola, spesifikasi ukuran, warna, atau jumlah stok (<code class="font-mono text-indigo-600">Stok_Barang_Jadi</code>)</p>
      </div>
      <button type="button" onclick="closeModal('modal-edit-stok-jadi')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-edit-stok-jadi" class="space-y-4 mt-4">
      <input type="hidden" id="edit-stok-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Pola Pakaian (<code class="font-mono">id_pola</code>) <span class="text-rose-500">*</span></label>
        <select id="edit-stok-pola" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white">
          @foreach($pola ?? [] as $pl)
            <option value="{{ $pl->id_pola }}">{{ $pl->kode_pola }} — {{ $pl->nama_pola }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Spesifikasi Ukuran (<code class="font-mono">id_spesifikasi</code>) <span class="text-rose-500">*</span></label>
        <select id="edit-stok-spesifikasi" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white">
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Variasi Warna (<code class="font-mono">nama_warna</code>) <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-stok-warna" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Stok Aktual (Pcs) (<code class="font-mono">jumlah_stok_pcs</code>) <span class="text-rose-500">*</span></label>
        <input type="number" min="0" id="edit-stok-pcs" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono font-bold text-indigo-700">
      </div>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-stok-jadi')" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm">Simpan Perubahan Stok</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 6: Tambah & Kelola Supplier (Gudang & Aksesoris) -->
<div id="modal-gudang-supplier" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 id="gud-sup-title" class="font-bold text-slate-900 text-base">Tambah & Kelola Supplier</h3>
        <p class="text-xs text-slate-500">Registrasi dan revisi data supplier pada tabel <code class="font-mono text-indigo-600">Supplier</code></p>
      </div>
      <button type="button" onclick="closeModal('modal-gudang-supplier')" class="text-slate-400 hover:text-slate-600">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <form id="form-gudang-supplier" class="space-y-3 mb-4">
      <input type="hidden" id="gud-sup-edit-id" value="">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label id="gud-sup-nama-label" class="block text-xs font-semibold text-slate-700 mb-1">Nama Supplier <span class="text-rose-500">*</span></label>
          <input type="text" id="gud-sup-nama" required placeholder="Contoh: CV Aksesoris Garment Harapan" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak / No. Telepon</label>
          <input type="text" id="gud-sup-kontak" placeholder="Contoh: 021-6901234" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Supplier</label>
        <textarea id="gud-sup-alamat" rows="2" placeholder="Contoh: Pasar Pagi Asemka Blok B No. 12, Jakarta" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
      </div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="gud-sup-cancel-btn" onclick="resetGudangSupplierForm()" class="hidden px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Batal Edit</button>
        <button type="submit" id="gud-sup-submit-btn" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">+ Simpan Supplier Baru</button>
      </div>
    </form>

    <div class="border-t border-slate-100 pt-3">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Daftar Supplier Terdaftar (Klik Edit untuk Revisi)</p>
      <div id="gud-supplier-list" class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
        @foreach($suppliers ?? [] as $sp)
        <div id="gud-sup-row-{{ $sp->id_supplier }}" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
          <div>
            <p id="gud-sup-nama-text-{{ $sp->id_supplier }}" class="font-semibold text-slate-800">{{ $sp->nama_supplier }}</p>
            <p id="gud-sup-meta-text-{{ $sp->id_supplier }}" class="text-[11px] text-slate-500 font-mono">{{ $sp->kontak ?? 'Tanpa kontak' }} {{ $sp->alamat ? '• ' . $sp->alamat : '' }}</p>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <button type="button" onclick="startEditGudangSupplier({{ $sp->id_supplier }}, '{{ addslashes($sp->nama_supplier) }}', '{{ addslashes($sp->kontak ?? '') }}', '{{ addslashes($sp->alamat ?? '') }}')" class="px-2 py-1 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteGudangSupplier({{ $sp->id_supplier }})" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="flex justify-end pt-3 mt-3 border-t border-slate-100">
      <button type="button" onclick="closeModal('modal-gudang-supplier')" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Tutup</button>
    </div>
  </div>
</div>

@php
  $polaSpecsMap = [];
  foreach (($pola ?? []) as $pl) {
    $polaSpecsMap[$pl->id_pola] = ($pl->spesifikasiUkuran ?? collect())->map(function($s) {
      return [
        'id_spesifikasi' => $s->id_spesifikasi,
        'nama_ukuran' => $s->nama_ukuran,
      ];
    })->values();
  }
@endphp

@push('scripts')
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const polaSpecsMap = @json($polaSpecsMap);

  function openGudangSupplierModal() {
    resetGudangSupplierForm();
    openModal('modal-gudang-supplier');
  }
  function resetGudangSupplierForm() {
    document.getElementById('gud-sup-edit-id').value = '';
    document.getElementById('gud-sup-nama').value = '';
    document.getElementById('gud-sup-kontak').value = '';
    document.getElementById('gud-sup-alamat').value = '';
    document.getElementById('gud-sup-title').textContent = 'Tambah & Kelola Supplier';
    document.getElementById('gud-sup-nama-label').innerHTML = 'Nama Supplier <span class="text-rose-500">*</span>';
    document.getElementById('gud-sup-submit-btn').textContent = '+ Simpan Supplier Baru';
    document.getElementById('gud-sup-cancel-btn').classList.add('hidden');
  }
  function startEditGudangSupplier(id, nama, kontak, alamat) {
    document.getElementById('gud-sup-edit-id').value = id;
    document.getElementById('gud-sup-nama').value = nama || '';
    document.getElementById('gud-sup-kontak').value = kontak || '';
    document.getElementById('gud-sup-alamat').value = alamat || '';
    document.getElementById('gud-sup-title').textContent = 'Edit Data Supplier #' + id;
    document.getElementById('gud-sup-nama-label').innerHTML = 'Edit Nama Supplier <span class="font-mono text-indigo-600">#' + id + '</span> <span class="text-rose-500">*</span>';
    document.getElementById('gud-sup-submit-btn').textContent = 'Simpan Perubahan Supplier';
    document.getElementById('gud-sup-cancel-btn').classList.remove('hidden');
    openModal('modal-gudang-supplier');
    document.getElementById('gud-sup-nama').focus();
  }
  document.getElementById('form-gudang-supplier')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const editId = document.getElementById('gud-sup-edit-id').value;
    const payload = {
      nama_supplier: document.getElementById('gud-sup-nama').value.trim(),
      kontak: document.getElementById('gud-sup-kontak').value.trim() || null,
      alamat: document.getElementById('gud-sup-alamat').value.trim() || null
    };
    const url = editId ? (`/supplier/${editId}/update`) : '/supplier';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menyimpan supplier.');
        return;
      }
      const sp = json.data;
      if (editId) {
        ['add-acc-supplier', 'edit-acc-supplier'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = sel.querySelector('option[value="' + sp.id_supplier + '"]');
            if (opt) opt.textContent = selId === 'add-acc-supplier' ? `${sp.nama_supplier} (${sp.kontak || '-'})` : sp.nama_supplier;
          }
        });
        const nTxt = document.getElementById('gud-sup-nama-text-' + sp.id_supplier);
        if (nTxt) nTxt.textContent = sp.nama_supplier;
        const mTxt = document.getElementById('gud-sup-meta-text-' + sp.id_supplier);
        if (mTxt) mTxt.textContent = (sp.kontak || 'Tanpa kontak') + (sp.alamat ? ' • ' + sp.alamat : '');
        resetGudangSupplierForm();
        alert(json.message || 'Supplier berhasil diperbarui!');
      } else {
        ['add-acc-supplier', 'edit-acc-supplier'].forEach(selId => {
          const sel = document.getElementById(selId);
          if (sel) {
            const opt = document.createElement('option');
            opt.value = sp.id_supplier;
            opt.textContent = selId === 'add-acc-supplier' ? `${sp.nama_supplier} (${sp.kontak || '-'})` : sp.nama_supplier;
            opt.selected = true;
            sel.appendChild(opt);
          }
        });
        const list = document.getElementById('gud-supplier-list');
        if (list) {
          const row = document.createElement('div');
          row.id = 'gud-sup-row-' + sp.id_supplier;
          row.className = 'flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs';
          const safeNama = (sp.nama_supplier || '').replace(/'/g, "\\'");
          const safeKontak = (sp.kontak || '').replace(/'/g, "\\'");
          const safeAlamat = (sp.alamat || '').replace(/'/g, "\\'");
          row.innerHTML = '<div><p id="gud-sup-nama-text-' + sp.id_supplier + '" class="font-semibold text-slate-800">' + sp.nama_supplier + '</p>' +
            '<p id="gud-sup-meta-text-' + sp.id_supplier + '" class="text-[11px] text-slate-500 font-mono">' + (sp.kontak || 'Tanpa kontak') + (sp.alamat ? ' • ' + sp.alamat : '') + '</p></div>' +
            '<div class="flex items-center gap-1 shrink-0">' +
            '<button type="button" onclick="startEditGudangSupplier(' + sp.id_supplier + ', \'' + safeNama + '\', \'' + safeKontak + '\', \'' + safeAlamat + '\')" class="px-2 py-1 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>' +
            '<button type="button" onclick="deleteGudangSupplier(' + sp.id_supplier + ')" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>' +
            '</div>';
          list.appendChild(row);
        }
        resetGudangSupplierForm();
        alert(json.message || 'Supplier baru berhasil ditambahkan!');
      }
    } catch (e) {
      alert('Terjadi kesalahan saat menyimpan supplier.');
    }
  });
  async function deleteGudangSupplier(id) {
    if (!confirm('Hapus supplier ini?')) return;
    try {
      const res = await fetch(`/supplier/${id}/delete`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
      });
      const json = await res.json();
      if (!res.ok || !json.success) {
        alert(json.message || 'Gagal menghapus supplier.');
        return;
      }
      ['add-acc-supplier', 'edit-acc-supplier'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (sel) {
          const opt = sel.querySelector('option[value="' + id + '"]');
          if (opt) opt.remove();
        }
      });
      const row = document.getElementById('gud-sup-row-' + id);
      if (row) row.remove();
    } catch (e) {
      alert('Terjadi kesalahan saat menghapus supplier.');
    }
  }

  function populateSpecsForPola(idPola, selectedSpecId) {
    const specSel = document.getElementById('adj-id-spesifikasi');
    if (!specSel) return;
    const specs = polaSpecsMap[idPola] || [];
    specSel.innerHTML = specs.map(function (s) {
      const sel = Number(selectedSpecId) === Number(s.id_spesifikasi) ? 'selected' : '';
      return `<option value="${s.id_spesifikasi}" ${sel}>Ukuran ${s.nama_ukuran} (#${s.id_spesifikasi})</option>`;
    }).join('');
  }

  document.getElementById('adj-id-pola')?.addEventListener('change', function () {
    populateSpecsForPola(this.value, null);
  });

  function toggleMitraFieldRequirement() {
    const alasan = document.getElementById('adj-alasan')?.value || '';
    const mitraInp = document.getElementById('adj-nama-mitra');
    const mitraLabel = document.getElementById('adj-mitra-label');
    if (!mitraInp || !mitraLabel) return;

    if (alasan.toLowerCase().includes('mitra')) {
      mitraInp.required = true;
      mitraInp.placeholder = 'Ketik atau pilih Nama Mitra pengambil barang...';
      mitraInp.className = 'w-full text-xs p-2.5 border border-indigo-200 bg-indigo-50/30 rounded-lg focus:ring-2 focus:ring-indigo-500';
      mitraLabel.innerHTML = '7. Nama Mitra Pengambil Barang (<code class="font-mono">nama_mitra</code>) <span class="text-rose-500">*</span>';
    } else {
      mitraInp.required = false;
      mitraInp.placeholder = 'Opsional (Nama pembeli / penerima barang)';
      mitraInp.className = 'w-full text-xs p-2.5 border border-slate-200 bg-white rounded-lg focus:ring-2 focus:ring-indigo-500';
      mitraLabel.innerHTML = '7. Nama Mitra / Penerima (<code class="font-mono">nama_mitra</code>) <span class="text-slate-400 font-normal">(Opsional)</span>';
    }
  }

  function openOutboundStokModal(rowInfo) {
    const polaSel = document.getElementById('adj-id-pola');
    const warnaInp = document.getElementById('adj-nama-warna');
    const jenisSel = document.getElementById('adj-jenis');

    if (rowInfo) {
      if (polaSel) polaSel.value = rowInfo.id_pola;
      populateSpecsForPola(rowInfo.id_pola, rowInfo.id_spesifikasi);
      if (warnaInp) warnaInp.value = rowInfo.nama_warna;
      if (jenisSel) jenisSel.value = 'KURANG';
    } else if (polaSel) {
      populateSpecsForPola(polaSel.value, null);
    }

    toggleMitraFieldRequirement();
    openModal('modal-adjust-stok');
  }

  // 1. Submit Outbound / Adjust Stok Barang Jadi
  document.getElementById('form-adjust-stok')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const idPola = Number(document.getElementById('adj-id-pola').value);
    const idSpesifikasi = Number(document.getElementById('adj-id-spesifikasi').value);
    const namaWarna = document.getElementById('adj-nama-warna').value.trim();
    const jenis = document.getElementById('adj-jenis').value;
    const qty = Math.abs(parseInt(document.getElementById('adj-jumlah-pcs').value, 10) || 0);
    const alasan = document.getElementById('adj-alasan').value;
    const namaMitra = document.getElementById('adj-nama-mitra')?.value.trim() || '';
    const keterangan = document.getElementById('adj-keterangan')?.value.trim() || '';

    if (!idPola || !idSpesifikasi || !namaWarna || qty <= 0) {
      alert('Harap lengkapi seluruh kolom penyesuaian stok barang jadi.');
      return;
    }

    if (alasan.toLowerCase().includes('mitra') && !namaMitra) {
      alert('Harap isi Nama Mitra pengambil barang.');
      document.getElementById('adj-nama-mitra')?.focus();
      return;
    }

    const deltaPcs = jenis === 'KURANG' ? -qty : qty;

    fetch("{{ route('stok.adjust', [], false) }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        id_pola: idPola,
        id_spesifikasi: idSpesifikasi,
        nama_warna: namaWarna,
        delta_pcs: deltaPcs,
        alasan_penyesuaian: alasan,
        nama_mitra: namaMitra || null,
        keterangan: keterangan || null
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Stok barang jadi berhasil diperbarui!');
        window.location.reload();
      } else {
        alert('Gagal menyesuaikan stok: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat memproses transaksi stok.');
    });
  });

  // 2. Submit Add Aksesoris
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

    fetch("{{ route('aksesoris.store', [], false) }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Aksesoris berhasil didaftarkan!');
        window.location.reload();
      } else {
        alert('Gagal mendaftarkan aksesoris: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat memproses permintaan.');
    });
  });

  // 3. Quick Stock Update (+100, +500, -100, -500)
  window.quickStock = function (id, delta) {
    const actionLabel = delta > 0 ? ('Tambahkan +' + delta) : ('Kurangi ' + delta + ' (Pemakaian Produksi)');
    if (!confirm(actionLabel + ' unit pada stok aksesoris #' + id + '?')) return;

    fetch('/aksesoris/' + id + '/stock', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ delta_stok: delta })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Stok aksesoris berhasil diperbarui!');
        window.location.reload();
      } else {
        alert('Gagal memperbarui stok: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat memperbarui stok aksesoris.');
    });
  };

  // 4. Edit & Delete Aksesoris
  window.openEditAksesorisModal = function (acc) {
    document.getElementById('edit-acc-id').value = acc.id_aksesoris;
    document.getElementById('edit-acc-nama').value = acc.nama_aksesoris;
    document.getElementById('edit-acc-ukuran').value = acc.ukuran || '';
    document.getElementById('edit-acc-satuan').value = acc.satuan;
    document.getElementById('edit-acc-stok').value = acc.stok;
    document.getElementById('edit-acc-supplier').value = acc.id_supplier;
    openModal('modal-edit-aksesoris');
  };

  document.getElementById('form-edit-aksesoris')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('edit-acc-id').value;
    const payload = {
      nama_aksesoris: document.getElementById('edit-acc-nama').value,
      ukuran: document.getElementById('edit-acc-ukuran').value || null,
      satuan: document.getElementById('edit-acc-satuan').value,
      stok: Number(document.getElementById('edit-acc-stok').value),
      id_supplier: Number(document.getElementById('edit-acc-supplier').value)
    };

    fetch('/aksesoris/' + id + '/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Data aksesoris berhasil diperbarui!');
        window.location.reload();
      } else {
        alert('Gagal memperbarui aksesoris: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat memperbarui aksesoris.');
    });
  });

  window.deleteAksesoris = function (id, nama) {
    if (!confirm('Hapus aksesoris "' + nama + '" (#' + id + ') dari inventori?')) return;

    fetch('/aksesoris/' + id + '/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Aksesoris berhasil dihapus!');
        window.location.reload();
      } else {
        alert('Gagal menghapus aksesoris: ' + (data.message || 'Terjadi kesalahan'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menghapus aksesoris.');
    });
  };

  // 5. Map & Delete Detail_Aksesoris_Pola
  document.getElementById('form-map-aksesoris-pola')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const payload = {
      id_pola: Number(document.getElementById('map-id-pola').value),
      id_aksesoris: Number(document.getElementById('map-id-aksesoris').value),
      jumlah_digunakan: Number(document.getElementById('map-jumlah-digunakan').value)
    };

    fetch('/aksesoris/pola-mapping', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Kebutuhan aksesoris pola berhasil disimpan!');
        window.location.reload();
      } else {
        alert('Gagal menyimpan pemetaan: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan pemetaan aksesoris.');
    });
  });

  window.deletePolaMapping = function (idPola, idAksesoris) {
    if (!confirm('Hapus hubungan aksesoris ini dari pola?')) return;

    fetch('/aksesoris/pola-mapping/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ id_pola: idPola, id_aksesoris: idAksesoris })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      } else {
        alert('Gagal menghapus pemetaan.');
      }
    });
  };

  window.openEditPolaMapping = function (idPola, idAksesoris, jumlahDigunakan) {
    document.getElementById('map-id-pola').value = idPola;
    document.getElementById('map-id-aksesoris').value = idAksesoris;
    document.getElementById('map-jumlah-digunakan').value = jumlahDigunakan;
    openModal('modal-map-aksesoris-pola');
  };

  function populateEditStokSpecs(idPola, selectedSpecId) {
    const specSel = document.getElementById('edit-stok-spesifikasi');
    if (!specSel) return;
    const specs = polaSpecsMap[idPola] || [];
    specSel.innerHTML = specs.map(function (s) {
      const sel = Number(selectedSpecId) === Number(s.id_spesifikasi) ? 'selected' : '';
      return `<option value="${s.id_spesifikasi}" ${sel}>Ukuran ${s.nama_ukuran} (#${s.id_spesifikasi})</option>`;
    }).join('');
  }

  document.getElementById('edit-stok-pola')?.addEventListener('change', function () {
    populateEditStokSpecs(this.value, null);
  });

  window.openEditStokJadiModal = function (st) {
    document.getElementById('edit-stok-id').value = st.id_stok;
    document.getElementById('edit-stok-pola').value = st.id_pola;
    populateEditStokSpecs(st.id_pola, st.id_spesifikasi);
    document.getElementById('edit-stok-warna').value = st.nama_warna || '';
    document.getElementById('edit-stok-pcs').value = st.jumlah_stok_pcs;
    openModal('modal-edit-stok-jadi');
  };

  document.getElementById('form-edit-stok-jadi')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('edit-stok-id').value;
    const payload = {
      id_pola: Number(document.getElementById('edit-stok-pola').value),
      id_spesifikasi: Number(document.getElementById('edit-stok-spesifikasi').value),
      nama_warna: document.getElementById('edit-stok-warna').value.trim(),
      jumlah_stok_pcs: Number(document.getElementById('edit-stok-pcs').value)
    };

    fetch('/stok/' + id + '/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message || 'Data stok barang jadi berhasil diperbarui.');
        window.location.reload();
      } else {
        alert('Gagal memperbarui stok: ' + (data.message || 'Validasi gagal'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('Terjadi kesalahan saat menyimpan perubahan stok barang jadi.');
    });
  });

  if (window.lucide) {
    window.lucide.createIcons();
  }
</script>
@endpush
@endsection
