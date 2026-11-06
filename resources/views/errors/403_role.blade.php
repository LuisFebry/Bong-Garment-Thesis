<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akses Ditolak (403) - Bong Garment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans flex items-center justify-center min-h-screen p-4">
  <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200 shadow-xl p-8 text-center space-y-6">
    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto border border-rose-100 shadow-sm">
      <i data-lucide="shield-alert" class="w-8 h-8"></i>
    </div>
    
    <div>
      <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
        403 - Akses Ditolak
      </span>
      <h2 class="text-xl font-bold text-slate-900 mt-3">Izin Akses Tidak Mencukupi</h2>
      <p class="text-xs text-slate-500 mt-2">
        {{ $customMessage ?? "Peran aktif Anda saat ini (" . ($userRole ?? 'Guest') . ") tidak memiliki hak akses untuk membuka modul ini." }}
      </p>
    </div>

    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-left text-xs space-y-2">
      <div class="flex justify-between">
        <span class="text-slate-400">Modul Diminta:</span>
        <span class="font-mono font-semibold text-slate-700">/{{ $requestedUrl ?? '' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-400">Peran Aktif:</span>
        <span class="font-semibold text-rose-600">{{ $userRole ?? 'Unknown' }}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-400">Peran Berizin:</span>
        <span class="font-medium text-slate-600">{{ implode(', ', (array)($allowedRoles ?? ['Admin'])) }}</span>
      </div>
    </div>

    <div class="flex flex-col gap-2 pt-2">
      <a href="/dashboard" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>Kembali ke Dashboard</span>
      </a>
      <button type="button" onclick="resetToAdmin()" class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
        Beralih ke Peran Admin (Akses Penuh)
      </button>
    </div>
  </div>

  <script>
    if (window.lucide) {
      lucide.createIcons();
    }
    function resetToAdmin() {
      localStorage.setItem('bong_garment_role', 'Admin');
      document.cookie = "bong_garment_role=Admin; path=/; max-age=31536000; SameSite=Lax";
      document.cookie = "bong_garment_user_id=1; path=/; max-age=31536000; SameSite=Lax";
      window.location.reload();
    }
  </script>
</body>
</html>