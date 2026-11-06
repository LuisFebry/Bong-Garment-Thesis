@php
  $currentRole = $_COOKIE['bong_garment_role'] ?? request()->cookie('bong_garment_role', 'Admin');
  $validRoles = ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang'];
  if (!in_array($currentRole, $validRoles)) {
      $currentRole = 'Admin';
  }

  $roleUsers = [
      'Admin' => ['id' => 1, 'name' => 'Luis Febry (Admin Produksi)', 'username' => '@admin', 'initials' => 'LF'],
      'Pemilik' => ['id' => 2, 'name' => 'Bong (Pemilik CMT)', 'username' => '@pemilik', 'initials' => 'BG'],
      'Pemotong' => ['id' => 3, 'name' => 'Budi Santoso (Head Cutter)', 'username' => '@pemotong', 'initials' => 'BS'],
      'QC' => ['id' => 4, 'name' => 'Siti Rahma (Lead QC)', 'username' => '@qc', 'initials' => 'SR'],
      'Gudang' => ['id' => 5, 'name' => 'Agus Hermawan (Kepala Gudang)', 'username' => '@gudang', 'initials' => 'AH'],
  ];
  $activeUser = $roleUsers[$currentRole] ?? $roleUsers['Admin'];

  $showPesanan = in_array($currentRole, ['Admin']);
  $showPenerimaan = in_array($currentRole, ['Admin', 'Gudang']);
  $showPemotongan = in_array($currentRole, ['Admin', 'Pemotong']);
  $showQc = in_array($currentRole, ['Admin', 'QC']);
  $showStok = in_array($currentRole, ['Admin', 'Gudang', 'Pemilik']);
  $showLaporan = in_array($currentRole, ['Admin', 'Pemilik']);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Bong Garment - Sistem Produksi & Inventori CMT')</title>

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Immediate Anti-FOUC Cookie & Role Sync Script -->
  <script>
    (function () {
      var saved = localStorage.getItem('bong_garment_role');
      if (saved) {
        document.cookie = "bong_garment_role=" + encodeURIComponent(saved) + "; path=/; max-age=31536000; SameSite=Lax";
      }
    })();
  </script>

  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          },
          colors: {
            brand: {
              50: '#eef2ff',
              100: '#e0e7ff',
              200: '#c7d2fe',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
              900: '#312e81',
            }
          }
        }
      }
    }
  </script>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    @media print {
      aside, header, .no-print { display: none !important; }
      body, main { height: auto !important; overflow: visible !important; background: #fff !important; padding: 0 !important; }
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex h-screen overflow-hidden">

  <!-- Left Sidebar -->
  <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 z-30 select-none">
    <div>
      <!-- Brand Header -->
      <div class="h-16 flex items-center px-6 border-b border-slate-100 gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-100">
          <i data-lucide="scissors" class="w-5 h-5"></i>
        </div>
        <div>
          <h1 class="font-bold text-slate-900 tracking-tight text-base leading-none">BONG GARMENT</h1>
          <p class="text-[11px] text-slate-400 font-medium tracking-wider uppercase mt-1">SISTEM PRODUKSI CMT</p>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="p-4 space-y-1">
        <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">MENU NAVIGASI</p>

        <a href="{{ route('dashboard', [], false) }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') || request()->is('/') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="layout-grid" class="w-4 h-4"></i>
          <span>Dashboard</span>
        </a>

        <a href="{{ route('pesanan.index', [], false) }}" data-roles="Admin" style="{{ $showPesanan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pesanan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i>
          <span>Pesanan & Pola</span>
        </a>

        <a href="{{ route('penerimaan.index', [], false) }}" data-roles="Admin,Gudang" style="{{ $showPenerimaan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('penerimaan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="package-check" class="w-4 h-4"></i>
          <span>Penerimaan Bahan</span>
        </a>

        <a href="{{ route('pemotongan.index', [], false) }}" data-roles="Admin,Pemotong" style="{{ $showPemotongan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pemotongan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="scissors" class="w-4 h-4"></i>
          <span>Pemotongan Seri</span>
        </a>

        <a href="{{ route('qc.index', [], false) }}" data-roles="Admin,QC" style="{{ $showQc ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('qc.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="shield-alert" class="w-4 h-4"></i>
          <span>Quality Control</span>
        </a>

        <a href="{{ route('stok.index', [], false) }}" data-roles="Admin,Gudang,Pemilik" style="{{ $showStok ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('stok.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="boxes" class="w-4 h-4"></i>
          <span>Gudang & Aksesoris</span>
        </a>

        <a href="{{ route('laporan.index', [], false) }}" data-roles="Admin,Pemilik" style="{{ $showLaporan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('laporan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="pie-chart" class="w-4 h-4"></i>
          <span>Laporan Eksekutif</span>
        </a>
      </nav>
    </div>

    <!-- User / Session Profile Card Footer -->
    <div class="p-4 border-t border-slate-100">
      <div class="flex items-center justify-between gap-2 p-2.5 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <div id="sidebar-user-initials" class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0">
            {{ $activeUser['initials'] }}
          </div>
          <div class="overflow-hidden">
            <p id="sidebar-user-name" class="text-xs font-semibold text-slate-800 truncate">{{ $activeUser['name'] }}</p>
            <p class="text-[10px] text-slate-400 font-mono">Luis Febry &bull; 825220088</p>
          </div>
        </div>
        <a href="{{ route('login', [], false) }}" title="Ganti Akun / Login" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors shrink-0">
          <i data-lucide="log-out" class="w-4 h-4"></i>
        </a>
      </div>
      <p class="text-[10px] text-slate-400 text-center mt-2 font-medium">Skripsi SI UNTAR &copy; 2026</p>
    </div>
  </aside>

  <!-- Main Content Area -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Navigation Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between shrink-0 z-20">
      <!-- Left: System Context Info -->
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          Sistem Produksi Aktif
        </span>
        <span class="hidden sm:inline-block text-xs text-slate-400 font-medium">
          Manajemen Produksi &amp; Inventori Konveksi CMT (EDF Scheduling)
        </span>
      </div>

      <!-- Header Right: Active Account, Role Switcher & Logout -->
      <div class="flex items-center gap-3">
        <!-- Mode Audit Indicator (for Pemilik) -->
        <div id="audit-mode-badge" class="{{ $currentRole === 'Pemilik' ? '' : 'hidden' }} flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium">
          <i data-lucide="eye" class="w-3.5 h-3.5"></i>
          <span>Mode Audit (Read-Only)</span>
        </div>

        <!-- Role Selector Dropdown -->
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1">
          <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-600"></i>
          <label for="role-select" class="text-xs text-slate-500 font-medium">Peran Aktif:</label>
          <select id="role-select" class="text-xs font-semibold bg-transparent text-indigo-900 border-none focus:ring-0 cursor-pointer pr-6 py-1">
            <option value="Admin" {{ $currentRole === 'Admin' ? 'selected' : '' }}>Admin (Penuh)</option>
            <option value="Pemilik" {{ $currentRole === 'Pemilik' ? 'selected' : '' }}>Pemilik (Mode Audit)</option>
            <option value="Pemotong" {{ $currentRole === 'Pemotong' ? 'selected' : '' }}>Pemotong (Cutting)</option>
            <option value="QC" {{ $currentRole === 'QC' ? 'selected' : '' }}>QC (Inspeksi)</option>
            <option value="Gudang" {{ $currentRole === 'Gudang' ? 'selected' : '' }}>Gudang (Bahan & Stok)</option>
          </select>
        </div>

        <!-- Login / Switch User Page Link -->
        <a href="{{ route('login', [], false) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-indigo-600 text-xs font-semibold flex items-center gap-1.5 transition-colors">
          <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
          <span class="hidden md:inline">Halaman Login</span>
        </a>
      </div>
    </header>

    <!-- Main Dynamic Content Container -->
    <main class="flex-1 overflow-y-auto p-8">
      @yield('content')
    </main>
  </div>

  <!-- Global Application Scripts -->
  <script>
    if (window.lucide) {
      lucide.createIcons();
    }

    // Modal Helper functions
    window.openModal = function (id) {
      const el = document.getElementById(id);
      if (el) el.classList.remove('hidden');
      if (window.lucide) lucide.createIcons();
    };
    window.closeModal = function (id) {
      const el = document.getElementById(id);
      if (el) el.classList.add('hidden');
    };

    // Global fetch interceptor to append current role and CSRF header
    const originalFetch = window.fetch;
    window.fetch = function (url, options) {
      options = options || {};
      options.headers = options.headers || {};
      const role = localStorage.getItem('bong_garment_role') || '{{ $currentRole }}';

      if (options.headers instanceof Headers) {
        if (!options.headers.has('X-User-Role')) options.headers.set('X-User-Role', role);
      } else {
        options.headers['X-User-Role'] = options.headers['X-User-Role'] || role;
      }
      return originalFetch(url, options);
    };

    document.addEventListener('DOMContentLoaded', function () {
      const roleSelect = document.getElementById('role-select');
      const auditBadge = document.getElementById('audit-mode-badge');
      const navItems = document.querySelectorAll('.nav-item[data-roles]');

      const roleDirectory = {
        'Admin': { id: 1, name: 'Luis Febry (Admin Produksi)', initials: 'LF' },
        'Pemilik': { id: 2, name: 'Bong (Pemilik CMT)', initials: 'BG' },
        'Pemotong': { id: 3, name: 'Budi Santoso (Head Cutter)', initials: 'BS' },
        'QC': { id: 4, name: 'Siti Rahma (Lead QC)', initials: 'SR' },
        'Gudang': { id: 5, name: 'Agus Hermawan (Kepala Gudang)', initials: 'AH' }
      };

      function applyRole(role) {
        localStorage.setItem('bong_garment_role', role);
        document.cookie = "bong_garment_role=" + encodeURIComponent(role) + "; path=/; max-age=31536000; SameSite=Lax";
        if (roleSelect && roleSelect.value !== role) {
          roleSelect.value = role;
        }

        const profile = roleDirectory[role] || roleDirectory['Admin'];
        const nameEl = document.getElementById('sidebar-user-name');
        const initEl = document.getElementById('sidebar-user-initials');
        if (nameEl) nameEl.textContent = profile.name;
        if (initEl) initEl.textContent = profile.initials;

        if (auditBadge) {
          auditBadge.classList.toggle('hidden', role !== 'Pemilik');
        }

        navItems.forEach(function (item) {
          const roles = (item.dataset.roles || '').split(',').map(function (r) { return r.trim(); });
          if (roles.includes('Admin') && role === 'Admin') {
            item.style.display = '';
          } else if (roles.includes(role)) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      }

      // Sync role on load if localStorage differs from cookie
      const savedRole = localStorage.getItem('bong_garment_role') || '{{ $currentRole }}';
      if (savedRole !== '{{ $currentRole }}') {
        applyRole(savedRole);
      }

      if (roleSelect) {
        roleSelect.addEventListener('change', function () {
          const newRole = this.value;
          localStorage.setItem('bong_garment_role', newRole);
          document.cookie = "bong_garment_role=" + encodeURIComponent(newRole) + "; path=/; max-age=31536000; SameSite=Lax";

          const path = window.location.pathname;
          const rolePermissions = {
            'Admin': ['*'],
            'Pemilik': ['/', '/dashboard', '/stok', '/laporan'],
            'Pemotong': ['/', '/dashboard', '/pemotongan'],
            'QC': ['/', '/dashboard', '/qc'],
            'Gudang': ['/', '/dashboard', '/penerimaan', '/stok', '/aksesoris']
          };

          const allowed = rolePermissions[newRole] || ['/dashboard'];
          const canStay = allowed.includes('*') || allowed.some(function (p) {
            return path === p || path.startsWith(p + '/');
          });

          if (canStay) {
            window.location.reload();
          } else {
            alert("Peran diubah menjadi '" + newRole + "'. Anda dialihkan ke Dashboard karena peran ini tidak memiliki akses ke halaman saat ini.");
            window.location.href = '/dashboard';
          }
        });
      }

      // CSRF token helper
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      // Window helper: Revise Deadline
      window.openReviseDeadlineModal = function (id, dlAwal, dlTerbaru) {
        const currentTarget = dlTerbaru || dlAwal || new Date().toISOString().split('T')[0];
        const newDeadline = prompt('Masukkan Deadline Terbaru (YYYY-MM-DD):', currentTarget);
        if (!newDeadline) return;

        const reason = prompt('Alasan Revisi Deadline (Contoh: Permintaan percepatan mitra / antrean meja potong):', 'Penyesuaian Antrean Pemotongan');
        if (!reason) return;

        fetch('/pesanan/' + id + '/revise-deadline', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            deadline_terbaru: newDeadline,
            alasan_perubahan_deadline: reason
          })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            alert(data.message || 'Deadline berhasil direvisi!');
            window.location.reload();
          } else {
            alert('Gagal merevisi deadline: ' + (data.message || 'Terjadi kesalahan'));
          }
        })
        .catch(function (err) {
          console.error(err);
          alert('Terjadi kesalahan saat memproses permintaan.');
        });
      };

      // Window helper: Complete QC Rework with actual completion date/time
      window.completeRework = function (id) {
        const nowStr = new Date().toISOString().slice(0, 16).replace('T', ' ');
        const tglSelesai = prompt('Masukkan Tanggal & Waktu Aktual Selesai Perbaikan Rework (YYYY-MM-DD HH:MM):', nowStr);
        if (!tglSelesai) return;

        fetch('/qc/' + id + '/complete-rework', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            tgl_selesai_perbaikan: tglSelesai
          })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            alert(data.message || 'Rework berhasil diselesaikan!');
            window.location.reload();
          } else {
            alert('Gagal menyelesaikan rework: ' + (data.message || 'Terjadi kesalahan'));
          }
        })
        .catch(function (err) {
          console.error(err);
          alert('Terjadi kesalahan saat memproses permintaan.');
        });
      };
    });
  </script>
  @stack('scripts')
</body>
</html>
