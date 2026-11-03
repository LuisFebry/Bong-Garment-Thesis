<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Bong Garment — Sistem Produksi & Inventori CMT')</title>

  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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

        <a href="{{ route('pesanan.index') }}" data-roles="Admin" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pesanan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i>
          <span>Pesanan & Pola</span>
        </a>

        <a href="{{ route('penerimaan.index') }}" data-roles="Admin,Gudang" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('penerimaan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="package-check" class="w-4 h-4"></i>
          <span>Penerimaan Bahan</span>
        </a>

        <a href="{{ route('pemotongan.index') }}" data-roles="Admin,Pemotong" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pemotongan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="scissors" class="w-4 h-4"></i>
          <span>Pemotongan Seri</span>
        </a>

        <a href="{{ route('qc.index') }}" data-roles="Admin,QC" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('qc.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="shield-alert" class="w-4 h-4"></i>
          <span>Quality Control</span>
        </a>

        <a href="{{ route('stok.index') }}" data-roles="Admin,Gudang" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('stok.*') || request()->routeIs('aksesoris.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="boxes" class="w-4 h-4"></i>
          <span>Gudang & Aksesoris</span>
        </a>

        <a href="{{ route('laporan.index') }}" data-roles="Admin,Pemilik" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('laporan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
          <i data-lucide="file-bar-chart" class="w-4 h-4"></i>
          <span>Laporan Eksekutif</span>
        </a>
      </nav>
    </div>

    <!-- User & Role Footer -->
    <div class="p-4 border-t border-slate-100">
      <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg">
        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs">
          LF
        </div>
        <div class="overflow-hidden">
          <p class="text-xs font-semibold text-slate-900 truncate">Luis Febry</p>
          <p class="text-[10px] text-slate-500 font-mono">NIM: 825220088</p>
        </div>
      </div>
      <p class="text-[10px] text-center text-slate-400 mt-2">Skripsi SI UNTAR &copy; 2026</p>
    </div>
  </aside>

  <!-- Main Container -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Bar -->
    <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between sticky top-0 z-20">
      <!-- Search & Breadcrumb -->
      <div class="flex items-center gap-4">
        <div class="relative w-72">
          <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input type="text" id="global-search" placeholder="Cari pesanan, nota, seri..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
        </div>
      </div>

      <!-- Role Switcher & Status -->
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-slate-500">Peran Aktif:</span>
          <select id="role-select" class="text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="Admin">Admin (Penuh)</option>
            <option value="Pemilik">Pemilik (Mode Audit)</option>
            <option value="Pemotong">Pemotong (Cutting)</option>
            <option value="QC">QC (Inspeksi)</option>
            <option value="Gudang">Gudang (Bahan & Stok)</option>
          </select>
        </div>

        <div id="audit-mode-badge" class="hidden px-2.5 py-1 bg-amber-100 text-amber-800 text-[11px] font-semibold rounded-full flex items-center gap-1.5">
          <i data-lucide="eye" class="w-3.5 h-3.5"></i>
          <span>Mode Audit (Read-Only)</span>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-8">
      @yield('content')
    </main>
  </div>

  <!-- Lucide Script -->
  <script>
    lucide.createIcons();
  </script>
  <script src="{{ asset('js/app.js') }}"></script>

  <!-- Global Bridging JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // 1. Role synchronization & RBAC navigation filtering
      const roleSelect = document.getElementById('role-select');
      const auditBadge = document.getElementById('audit-mode-badge');
      const navItems = document.querySelectorAll('.nav-item[data-roles]');

      function applyRole(role) {
        localStorage.setItem('bong_garment_role', role);
        if (roleSelect && roleSelect.value !== role) {
          roleSelect.value = role;
        }

        // Toggle audit mode badge for Pemilik (Owner)
        if (auditBadge) {
          auditBadge.classList.toggle('hidden', role !== 'Pemilik');
        }

        // Filter navigation items based on data-roles
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

      const savedRole = localStorage.getItem('bong_garment_role') || 'Admin';
      applyRole(savedRole);

      if (roleSelect) {
        roleSelect.addEventListener('change', function () {
          applyRole(this.value);
        });
      }

      // CSRF token helper
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      // 2. Define window.openReviseDeadlineModal
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

      // 3. Define window.completeRework
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
    });
  </script>
  @stack('scripts')
</body>
</html>

