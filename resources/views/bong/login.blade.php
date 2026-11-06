<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Masuk Sistem - Bong Garment CMT Production</title>

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

  <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200/80 p-8 sm:p-10">
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
      <p class="text-xs text-slate-500 mt-1">Masukkan username dan password akun resmi Anda untuk mengakses sistem</p>

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
            <input type="text" id="inp-username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Masukkan username Anda" class="w-full pl-10 pr-4 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-medium">
          </div>
        </div>

        <div>
          <label for="inp-password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
          <div class="relative">
            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
            <input type="password" id="inp-password" name="password" required autocomplete="current-password" placeholder="Masukkan password Anda" class="w-full pl-10 pr-10 py-2.5 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
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
      <span>Bong Garment CMT System</span>
      <span>&copy; 2026 Luis Febry</span>
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
          localStorage.setItem('bong_garment_auth_role', data.user.role);
          localStorage.setItem('bong_garment_role', data.user.role);
          localStorage.setItem('bong_garment_user_id', String(data.user.id_user));
          localStorage.setItem('bong_garment_user_name', data.user.nama_user);
          document.cookie = "bong_garment_auth_role=" + encodeURIComponent(data.user.role) + "; path=/; max-age=31536000; SameSite=Lax";
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
