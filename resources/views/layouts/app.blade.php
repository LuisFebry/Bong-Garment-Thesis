@php
  $validRoles = ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang'];
  $authRole = $_COOKIE['bong_garment_auth_role'] ?? request()->cookie('bong_garment_auth_role');
  $cookieRole = $_COOKIE['bong_garment_role'] ?? request()->cookie('bong_garment_role');
  $authUserId = $_COOKIE['bong_garment_user_id'] ?? request()->cookie('bong_garment_user_id');

  // Query the actual authenticated user from the database so their real identity is always preserved
  $loggedInUser = null;
  if ($authUserId) {
      try {
          $loggedInUser = \App\Models\User::find($authUserId);
      } catch (\Throwable $e) {
          $loggedInUser = null;
      }
  }

  $trueUserRole = $loggedInUser ? $loggedInUser->role : ($authRole ?: 'Admin');
  $isAdminAccount = ($trueUserRole === 'Admin');

  if ($isAdminAccount && $cookieRole && in_array($cookieRole, $validRoles, true)) {
      $currentRole = $cookieRole;
  } elseif (in_array($trueUserRole, $validRoles, true)) {
      $currentRole = $trueUserRole;
  } else {
      $currentRole = 'Admin';
  }

  $displayUserName = $loggedInUser ? $loggedInUser->nama_user : ($_COOKIE['bong_garment_user_name'] ?? request()->cookie('bong_garment_user_name') ?? 'Administrator');
  // Clean up any legacy seeded name if still in cookie
  if (stripos($displayUserName, 'Luis Febry') !== false) {
      $displayUserName = 'Administrator';
  }
  $displayUsername = $loggedInUser ? ('@' . $loggedInUser->username) : '@admin';

  $words = preg_split('/\s+/', trim(preg_replace('/\([^)]*\)/', '', $displayUserName)));
  $initials = strtoupper(substr($words[0] ?? 'A', 0, 1) . substr($words[1] ?? ($words[0] ?? 'D'), 0, 1));

  $roleLabels = [
      'Admin' => 'Admin (Akses Penuh)',
      'Pemilik' => 'Pemilik (Mode Audit)',
      'Pemotong' => 'Pemotong (Cutting)',
      'QC' => 'QC (Inspeksi)',
      'Gudang' => 'Gudang (Bahan & Stok)',
  ];

  $showPesanan = in_array($currentRole, ['Admin']);
  $showPenerimaan = in_array($currentRole, ['Admin', 'Gudang']);
  $showPemotongan = in_array($currentRole, ['Admin', 'Pemotong']);
  $showQc = in_array($currentRole, ['Admin', 'QC']);
  $showStok = in_array($currentRole, ['Admin', 'Gudang', 'Pemilik']);
  $showLaporan = in_array($currentRole, ['Admin', 'Pemilik']);
  $showPengaturan = $isAdminAccount;
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

        @if($showPengaturan)
        <div class="pt-3 mt-3 border-t border-slate-100">
          <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ADMINISTRASI SISTEM</p>
          <a href="{{ route('pengaturan.index', [], false) }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pengaturan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="settings" class="w-4 h-4"></i>
            <span>Pengaturan & Pengguna</span>
          </a>
        </div>
        @endif
      </nav>
    </div>

    <!-- User / Session Profile Card Footer (Always shows the actual logged-in user's identity) -->
    <div class="p-4 border-t border-slate-100">
      <div class="flex items-center justify-between gap-2 p-2.5 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <div id="sidebar-user-initials" class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0">
            {{ $initials }}
          </div>
          <div class="overflow-hidden">
            <p id="sidebar-user-name" class="text-xs font-semibold text-slate-800 truncate">{{ $displayUserName }}</p>
            <p class="text-[10px] text-slate-500 font-mono truncate">{{ $displayUsername }} &bull; {{ $trueUserRole }}</p>
          </div>
        </div>
        <a href="{{ route('logout', [], false) }}" onclick="window.clearAuthStorage()" title="Keluar (Logout)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0">
          <i data-lucide="log-out" class="w-4 h-4"></i>
        </a>
      </div>
      <p class="text-[10px] text-slate-400 text-center mt-2 font-medium">&copy; 2026 Luis Febry</p>
    </div>
  </aside>

  <!-- Main Content Area -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top Navigation Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between shrink-0 z-20">
      <!-- Left: Welcome & Workspace Context -->
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-slate-500">
            Selamat Datang, <strong class="text-slate-800 font-semibold">{{ $displayUserName }}</strong>
          </span>
          <span class="hidden md:inline-block text-slate-300">&bull;</span>
          <span class="hidden md:inline-block text-xs text-slate-400 font-medium">
            Manajemen Produksi &amp; Inventori Konveksi CMT
          </span>
        </div>
      </div>

      <!-- Header Right: Active Account, Role View Simulator (Admin Only) & Logout -->
      <div class="flex items-center gap-3">
        <!-- Mode Audit Indicator (for Pemilik) -->
        <div id="audit-mode-badge" class="{{ $currentRole === 'Pemilik' ? '' : 'hidden' }} flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium">
          <i data-lucide="eye" class="w-3.5 h-3.5"></i>
          <span>Mode Audit (Read-Only)</span>
        </div>

        @if($isAdminAccount)
        <!-- Role View Simulator (Visible ONLY when logged in with an Admin account; keeps Admin identity while previewing role permissions) -->
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1" title="Simulasikan tampilan menu dan batasan hak akses divisi lain tanpa mengganti akun login Anda">
          <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-600"></i>
          <label for="role-select" class="text-xs text-slate-500 font-medium">Mode Tampilan:</label>
          <select id="role-select" class="text-xs font-semibold bg-transparent text-indigo-900 border-none focus:ring-0 cursor-pointer pr-6 py-1">
            <option value="Admin" {{ $currentRole === 'Admin' ? 'selected' : '' }}>Admin (Akses Penuh)</option>
            <option value="Pemilik" {{ $currentRole === 'Pemilik' ? 'selected' : '' }}>Simulasi: Pemilik (Mode Audit)</option>
            <option value="Pemotong" {{ $currentRole === 'Pemotong' ? 'selected' : '' }}>Simulasi: Pemotong (Cutting)</option>
            <option value="QC" {{ $currentRole === 'QC' ? 'selected' : '' }}>Simulasi: QC (Inspeksi)</option>
            <option value="Gudang" {{ $currentRole === 'Gudang' ? 'selected' : '' }}>Simulasi: Gudang (Bahan & Stok)</option>
          </select>
        </div>
        @else
        <!-- Static Division Badge for non-Admin users -->
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
          <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-600"></i>
          <span class="text-xs text-slate-500 font-medium">Divisi:</span>
          <span class="text-xs font-bold text-indigo-900">{{ $roleLabels[$currentRole] ?? $currentRole }}</span>
        </div>
        @endif

        <!-- Logout Button -->
        <a href="{{ route('logout', [], false) }}" onclick="window.clearAuthStorage()" class="px-3 py-1.5 rounded-lg border border-rose-200 bg-rose-50/50 hover:bg-rose-100 text-rose-700 text-xs font-semibold flex items-center gap-1.5 transition-colors">
          <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
          <span class="hidden md:inline">Keluar</span>
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

    window.clearAuthStorage = function () {
      localStorage.removeItem('bong_garment_auth_role');
      localStorage.removeItem('bong_garment_role');
      localStorage.removeItem('bong_garment_user_id');
      localStorage.removeItem('bong_garment_user_name');
    };

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
      const role = '{{ $currentRole }}';

      if (options.headers instanceof Headers) {
        if (!options.headers.has('X-User-Role')) options.headers.set('X-User-Role', role);
      } else {
        options.headers['X-User-Role'] = options.headers['X-User-Role'] || role;
      }
      return originalFetch(url, options);
    };

    document.addEventListener('DOMContentLoaded', function () {
      const roleSelect = document.getElementById('role-select');

      if (roleSelect) {
        roleSelect.addEventListener('change', function () {
          const newRole = this.value;
          localStorage.setItem('bong_garment_role', newRole);
          document.cookie = "bong_garment_role=" + encodeURIComponent(newRole) + "; path=/; max-age=31536000; SameSite=Lax";

          const path = window.location.pathname;
          const rolePermissions = {
            'Admin': ['*'],
            'Pemilik': ['/', '/dashboard', '/stok', '/laporan', '/pengaturan'],
            'Pemotong': ['/', '/dashboard', '/pemotongan', '/pengaturan'],
            'QC': ['/', '/dashboard', '/qc', '/pengaturan'],
            'Gudang': ['/', '/dashboard', '/penerimaan', '/stok', '/aksesoris', '/pengaturan']
          };

          const allowed = rolePermissions[newRole] || ['/dashboard'];
          const canStay = allowed.includes('*') || allowed.some(function (p) {
            return path === p || path.startsWith(p + '/');
          });

          if (canStay) {
            window.location.reload();
          } else {
            alert("Mode tampilan diubah menjadi '" + newRole + "'. Anda dialihkan ke Dashboard karena peran ini tidak memiliki akses ke halaman saat ini.");
            window.location.href = '/dashboard';
          }
        });
      }

      // CSRF token helper
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      // Global dd/mm/yyyy date & datetime-local input enhancer
      function isoToDisplayDate(iso, isDateTime) {
        if (!iso) return '';
        const str = String(iso).trim();
        const m = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
        if (!m) return '';
        const datePart = `${m[3]}/${m[2]}/${m[1]}`;
        if (!isDateTime) return datePart;
        return `${datePart} ${m[4] || '00'}:${m[5] || '00'}`;
      }

      function normalizeIsoValue(val, isDateTime) {
        if (!val) return '';
        const str = String(val).trim();
        const m = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
        if (!m) return '';
        if (!isDateTime) return `${m[1]}-${m[2]}-${m[3]}`;
        return `${m[1]}-${m[2]}-${m[3]}T${m[4] || '00'}:${m[5] || '00'}`;
      }

      function isValidYmd(year, month, day) {
        if (month < 1 || month > 12 || day < 1 || day > 31) return false;
        const dt = new Date(Date.UTC(year, month - 1, day));
        return dt.getUTCFullYear() === year && dt.getUTCMonth() === month - 1 && dt.getUTCDate() === day;
      }

      function displayToIsoDate(disp, isDateTime) {
        if (!disp) return '';
        const trimmed = String(disp).trim();
        const isoDirect = normalizeIsoValue(trimmed, isDateTime);
        if (isoDirect) {
          const p = isoDirect.slice(0, 10).split('-').map(Number);
          return isValidYmd(p[0], p[1], p[2]) ? isoDirect : '';
        }
        const m = trimmed.match(/^(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(\d{4})(?:[\sT]+(\d{1,2}):(\d{2}))?$/);
        if (!m) return '';
        const day = Number(m[1]);
        const month = Number(m[2]);
        const year = Number(m[3]);
        if (!isValidYmd(year, month, day)) return '';
        const yyyy = String(year).padStart(4, '0');
        const mm = String(month).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        if (!isDateTime) return `${yyyy}-${mm}-${dd}`;
        if (m[4] === undefined || m[5] === undefined) return '';
        const hour = Number(m[4]);
        const minute = Number(m[5]);
        if (hour < 0 || hour > 23 || minute < 0 || minute > 59) return '';
        return `${yyyy}-${mm}-${dd}T${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
      }

      const nativeValueDesc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
      let enhancedAnyDate = false;

      document.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function (origInput) {
        if (origInput.classList.contains('pointer-events-none') || origInput.dataset.ddmmyyyyEnhanced) return;
        origInput.dataset.ddmmyyyyEnhanced = '1';
        enhancedAnyDate = true;

        const isDateTime = origInput.type === 'datetime-local';
        const initialIso = normalizeIsoValue(nativeValueDesc.get.call(origInput), isDateTime);

        const wrapper = document.createElement('div');
        wrapper.className = 'relative flex items-center w-full';
        origInput.parentNode.insertBefore(wrapper, origInput);

        const dispInput = document.createElement('input');
        dispInput.type = 'text';
        dispInput.className = origInput.className + ' pr-9 font-mono';
        dispInput.placeholder = isDateTime ? 'dd / mm / yyyy hh:mm' : 'dd / mm / yyyy';
        dispInput.maxLength = isDateTime ? 19 : 14;
        dispInput.autocomplete = 'off';
        if (origInput.required) {
          dispInput.required = true;
          origInput.required = false;
        }
        dispInput.value = isoToDisplayDate(initialIso, isDateTime);

        origInput.className = 'absolute right-2 bottom-0 w-px h-px opacity-0 pointer-events-none';
        origInput.tabIndex = -1;
        origInput.setAttribute('aria-hidden', 'true');
        if (initialIso) {
          nativeValueDesc.set.call(origInput, initialIso);
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.title = 'Pilih tanggal dari kalender';
        btn.className = 'absolute right-2.5 text-slate-400 hover:text-indigo-600 p-0.5 rounded transition-colors';
        btn.innerHTML = '<i data-lucide="calendar" class="w-4 h-4"></i>';

        wrapper.appendChild(dispInput);
        wrapper.appendChild(origInput);
        wrapper.appendChild(btn);

        Object.defineProperty(origInput, 'value', {
          configurable: true,
          enumerable: true,
          get: function () {
            const raw = nativeValueDesc.get.call(origInput);
            if (raw) return raw;
            return displayToIsoDate(dispInput.value, isDateTime);
          },
          set: function (val) {
            const norm = normalizeIsoValue(val, isDateTime);
            nativeValueDesc.set.call(origInput, norm);
            dispInput.value = isoToDisplayDate(norm, isDateTime);
            dispInput.setCustomValidity('');
          }
        });

        dispInput.addEventListener('input', function (e) {
          const isDelete = String(e.inputType || '').startsWith('delete');
          if (!isDelete && dispInput.selectionStart === dispInput.value.length) {
            if (/^\d{2}$/.test(dispInput.value) || /^\d{2}\/\d{2}$/.test(dispInput.value)) {
              dispInput.value += '/';
            } else if (isDateTime && /^\d{2}\/\d{2}\/\d{4}$/.test(dispInput.value)) {
              dispInput.value += ' ';
            } else if (isDateTime && /^\d{2}\/\d{2}\/\d{4}\s\d{2}$/.test(dispInput.value)) {
              dispInput.value += ':';
            }
          }
          const rawDisp = dispInput.value.trim();
          const iso = displayToIsoDate(rawDisp, isDateTime);
          nativeValueDesc.set.call(origInput, iso);
          if (rawDisp === '' || iso) {
            dispInput.setCustomValidity('');
          } else {
            dispInput.setCustomValidity(isDateTime ? 'Gunakan format dd/mm/yyyy hh:mm yang valid' : 'Gunakan format dd/mm/yyyy yang valid');
          }
        });

        dispInput.addEventListener('blur', function () {
          let rawDisp = dispInput.value.trim();
          if (isDateTime && /^\d{1,2}\s*[\/\-]\s*\d{1,2}\s*[\/\-]\s*\d{4}$/.test(rawDisp)) {
            rawDisp = rawDisp + ' 00:00';
          }
          const iso = displayToIsoDate(rawDisp, isDateTime);
          if (iso) {
            nativeValueDesc.set.call(origInput, iso);
            dispInput.value = isoToDisplayDate(iso, isDateTime);
            dispInput.setCustomValidity('');
          }
        });

        origInput.addEventListener('change', function () {
          const picked = nativeValueDesc.get.call(origInput);
          dispInput.value = isoToDisplayDate(picked, isDateTime);
          dispInput.setCustomValidity('');
        });

        btn.addEventListener('click', function () {
          if (typeof origInput.showPicker === 'function') {
            try { origInput.showPicker(); } catch (_) { origInput.focus(); origInput.click(); }
          } else {
            origInput.focus();
            origInput.click();
          }
        });

        if (origInput.form) {
          origInput.form.addEventListener('reset', function () {
            setTimeout(function () {
              const resetVal = nativeValueDesc.get.call(origInput);
              dispInput.value = isoToDisplayDate(resetVal, isDateTime);
              dispInput.setCustomValidity('');
            }, 0);
          });
        }
      });

      if (enhancedAnyDate && window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }

      // Window helper: Revise Deadline
      window.openReviseDeadlineModal = function (id, dlAwal, dlTerbaru) {
        const currentTargetIso = dlTerbaru || dlAwal || new Date().toISOString().split('T')[0];
        const currentTargetDisp = isoToDisplayDate(currentTargetIso, false) || currentTargetIso;
        const inputDeadline = prompt('Masukkan Deadline Terbaru (dd/mm/yyyy):', currentTargetDisp);
        if (!inputDeadline) return;

        const newDeadline = displayToIsoDate(inputDeadline, false);
        if (!newDeadline) {
          alert('Format tanggal tidak valid. Gunakan format dd/mm/yyyy (contoh: 15/11/2026).');
          return;
        }

        const reason = prompt('Alasan Revisi Deadline:', 'Penyesuaian Antrean Pemotongan');
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
        const nowLocal = new Date();
        nowLocal.setMinutes(nowLocal.getMinutes() - nowLocal.getTimezoneOffset());
        const nowDisp = isoToDisplayDate(nowLocal.toISOString().slice(0, 16), true);
        const inputTglSelesai = prompt('Masukkan Tanggal & Waktu Aktual Selesai Perbaikan Rework (dd/mm/yyyy hh:mm):', nowDisp);
        if (!inputTglSelesai) return;

        const isoSelesai = displayToIsoDate(inputTglSelesai, true);
        if (!isoSelesai) {
          alert('Format tanggal & waktu tidak valid. Gunakan format dd/mm/yyyy hh:mm.');
          return;
        }
        const tglSelesai = isoSelesai.replace('T', ' ');

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
