<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Masuk Sistem — Bong Garment CMT Production</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          }
        }
      }
    }
  </script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 font-sans antialiased text-slate-800 relative overflow-x-hidden">
  <!-- Background gradient decoration -->
  <div class="fixed inset-0 pointer-events-none overflow-hidden">
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-blue-600/20 blur-3xl"></div>
  </div>

  <div class="relative z-10 w-full max-w-4xl grid grid-cols-1 lg:grid-cols-12 bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200/80">
    <!-- Left Column: Login Form -->
    <div class="lg:col-span-7 p-8 sm:p-10 flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-3 mb-8">
          <div class="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
            <i data-lucide="scissors" class="w-5 h-5"></i>
          </div>
          <div>
            <h1 class="font-bold text-slate-900 tracking-tight text-lg leading-none">BONG GARMENT</h1>
            <p class="text-[11px] text-slate-400 font-semibold tracking-wider uppercase mt-1">SISTEM PRODUKSI & INVENTORI CMT</p>
          </div>
        </div>

        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Autentikasi Pengguna</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan kredensial akun Anda sesuai otorisasi divisi operasional CMT</p>

        @if($errors->any())
        <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
          <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <div id="login-error-box" class="hidden mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
          <span id="login-error-text">Username atau password tidak sesuai.</span>
        </div>

        <form id="form-login" method="POST" action="{{ route('login.post', [], false) }}" class="mt-6 space-y-4">
          @csrf
          <div>
            <label for="inp-username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
            <div class="relative">
              <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
              <input type="text" id="inp-username" name="username" value="{{ old('username', 'admin') }}" required autocomplete="username" placeholder="Masukkan username (cth: admin)" class="w-full pl-10 pr-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium">
            </div>
          </div>

          <div>
            <label for="inp-password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
            <div class="relative">
              <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
              <input type="password" id="inp-password" name="password" value="admin123" required autocomplete="current-password" placeholder="Masukkan password" class="w-full pl-10 pr-10 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
              <button type="button" id="btn-toggle-pw" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <i data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
          </div>

          <div class="pt-2">
            <button type="submit" id="btn-submit-login" class="w-full py-3 px-5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-md shadow-indigo-100 transition-all flex items-center justify-center gap-2">
              <i data-lucide="log-in" class="w-4 h-4"></i>
              <span>Masuk ke Sistem Produksi</span>
            </button>
          </div>
        </form>
      </div>

      <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
        <span>Luis Febry &bull; NIM: 825220088</span>
        <span>Skripsi SI UNTAR &copy; 2026</span>
      </div>
    </div>

    <!-- Right Column: Quick Account Selector for RBAC Evaluation -->
    <div class="lg:col-span-5 bg-slate-50 border-t lg:border-t-0 lg:border-l border-slate-200 p-8 flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700">Tabel User (5 Peran)</span>
        </div>
        <h3 class="font-bold text-slate-900 text-sm">Akun Otorisasi Divisi CMT</h3>
        <p class="text-[11px] text-slate-500 mt-0.5 mb-4">Klik salah satu akun di bawah untuk mengisi otomatis kredensial login sesuai peran:</p>

        <div class="space-y-2.5">
          <button type="button" onclick="fillCredentials('admin', 'admin123', 'Admin', 1, 'Luis Febry (Admin Produksi)')" class="w-full p-3 rounded-xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-left transition-all flex items-center justify-between group">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-700">Admin (Akses Penuh)</span>
                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">@admin</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">Luis Febry &bull; Pesanan, Pola, Gudang, QC & Laporan</p>
            </div>
            <i data-lucide="arrow-right-circle" class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 shrink-0"></i>
          </button>

          <button type="button" onclick="fillCredentials('pemilik', 'pemilik123', 'Pemilik', 2, 'Bong (Pemilik CMT)')" class="w-full p-3 rounded-xl bg-white hover:bg-amber-50/60 border border-slate-200 hover:border-amber-300 text-left transition-all flex items-center justify-between group">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900 group-hover:text-amber-700">Pemilik (Mode Audit Read-Only)</span>
                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">@pemilik</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">Bong &bull; Monitoring Dashboard, Stok & Laporan Eksekutif</p>
            </div>
            <i data-lucide="arrow-right-circle" class="w-4 h-4 text-slate-300 group-hover:text-amber-600 shrink-0"></i>
          </button>

          <button type="button" onclick="fillCredentials('pemotong', 'potong123', 'Pemotong', 3, 'Budi Santoso (Head Cutter)')" class="w-full p-3 rounded-xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-left transition-all flex items-center justify-between group">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-700">Pemotong (Meja Potong)</span>
                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">@pemotong</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">Budi Santoso &bull; Antrean EDF, Gelaran Roll & Seri Potong</p>
            </div>
            <i data-lucide="arrow-right-circle" class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 shrink-0"></i>
          </button>

          <button type="button" onclick="fillCredentials('qc', 'qc123', 'QC', 4, 'Siti Rahma (Lead Quality Control)')" class="w-full p-3 rounded-xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-left transition-all flex items-center justify-between group">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-700">QC (Inspeksi Mutu)</span>
                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">@qc</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">Siti Rahma &bull; Inspeksi Prototipe, Potong, Jahit Akhir & Rework</p>
            </div>
            <i data-lucide="arrow-right-circle" class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 shrink-0"></i>
          </button>

          <button type="button" onclick="fillCredentials('gudang', 'gudang123', 'Gudang', 5, 'Agus Hermawan (Kepala Gudang)')" class="w-full p-3 rounded-xl bg-white hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 text-left transition-all flex items-center justify-between group">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900 group-hover:text-indigo-700">Gudang (Bahan & Inventori)</span>
                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">@gudang</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">Agus Hermawan &bull; Penerimaan Roll Kain, Stok Jadi & Aksesoris</p>
            </div>
            <i data-lucide="arrow-right-circle" class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 shrink-0"></i>
          </button>
        </div>
      </div>

      <div class="mt-6 p-3 rounded-xl bg-indigo-50/70 border border-indigo-100 text-[11px] text-indigo-900 flex items-start gap-2">
        <i data-lucide="shield-check" class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5"></i>
        <span>Role-Based Access Control (RBAC) membatasi navigasi menu dan hak mutasi data secara ketat sesuai peran akun yang masuk.</span>
      </div>
    </div>
  </div>

  <script>
    if (window.lucide) {
      lucide.createIcons();
    }

    const pwInput = document.getElementById('inp-password');
    document.getElementById('btn-toggle-pw')?.addEventListener('click', function () {
      if (!pwInput) return;
      pwInput.type = pwInput.type === 'password' ? 'text' : 'password';
    });

    window.fillCredentials = function (username, password, role, userId, userName) {
      document.getElementById('inp-username').value = username;
      document.getElementById('inp-password').value = password;
      localStorage.setItem('bong_garment_role', role);
      localStorage.setItem('bong_garment_user_id', String(userId));
      localStorage.setItem('bong_garment_user_name', userName);
    };

    document.getElementById('form-login')?.addEventListener('submit', function (e) {
      e.preventDefault();
      const username = document.getElementById('inp-username').value.trim();
      const password = document.getElementById('inp-password').value;
      const errBox = document.getElementById('login-error-box');
      const errText = document.getElementById('login-error-text');
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      fetch("{{ route('login.post', [], false) }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ username: username, password: password })
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success && data.user) {
          localStorage.setItem('bong_garment_role', data.user.role);
          localStorage.setItem('bong_garment_user_id', String(data.user.id_user));
          localStorage.setItem('bong_garment_user_name', data.user.nama_user);
          document.cookie = "bong_garment_role=" + encodeURIComponent(data.user.role) + "; path=/; max-age=31536000; SameSite=Lax";
          document.cookie = "bong_garment_user_id=" + encodeURIComponent(data.user.id_user) + "; path=/; max-age=31536000; SameSite=Lax";
          document.cookie = "bong_garment_user_name=" + encodeURIComponent(data.user.nama_user) + "; path=/; max-age=31536000; SameSite=Lax";
          window.location.href = data.redirect || '/dashboard';
        } else {
          if (errBox && errText) {
            errText.textContent = data.message || 'Username atau password tidak sesuai.';
            errBox.classList.remove('hidden');
          }
        }
      })
      .catch(function () {
        e.target.submit();
      });
    });
  </script>
</body>
</html>

