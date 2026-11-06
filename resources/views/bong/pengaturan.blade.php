@extends('layouts.app')

@section('title', 'Pengaturan & Manajemen Pengguna — Bong Garment')

@section('content')
@php
  $activeUserId = $_COOKIE['bong_garment_user_id'] ?? request()->cookie('bong_garment_user_id');
@endphp

<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Sistem & Manajemen Pengguna</h2>
      <p class="text-xs text-slate-500 mt-1">Kelola akun & password pengguna (<code class="font-mono text-indigo-600">User</code>), referensi master (<code class="font-mono text-indigo-600">Kategori_Pola</code>, <code class="font-mono text-indigo-600">Sumber_Inisiasi</code>, <code class="font-mono text-indigo-600">Supplier</code>), serta reset basis data produksi</p>
    </div>
    <div>
      <button type="button" onclick="openAddUserModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        <span>+ Tambah Akun Pengguna</span>
      </button>
    </div>
  </div>

  <!-- 1. Manajemen Akun & Otorisasi Pengguna (Tabel User) -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <i data-lucide="users" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">1. Daftar Akun Pengguna & Peran Otorisasi (<code class="font-mono text-indigo-600">User</code>)</h3>
          <p class="text-[11px] text-slate-500">Ubah nama pengguna, username login, password, atau tambahkan akun staf operasional baru</p>
        </div>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
        {{ count($users ?? []) }} Akun Terdaftar
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
            <th class="pb-2">ID</th>
            <th class="pb-2">Nama Lengkap / Jabatan</th>
            <th class="pb-2">Username Login</th>
            <th class="pb-2">Peran Otorisasi (RBAC)</th>
            <th class="pb-2">Hak Akses Modul</th>
            <th class="pb-2 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($users ?? [] as $u)
          @php
            $isMe = (int)$activeUserId === (int)$u->id_user;
            $roleAccessText = [
              'Admin' => 'Akses Penuh Seluruh Modul & Pengaturan',
              'Pemilik' => 'Mode Audit (Read-Only): Dashboard, Gudang & Laporan Eksekutif',
              'Pemotong' => 'Dashboard, Antrean EDF & Pemotongan Seri',
              'QC' => 'Dashboard & Quality Control (Prototipe, Potong, Jahit Akhir)',
              'Gudang' => 'Dashboard, Penerimaan Bahan, Stok Barang Jadi & Aksesoris',
            ][$u->role] ?? '-';
          @endphp
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#{{ $u->id_user }}</td>
            <td class="py-3 font-semibold text-slate-800">
              {{ $u->nama_user }}
              @if($isMe)
                <span class="ml-1.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Sesi Anda</span>
              @endif
            </td>
            <td class="py-3 font-mono text-indigo-700 font-semibold">@ {{ $u->username }}</td>
            <td class="py-3">
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold
                {{ $u->role === 'Admin' ? 'bg-indigo-100 text-indigo-800' : ($u->role === 'Pemilik' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                {{ $u->role }}
              </span>
            </td>
            <td class="py-3 text-[11px] text-slate-500">{{ $roleAccessText }}</td>
            <td class="py-3 text-right">
              <div class="inline-flex items-center justify-end gap-1.5">
                <button type="button"
                  onclick="openEditUserModal({{ json_encode([
                    'id_user' => $u->id_user,
                    'nama_user' => $u->nama_user,
                    'username' => $u->username,
                    'role' => $u->role,
                  ]) }})"
                  class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1 transition-colors">
                  <i data-lucide="key-round" class="w-3 h-3"></i> Edit / Password
                </button>
                @if(!$isMe)
                <button type="button"
                  onclick="deleteUser({{ $u->id_user }}, '{{ addslashes($u->nama_user) }}')"
                  class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1 transition-colors">
                  <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
                </button>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- 2. Manajemen Basis Data Produksi (Mulai Bersih / Reset Operasional vs. Data Contoh) -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
          <i data-lucide="database" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-sm">2. Manajemen Data Produksi (Mulai Bersih / Reset Data Contoh)</h3>
          <p class="text-[11px] text-slate-500">Kosongkan data contoh (sample data) untuk mulai menggunakan sistem dari nol pada operasional pabrik nyata</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5 mb-5">
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['pesanan'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Pesanan</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['penerimaan'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Nota Kain</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['roll'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Roll Fisik</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['pemotongan'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Seri Potong</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['qc'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Log QC</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['stok_jadi'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Stok Jadi</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['pola'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Master Pola</div>
      </div>
      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono text-slate-800">{{ $counts['aksesoris'] ?? 0 }}</div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase">Aksesoris</div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/50 flex flex-col justify-between space-y-3">
        <div>
          <h4 class="font-bold text-xs text-rose-900">Mode Produksi Bersih (Hapus Seluruh Data Transaksi / Contoh)</h4>
          <p class="text-[11px] text-rose-700 mt-1">
            Menghapus seluruh catatan Pesanan, Penerimaan & Roll Kain, Pemotongan Seri, Log QC, dan Stok Barang Jadi agar Anda dapat menginput data pabrik dari nol. <b>Akun Pengguna (<code class="font-mono">User</code>) tidak akan terhapus.</b>
          </p>
          <label class="inline-flex items-center gap-2 mt-2.5 text-xs font-medium text-rose-900 cursor-pointer">
            <input type="checkbox" id="chk-reset-include-master" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
            <span>Kosongkan juga Katalog Master Pola, Aksesoris & Supplier</span>
          </label>
        </div>
        <div>
          <button type="button" onclick="resetOperationalDatabase()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            <span>Kosongkan Data Operasional Sekarang</span>
          </button>
        </div>
      </div>

      <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex flex-col justify-between space-y-3">
        <div>
          <h4 class="font-bold text-xs text-slate-900">Muat Data Contoh Produksi (Sample Dataset)</h4>
          <p class="text-[11px] text-slate-600 mt-1">
            Mengisi tabel dengan data contoh produksi CMT (contoh pesanan, roll kain, pemotongan seri, dan QC) untuk keperluan simulasi atau pelatihan staf. <b>Akun Pengguna (<code class="font-mono">User</code>) Anda tetap dipertahankan.</b>
          </p>
        </div>
        <div>
          <button type="button" onclick="seedSampleDatabase()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg shadow-sm inline-flex items-center gap-1.5 transition-colors">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            <span>Muat Data Contoh</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Manajemen Tabel Referensi Master (Kategori_Pola, Sumber_Inisiasi, Supplier) -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Kategori Pola -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
        <div>
          <h3 class="font-bold text-slate-900 text-xs">Kategori Pola (<code class="font-mono text-indigo-600">Kategori_Pola</code>)</h3>
          <p class="text-[10px] text-slate-500">Klasifikasi jenis pakaian</p>
        </div>
      </div>
      <form id="form-add-kategori" class="flex gap-2 mb-3">
        <input type="text" id="inp-new-kategori" required placeholder="Tambah kategori baru..." class="flex-1 text-xs p-2 border border-slate-200 rounded-lg">
        <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg">+ Tambah</button>
      </form>
      <div class="divide-y divide-slate-100 text-xs">
        @foreach($kategori ?? [] as $kat)
        <div class="py-2 flex items-center justify-between gap-2">
          <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#{{ $kat->id_kategori }}</span> {{ $kat->nama_kategori }}</span>
          <div class="inline-flex items-center gap-1">
            <button type="button" onclick="editKategori({{ $kat->id_kategori }}, '{{ addslashes($kat->nama_kategori) }}')" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteKategori({{ $kat->id_kategori }})" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Sumber Inisiasi -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
        <div>
          <h3 class="font-bold text-slate-900 text-xs">Sumber Inisiasi (<code class="font-mono text-indigo-600">Sumber_Inisiasi</code>)</h3>
          <p class="text-[10px] text-slate-500">Asal order produksi</p>
        </div>
      </div>
      <form id="form-add-sumber" class="flex gap-2 mb-3">
        <input type="text" id="inp-new-sumber" required placeholder="Tambah sumber baru..." class="flex-1 text-xs p-2 border border-slate-200 rounded-lg">
        <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg">+ Tambah</button>
      </form>
      <div class="divide-y divide-slate-100 text-xs">
        @foreach($sumber ?? [] as $sb)
        <div class="py-2 flex items-center justify-between gap-2">
          <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#{{ $sb->id_sumber }}</span> {{ $sb->nama_sumber }}</span>
          <div class="inline-flex items-center gap-1">
            <button type="button" onclick="editSumber({{ $sb->id_sumber }}, '{{ addslashes($sb->nama_sumber) }}')" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteSumber({{ $sb->id_sumber }})" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Daftar Supplier -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
        <div>
          <h3 class="font-bold text-slate-900 text-xs">Daftar Supplier (<code class="font-mono text-indigo-600">Supplier</code>)</h3>
          <p class="text-[10px] text-slate-500">Mitra pemasok kain & aksesoris</p>
        </div>
      </div>
      <div class="divide-y divide-slate-100 text-xs max-h-64 overflow-y-auto">
        @forelse($suppliers ?? [] as $sp)
        <div class="py-2 flex items-center justify-between gap-2">
          <div>
            <span class="font-semibold text-slate-800 block">{{ $sp->nama_supplier }}</span>
            <span class="text-[10px] text-slate-400">{{ $sp->kontak ?? '-' }}</span>
          </div>
          <div class="inline-flex items-center gap-1 shrink-0">
            <button type="button" onclick="editSupplier({{ $sp->id_supplier }}, '{{ addslashes($sp->nama_supplier) }}', '{{ addslashes($sp->kontak ?? '') }}', '{{ addslashes($sp->alamat ?? '') }}')" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
            <button type="button" onclick="deleteSupplier({{ $sp->id_supplier }})" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
          </div>
        </div>
        @empty
        <p class="text-[11px] text-slate-400 italic py-3">Belum ada supplier terdaftar.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Pengguna Baru -->
<div id="modal-add-user" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Tambah Akun Pengguna Baru</h3>
        <p class="text-xs text-slate-500">Daftarkan akun staf baru ke tabel <code class="font-mono text-indigo-600">User</code></p>
      </div>
      <button type="button" onclick="closeModal('modal-add-user')" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form id="form-add-user" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Jabatan <span class="text-rose-500">*</span></label>
        <input type="text" id="add-user-nama" required placeholder="Contoh: Hendra Wijaya (Admin Produksi)" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login <span class="text-rose-500">*</span></label>
        <input type="text" id="add-user-username" required placeholder="Contoh: hendra" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
        <input type="password" id="add-user-password" required minlength="4" placeholder="Minimal 4 karakter" class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Otorisasi (Role) <span class="text-rose-500">*</span></label>
        <select id="add-user-role" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white font-semibold">
          <option value="Admin">Admin (Akses Penuh)</option>
          <option value="Pemilik">Pemilik (Mode Audit Read-Only)</option>
          <option value="Pemotong">Pemotong (Meja Potong & Antrean EDF)</option>
          <option value="QC">QC (Inspeksi Mutu)</option>
          <option value="Gudang">Gudang (Bahan & Inventori)</option>
        </select>
      </div>
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-add-user')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Akun</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Pengguna & Ubah Password -->
<div id="modal-edit-user" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Edit Akun Pengguna & Password</h3>
        <p class="text-xs text-slate-500">Perbarui identitas, peran, atau ganti password login</p>
      </div>
      <button type="button" onclick="closeModal('modal-edit-user')" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form id="form-edit-user" class="space-y-4">
      <input type="hidden" id="edit-user-id">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Jabatan <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-user-nama" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-user-username" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin mengubah password)</span></label>
        <input type="password" id="edit-user-password" minlength="4" placeholder="Ketik password baru untuk mengganti..." class="w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Otorisasi (Role) <span class="text-rose-500">*</span></label>
        <select id="edit-user-role" required class="w-full text-xs p-2.5 border border-slate-200 rounded-lg bg-white font-semibold">
          <option value="Admin">Admin (Akses Penuh)</option>
          <option value="Pemilik">Pemilik (Mode Audit Read-Only)</option>
          <option value="Pemotong">Pemotong (Meja Potong & Antrean EDF)</option>
          <option value="QC">QC (Inspeksi Mutu)</option>
          <option value="Gudang">Gudang (Bahan & Inventori)</option>
        </select>
      </div>
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
        <button type="button" onclick="closeModal('modal-edit-user')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function openAddUserModal() {
    const m = document.getElementById('modal-add-user');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  function openEditUserModal(u) {
    document.getElementById('edit-user-id').value = u.id_user;
    document.getElementById('edit-user-nama').value = u.nama_user || '';
    document.getElementById('edit-user-username').value = u.username || '';
    document.getElementById('edit-user-password').value = '';
    document.getElementById('edit-user-role').value = u.role || 'Admin';
    const m = document.getElementById('modal-edit-user');
    m.classList.remove('hidden');
    m.classList.add('flex');
  }

  document.getElementById('form-add-user')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/pengaturan/user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        nama_user: document.getElementById('add-user-nama').value.trim(),
        username: document.getElementById('add-user-username').value.trim(),
        password: document.getElementById('add-user-password').value,
        role: document.getElementById('add-user-role').value
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { alert(d.message); window.location.reload(); }
      else { alert('Gagal menambah pengguna: ' + (d.message || 'Validasi gagal')); }
    });
  });

  document.getElementById('form-edit-user')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('edit-user-id').value;
    fetch(`/pengaturan/user/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        nama_user: document.getElementById('edit-user-nama').value.trim(),
        username: document.getElementById('edit-user-username').value.trim(),
        password: document.getElementById('edit-user-password').value || null,
        role: document.getElementById('edit-user-role').value
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { alert(d.message); window.location.reload(); }
      else { alert('Gagal memperbarui pengguna: ' + (d.message || 'Validasi gagal')); }
    });
  });

  function deleteUser(id, nama) {
    if (!confirm(`Hapus akun pengguna "${nama}" (#${id})?`)) return;
    fetch(`/pengaturan/user/${id}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { alert(d.message); window.location.reload(); }
      else { alert(d.message || 'Gagal menghapus pengguna.'); }
    });
  }

  function resetOperationalDatabase() {
    const includeMaster = document.getElementById('chk-reset-include-master')?.checked || false;
    const msg = includeMaster
      ? 'PERINGATAN: Anda akan mengosongkan SELURUH data transaksi operasional DAN katalog master (Pola, Aksesoris, Supplier). Akun pengguna tetap disimpan. Lanjutkan?'
      : 'Konfirmasi: Kosongkan seluruh data transaksi operasional (Pesanan, Nota & Roll Bahan, Pemotongan, QC, Stok Jadi) untuk memulai produksi bersih?';
    if (!confirm(msg)) return;

    fetch('/pengaturan/reset-operational', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ include_master: includeMaster })
    })
    .then(r => r.json())
    .then(d => {
      alert(d.message || 'Basis data berhasil dikosongkan.');
      window.location.reload();
    });
  }

  function seedSampleDatabase() {
    if (!confirm('Muat ulang data contoh produksi CMT ke dalam basis data? (Akun pengguna Anda tidak akan diubah)')) return;
    fetch('/pengaturan/seed-sample', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(d => {
      alert(d.message || 'Data contoh berhasil dimuat.');
      window.location.reload();
    });
  }

  document.getElementById('form-add-kategori')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/pengaturan/kategori', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ nama_kategori: document.getElementById('inp-new-kategori').value.trim() })
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  });

  function editKategori(id, oldName) {
    const val = prompt('Nama Kategori Pola:', oldName);
    if (!val || !val.trim()) return;
    fetch(`/pengaturan/kategori/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ nama_kategori: val.trim() })
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }

  function deleteKategori(id) {
    if (!confirm('Hapus kategori pola ini?')) return;
    fetch(`/pengaturan/kategori/${id}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }

  document.getElementById('form-add-sumber')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/pengaturan/sumber', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ nama_sumber: document.getElementById('inp-new-sumber').value.trim() })
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  });

  function editSumber(id, oldName) {
    const val = prompt('Nama Sumber Inisiasi:', oldName);
    if (!val || !val.trim()) return;
    fetch(`/pengaturan/sumber/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ nama_sumber: val.trim() })
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }

  function deleteSumber(id) {
    if (!confirm('Hapus sumber inisiasi ini?')) return;
    fetch(`/pengaturan/sumber/${id}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }

  function editSupplier(id, nama, kontak, alamat) {
    const newNama = prompt('Nama Supplier:', nama);
    if (!newNama || !newNama.trim()) return;
    const newKontak = prompt('Kontak / Telepon Supplier:', kontak || '');
    const newAlamat = prompt('Alamat Supplier:', alamat || '');
    fetch(`/pengaturan/supplier/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ nama_supplier: newNama.trim(), kontak: newKontak, alamat: newAlamat })
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }

  function deleteSupplier(id) {
    if (!confirm('Hapus supplier ini?')) return;
    fetch(`/pengaturan/supplier/${id}/delete`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message); });
  }
</script>
@endpush
@endsection
