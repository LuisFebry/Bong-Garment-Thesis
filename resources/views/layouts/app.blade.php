@php
  $currentRole = $_COOKIE['bong_garment_role'] ?? request()->cookie('bong_garment_role', 'Admin');
  $validRoles = ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang'];
  if (!in_array($currentRole, $validRoles)) {
      $currentRole = 'Admin';
  }

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
    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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

        <a href="{{ route('dashboard') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') || request()->is('/') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="layout-grid" class="w-4 h-4"></i>
          <span>Dashboard</span>
        </a>

        <a href="{{ route('pesanan.index') }}" data-roles="Admin" style="{{ $showPesanan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pesanan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i>
          <span>Pesanan & Pola</span>
        </a>

        <a href="{{ route('penerimaan.index') }}" data-roles="Admin,Gudang" style="{{ $showPenerimaan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('penerimaan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="package-check" class="w-4 h-4"></i>
          <span>Penerimaan Bahan</span>
        </a>

        <a href="{{ route('pemotongan.index') }}" data-roles="Admin,Pemotong" style="{{ $showPemotongan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pemotongan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="scissors" class="w-4 h-4"></i>
          <span>Pemotongan Seri</span>
        </a>

        <a href="{{ route('qc.index') }}" data-roles="Admin,QC" style="{{ $showQc ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('qc.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="shield-alert" class="w-4 h-4"></i>
          <span>Quality Control</span>
        </a>

        <a href="{{ route('stok.index') }}" data-roles="Admin,Gudang,Pemilik" style="{{ $showStok ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('stok.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="boxes" class="w-4 h-4"></i>
          <span>Gudang & Aksesoris</span>
        </a>

        <a href="{{ route('laporan.index') }}" data-roles="Admin,Pemilik" style="{{ $showLaporan ? '' : 'display: none !important;' }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('laporan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="pie-chart" class="w-4 h-4"></i>
          <span>Laporan Eksekutif</span>
        </a>
      </nav>
    </div>

    <!-- User / Thesis Badge Footer -->
    <div class="p-4 border-t border-slate-100">
      <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-100">
        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
          LF
        </div>
        <div class="overflow-hidden">
          <p class="text-xs font-semibold text-slate-800 truncate">Luis Febry</p>
          <p class="text-[10px] text-slate-400 font-mono">NIM: 825220088</p>
        </div>
      </div>
      <p class="text-[10px] text-slate-400 text-center mt-2 font-medium">Skripsi SI UNTAR &copy; 2026</p>
    </div>
  </aside>

  <!-- Main Content Area -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Navigation Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between shrink-0 z-20">
      <!-- Search Input -->
      <div class="relative w-80">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" id="global-search" placeholder="Cari pesanan, nota, seri..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700">
      </div>

      <!-- Header Right: Role Switcher & Audit Mode Badge -->
      <div class="flex items-center gap-4">
        <!-- Mode Audit Indicator (for Pemilik) -->
        <div id="audit-mode-badge" class="{{ $currentRole === 'Pemilik' ? '' : 'hidden' }} flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium">
          <i data-lucide="eye" class="w-3.5 h-3.5"></i>
          <span>Mode Audit (Read-Only)</span>
        </div>

        <!-- Role Selector Dropdown -->
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1">
          <label for="role-select" class="text-xs text-slate-500 font-medium">Peran Aktif:</label>
          <select id="role-select" class="text-xs font-semibold bg-transparent text-indigo-900 border-none focus:ring-0 cursor-pointer pr-6 py-1">
            <option value="Admin" {{ $currentRole === 'Admin' ? 'selected' : '' }}>Admin (Penuh)</option>
            <option value="Pemilik" {{ $currentRole === 'Pemilik' ? 'selected' : '' }}>Pemilik (Mode Audit)</option>
            <option value="Pemotong" {{ $currentRole === 'Pemotong' ? 'selected' : '' }}>Pemotong (Cutting)</option>
            <option value="QC" {{ $currentRole === 'QC' ? 'selected' : '' }}>QC (Inspeksi)</option>
            <option value="Gudang" {{ $currentRole === 'Gudang' ? 'selected' : '' }}>Gudang (Bahan & Stok)</option>
          </select>
        </div>
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
      const role = localStorage.getItem('bong_garment_role') || 'Admin';

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

      function applyRole(role) {
        localStorage.setItem('bong_garment_role', role);
        document.cookie = "bong_garment_role=" + encodeURIComponent(role) + "; path=/; max-age=31536000; SameSite=Lax";
        if (roleSelect && roleSelect.value !== role) {
          roleSelect.value = role;
        }

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

          // Route permission check on role change
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

      // Window helper: Complete QC Rework
      window.completeRework = function (id) {
        if (!confirm('Tandai inspeksi QC #' + id + ' sebagai Rework Selesai?')) return;

        fetch('/qc/' + id + '/complete-rework', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          }
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

      // Real-time global search across tables
      const searchInput = document.getElementById('global-search');
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          const query = this.value.toLowerCase().trim();
          const rows = document.querySelectorAll('main table tbody tr');
          rows.forEach(function (row) {
            if (row.querySelector('td[colspan]')) return;
            const text = row.textContent.toLowerCase();
            row.style.display = (!query || text.includes(query)) ? '' : 'none';
          });
        });
      }
    });
  </script>
  @stack('scripts')
</body>
</html>