/* ============================================================================
 * BONG GARMENT — Static Prototype SPA Controller
 * Mirrors Bong-Garment-Thesis-main UI, 8 Screens, 5 Roles & Report Exporters.
 * ========================================================================== */
(function () {
  'use strict';

  const {
    esc, sum, round2, fmtNum, todayIso, nowLocalIsoMin, addDays,
    datePart, toInputDateTime, fmtDateDmy, fmtDateTimeDmy, fmtDateShortId, fmtDateLongId,
  } = window.BongUtil;
  const DB = window.BongDB;
  const Auth = window.BongAuth;
  const Q = window.BongQ;
  const Svc = window.BongService;

  const TABS = ['dashboard', 'pesanan', 'penerimaan', 'pemotongan', 'qc', 'gudang', 'laporan', 'pengaturan'];
  const TAB_TITLES = {
    dashboard: 'Dashboard Produksi - Bong Garment',
    pesanan: 'Kelola Pesanan & Pola - Bong Garment',
    penerimaan: 'Penerimaan Bahan & Roll - Bong Garment',
    pemotongan: 'Pemotongan Seri & Gelaran - Bong Garment',
    qc: 'Quality Control & Cacat - Bong Garment',
    gudang: 'Gudang & Stok Barang Jadi - Bong Garment',
    laporan: 'Laporan Eksekutif & Rekapan - Bong Garment',
    pengaturan: 'Pengaturan & Manajemen Pengguna - Bong Garment',
  };

  const state = {
    tab: 'dashboard',
    // Pesanan screen draft state
    polaMode: 'existing',
    newPolaSpecs: [
      { nama_ukuran: 'All', lingkar_dada: '', panjang_baju_celana: '', lebar_baju: '', panjang_pundak: '', panjang_tangan: '', lingkar_lengan: '', diameter_leher: '', lingkar_pinggang: '', lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' },
    ],
    newPolaAccs: [],

    // Penerimaan screen draft state
    nextColorGroupId: 2,
    colorGroups: [{ id: 1, namaWarna: '', kodeWarna: '', satuanUkur: 'Kg' }],
    draftRolls: [
      { colorGroupId: 1, berat: 25.00 },
      { colorGroupId: 1, berat: 25.00 },
      { colorGroupId: 1, berat: 25.00 },
    ],

    // Pemotongan screen draft state
    gelaranRows: [
      { id_roll: '', panjang_gelar: 2.50, jumlah_tumpuk: 50, total_lembar_gelar: 300, status_roll_baru: 'Dipotong' },
    ],

    // Laporan filter state
    laporanFilters: {
      jenis_laporan: 'KONSOLIDASI',
      id_sumber: '',
      start_date: '',
      end_date: '',
    },
    lastReportData: null,
  };

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      try { window.lucide.createIcons(); } catch (_) { /* ignore */ }
    }
  }

  /* --------------------------------------------------------------------------
   * Toast & Modal Helpers
   * ------------------------------------------------------------------------ */
  function showToast(msg, type = 'success') {
    const root = $('#toast-root');
    if (!root) return;
    const cfg = {
      success: { bg: 'border-emerald-200 bg-white', icon: 'check-circle-2', iconCls: 'bg-emerald-50 text-emerald-600', title: 'Berhasil' },
      info: { bg: 'border-indigo-200 bg-white', icon: 'info', iconCls: 'bg-indigo-50 text-indigo-600', title: 'Informasi' },
      warning: { bg: 'border-amber-200 bg-white', icon: 'alert-triangle', iconCls: 'bg-amber-50 text-amber-600', title: 'Perhatian' },
      error: { bg: 'border-rose-200 bg-white', icon: 'alert-circle', iconCls: 'bg-rose-50 text-rose-600', title: 'Gagal' },
    }[type] || { bg: 'border-slate-200 bg-white', icon: 'info', iconCls: 'bg-slate-100 text-slate-600', title: 'Info' };

    const el = document.createElement('div');
    el.className = `toast pointer-events-auto w-80 sm:w-96 rounded-xl border ${cfg.bg} shadow-xl p-3.5 pr-9 relative flex items-start gap-3`;
    el.innerHTML = `
      <div class="w-8 h-8 rounded-lg ${cfg.iconCls} flex items-center justify-center shrink-0">
        <i data-lucide="${cfg.icon}" class="w-4 h-4"></i>
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-bold text-slate-900">${cfg.title}</p>
        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">${esc(msg)}</p>
      </div>
      <button type="button" class="absolute top-2.5 right-2.5 text-slate-400 hover:text-slate-600" aria-label="Tutup">
        <i data-lucide="x" class="w-3.5 h-3.5"></i>
      </button>`;
    root.appendChild(el);
    while (root.children.length > 4) root.firstElementChild.remove();
    refreshIcons();
    const dismiss = () => {
      if (el.classList.contains('is-leaving')) return;
      el.classList.add('is-leaving');
      setTimeout(() => el.remove(), 200);
    };
    el.querySelector('button').addEventListener('click', dismiss);
    setTimeout(dismiss, 3800);
  }

  function openModalById(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('hidden');
    m.classList.add('flex');
    refreshIcons();
  }

  function closeModalById(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  function closeAllModals() {
    $$('[id^="modal-"]').forEach((m) => {
      m.classList.add('hidden');
      m.classList.remove('flex');
    });
  }

  function runAction(fn, successMsg = null) {
    try {
      const res = fn();
      if (successMsg) showToast(successMsg, 'success');
      else if (res && typeof res === 'object' && res.message) showToast(res.message, 'success');
      return res;
    } catch (err) {
      showToast(err.message || 'Terjadi kesalahan.', 'error');
      return null;
    }
  }

  /* --------------------------------------------------------------------------
   * Global dd/mm/yyyy & dd/mm/yyyy hh:mm Date Input Enhancer (from app.blade.php)
   * ------------------------------------------------------------------------ */
  function isoToDisplayDate(iso, isDateTime) {
    if (!iso) return '';
    const m = String(iso).trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
    if (!m) return '';
    const datePartStr = `${m[3]}/${m[2]}/${m[1]}`;
    if (!isDateTime) return datePartStr;
    return `${datePartStr} ${m[4] || '00'}:${m[5] || '00'}`;
  }

  function normalizeIsoValue(val, isDateTime) {
    if (!val) return '';
    const m = String(val).trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
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

  function enhanceDateInputs(root = document) {
    const nativeValueDesc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    let enhancedAny = false;

    $$('input[type="date"], input[type="datetime-local"]', root).forEach((origInput) => {
      if (origInput.classList.contains('pointer-events-none') || origInput.dataset.ddmmyyyyEnhanced) return;
      origInput.dataset.ddmmyyyyEnhanced = '1';
      enhancedAny = true;

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
      if (initialIso) nativeValueDesc.set.call(origInput, initialIso);

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
        get() {
          const raw = nativeValueDesc.get.call(origInput);
          if (raw) return raw;
          return displayToIsoDate(dispInput.value, isDateTime);
        },
        set(val) {
          const norm = normalizeIsoValue(val, isDateTime);
          nativeValueDesc.set.call(origInput, norm);
          dispInput.value = isoToDisplayDate(norm, isDateTime);
          dispInput.setCustomValidity('');
        },
      });

      dispInput.addEventListener('input', (e) => {
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

      dispInput.addEventListener('blur', () => {
        let rawDisp = dispInput.value.trim();
        if (isDateTime && /^\d{1,2}\s*[\/\-]\s*\d{1,2}\s*[\/\-]\s*\d{4}$/.test(rawDisp)) {
          rawDisp += ' 00:00';
        }
        const iso = displayToIsoDate(rawDisp, isDateTime);
        if (iso) {
          nativeValueDesc.set.call(origInput, iso);
          dispInput.value = isoToDisplayDate(iso, isDateTime);
          dispInput.setCustomValidity('');
        }
      });

      origInput.addEventListener('change', () => {
        const picked = nativeValueDesc.get.call(origInput);
        dispInput.value = isoToDisplayDate(picked, isDateTime);
        dispInput.setCustomValidity('');
      });

      btn.addEventListener('click', () => {
        if (typeof origInput.showPicker === 'function') {
          try { origInput.showPicker(); } catch (_) { origInput.focus(); origInput.click(); }
        } else {
          origInput.focus();
          origInput.click();
        }
      });

      if (origInput.form) {
        origInput.form.addEventListener('reset', () => {
          setTimeout(() => {
            const resetVal = nativeValueDesc.get.call(origInput);
            dispInput.value = isoToDisplayDate(resetVal, isDateTime);
            dispInput.setCustomValidity('');
          }, 0);
        });
      }
    });

    if (enhancedAny) refreshIcons();
  }

  /* --------------------------------------------------------------------------
   * Auth, Role Chrome & Router
   * ------------------------------------------------------------------------ */
  function syncAuthVisibility() {
    const loggedIn = Auth.isLoggedIn();
    const overlay = $('#login-overlay');
    const shell = $('#app-shell');
    if (loggedIn) {
      overlay.classList.add('hidden');
      overlay.classList.remove('flex');
      shell.classList.remove('hidden');
      shell.classList.add('flex');
    } else {
      overlay.classList.remove('hidden');
      overlay.classList.add('flex');
      shell.classList.add('hidden');
      shell.classList.remove('flex');
      $('#login-error-box')?.classList.add('hidden');
    }
  }

  function syncChrome() {
    const user = Auth.getUser();
    const trueRole = Auth.getTrueRole();
    const effRole = Auth.getEffectiveRole();
    const isAdminAcct = Auth.isAdminAccount();

    $('#sidebar-user-initials').textContent = Auth.getInitials(user.nama_user);
    $('#sidebar-user-name').textContent = user.nama_user;
    $('#sidebar-user-meta').textContent = `@${user.username} • ${trueRole}`;
    $('#header-user-name').textContent = user.nama_user;

    const auditBadge = $('#audit-mode-badge');
    if (effRole === 'Pemilik') {
      auditBadge.classList.remove('hidden');
      auditBadge.classList.add('flex');
    } else {
      auditBadge.classList.add('hidden');
      auditBadge.classList.remove('flex');
    }

    const simWrap = $('#role-sim-wrap');
    const staticBadge = $('#role-static-badge');
    if (isAdminAcct) {
      simWrap.classList.remove('hidden');
      simWrap.classList.add('flex');
      staticBadge.classList.add('hidden');
      staticBadge.classList.remove('flex');
      $('#role-select').value = effRole;
    } else {
      simWrap.classList.add('hidden');
      simWrap.classList.remove('flex');
      staticBadge.classList.remove('hidden');
      staticBadge.classList.add('flex');
      $('#role-static-label').textContent = Auth.getRoleLabel(effRole);
    }

    // Sidebar visibility per RBAC
    $$('.nav-item[data-tab]').forEach((a) => {
      const tab = a.dataset.tab;
      if (tab === 'pengaturan') return; // handled by section wrapper
      a.style.display = Auth.canAccessTab(tab) ? '' : 'none';
    });
    const adminSec = $('#nav-admin-section');
    if (adminSec) {
      adminSec.style.display = isAdminAcct ? '' : 'none';
    }
  }

  function switchTab(tabId, opts = {}) {
    if (!Auth.isLoggedIn()) {
      syncAuthVisibility();
      return;
    }
    if (!TABS.includes(tabId)) tabId = 'dashboard';
    if (!Auth.canAccessTab(tabId)) {
      showToast(`Peran '${Auth.getEffectiveRole()}' tidak memiliki akses ke modul ${tabId}. Dialihkan ke Dashboard.`, 'warning');
      tabId = 'dashboard';
    }
    state.tab = tabId;

    $$('.screen-section').forEach((sec) => {
      sec.classList.toggle('is-active', sec.id === `screen-${tabId}`);
    });
    $$('.nav-item[data-tab]').forEach((link) => {
      const active = link.dataset.tab === tabId;
      link.classList.toggle('bg-indigo-600', active);
      link.classList.toggle('text-white', active);
      link.classList.toggle('shadow-sm', active);
      link.classList.toggle('text-slate-600', !active);
      link.classList.toggle('hover:bg-slate-100', !active);
    });

    if (opts.updateHash !== false && location.hash !== `#${tabId}`) {
      try { history.pushState(null, '', `#${tabId}`); } catch (_) { location.hash = tabId; }
    }
    document.title = TAB_TITLES[tabId] || 'Bong Garment - Sistem Produksi CMT';
    renderActiveScreen();
    if (opts.scrollTop !== false) {
      const main = $('#main-content');
      if (main) main.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function renderActiveScreen() {
    syncChrome();
    switch (state.tab) {
      case 'dashboard': renderDashboard(); break;
      case 'pesanan': renderPesanan(); break;
      case 'penerimaan': renderPenerimaan(); break;
      case 'pemotongan': renderPemotongan(); break;
      case 'qc': renderQc(); break;
      case 'gudang': renderGudang(); break;
      case 'laporan': renderLaporan(); break;
      case 'pengaturan': renderPengaturan(); break;
    }
    refreshIcons();
  }

  /* --------------------------------------------------------------------------
   * SCREEN 1: DASHBOARD
   * ------------------------------------------------------------------------ */
  function renderDashboard() {
    $('#dash-today-label').textContent = fmtDateLongId(todayIso());
    const orders = DB.all('Pesanan');
    const activeOrders = orders.filter((o) => !['Selesai', 'Dibatalkan'].includes(o.status_pesanan));
    const rollsTersedia = DB.where('Detail_Roll_Bahan', (r) => r.status_roll === 'Tersedia').length;
    const totalStokJadi = sum(DB.all('Stok_Barang_Jadi'), (s) => s.jumlah_stok_pcs);
    const needRework = DB.where('QC', (q) => q.status_qc === 'Need Rework').length;

    $('#kpi-pesanan-aktif').textContent = fmtNum(activeOrders.length);
    $('#kpi-roll-tersedia').textContent = fmtNum(rollsTersedia);
    $('#kpi-stok-jadi').innerHTML = `${fmtNum(totalStokJadi)} <span class="text-sm font-normal text-slate-500">pcs</span>`;
    $('#kpi-qc-rework').textContent = fmtNum(needRework);

    const edfAll = Q.edfSortOrders(orders.filter((o) => ['Antrean', 'Pemotongan'].includes(o.status_pesanan)));
    const edfTop5 = edfAll.slice(0, 5);
    $('#dash-edf-count').textContent = `${edfAll.length} Pesanan Aktif`;

    const edfBody = $('#dash-edf-tbody');
    if (edfTop5.length === 0) {
      edfBody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-slate-400">Tidak ada antrean pesanan aktif saat ini.</td></tr>';
    } else {
      edfBody.innerHTML = edfTop5.map((q) => {
        const pola = Q.pola(q.id_pola);
        const dAwal = fmtDateDmy(q.deadline_awal);
        const dBaru = fmtDateDmy(q.deadline_terbaru || q.deadline_awal);
        const revised = dBaru !== dAwal;
        const badgeCls = q.status_pesanan === 'Pemotongan' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700';
        return `
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#${q.id_pesanan}</td>
            <td class="py-3">
              <p class="font-medium text-slate-800">${esc(q.nama_mitra || 'Non-Mitra (Produk Abadi/Tren)')}</p>
              <p class="text-[11px] text-slate-400">${esc(q.jenis_pesanan)}</p>
            </td>
            <td class="py-3 text-slate-600">${esc(pola?.nama_pola || '-')}</td>
            <td class="py-3 font-mono">
              ${revised
                ? `<span class="text-amber-600 font-semibold">${esc(dBaru)}</span><span class="text-[10px] text-slate-400 block">(Revisi dari ${esc(dAwal)})</span>`
                : `<span class="text-slate-600">${esc(dBaru)}</span>`}
            </td>
            <td class="py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${badgeCls}">${esc(q.status_pesanan)}</span></td>
          </tr>`;
      }).join('');
    }

    const recentQc = [...DB.all('QC')]
      .sort((a, b) => String(b.tgl_pemeriksaan || '').localeCompare(String(a.tgl_pemeriksaan || '')) || b.id_qc - a.id_qc)
      .slice(0, 5);
    const qcWrap = $('#dash-recent-qc');
    if (recentQc.length === 0) {
      qcWrap.innerHTML = '<p class="text-center text-slate-400 text-xs py-4">Belum ada catatan inspeksi QC.</p>';
    } else {
      qcWrap.innerHTML = recentQc.map((qc) => {
        const badgeCls = qc.status_qc === 'Lolos QC'
          ? 'bg-emerald-100 text-emerald-800'
          : qc.status_qc === 'Need Rework'
            ? 'bg-amber-100 text-amber-800'
            : 'bg-blue-100 text-blue-800';
        return `
          <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-slate-800">Pesanan #${qc.id_pesanan} (${esc(qc.tahap_qc)})</span>
              <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold ${badgeCls}">${esc(qc.status_qc)}</span>
            </div>
            <div class="flex items-center justify-between mt-2 text-slate-500 text-[11px]">
              <span>Lolos: <b class="text-slate-700">${qc.jumlah_lolos}</b> / Cacat: <b class="text-rose-600">${qc.jumlah_cacat}</b></span>
              <span class="font-mono text-[10px]">${fmtDateDmy(qc.tgl_pemeriksaan)}</span>
            </div>
          </div>`;
      }).join('');
    }
  }

  /* --------------------------------------------------------------------------
   * SCREEN 2: KELOLA PESANAN & POLA
   * ------------------------------------------------------------------------ */
  function syncSelectOptions(selectEl, optionsHtml, preserve = true) {
    if (!selectEl) return;
    const prev = selectEl.value;
    selectEl.innerHTML = optionsHtml;
    if (preserve && prev && Array.from(selectEl.options).some((o) => o.value === prev)) {
      selectEl.value = prev;
    }
  }

  function refreshPesananDropdowns() {
    const sumbers = DB.all('Sumber_Inisiasi');
    const polas = DB.all('Pola');
    const kats = DB.all('Kategori_Pola');
    const pens = DB.all('Penerimaan_Bahan');

    const sumberOpts = sumbers.map((s) => `<option value="${s.id_sumber}">${esc(s.nama_sumber)}</option>`).join('');
    syncSelectOptions($('#pesanan-sumber'), sumberOpts);
    syncSelectOptions($('#edit-id-sumber'), sumberOpts);

    const polaOpts = polas.map((p) => {
      const k = Q.kategori(p.id_kategori);
      return `<option value="${p.id_pola}">${esc(p.kode_pola)} - ${esc(p.nama_pola)} (${esc(k?.nama_kategori || 'Umum')})</option>`;
    }).join('');
    syncSelectOptions($('#sel-pola-existing'), polaOpts);
    syncSelectOptions($('#edit-id-pola'), polas.map((p) => `<option value="${p.id_pola}">${esc(p.kode_pola)} - ${esc(p.nama_pola)}</option>`).join(''));

    const katOpts = kats.map((k) => `<option value="${k.id_kategori}">${esc(k.nama_kategori)}</option>`).join('');
    syncSelectOptions($('#new-pola-kategori'), katOpts);
    syncSelectOptions($('#edit-pola-kategori'), katOpts);

    const penOpts = '<option value="">-- Pilih Nota Kain (Opsional, Dapat Diisi Nanti Saat Kain Tiba) --</option>' +
      pens.map((pn) => `<option value="${pn.id_penerimaan}">#${pn.id_penerimaan} - ${esc(pn.no_nota)} - ${esc(pn.nama_kain)} (Tgl: ${fmtDateDmy(pn.tgl_nota)})</option>`).join('');
    syncSelectOptions($('#sel-pesanan-penerimaan'), penOpts);

    const editPenOpts = '<option value="">-- Tanpa Nota / Belum Ada Bahan Kain (Menunggu Kedatangan) --</option>' +
      pens.map((pn) => `<option value="${pn.id_penerimaan}">#${pn.id_penerimaan} - ${esc(pn.no_nota)} - ${esc(pn.nama_kain)} (Tgl: ${fmtDateDmy(pn.tgl_nota)})</option>`).join('');
    syncSelectOptions($('#edit-id-penerimaan'), editPenOpts);
  }

  function syncCreateMitraField() {
    const sel = $('#pesanan-sumber');
    const wrap = $('#field-nama-mitra');
    const inp = $('#input-nama-mitra');
    if (!sel || !wrap) return;
    const isMitra = sel.value === '1';
    wrap.style.display = isMitra ? '' : 'none';
    if (!isMitra && inp) inp.value = '';
  }

  function syncEditMitraField() {
    const sel = $('#edit-id-sumber');
    const wrap = $('#edit-field-nama-mitra');
    const inp = $('#edit-nama-mitra');
    if (!sel || !wrap) return;
    const isMitra = sel.value === '1';
    wrap.style.display = isMitra ? '' : 'none';
    if (!isMitra && inp) inp.value = '';
  }

  function setPolaMode(mode) {
    state.polaMode = mode;
    $('#inp-pola-mode').value = mode;
    const secExisting = $('#section-pola-existing');
    const secNew = $('#section-pola-new');
    const btnExisting = $('#btn-mode-existing');
    const btnNew = $('#btn-mode-new');
    if (mode === 'new') {
      secExisting.classList.add('hidden');
      secNew.classList.remove('hidden');
      btnNew.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all bg-white text-indigo-700 shadow-sm inline-flex items-center gap-1.5';
      btnExisting.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5';
      renderNewPolaSpecs();
      renderNewPolaAccs();
    } else {
      secNew.classList.add('hidden');
      secExisting.classList.remove('hidden');
      btnExisting.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all bg-white text-indigo-700 shadow-sm inline-flex items-center gap-1.5';
      btnNew.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-all text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5';
    }
    refreshIcons();
  }

  function renderNewPolaSpecs() {
    const tbody = $('#tbody-new-pola-specs');
    if (!tbody) return;
    const fields = ['lingkar_dada', 'panjang_baju_celana', 'lebar_baju', 'panjang_pundak', 'panjang_tangan', 'lingkar_lengan', 'diameter_leher', 'lingkar_pinggang', 'lingkar_selangkang', 'lingkar_paha', 'lebar_kaki'];
    tbody.innerHTML = state.newPolaSpecs.map((sp, idx) => {
      const cells = fields.map((f) => {
        const val = sp[f] !== undefined && sp[f] !== null ? sp[f] : '';
        return `<td class="p-1"><input type="number" step="0.1" lang="en-US" value="${esc(val)}" data-idx="${idx}" data-field="${f}" class="inp-spec-metric w-14 text-center font-mono text-[11px] p-1 border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500" placeholder="-" /></td>`;
      }).join('');
      return `
        <tr>
          <td class="p-1.5"><input type="text" value="${esc(sp.nama_ukuran)}" placeholder="All" data-idx="${idx}" data-field="nama_ukuran" class="inp-spec-metric w-14 font-mono font-bold text-indigo-700 text-center text-[11px] p-1 border border-slate-200 rounded bg-indigo-50/40" /></td>
          ${cells}
          <td class="p-1 text-center">
            <button type="button" data-action="remove-pola-spec" data-idx="${idx}" class="text-slate-400 hover:text-rose-600 p-1" ${state.newPolaSpecs.length <= 1 ? 'disabled' : ''}>
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>`;
    }).join('');
    refreshIcons();
  }

  function renderNewPolaAccs() {
    const container = $('#container-new-pola-acc');
    if (!container) return;
    if (state.newPolaAccs.length === 0) {
      container.classList.add('hidden');
      container.innerHTML = '';
      return;
    }
    container.classList.remove('hidden');
    const accList = DB.all('Aksesoris');
    container.innerHTML = state.newPolaAccs.map((item, idx) => {
      const opts = accList.map((a) => `<option value="${a.id_aksesoris}" ${Number(item.id_aksesoris) === Number(a.id_aksesoris) ? 'selected' : ''}>${esc(a.nama_aksesoris)} (${esc(a.ukuran || '-')}) - Stok: ${a.stok} ${esc(a.satuan)}</option>`).join('');
      return `
        <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200">
          <select data-idx="${idx}" class="sel-new-acc flex-1 text-xs p-1.5 border border-slate-200 rounded">${opts}</select>
          <div class="flex items-center gap-1.5">
            <span class="text-[11px] text-slate-500">Kebutuhan/Pcs:</span>
            <input type="number" min="1" value="${item.jumlah_digunakan || 1}" data-idx="${idx}" class="inp-new-acc-qty w-16 text-xs font-mono p-1.5 border border-slate-200 rounded text-center" />
          </div>
          <button type="button" data-action="remove-pola-acc" data-idx="${idx}" class="p-1.5 text-slate-400 hover:text-rose-600">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
          </button>
        </div>`;
    }).join('');
    refreshIcons();
  }

  function renderKategoriModalList() {
    const list = $('#kategori-modal-list');
    if (!list) return;
    list.innerHTML = DB.all('Kategori_Pola').map((kat) => `
      <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
        <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#${kat.id_kategori}</span>${esc(kat.nama_kategori)}</span>
        <div class="flex items-center gap-1">
          <button type="button" data-action="edit-kategori-modal" data-id="${kat.id_kategori}" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="delete-kategori-modal" data-id="${kat.id_kategori}" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('');
  }

  function renderSumberModalList() {
    const list = $('#sumber-modal-list');
    if (!list) return;
    list.innerHTML = DB.all('Sumber_Inisiasi').map((s) => `
      <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
        <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#${s.id_sumber}</span>${esc(s.nama_sumber)}</span>
        <div class="flex items-center gap-1">
          <button type="button" data-action="edit-sumber-modal" data-id="${s.id_sumber}" class="px-2 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="delete-sumber-modal" data-id="${s.id_sumber}" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('');
  }

  function renderPesanan() {
    refreshPesananDropdowns();
    syncCreateMitraField();

    const tglOrderInp = $('#inp-tgl-order');
    if (tglOrderInp && !tglOrderInp.value) tglOrderInp.value = todayIso();
    const dlAwalInp = $('#inp-deadline-awal');
    if (dlAwalInp && !dlAwalInp.value) dlAwalInp.value = addDays(todayIso(), 14);

    if (state.polaMode === 'new') {
      renderNewPolaSpecs();
      renderNewPolaAccs();
    }

    const orders = Q.edfSortOrders(DB.all('Pesanan'));
    $('#pesanan-total-badge').textContent = `Total: ${orders.length} Pesanan`;

    const statusColors = {
      Antrean: 'bg-indigo-50 text-indigo-700 border-indigo-100',
      Pemotongan: 'bg-amber-50 text-amber-700 border-amber-100',
      'Dalam QC': 'bg-rose-50 text-rose-700 border-rose-100',
      Jahit: 'bg-purple-50 text-purple-700 border-purple-100',
      Finishing: 'bg-blue-50 text-blue-700 border-blue-100',
      Selesai: 'bg-emerald-50 text-emerald-700 border-emerald-100',
      Dibatalkan: 'bg-rose-50 text-rose-700 border-rose-100',
    };

    const tbody = $('#pesanan-list-tbody');
    if (orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Belum ada data pesanan.</td></tr>';
    } else {
      tbody.innerHTML = orders.map((item) => {
        const pola = Q.pola(item.id_pola);
        const sumber = Q.sumber(item.id_sumber);
        const pen = item.id_penerimaan ? Q.penerimaan(item.id_penerimaan) : null;
        const dlAwal = fmtDateDmy(item.deadline_awal);
        const dlTerbaru = fmtDateDmy(item.deadline_terbaru || item.deadline_awal);
        const isRevised = dlTerbaru !== dlAwal && dlAwal !== '-';
        const badgeStyle = statusColors[item.status_pesanan] || 'bg-slate-100 text-slate-700 border-slate-200';
        return `
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-semibold text-indigo-600">#${item.id_pesanan}</td>
            <td class="py-3">
              <span class="font-medium text-slate-900">${esc(item.nama_mitra || 'Non-Mitra')}</span>
              <span class="text-[10px] text-slate-400 block">${esc(sumber?.nama_sumber || '-')}</span>
            </td>
            <td class="py-3">
              <span class="font-medium text-slate-800">${esc(pola?.nama_pola || '-')}</span>
              <span class="text-[10px] text-slate-400 block">${esc(item.jenis_pesanan)}</span>
            </td>
            <td class="py-3">
              ${pen ? `
                <div class="inline-flex items-center gap-1 font-mono text-[11px] font-semibold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                  <i data-lucide="file-text" class="w-3 h-3 text-indigo-500"></i>
                  #${pen.id_penerimaan} &bull; ${esc(pen.no_nota)}
                </div>
                <span class="text-[10px] text-slate-400 block mt-0.5">${esc(pen.nama_kain)}</span>
              ` : `
                <span class="inline-flex items-center gap-1 text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-medium">
                  <i data-lucide="alert-circle" class="w-3 h-3 text-amber-500"></i> Belum Ditautkan
                </span>
              `}
            </td>
            <td class="py-3 font-mono text-slate-500">${fmtDateDmy(item.tgl_order)}</td>
            <td class="py-3 font-mono text-slate-500">${esc(dlAwal)}</td>
            <td class="py-3 font-mono">
              ${isRevised
                ? `<span class="text-amber-600 font-semibold">${esc(dlTerbaru)}</span><span class="text-[9px] text-amber-500 block font-sans">(Revisi)</span>`
                : `<span class="text-slate-600">${esc(dlTerbaru)}</span>`}
            </td>
            <td class="py-3"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border ${badgeStyle}">${esc(item.status_pesanan)}</span></td>
            <td class="py-3 text-right">
              <div class="flex items-center justify-end gap-1.5">
                <button type="button" data-action="edit-pesanan" data-id="${item.id_pesanan}" class="px-2.5 py-1 text-[11px] bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-md font-semibold inline-flex items-center gap-1 transition-colors">
                  <i data-lucide="edit-3" class="w-3 h-3"></i> Edit
                </button>
                <button type="button" data-action="revise-deadline" data-id="${item.id_pesanan}" class="px-2.5 py-1 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-medium transition-colors">Revisi</button>
                <button type="button" data-action="delete-pesanan" data-id="${item.id_pesanan}" class="px-2 py-1 text-[11px] bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-md font-semibold inline-flex items-center gap-1 transition-colors">
                  <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
                </button>
              </div>
            </td>
          </tr>`;
      }).join('');
    }

    const polas = DB.all('Pola');
    $('#pola-total-badge').textContent = `${polas.length} Master Pola`;
    const grid = $('#pola-catalog-grid');
    if (polas.length === 0) {
      grid.innerHTML = '<p class="col-span-full text-center text-xs text-slate-400 py-6">Belum ada Master Pola terdaftar.</p>';
    } else {
      grid.innerHTML = polas.map((p) => {
        const kat = Q.kategori(p.id_kategori);
        const specs = Q.specsOfPola(p.id_pola);
        const accs = Q.aksesorisOfPola(p.id_pola);
        return `
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="font-mono text-xs font-bold text-indigo-600">${esc(p.kode_pola)}</span>
                <h4 class="font-bold text-slate-900 text-sm">${esc(p.nama_pola)}</h4>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-600">${esc(kat?.nama_kategori || 'Umum')}</span>
                <button type="button" data-action="edit-pola" data-id="${p.id_pola}" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 inline-flex items-center gap-1">
                  <i data-lucide="edit-3" class="w-3 h-3"></i> Edit Pola
                </button>
                <button type="button" data-action="delete-pola" data-id="${p.id_pola}" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 inline-flex items-center gap-1">
                  <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
                </button>
              </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
              <table class="w-full text-[10px] text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold">
                  <tr>
                    <th class="py-1.5 px-2">Ukuran</th>
                    <th class="py-1.5 px-2">L. Dada</th>
                    <th class="py-1.5 px-2">P. Baju/Cln</th>
                    <th class="py-1.5 px-2">P. Pundak</th>
                    <th class="py-1.5 px-2">P. Tangan</th>
                    <th class="py-1.5 px-2">L. Pinggang</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                  ${specs.length ? specs.map((sp) => `
                    <tr>
                      <td class="py-1 px-2 font-bold text-indigo-600">${esc(sp.nama_ukuran)}</td>
                      <td class="py-1 px-2">${sp.lingkar_dada ?? '-'}</td>
                      <td class="py-1 px-2">${sp.panjang_baju_celana ?? '-'}</td>
                      <td class="py-1 px-2">${sp.panjang_pundak ?? '-'}</td>
                      <td class="py-1 px-2">${sp.panjang_tangan ?? '-'}</td>
                      <td class="py-1 px-2">${sp.lingkar_pinggang ?? '-'}</td>
                    </tr>`).join('') : '<tr><td colspan="6" class="py-2 text-center text-slate-400">Belum ada spesifikasi ukuran.</td></tr>'}
                </tbody>
              </table>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <span class="text-[10px] font-semibold text-slate-500">Aksesoris / Pcs:</span>
              ${accs.length ? accs.map((d) => `
                <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-medium text-slate-700">
                  ${esc(d.aksesoris?.nama_aksesoris || `Aksesoris #${d.id_aksesoris}`)}: <b class="font-mono text-indigo-600">${d.jumlah_digunakan} ${esc(d.aksesoris?.satuan || 'pcs')}</b>
                </span>`).join('') : '<span class="text-[10px] text-slate-400 italic">Tidak ada aksesoris khusus</span>'}
            </div>
          </div>`;
      }).join('');
    }
  }

  /* --------------------------------------------------------------------------
   * SCREEN 3: PENERIMAAN & ROLL BAHAN
   * ------------------------------------------------------------------------ */
  function refreshSupplierSelects() {
    const suppliers = DB.all('Supplier');
    const opts = suppliers.map((sp) => `<option value="${sp.id_supplier}">${esc(sp.nama_supplier)}</option>`).join('');
    syncSelectOptions($('#sel-supplier'), opts);
    syncSelectOptions($('#edit-nota-supplier'), opts);
    syncSelectOptions($('#edit-acc-supplier'), opts);
    const addAccOpts = '<option value="" disabled>-- Pilih Supplier --</option>' +
      suppliers.map((sp) => `<option value="${sp.id_supplier}">${esc(sp.nama_supplier)} (${esc(sp.kontak || '-')})</option>`).join('');
    syncSelectOptions($('#add-acc-supplier'), addAccOpts);
  }

  function renderSupplierModalList() {
    const list = $('#supplier-modal-list');
    if (!list) return;
    list.innerHTML = DB.all('Supplier').map((sp) => `
      <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
        <div>
          <p class="font-semibold text-slate-800">${esc(sp.nama_supplier)}</p>
          <p class="text-[11px] text-slate-500 font-mono">${esc(sp.kontak || 'Tanpa kontak')}${sp.alamat ? ' • ' + esc(sp.alamat) : ''}</p>
        </div>
        <div class="flex items-center gap-1 shrink-0">
          <button type="button" data-action="edit-supplier-modal" data-id="${sp.id_supplier}" class="px-2 py-1 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="delete-supplier-modal" data-id="${sp.id_supplier}" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('') || '<p class="text-xs text-slate-400 italic py-2">Belum ada supplier terdaftar.</p>';
  }

  function updateDraftRollsSummary() {
    const totalRolls = state.draftRolls.length;
    const totalsByUnit = {};
    state.draftRolls.forEach((r) => {
      const cg = state.colorGroups.find((c) => Number(c.id) === Number(r.colorGroupId)) || state.colorGroups[0];
      const unit = cg ? (cg.satuanUkur || 'Kg') : 'Kg';
      totalsByUnit[unit] = (totalsByUnit[unit] || 0) + (parseFloat(r.berat) || 0);
    });
    const countEl = $('#summary-rolls-count');
    if (countEl) countEl.textContent = `${totalRolls} Roll Terdaftar`;
    const weightEl = $('#summary-rolls-weight');
    if (weightEl) {
      const parts = Object.keys(totalsByUnit).map((u) => `${totalsByUnit[u].toFixed(2)} ${u}`);
      const fallbackUnit = state.colorGroups[0]?.satuanUkur || 'Kg';
      weightEl.textContent = 'Total: ' + (parts.length > 0 ? parts.join(' + ') : `0.00 ${fallbackUnit}`);
    }
    state.colorGroups.forEach((cg) => {
      const badgeEl = document.querySelector(`.badge-cg-summary[data-cg-id="${cg.id}"]`);
      if (badgeEl) {
        const rollsInGroup = state.draftRolls.filter((r) => Number(r.colorGroupId) === Number(cg.id));
        const totalSize = sum(rollsInGroup, (r) => r.berat);
        badgeEl.textContent = `${rollsInGroup.length} Roll (${totalSize.toFixed(2)} ${cg.satuanUkur || 'Kg'})`;
      }
    });
  }

  function renderColorGroups() {
    const container = $('#color-groups-container');
    if (!container) return;
    container.innerHTML = state.colorGroups.map((cg) => {
      const rollsInGroup = state.draftRolls.filter((r) => Number(r.colorGroupId) === Number(cg.id));
      const totalSize = sum(rollsInGroup, (r) => r.berat);
      return `
        <div class="p-4 rounded-xl border border-slate-200 bg-white grid grid-cols-1 md:grid-cols-4 gap-4 items-end" data-cg-id="${cg.id}">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Warna Kain <span class="text-rose-500">*</span></label>
            <input type="text" value="${esc(cg.namaWarna)}" class="inp-cg-nama w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Hitam" data-cg-id="${cg.id}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Lab / Lot Warna</label>
            <input type="text" value="${esc(cg.kodeWarna)}" class="inp-cg-kode w-full text-xs p-2.5 border border-slate-200 rounded-lg font-mono uppercase placeholder:normal-case focus:ring-2 focus:ring-indigo-500" placeholder="WD2605226" data-cg-id="${cg.id}" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Satuan Ukur</label>
            <select class="sel-cg-satuan w-full text-xs p-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" data-cg-id="${cg.id}">
              <option value="Kg" ${cg.satuanUkur === 'Kg' ? 'selected' : ''}>Kg (Kilogram)</option>
              <option value="Yard" ${cg.satuanUkur === 'Yard' ? 'selected' : ''}>Yard</option>
              <option value="Meter" ${cg.satuanUkur === 'Meter' ? 'selected' : ''}>Meter</option>
            </select>
          </div>
          <div class="flex items-center justify-between gap-2">
            <span class="badge-cg-summary inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100" data-cg-id="${cg.id}">
              ${rollsInGroup.length} Roll (${totalSize.toFixed(2)} ${cg.satuanUkur || 'Kg'})
            </span>
            ${state.colorGroups.length > 1 ? `
              <button type="button" data-action="remove-color-group" data-cg-id="${cg.id}" class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg inline-flex items-center gap-1 transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
              </button>` : ''}
          </div>
        </div>`;
    }).join('');
    refreshIcons();
  }

  function renderDraftRollsTable() {
    const tbody = $('#tbl-draft-rolls-body');
    if (!tbody) return;
    tbody.innerHTML = state.draftRolls.map((r, i) => {
      const colorOptions = state.colorGroups.map((cg) => {
        const selected = Number(r.colorGroupId) === Number(cg.id) ? 'selected' : '';
        const label = (cg.namaWarna || `Warna ${cg.id}`) + (cg.kodeWarna ? ` (${cg.kodeWarna})` : '');
        return `<option value="${cg.id}" ${selected}>${esc(label)}</option>`;
      }).join('');
      const activeCg = state.colorGroups.find((c) => Number(c.id) === Number(r.colorGroupId)) || state.colorGroups[0];
      const unitLabel = activeCg ? (activeCg.satuanUkur || 'Kg') : 'Kg';
      return `
        <tr class="hover:bg-slate-50/80 transition-colors" data-roll-idx="${i}">
          <td class="pl-4 py-2.5 font-bold font-mono text-slate-800">Roll #${i + 1}</td>
          <td class="px-3 py-2.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100"># id_roll: Auto</span>
          </td>
          <td class="px-3 py-2.5">
            <select class="sel-roll-color w-full h-8 text-xs p-1.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500" data-roll-idx="${i}">${colorOptions}</select>
          </td>
          <td class="px-3 py-2.5">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 bg-white">
              <input type="number" step="0.01" min="0.1" lang="en-US" class="inp-roll-weight flex-1 min-w-0 h-8 text-xs font-mono px-2.5 py-1.5 border-0 focus:outline-none" data-roll-idx="${i}" value="${Number(r.berat).toFixed(2)}" />
              <span class="px-2.5 bg-slate-50 border-l border-slate-200 text-[11px] text-slate-600 font-mono font-semibold flex items-center select-none">${esc(unitLabel)}</span>
            </div>
          </td>
          <td class="px-3 py-2.5 text-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">&bull; Tersedia</span>
          </td>
          <td class="pr-4 py-2.5 text-center">
            <button type="button" data-action="remove-draft-roll" data-roll-idx="${i}" class="w-7 h-7 text-slate-400 hover:text-rose-600 rounded flex items-center justify-center transition-colors ${state.draftRolls.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''}" ${state.draftRolls.length <= 1 ? 'disabled' : ''}>
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>`;
    }).join('');
    updateDraftRollsSummary();
    refreshIcons();
  }

  function renderPenerimaan() {
    refreshSupplierSelects();
    const tglNotaInp = $('#inp-tgl-nota');
    if (tglNotaInp && !tglNotaInp.value) tglNotaInp.value = todayIso();

    renderColorGroups();
    renderDraftRollsTable();

    const list = [...DB.all('Penerimaan_Bahan')].sort((a, b) => b.id_penerimaan - a.id_penerimaan);
    $('#penerimaan-total-badge').textContent = `Total: ${list.length} Nota Masuk`;
    const wrap = $('#penerimaan-cards-list');
    if (list.length === 0) {
      wrap.innerHTML = '<p class="text-center text-slate-400 text-xs py-8">Belum ada nota penerimaan bahan terdaftar.</p>';
      return;
    }

    wrap.innerHTML = list.map((pn) => {
      const sup = Q.supplier(pn.id_supplier);
      const warnas = Q.warnaOfPenerimaan(pn.id_penerimaan);
      return `
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 transition-all">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-indigo-100 text-indigo-700 mr-1.5">#${pn.id_penerimaan}</span>
              <span class="font-mono font-bold text-sm text-indigo-600">${esc(pn.no_nota)}</span>
              <span class="text-xs font-semibold text-slate-700 ml-2">${esc(pn.nama_kain)}</span>
              ${pn.lebar_gramasi ? `<span class="text-[11px] text-slate-500 ml-1">(${esc(pn.lebar_gramasi)})</span>` : ''}
              <span class="text-[11px] text-slate-400 block">
                ${esc(sup?.nama_supplier || '-')} &bull; ${fmtDateDmy(pn.tgl_nota)}${pn.penerima ? ` &bull; Penerima: ${esc(pn.penerima)}` : ''}
              </span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono bg-white px-2.5 py-1 rounded-md border border-slate-200 text-slate-600">Kendaraan: ${esc(pn.no_kendaraan || '-')}</span>
              <button type="button" data-action="edit-nota" data-id="${pn.id_penerimaan}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Nota
              </button>
              <button type="button" data-action="delete-nota" data-id="${pn.id_penerimaan}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-md text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Nota
              </button>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            ${warnas.map((bw) => {
              const rolls = Q.rollsOfWarna(bw.id_detail_warna);
              return `
                <div class="bg-white p-3 rounded-lg border border-slate-200 text-xs">
                  <div class="flex items-center justify-between gap-1">
                    <div>
                      <span class="font-semibold text-slate-800">${esc(bw.nama_warna)}</span>
                      ${bw.kode_warna ? `<span class="text-[10px] font-mono text-slate-400 ml-1">(${esc(bw.kode_warna)})</span>` : ''}
                    </div>
                    <div class="flex items-center gap-1">
                      <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono">${esc(bw.satuan_ukur)}</span>
                      <button type="button" data-action="edit-warna" data-id="${bw.id_detail_warna}" class="px-1.5 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 rounded text-[10px] font-semibold">Edit Warna</button>
                      <button type="button" data-action="add-roll-existing" data-id="${bw.id_detail_warna}" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[10px] font-semibold">+ Roll</button>
                      <button type="button" data-action="delete-warna" data-id="${bw.id_detail_warna}" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
                    </div>
                  </div>
                  <div class="mt-1.5 text-[11px] text-slate-500">Total: ${bw.total_roll} roll &bull; ${Number(bw.total_ukuran).toFixed(2)} ${esc(bw.satuan_ukur)}</div>
                  <div class="mt-2 space-y-1">
                    ${rolls.map((roll) => {
                      const stCls = roll.status_roll === 'Tersedia' ? 'bg-emerald-100 text-emerald-800' : (roll.status_roll === 'Dipotong' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800');
                      return `
                        <div class="flex items-center justify-between text-[10px] font-mono bg-slate-50 p-1.5 rounded border border-slate-100">
                          <span>Roll #${roll.no_roll_ke} (#${roll.id_roll})</span>
                          <span>Sisa: <b>${Number(roll.ukuran_sisa).toFixed(2)}</b> / ${Number(roll.ukuran_awal).toFixed(2)} ${esc(roll.satuan)}</span>
                          <div class="flex items-center gap-1">
                            <span class="px-1.5 py-0.5 rounded font-semibold ${stCls}">${esc(roll.status_roll)}</span>
                            <button type="button" data-action="edit-roll" data-id="${roll.id_roll}" class="px-1.5 py-0.5 bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-700 border border-slate-200 rounded font-sans font-semibold">Edit</button>
                            <button type="button" data-action="delete-roll" data-id="${roll.id_roll}" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded font-sans font-semibold">Hapus</button>
                          </div>
                        </div>`;
                    }).join('')}
                  </div>
                </div>`;
            }).join('')}
          </div>
        </div>`;
    }).join('');
  }

  /* --------------------------------------------------------------------------
   * SCREEN 4: PEMOTONGAN SERI & GELARAN
   * ------------------------------------------------------------------------ */
  function updateUnitBadgeFromWarna() {
    const sel = $('#sel-warna-bahan');
    const opt = sel?.options[sel.selectedIndex];
    const satuan = opt?.getAttribute('data-satuan') || 'Kg';
    const badge = $('#badge-satuan-kain');
    if (badge) badge.textContent = satuan;
  }

  function syncTotalHasil() {
    let totalLembar = 0;
    let totalKainEst = 0;
    state.gelaranRows.forEach((r) => {
      totalLembar += parseInt(r.total_lembar_gelar, 10) || 0;
      totalKainEst += (parseFloat(r.panjang_gelar) || 0) * (parseInt(r.jumlah_tumpuk, 10) || 0) * 0.36;
    });
    const hasilInp = $('#inp-total-hasil');
    if (hasilInp && totalLembar > 0) hasilInp.value = totalLembar;
    const kainInp = $('#inp-kain-terpakai');
    if (kainInp && totalKainEst > 0) kainInp.value = totalKainEst.toFixed(2);
  }

  function renderGelaranRows() {
    const tbody = $('#gelaran-rows-body');
    if (!tbody) return;
    const activeRolls = DB.where('Detail_Roll_Bahan', (r) => r.status_roll !== 'Habis').sort((a, b) => a.id_roll - b.id_roll);
    if (state.gelaranRows.length && (!state.gelaranRows[0].id_roll || !activeRolls.some((r) => Number(r.id_roll) === Number(state.gelaranRows[0].id_roll)))) {
      state.gelaranRows[0].id_roll = activeRolls[0]?.id_roll || '';
    }
    const selectedWarnaId = Number($('#sel-warna-bahan')?.value || 0);

    tbody.innerHTML = state.gelaranRows.map((row, idx) => {
      const rollOpts = activeRolls.map((r) => {
        const w = Q.warna(r.id_detail_warna);
        const prefix = Number(r.id_detail_warna) === selectedWarnaId ? '★ ' : '';
        const selected = Number(row.id_roll) === Number(r.id_roll) ? 'selected' : '';
        return `<option value="${r.id_roll}" ${selected}>${prefix}#id_roll: ${r.id_roll} - Roll #${r.no_roll_ke} (${esc(w?.nama_warna || '-')} · Sisa: ${Number(r.ukuran_sisa).toFixed(2)}/${Number(r.ukuran_awal).toFixed(2)} ${esc(r.satuan)}) [${esc(r.status_roll)}]</option>`;
      }).join('');
      return `
        <tr class="hover:bg-slate-50/70">
          <td class="py-2.5 pl-4">
            <select class="sel-gelar-roll w-full text-xs p-2 border border-slate-200 rounded-lg font-mono bg-white" data-idx="${idx}" required>
              ${rollOpts || '<option value="">-- Tidak ada roll aktif --</option>'}
            </select>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" step="0.01" min="0.1" lang="en-US" class="inp-panjang-gelar flex-1 min-w-0 text-xs font-mono p-2 border-0 focus:outline-none" data-idx="${idx}" value="${Number(row.panjang_gelar).toFixed(2)}" />
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">m/yd</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" min="1" class="inp-jumlah-tumpuk flex-1 min-w-0 text-xs font-mono p-2 border-0 focus:outline-none" data-idx="${idx}" value="${row.jumlah_tumpuk}" />
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">Ply</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <div class="flex items-stretch border border-slate-200 rounded-lg overflow-hidden bg-white">
              <input type="number" min="1" class="inp-lembar-gelar flex-1 min-w-0 text-xs font-mono font-bold text-indigo-700 p-2 border-0 focus:outline-none" data-idx="${idx}" value="${row.total_lembar_gelar}" />
              <span class="px-2 bg-slate-50 border-l border-slate-200 text-[10px] font-mono text-slate-500 flex items-center">Lembar</span>
            </div>
          </td>
          <td class="py-2.5 px-3">
            <select class="sel-status-roll w-full text-xs p-2 border border-slate-200 rounded-lg bg-white font-semibold" data-idx="${idx}">
              <option value="Dipotong" ${row.status_roll_baru === 'Dipotong' ? 'selected' : ''}>Dipotong (Masih Tersisa)</option>
              <option value="Habis" ${row.status_roll_baru === 'Habis' ? 'selected' : ''}>Habis (Tuntas 0.00)</option>
            </select>
          </td>
          <td class="py-2.5 pr-4 text-center">
            <button type="button" data-action="remove-gelaran-row" data-idx="${idx}" class="w-7 h-7 text-slate-400 hover:text-rose-600 rounded inline-flex items-center justify-center ${state.gelaranRows.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''}" ${state.gelaranRows.length <= 1 ? 'disabled' : ''}>
              <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            </button>
          </td>
        </tr>`;
    }).join('');
    syncTotalHasil();
    refreshIcons();
  }

  function renderPemotongan() {
    const orders = Q.edfSortOrders(DB.all('Pesanan'));
    const cutters = DB.where('User', (u) => ['Pemotong', 'Admin'].includes(u.role));
    const warnaList = DB.all('Detail_Bahan_Warna');
    const allRolls = [...DB.all('Detail_Roll_Bahan')].sort((a, b) => a.id_roll - b.id_roll);

    const dtInput = $('#inp-tgl-potong');
    if (dtInput && !dtInput.value) dtInput.value = nowLocalIsoMin();

    $('#pm-queue-badge').textContent = `${orders.length} Pesanan Terjadwal`;
    const queueBody = $('#pm-edf-tbody');
    if (orders.length === 0) {
      queueBody.innerHTML = '<tr><td colspan="8" class="py-6 text-center text-slate-400">Belum ada antrean pesanan untuk dipotong.</td></tr>';
    } else {
      queueBody.innerHTML = orders.map((ord, idx) => {
        const pola = Q.pola(ord.id_pola);
        const sumber = Q.sumber(ord.id_sumber);
        const pen = ord.id_penerimaan ? Q.penerimaan(ord.id_penerimaan) : null;
        const warnas = pen ? Q.warnaOfPenerimaan(pen.id_penerimaan) : [];
        const firstWarna = warnas[0] || null;
        const firstRoll = firstWarna ? Q.rollsOfWarna(firstWarna.id_detail_warna).find((r) => r.status_roll !== 'Habis') : null;
        const prioCls = idx === 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : (idx === 1 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700');
        const stCls = ord.status_pesanan === 'Selesai' ? 'bg-emerald-100 text-emerald-800' : (ord.status_pesanan === 'Pemotongan' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700');
        return `
          <tr class="hover:bg-indigo-50/40 transition-colors">
            <td class="py-3"><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold ${prioCls}">#${idx + 1} ${idx === 0 ? 'PRIORITAS UTAMA' : 'ANTREAN'}</span></td>
            <td class="py-3">
              <span class="font-mono font-bold text-indigo-600">#${ord.id_pesanan}</span>
              <span class="font-semibold text-slate-800 ml-1">${esc(ord.nama_mitra || 'Internal (Produk Abadi)')}</span>
              <span class="text-[10px] text-slate-400 block">${esc(sumber?.nama_sumber || '-')} &bull; ${esc(ord.jenis_pesanan)}</span>
            </td>
            <td class="py-3 text-slate-700 font-medium">${esc(pola?.nama_pola || '-')}<span class="text-[10px] font-mono text-slate-400 block">${esc(pola?.kode_pola || '')}</span></td>
            <td class="py-3 text-[11px] text-slate-600">
              ${pen ? `<span class="font-mono font-semibold text-indigo-700">#${pen.id_penerimaan} &bull; ${esc(pen.no_nota)}</span><span class="block text-[10px] text-slate-400">${esc(warnas.map((w) => w.nama_warna).join(', ') || pen.nama_kain)}</span>` : '<span class="text-slate-400 italic">Belum terhubung nota</span>'}
            </td>
            <td class="py-3 font-mono text-slate-500">${fmtDateDmy(ord.deadline_awal)}</td>
            <td class="py-3 font-mono font-bold text-amber-600">
              ${fmtDateDmy(ord.deadline_terbaru || ord.deadline_awal)}
              ${ord.alasan_perubahan_deadline ? `<span class="block text-[10px] font-sans font-normal text-slate-400">Revisi: ${esc(ord.alasan_perubahan_deadline)}</span>` : ''}
            </td>
            <td class="py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${stCls}">${esc(ord.status_pesanan)}</span></td>
            <td class="py-3 text-right">
              <button type="button" data-action="select-order-queue" data-pesanan="${ord.id_pesanan}" data-warna="${firstWarna?.id_detail_warna || ''}" data-roll="${firstRoll?.id_roll || ''}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-semibold shadow-sm transition-colors inline-flex items-center gap-1">
                <i data-lucide="scissors" class="w-3 h-3"></i> Pilih Potong
              </button>
            </td>
          </tr>`;
      }).join('');
    }

    syncSelectOptions($('#sel-pm-pesanan'), '<option value="" disabled selected>-- Pilih Pesanan Target --</option>' +
      orders.map((ord, idx) => {
        const pola = Q.pola(ord.id_pola);
        const wId = ord.id_penerimaan ? (Q.warnaOfPenerimaan(ord.id_penerimaan)[0]?.id_detail_warna || '') : '';
        return `<option value="${ord.id_pesanan}" data-default-warna="${wId}">[Prioritas #${idx + 1}] Pesanan #${ord.id_pesanan} - ${esc(pola?.nama_pola || ord.jenis_pesanan)} (${esc(ord.nama_mitra || 'Internal')})</option>`;
      }).join(''));

    const cutterOpts = cutters.map((u) => `<option value="${u.id_user}">${esc(u.nama_user)} (${esc(u.role)} - @ ${esc(u.username)})</option>`).join('');
    syncSelectOptions($('#sel-pm-cutter'), cutterOpts);
    syncSelectOptions($('#edit-pm-cutter'), cutters.map((u) => `<option value="${u.id_user}">${esc(u.nama_user)} (${esc(u.role)})</option>`).join(''));
    syncSelectOptions($('#edit-pm-pesanan'), orders.map((o) => `<option value="${o.id_pesanan}">Pesanan #${o.id_pesanan} - ${esc(Q.pola(o.id_pola)?.nama_pola || o.jenis_pesanan)} (${esc(o.nama_mitra || 'Internal')})</option>`).join(''));

    const warnaOpts = warnaList.map((w) => {
      const pn = Q.penerimaan(w.id_penerimaan);
      return `<option value="${w.id_detail_warna}" data-satuan="${esc(w.satuan_ukur)}">#${w.id_detail_warna} - ${esc(w.nama_warna)} (#${w.id_penerimaan} - ${esc(pn?.no_nota || 'Nota')} · ${esc(pn?.nama_kain || '')} · ${Number(w.total_ukuran).toFixed(2)} ${esc(w.satuan_ukur)})</option>`;
    }).join('');
    syncSelectOptions($('#sel-warna-bahan'), warnaOpts);
    syncSelectOptions($('#edit-pm-warna'), warnaList.map((w) => `<option value="${w.id_detail_warna}">#${w.id_detail_warna} - ${esc(w.nama_warna)} (#${w.id_penerimaan} - ${esc(Q.penerimaan(w.id_penerimaan)?.no_nota || 'Nota')} · ${esc(w.satuan_ukur)})</option>`).join(''));
    syncSelectOptions($('#edit-pm-roll'), allRolls.map((r) => `<option value="${r.id_roll}">#id_roll: ${r.id_roll} - Roll #${r.no_roll_ke} (${esc(Q.warna(r.id_detail_warna)?.nama_warna || '-')} · Sisa: ${Number(r.ukuran_sisa).toFixed(2)} ${esc(r.satuan)})</option>`).join(''));

    updateUnitBadgeFromWarna();
    renderGelaranRows();

    const history = [...DB.all('Pemotongan_Seri')].sort((a, b) => String(b.tgl_potong || '').localeCompare(String(a.tgl_potong || '')) || b.id_potong - a.id_potong);
    const histBody = $('#pm-history-tbody');
    if (history.length === 0) {
      histBody.innerHTML = '<tr><td colspan="8" class="py-6 text-center text-slate-400">Belum ada catatan pemotongan seri.</td></tr>';
    } else {
      histBody.innerHTML = history.map((pm) => {
        const ord = Q.pesanan(pm.id_pesanan);
        const pola = ord ? Q.pola(ord.id_pola) : null;
        const cutter = Q.user(pm.id_user_pemotong);
        const pws = DB.where('Detail_Pemotongan_Warna', (pw) => Number(pw.id_potong) === Number(pm.id_potong));
        const totalRencana = sum(pws, (pw) => pw.total_lembar_direncanakan) || pm.total_lembar_hasil;
        const gelaranAll = [];
        pws.forEach((pw) => {
          DB.where('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna)).forEach((g) => gelaranAll.push(g));
        });
        return `
          <tr class="hover:bg-slate-50">
            <td class="py-3 font-mono font-bold text-indigo-600">
              ${esc(pm.kode_seri)}
              ${pm.jenis_variasi ? `<span class="block text-[10px] font-sans font-normal text-slate-400">${esc(pm.jenis_variasi)}</span>` : ''}
            </td>
            <td class="py-3">
              <span class="font-medium text-slate-800">Pesanan #${pm.id_pesanan}</span>
              <span class="text-[10px] text-slate-400 block">${esc(pola?.nama_pola || '-')}</span>
            </td>
            <td class="py-3 text-slate-600">${esc(cutter?.nama_user || '-')}</td>
            <td class="py-3 font-mono">
              <span class="text-slate-500">${fmtNum(totalRencana)}</span>
              <span class="text-slate-400">/</span>
              <span class="font-bold text-emerald-700">${fmtNum(pm.total_lembar_hasil)} Pcs</span>
            </td>
            <td class="py-3 font-mono text-slate-500">${fmtDateTimeDmy(pm.tgl_potong)}</td>
            <td class="py-3 text-[11px] text-slate-600">
              ${pws.map((pw) => {
                const w = Q.warna(pw.id_detail_warna);
                return `<div><span class="font-semibold">${esc(w?.nama_warna || '-')}</span>: <span class="font-mono font-bold text-indigo-700">${Number(pw.kain_terpakai).toFixed(2)} ${esc(w?.satuan_ukur || 'Kg')}</span></div>`;
              }).join('')}
            </td>
            <td class="py-3 text-[11px] text-slate-600 space-y-1">
              ${gelaranAll.map((gk) => {
                const rl = Q.roll(gk.id_roll);
                const rlCls = rl?.status_roll === 'Habis' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800';
                return `
                  <div class="bg-slate-50 border border-slate-200 rounded px-2 py-1 font-mono text-[10px]">
                    <span class="font-bold text-indigo-700">Roll #${rl?.no_roll_ke ?? '?'} (#${gk.id_roll})</span>
                    &bull; Gelar: <b>${Number(gk.panjang_gelar).toFixed(2)}m</b>
                    &bull; Ply: <b>${gk.jumlah_tumpuk} lapis</b>
                    &bull; Hasil: <b>${gk.total_lembar_gelar} lbr</b>
                    ${rl ? `<span class="ml-1 px-1.5 py-0.2 rounded ${rlCls}">${esc(rl.status_roll)} (Sisa: ${Number(rl.ukuran_sisa).toFixed(2)})</span>` : ''}
                  </div>`;
              }).join('')}
            </td>
            <td class="py-3 text-right">
              <div class="inline-flex items-center justify-end gap-1.5">
                <button type="button" data-action="edit-pemotongan" data-id="${pm.id_potong}" class="px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-[11px] font-semibold inline-flex items-center gap-1">
                  <i data-lucide="edit-3" class="w-3 h-3"></i> Edit
                </button>
                <button type="button" data-action="delete-pemotongan" data-id="${pm.id_potong}" class="px-2.5 py-1 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 rounded-lg text-[11px] font-semibold inline-flex items-center gap-1">
                  <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
                </button>
              </div>
            </td>
          </tr>`;
      }).join('');
    }
  }

  /* --------------------------------------------------------------------------
   * SCREEN 5: QUALITY CONTROL & KERUSAKAN
   * ------------------------------------------------------------------------ */
  function updateQcStokIndicator() {
    const idPesanan = $('#sel-qc-pesanan')?.value;
    const ord = idPesanan ? Q.pesanan(idPesanan) : null;
    const box = $('#box-integrasi-stok');
    const isAbadi = Q.isProdukAbadi(ord);
    if (box) box.classList.toggle('hidden', !isAbadi);
    if (!isAbadi) return;

    const tahap = $('#sel-tahap-qc')?.value;
    const lolos = parseInt($('#inp-qc-lolos')?.value, 10) || 0;
    const badge = $('#badge-indikator-stok');
    const warna = $('#inp-qc-warna')?.value || 'Hitam';
    const specSel = $('#sel-qc-spesifikasi');
    const specText = specSel?.options[specSel.selectedIndex]?.text || 'Ukuran Default';
    if (!badge) return;
    if (tahap === 'Jahit Akhir' && lolos > 0) {
      badge.className = 'px-3 py-1.5 rounded-full text-xs font-mono font-bold bg-emerald-600 text-white shadow-sm';
      badge.textContent = `✓ AKTIF: +${lolos} Pcs Masuk Stok_Barang_Jadi (${specText} · ${warna})`;
    } else {
      badge.className = 'px-3 py-1.5 rounded-full text-xs font-mono font-semibold bg-slate-200 text-slate-600';
      badge.textContent = 'Non-Aktif (Hanya Tahap "Jahit Akhir" yang menambah Stok_Barang_Jadi)';
    }
  }

  function syncQcOrderInfo() {
    const idPesanan = $('#sel-qc-pesanan')?.value;
    const ord = idPesanan ? Q.pesanan(idPesanan) : null;
    const dispPola = $('#inp-display-pola');
    const specSel = $('#sel-qc-spesifikasi');
    const warnaInp = $('#inp-qc-warna');
    if (ord) {
      const pola = Q.pola(ord.id_pola);
      const specs = Q.specsOfPola(ord.id_pola);
      const firstWarna = ord.id_penerimaan ? Q.warnaOfPenerimaan(ord.id_penerimaan)[0]?.nama_warna : null;
      if (dispPola) dispPola.value = pola?.nama_pola || '-';
      if (warnaInp && firstWarna) warnaInp.value = firstWarna;
      if (specSel) {
        specSel.innerHTML = specs.map((s) => `<option value="${s.id_spesifikasi}">Ukuran ${esc(s.nama_ukuran)} (#id_spesifikasi: ${s.id_spesifikasi})</option>`).join('') || '<option value="">-- Ukuran Default --</option>';
      }
    }
    updateQcStokIndicator();
  }

  function renderQc() {
    const orders = [...DB.all('Pesanan')].sort((a, b) => b.id_pesanan - a.id_pesanan);
    const qcUsers = DB.where('User', (u) => ['QC', 'Admin'].includes(u.role));

    const dtInput = $('#inp-tgl-pemeriksaan');
    if (dtInput && !dtInput.value) dtInput.value = nowLocalIsoMin();

    const ordOpts = '<option value="" disabled selected>-- Pilih Pesanan Produksi --</option>' +
      orders.map((ord) => {
        const pola = Q.pola(ord.id_pola);
        const sumber = Q.sumber(ord.id_sumber);
        return `<option value="${ord.id_pesanan}">Pesanan #${ord.id_pesanan} - ${esc(pola?.nama_pola || ord.jenis_pesanan)} (${esc(ord.nama_mitra || (sumber?.nama_sumber || 'Internal Produk Abadi'))} · ${esc(sumber?.nama_sumber || 'Umum')} · Status: ${esc(ord.status_pesanan)})</option>`;
      }).join('');
    syncSelectOptions($('#sel-qc-pesanan'), ordOpts);
    syncSelectOptions($('#edit-qc-pesanan'), orders.map((o) => `<option value="${o.id_pesanan}">Pesanan #${o.id_pesanan} - ${esc(Q.pola(o.id_pola)?.nama_pola || o.jenis_pesanan)}</option>`).join(''));

    const activeUid = Auth.getUser()?.id_user;
    const userOpts = qcUsers.map((u) => `<option value="${u.id_user}" ${Number(activeUid) === Number(u.id_user) ? 'selected' : ''}>${esc(u.nama_user)} (${esc(u.role)} - @ ${esc(u.username)})</option>`).join('');
    syncSelectOptions($('#sel-user-qc'), userOpts);
    syncSelectOptions($('#edit-qc-user'), qcUsers.map((u) => `<option value="${u.id_user}">${esc(u.nama_user)} (${esc(u.role)})</option>`).join(''));

    syncQcOrderInfo();

    const records = [...DB.all('QC')].sort((a, b) => String(b.tgl_pemeriksaan || '').localeCompare(String(a.tgl_pemeriksaan || '')) || b.id_qc - a.id_qc);
    const tbody = $('#qc-history-tbody');
    if (records.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="py-6 text-center text-slate-400">Belum ada data inspeksi QC.</td></tr>';
      return;
    }

    tbody.innerHTML = records.map((qc) => {
      const ord = Q.pesanan(qc.id_pesanan);
      const pola = ord ? Q.pola(ord.id_pola) : null;
      const usr = Q.user(qc.id_user_qc);
      const isAbadi = Q.isProdukAbadi(ord);
      const stCls = qc.status_qc === 'Lolos QC' ? 'bg-emerald-100 text-emerald-800' : (qc.status_qc === 'Need Rework' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800');
      return `
        <tr class="hover:bg-slate-50">
          <td class="py-3 font-mono font-semibold text-indigo-600">#${qc.id_qc}</td>
          <td class="py-3">
            <span class="font-mono text-slate-700 block">${fmtDateTimeDmy(qc.tgl_pemeriksaan)}</span>
            <span class="text-[10px] text-slate-500 font-semibold">Oleh: ${esc(usr?.nama_user || `User #${qc.id_user_qc}`)}</span>
          </td>
          <td class="py-3">
            <span class="font-semibold text-slate-800">Pesanan #${qc.id_pesanan}</span>
            <span class="text-[10px] text-slate-400 block">${esc(pola?.nama_pola || '-')}</span>
          </td>
          <td class="py-3">
            <span class="px-2 py-0.5 rounded font-mono font-semibold text-[10px] ${qc.tahap_qc === 'Jahit Akhir' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700'}">${esc(qc.tahap_qc)}</span>
            ${isAbadi && qc.tahap_qc === 'Jahit Akhir' && qc.jumlah_lolos > 0 ? `<span class="block text-[10px] text-emerald-600 font-semibold mt-0.5">+${qc.jumlah_lolos} Pcs ke Stok Jadi</span>` : ''}
          </td>
          <td class="py-3 font-mono">
            <span class="text-slate-600">${qc.jumlah_diperiksa}</span> /
            <span class="text-emerald-600 font-bold">${qc.jumlah_lolos}</span> /
            <span class="text-rose-600 font-bold">${qc.jumlah_cacat}</span>
          </td>
          <td class="py-3">
            <span class="font-semibold text-slate-700">${esc(qc.jenis_cacat)}</span>
            ${qc.catatan_qc ? `<span class="block text-[10px] text-slate-500 mt-0.5">${esc(qc.catatan_qc)}</span>` : ''}
          </td>
          <td class="py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${stCls}">${esc(qc.status_qc)}</span></td>
          <td class="py-3 font-mono text-[11px]">
            <div class="text-slate-500">Target: ${fmtDateDmy(qc.tgl_target_perbaikan)}</div>
            ${qc.tgl_selesai_perbaikan ? `<div class="text-emerald-700 font-bold mt-0.5">Selesai: ${fmtDateTimeDmy(qc.tgl_selesai_perbaikan)}</div>` : ''}
          </td>
          <td class="py-3 text-right">
            <div class="inline-flex items-center justify-end gap-1.5">
              ${qc.status_qc === 'Need Rework' ? `<button type="button" data-action="complete-rework" data-id="${qc.id_qc}" class="px-2.5 py-1.5 text-[11px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow-sm">Selesaikan Rework</button>` : ''}
              <button type="button" data-action="edit-qc" data-id="${qc.id_qc}" class="px-2.5 py-1.5 text-[11px] bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded-lg font-semibold inline-flex items-center gap-1">
                <i data-lucide="edit-3" class="w-3 h-3"></i> Edit
              </button>
              <button type="button" data-action="delete-qc" data-id="${qc.id_qc}" class="px-2.5 py-1.5 text-[11px] bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 rounded-lg font-semibold inline-flex items-center gap-1">
                <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
              </button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  /* --------------------------------------------------------------------------
   * SCREEN 6: GUDANG & STOK BARANG JADI
   * ------------------------------------------------------------------------ */
  function populateModalSpecsForPola(selectEl, idPola, selectedSpecId = null) {
    if (!selectEl) return;
    const specs = Q.specsOfPola(idPola);
    selectEl.innerHTML = specs.map((s) => {
      const sel = Number(selectedSpecId) === Number(s.id_spesifikasi) ? 'selected' : '';
      return `<option value="${s.id_spesifikasi}" ${sel}>Ukuran ${esc(s.nama_ukuran)} (#${s.id_spesifikasi})</option>`;
    }).join('');
  }

  function toggleMitraFieldRequirement() {
    const alasan = $('#adj-alasan')?.value || '';
    const mitraInp = $('#adj-nama-mitra');
    const mitraLabel = $('#adj-mitra-label');
    if (!mitraInp || !mitraLabel) return;
    if (alasan.toLowerCase().includes('mitra')) {
      mitraInp.required = true;
      mitraInp.placeholder = 'Jupiter';
      mitraInp.className = 'w-full text-xs p-2.5 border border-indigo-200 bg-indigo-50/30 rounded-lg focus:ring-2 focus:ring-indigo-500';
      mitraLabel.innerHTML = '7. Nama Mitra Pengambil Barang (<code class="font-mono">nama_mitra</code>) <span class="text-rose-500">*</span>';
    } else {
      mitraInp.required = false;
      mitraInp.placeholder = 'Pembeli Retail (Opsional)';
      mitraInp.className = 'w-full text-xs p-2.5 border border-slate-200 bg-white rounded-lg focus:ring-2 focus:ring-indigo-500';
      mitraLabel.innerHTML = '7. Nama Mitra / Penerima (<code class="font-mono">nama_mitra</code>) <span class="text-slate-400 font-normal">(Opsional)</span>';
    }
  }

  function renderGudang() {
    refreshSupplierSelects();
    const cfg = DB.config().thresholds;
    const minJadi = Number(cfg.min_stok_jadi ?? 20);
    const minAcc = Number(cfg.min_stok_aksesoris ?? 500);
    $('#inp-threshold-jadi').value = minJadi;
    $('#inp-threshold-aksesoris').value = minAcc;

    const stok = [...DB.all('Stok_Barang_Jadi')].sort((a, b) => String(b.tgl_update_terakhir || '').localeCompare(String(a.tgl_update_terakhir || '')));
    const aksesoris = DB.all('Aksesoris');
    const polas = DB.all('Pola');

    const lowStokJadi = stok.filter((s) => Number(s.jumlah_stok_pcs) <= minJadi);
    const lowAcc = aksesoris.filter((a) => Number(a.stok) < minAcc);
    const banner = $('#gudang-low-stock-banner');
    if (lowStokJadi.length > 0 || lowAcc.length > 0) {
      banner.innerHTML = `
        <div class="p-4 rounded-xl bg-rose-50 border-2 border-rose-200 flex items-start gap-3 shadow-sm">
          <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5"><i data-lucide="alert-triangle" class="w-4 h-4"></i></div>
          <div class="text-xs text-rose-900 space-y-1 flex-1">
            <div class="font-bold text-sm text-rose-800">Peringatan Stok Rendah / Habis (Perlu Restock Segera!)</div>
            ${lowStokJadi.length > 0 ? `<div><span class="font-semibold">Stok Barang Jadi Menipis / Habis (&le; ${fmtNum(minJadi)} Pcs):</span> ${lowStokJadi.map((ls) => `<span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-mono font-bold text-[11px] mr-1 mt-1 border border-rose-300">${esc(Q.pola(ls.id_pola)?.nama_pola || 'Pola')} (${esc(Q.spec(ls.id_spesifikasi)?.nama_ukuran || '-')} · ${esc(ls.nama_warna)}): ${ls.jumlah_stok_pcs} Pcs</span>`).join('')}</div>` : ''}
            ${lowAcc.length > 0 ? `<div><span class="font-semibold">Stok Aksesoris Menipis / Habis (&lt; ${fmtNum(minAcc)} Unit):</span> ${lowAcc.map((la) => `<span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-mono font-bold text-[11px] mr-1 mt-1 border border-rose-300">${esc(la.nama_aksesoris)}: ${fmtNum(la.stok)} ${esc(la.satuan)}</span>`).join('')}</div>` : ''}
          </div>
        </div>`;
    } else {
      banner.innerHTML = `
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex flex-wrap items-center justify-between gap-2 text-xs text-emerald-800">
          <div class="flex items-center gap-2 font-semibold"><i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i><span>Seluruh Stok Barang Jadi &amp; Inventori Aksesoris berada di atas batas aman minimum.</span></div>
          <span class="text-[11px] font-mono text-emerald-700">Indikator Merah aktif otomatis saat stok &le; ${fmtNum(minJadi)} Pcs (Barang Jadi) atau &lt; ${fmtNum(minAcc)} Unit (Aksesoris)</span>
        </div>`;
    }

    const stokBody = $('#gudang-stok-tbody');
    stokBody.innerHTML = stok.length ? stok.map((st) => {
      const pola = Q.pola(st.id_pola);
      const spec = Q.spec(st.id_spesifikasi);
      const qty = Number(st.jumlah_stok_pcs);
      const isZero = qty <= 0;
      const isLow = qty > 0 && qty <= minJadi;
      return `
        <tr class="${(isZero || isLow) ? 'bg-rose-50/70 hover:bg-rose-100/60' : 'hover:bg-slate-50'} transition-colors">
          <td class="py-3 font-medium text-slate-800">${esc(pola?.nama_pola || '-')}</td>
          <td class="py-3 font-mono font-semibold text-indigo-600">${esc(spec?.nama_ukuran || '-')}</td>
          <td class="py-3">${esc(st.nama_warna)}</td>
          <td class="py-3 font-mono font-bold ${(isZero || isLow) ? 'text-rose-600' : 'text-emerald-600'}">
            ${fmtNum(qty)} pcs
            ${isZero ? '<span class="ml-1 px-1.5 py-0.5 rounded bg-rose-600 text-white text-[9px] font-sans uppercase">HABIS (0)</span>' : (isLow ? '<span class="ml-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-300 text-[9px] font-sans uppercase">RENDAH</span>' : '')}
          </td>
          <td class="py-3 font-mono text-[10px] text-slate-400">${fmtDateTimeDmy(st.tgl_update_terakhir)}</td>
          <td class="py-3 text-right">
            <div class="inline-flex items-center justify-end gap-1">
              <button type="button" data-action="edit-stok-jadi" data-id="${st.id_stok}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md font-semibold text-[11px] inline-flex items-center gap-1"><i data-lucide="edit-3" class="w-3 h-3"></i> Edit</button>
              <button type="button" data-action="outbound-stok-jadi" data-id="${st.id_stok}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1"><i data-lucide="log-out" class="w-3 h-3"></i> Keluar</button>
              <button type="button" data-action="delete-stok-jadi" data-id="${st.id_stok}" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada stok barang jadi terdaftar.</td></tr>';

    const accBody = $('#gudang-aksesoris-tbody');
    accBody.innerHTML = aksesoris.length ? aksesoris.map((acc) => {
      const sup = Q.supplier(acc.id_supplier);
      const qty = Number(acc.stok);
      const accZero = qty <= 0;
      const accLow = qty > 0 && qty < minAcc;
      return `
        <tr class="${(accZero || accLow) ? 'bg-rose-50/70 hover:bg-rose-100/60' : 'hover:bg-slate-50'} transition-colors">
          <td class="py-3 font-medium text-slate-800">${esc(acc.nama_aksesoris)}</td>
          <td class="py-3 font-mono text-slate-500">${esc(acc.ukuran || '-')}</td>
          <td class="py-3 font-mono font-bold ${(accZero || accLow) ? 'text-rose-600' : 'text-slate-800'}">
            ${fmtNum(qty)} <span class="text-[10px] text-slate-400 font-normal">${esc(acc.satuan)}</span>
            ${accZero ? '<span class="block text-[9px] font-sans font-bold text-rose-600 uppercase">STOK HABIS (0)</span>' : (accLow ? `<span class="block text-[9px] font-sans font-bold text-rose-600 uppercase">STOK RENDAH (&lt;${fmtNum(minAcc)})</span>` : '')}
          </td>
          <td class="py-3 text-slate-500 text-[11px]">${esc(sup?.nama_supplier || '-')}</td>
          <td class="py-3 text-center">
            <div class="inline-flex items-center gap-1">
              <button type="button" data-action="quick-acc" data-id="${acc.id_aksesoris}" data-delta="-500" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-mono font-semibold text-[10px] rounded border border-rose-200">-500</button>
              <button type="button" data-action="quick-acc" data-id="${acc.id_aksesoris}" data-delta="-100" class="px-1.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-mono font-semibold text-[10px] rounded border border-rose-200">-100</button>
              <button type="button" data-action="quick-acc" data-id="${acc.id_aksesoris}" data-delta="100" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[10px] rounded border border-indigo-200">+100</button>
              <button type="button" data-action="quick-acc" data-id="${acc.id_aksesoris}" data-delta="500" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-mono font-semibold text-[10px] rounded border border-indigo-200">+500</button>
            </div>
          </td>
          <td class="py-3 text-right">
            <div class="inline-flex items-center gap-1">
              <button type="button" data-action="edit-acc" data-id="${acc.id_aksesoris}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
              <button type="button" data-action="delete-acc" data-id="${acc.id_aksesoris}" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
            </div>
          </td>
        </tr>`;
    }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada data aksesoris.</td></tr>';

    $('#gudang-pola-acc-grid').innerHTML = polas.length ? polas.map((pl) => {
      const maps = Q.aksesorisOfPola(pl.id_pola);
      return `
        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="font-mono text-xs font-bold text-indigo-600">${esc(pl.kode_pola)}</span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600 font-semibold">${maps.length} Komponen</span>
            </div>
            <h4 class="font-bold text-xs text-slate-900 mb-3">${esc(pl.nama_pola)}</h4>
            <div class="space-y-1.5">
              ${maps.length ? maps.map((dap) => `
                <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs">
                  <div>
                    <span class="font-semibold text-slate-800">${esc(dap.aksesoris?.nama_aksesoris || `Aksesoris #${dap.id_aksesoris}`)}</span>
                    <span class="block text-[10px] font-mono text-indigo-600 font-bold">Kebutuhan: ${dap.jumlah_digunakan} ${esc(dap.aksesoris?.satuan || 'Pcs')} / 1 Pakaian</span>
                  </div>
                  <div class="inline-flex items-center gap-1">
                    <button type="button" data-action="edit-pola-map" data-pola="${pl.id_pola}" data-acc="${dap.id_aksesoris}" data-qty="${dap.jumlah_digunakan}" class="text-slate-400 hover:text-indigo-600 p-1"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                    <button type="button" data-action="delete-pola-map" data-pola="${pl.id_pola}" data-acc="${dap.id_aksesoris}" class="text-slate-400 hover:text-rose-600 p-1"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                  </div>
                </div>`).join('') : '<p class="text-[11px] text-slate-400 italic py-2">Belum ada aksesoris terhubung ke pola ini.</p>'}
            </div>
          </div>
        </div>`;
    }).join('') : '<p class="col-span-full text-center text-xs text-slate-400 py-4">Belum ada Master Pola terdaftar.</p>';

    const riwayat = [...DB.all('Riwayat_Stok_Barang_Jadi')].sort((a, b) => String(b.tgl_transaksi || '').localeCompare(String(a.tgl_transaksi || '')) || b.id_riwayat_stok - a.id_riwayat_stok);
    $('#gudang-riwayat-badge').textContent = `${riwayat.length} Transaksi Tercatat`;
    $('#gudang-riwayat-tbody').innerHTML = riwayat.length ? riwayat.map((rw) => {
      const pola = Q.pola(rw.id_pola);
      const spec = Q.spec(rw.id_spesifikasi);
      const usr = Q.user(rw.id_user);
      const isKeluar = rw.jenis_transaksi === 'KELUAR';
      return `
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="py-3 font-mono text-[11px] text-slate-500 whitespace-nowrap">${fmtDateTimeDmy(rw.tgl_transaksi)}</td>
          <td class="py-3">
            <span class="font-semibold text-slate-800 block">${esc(pola?.nama_pola || `Pola #${rw.id_pola}`)}</span>
            <span class="text-[11px] text-slate-500">Ukuran <strong class="font-mono text-indigo-600">${esc(spec?.nama_ukuran || '-')}</strong> &bull; ${esc(rw.nama_warna)}</span>
          </td>
          <td class="py-3 whitespace-nowrap">
            ${isKeluar
              ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 font-mono">-${rw.jumlah_pcs} pcs (KELUAR)</span>`
              : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">+${rw.jumlah_pcs} pcs (MASUK)</span>`}
          </td>
          <td class="py-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">${rw.stok_sebelum} &rarr; <strong class="text-slate-900">${rw.stok_sesudah} pcs</strong></td>
          <td class="py-3 text-slate-700 font-medium">${esc(rw.kategori_alasan)}</td>
          <td class="py-3">
            ${rw.nama_mitra ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 font-semibold text-[11px]"><i data-lucide="building-2" class="w-3 h-3"></i>${esc(rw.nama_mitra)}</span>` : '<span class="text-slate-400 italic text-[11px]">- Non-Mitra / Internal -</span>'}
          </td>
          <td class="py-3 text-[11px] text-slate-500">
            ${rw.keterangan ? `<span class="block text-slate-700">${esc(rw.keterangan)}</span>` : ''}
            <span class="text-[10px] text-slate-400">Oleh: ${esc(usr?.nama_user || 'Petugas Gudang')}</span>
          </td>
          <td class="py-3 text-right whitespace-nowrap">
            <button type="button" data-action="delete-riwayat-stok" data-id="${rw.id_riwayat_stok}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-md text-[11px] font-semibold inline-flex items-center gap-1">
              <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Hapus &amp; Reverse
            </button>
          </td>
        </tr>`;
    }).join('') : '<tr><td colspan="8" class="py-6 text-center text-slate-400">Belum ada riwayat transaksi keluar/masuk stok barang jadi yang tercatat.</td></tr>';

    const polaOpts = polas.map((pl) => `<option value="${pl.id_pola}">${esc(pl.kode_pola)} - ${esc(pl.nama_pola)}</option>`).join('');
    syncSelectOptions($('#adj-id-pola'), polaOpts);
    syncSelectOptions($('#edit-stok-pola'), polaOpts);
    syncSelectOptions($('#map-id-pola'), polaOpts);
    syncSelectOptions($('#map-id-aksesoris'), aksesoris.map((a) => `<option value="${a.id_aksesoris}">${esc(a.nama_aksesoris)} (Stok: ${fmtNum(a.stok)} ${esc(a.satuan)})</option>`).join(''));

    const mitras = Array.from(new Set([
      ...DB.all('Pesanan').map((p) => p.nama_mitra).filter(Boolean),
      ...riwayat.map((r) => r.nama_mitra).filter(Boolean),
    ]));
    $('#mitra-datalist').innerHTML = mitras.map((m) => `<option value="${esc(m)}"></option>`).join('');
  }

  /* --------------------------------------------------------------------------
   * SCREEN 7: LAPORAN EKSEKUTIF (5 Report Types + Kop Surat + .doc + .csv + PDF)
   * ------------------------------------------------------------------------ */
  function renderLaporan() {
    const canEditKop = Auth.getEffectiveRole() === 'Admin';
    const btnKop = $('#btn-edit-kop-surat');
    if (btnKop) btnKop.style.display = canEditKop ? '' : 'none';

    const sumberOpts = '<option value="">-- Semua Sumber Inisiasi --</option>' +
      DB.all('Sumber_Inisiasi').map((s) => `<option value="${s.id_sumber}">${esc(s.nama_sumber)}</option>`).join('');
    syncSelectOptions($('#lap-filter-sumber'), sumberOpts);

    $('#lap-filter-jenis').value = state.laporanFilters.jenis_laporan;
    $('#lap-filter-sumber').value = state.laporanFilters.id_sumber || '';
    $('#lap-filter-start').value = state.laporanFilters.start_date || '';
    $('#lap-filter-end').value = state.laporanFilters.end_date || '';

    const rep = Svc.buildReportData(state.laporanFilters);
    state.lastReportData = rep;

    const jenis = rep.filters.jenis_laporan;
    const company = rep.companyProfile;
    const meta = rep.meta;
    const summary = rep.summary;
    const sumStokMin = rep.thresholds.min_stok_jadi;
    const sumAccMin = rep.thresholds.min_stok_aksesoris;

    const titles = {
      KONSOLIDASI: 'Laporan Rekapitulasi & Konsolidasi Produksi CMT (End-to-End)',
      STOK: 'Laporan Posisi Stok Gudang Barang Jadi, Aksesoris & Roll Kain',
      BAHAN_MASUK: 'Laporan Penerimaan Bahan Baku & Registrasi Roll Supplier',
      MUTASI_KELUAR: 'Laporan Pengeluaran Barang Jadi (Outbound Mitra) & Riwayat Mutasi',
      QC: 'Laporan Rekapitulasi Inspeksi Quality Control (QC) & Kerusakan',
    };
    const activeReportTitle = titles[jenis] || titles.KONSOLIDASI;
    rep.activeReportTitle = activeReportTitle;

    const startLbl = rep.filters.start_date ? fmtDateShortId(rep.filters.start_date) : null;
    const endLbl = rep.filters.end_date ? fmtDateShortId(rep.filters.end_date) : null;
    let periodeText = '';
    if (startLbl && endLbl) periodeText = `${startLbl} s/d ${endLbl}`;
    else if (startLbl) periodeText = `Mulai ${startLbl} s/d ${meta.printed_date_short}`;
    else if (endLbl) periodeText = `Hingga ${endLbl}`;
    else periodeText = jenis === 'STOK' ? `Posisi Stok Per ${meta.printed_date_short}` : `Semua Periode Operasional (s/d ${meta.printed_date_short})`;
    rep.periodeText = periodeText;

    // 3 KPI Summary Cards
    let kpiHtml = '';
    if (jenis === 'KONSOLIDASI') {
      kpiHtml = `
        <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PESANAN PRODUKSI</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">${fmtNum(summary.total_pesanan)} Pesanan</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${summary.pesanan_selesai} Selesai &bull; ${summary.pesanan_proses} Dalam Proses</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL HASIL POTONG &amp; KAIN</p>
            <p class="text-xl font-extrabold text-indigo-700 mt-1">${fmtNum(summary.total_lembar_potong)} Lembar</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${summary.total_roll_dikonsumsi} Roll (${Number(summary.total_kain_terpakai).toFixed(2)} Kg/Yd/M)</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">KELOLOSAN QC &amp; STOK JADI</p>
            <p class="text-xl font-extrabold text-emerald-700 mt-1">${summary.pass_rate_pct}% Lolos QC</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Gudang Abadi: ${fmtNum(summary.total_stok_jadi)} Pcs</p>
          </div>
        </div>`;
    } else if (jenis === 'STOK') {
      const kritik = summary.low_stock_jadi_count + summary.low_stock_aksesoris_count;
      kpiHtml = `
        <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL VARIASI STOK JADI</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">${fmtNum(summary.total_sku_stok_jadi)} Item (${fmtNum(summary.total_stok_jadi)} Pcs)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Batas min. global: &le; ${sumStokMin} Pcs</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">PERSEDIAAN AKSESORIS CMT</p>
            <p class="text-xl font-extrabold text-indigo-700 mt-1">${fmtNum(summary.total_aksesoris_item)} Jenis (${fmtNum(summary.total_aksesoris_unit)} Unit)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Batas min. aksesoris: &lt; ${sumAccMin} Unit</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">ITEM STOK MINIMUM &amp; ROLL SISA</p>
            <p class="text-xl font-extrabold ${kritik > 0 ? 'text-rose-700' : 'text-emerald-700'} mt-1">${kritik} Item Kritis</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${summary.roll_tersedia_count + summary.roll_dipotong_count} Roll kain belum habis</p>
          </div>
        </div>`;
    } else if (jenis === 'BAHAN_MASUK') {
      const totalRollInPen = sum(rep.penerimaanList, (p) => sum(Q.warnaOfPenerimaan(p.id_penerimaan), (w) => w.total_roll));
      const totalUkrInPen = sum(rep.penerimaanList, (p) => sum(Q.warnaOfPenerimaan(p.id_penerimaan), (w) => w.total_ukuran));
      kpiHtml = `
        <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL NOTA PENERIMAAN</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">${fmtNum(summary.total_penerimaan_nota)} Nota</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Surat jalan bahan baku supplier</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL ROLL DITERIMA</p>
            <p class="text-xl font-extrabold text-indigo-700 mt-1">${fmtNum(totalRollInPen)} Roll Fisik</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Terdaftar pada kelompok warna</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL KUANTITAS BAHAN MASUK</p>
            <p class="text-xl font-extrabold text-emerald-700 mt-1">${Number(totalUkrInPen).toFixed(2)} Kg / Yd / M</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Akumulasi bruto penerimaan kain</p>
          </div>
        </div>`;
    } else if (jenis === 'MUTASI_KELUAR') {
      const keluarCnt = rep.riwayatStokList.filter((r) => r.jenis_transaksi === 'KELUAR').length;
      const masukCnt = rep.riwayatStokList.filter((r) => r.jenis_transaksi === 'MASUK').length;
      kpiHtml = `
        <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL LOG TRANSAKSI STOK</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">${fmtNum(rep.riwayatStokList.length)} Transaksi</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${keluarCnt} Keluar &bull; ${masukCnt} Masuk</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PENGELUARAN (OUTBOUND)</p>
            <p class="text-xl font-extrabold text-rose-700 mt-1">-${fmtNum(summary.total_outbound_pcs)} Pcs</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Diserahkan ke Mitra / Distribusi</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL PEMASUKAN (INBOUND)</p>
            <p class="text-xl font-extrabold text-emerald-700 mt-1">+${fmtNum(summary.total_inbound_pcs)} Pcs</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Masuk dari QC Produk Abadi / Opname</p>
          </div>
        </div>`;
    } else if (jenis === 'QC') {
      kpiHtml = `
        <div class="kpi-summary-grid grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-4 print-avoid-break">
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL SAMPEL DIPERIKSA</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">${fmtNum(summary.total_sampel_diperiksa)} Pcs (${rep.qcList.length} Log)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Prototipe, Potong &amp; Jahit Akhir</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TINGKAT KELOLOSAN (PASS RATE)</p>
            <p class="text-xl font-extrabold text-emerald-700 mt-1">${summary.pass_rate_pct}% (${fmtNum(summary.total_lolos_qc)} Pcs)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Target standar mutu: &ge; 95.0%</p>
          </div>
          <div class="border border-slate-300 rounded-xl p-4 bg-white">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">CACAT MUTU &amp; ANTREAN REWORK</p>
            <p class="text-xl font-extrabold text-rose-700 mt-1">${fmtNum(summary.total_cacat_qc)} Pcs (${summary.defect_rate_pct}%)</p>
            <p class="text-[11px] text-slate-500 mt-0.5">${summary.total_rework_pending} batch status Need Rework</p>
          </div>
        </div>`;
    }

    // Formal 2-Column Table for KONSOLIDASI + Specific Tables
    let rekapanSheetHtml = '';
    if (jenis === 'KONSOLIDASI') {
      rekapanSheetHtml = `
        <div class="rekapan-sheet-block print-page-break print-avoid-break pt-2">
          <div class="text-center mb-3 font-serif">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900">KONVEKSI ${esc(company.nama_perusahaan.toUpperCase())}</h3>
            <p class="text-sm font-bold text-slate-900">Rekapitulasi Operasional Produksi &amp; Posisi Inventori CMT</p>
            <p class="text-xs font-bold text-slate-800">Per ${esc(meta.printed_date_long)}</p>
          </div>
          <table class="doc-table w-full border-collapse border border-slate-900 text-xs font-serif">
            <thead>
              <tr class="bg-slate-100">
                <th colspan="2" class="border border-slate-900 px-3 py-1.5 text-center font-bold uppercase tracking-wide text-slate-900 w-1/2">PRODUKSI &amp; BAHAN BAKU</th>
                <th colspan="2" class="border border-slate-900 px-3 py-1.5 text-center font-bold uppercase tracking-wide text-slate-900 w-1/2">MUTU (QC) &amp; INVENTORI GUDANG</th>
              </tr>
            </thead>
            <tbody>
              <tr class="bg-slate-50">
                <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">Status Pesanan Produksi (EDF)</td>
                <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">Hasil Quality Control (3 Tahap)</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800 w-[34%]">- Pesanan Selesai</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900 w-[16%]">${fmtNum(summary.pesanan_selesai)} Order</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800 w-[34%]">- Total Sampel Diperiksa</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900 w-[16%]">${fmtNum(summary.total_sampel_diperiksa)} Pcs</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Pesanan Dalam Proses (Antrean/Potong/QC)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.pesanan_proses)} Order</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Unit Lolos QC (${summary.pass_rate_pct}%)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_lolos_qc)} Pcs</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Batch Pemotongan Seri Terealisasi</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_batch_potong)} Batch</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Unit Cacat (${summary.defect_rate_pct}%)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_cacat_qc)} Pcs</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Akumulasi Lembar Hasil Potong</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_lembar_potong)} Lembar</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Batch Antrean Perbaikan (Need Rework)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_rework_pending)} Batch</td>
              </tr>
              <tr class="bg-slate-50/70 font-bold">
                <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Pesanan Terdaftar</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_pesanan)} Order</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Inspeksi QC (Lolos + Cacat)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_lolos_qc + summary.total_cacat_qc)} Pcs</td>
              </tr>
              <tr class="bg-slate-50">
                <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">Penerimaan &amp; Konsumsi Roll Kain Fisik</td>
                <td colspan="2" class="border border-slate-900 px-3 py-1 text-center font-bold text-slate-900">Inventori Barang Jadi &amp; Aksesoris CMT</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Nota Penerimaan Supplier</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_penerimaan_nota)} Nota</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Stok Barang Jadi (Produk Abadi)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_stok_jadi)} Pcs</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Utuh Tersedia (${summary.roll_tersedia_count} Roll)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${Number(summary.roll_tersedia_qty).toFixed(2)}</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Variasi SKU Barang Jadi Aktif</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_sku_stok_jadi)} SKU</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Sedang Dipotong (${summary.roll_dipotong_count} Roll)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${Number(summary.roll_dipotong_qty).toFixed(2)}</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Persediaan Aksesoris CMT (${summary.total_aksesoris_item} Jenis)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_aksesoris_unit)} Unit</td>
              </tr>
              <tr>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Roll Habis Terpakai</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.roll_habis_count)} Roll</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-800">- Barang Jadi Keluar / Outbound Mitra</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.total_outbound_pcs)} Pcs</td>
              </tr>
              <tr class="bg-slate-50/70 font-bold">
                <td class="border border-slate-900 px-3 py-1 text-slate-900">Total Sisa Kain Roll di Rak</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${Number(summary.total_ukuran_sisa_roll).toFixed(2)}</td>
                <td class="border border-slate-900 px-3 py-1 text-slate-900">Item Stok Rendah (&le;${sumStokMin} Pcs / &lt;${sumAccMin} Unit)</td>
                <td class="border border-slate-900 px-3 py-1 text-right font-mono text-slate-900">${fmtNum(summary.low_stock_jadi_count + summary.low_stock_aksesoris_count)} Item</td>
              </tr>
              <tr class="bg-slate-100 font-bold text-slate-900">
                <td class="border border-slate-900 px-3 py-1.5">Total Kain Terpakai Produksi (${summary.total_roll_dikonsumsi} Roll)</td>
                <td class="border border-slate-900 px-3 py-1.5 text-right font-mono">${Number(summary.total_kain_terpakai).toFixed(2)}</td>
                <td class="border border-slate-900 px-3 py-1.5">Total Stok Masuk Gudang (Inbound)</td>
                <td class="border border-slate-900 px-3 py-1.5 text-right font-mono">${fmtNum(summary.total_inbound_pcs)} Pcs</td>
              </tr>
            </tbody>
          </table>
        </div>`;
    }

    let detailTablesHtml = '';
    if (jenis === 'KONSOLIDASI') {
      const cOrders = rep.consolidatedOrders;
      detailTablesHtml = `
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-slate-900 text-sm">Rincian Konsolidasi Operasional Pesanan (End-to-End)</h3>
              <p class="text-[11px] text-slate-500">Pelacakan pesanan dari pola, pemakaian roll kain fisik, hasil potong, hingga kualitas QC akhir</p>
            </div>
            <span class="text-xs font-mono font-bold text-indigo-700">${cOrders.length} Baris</span>
          </div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300">
                  <th class="py-2.5 px-3 border-r border-slate-200">NO. ORDER</th>
                  <th class="py-2.5 px-3 border-r border-slate-200">TGL ORDER &amp; EDF</th>
                  <th class="py-2.5 px-3 border-r border-slate-200">POLA &amp; MITRA / SUMBER</th>
                  <th class="py-2.5 px-3 border-r border-slate-200">ROLL TERPAKAI</th>
                  <th class="py-2.5 px-3 border-r border-slate-200 text-right">YIELD POTONG</th>
                  <th class="py-2.5 px-3 border-r border-slate-200 text-right">HASIL QC</th>
                  <th class="py-2.5 px-3 text-center">STATUS</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                ${cOrders.length ? cOrders.map((r) => {
                  const stCls = r.status_pesanan === 'Selesai' ? 'border-emerald-300 text-emerald-800 bg-emerald-50' : (r.status_pesanan === 'Dalam QC' ? 'border-amber-300 text-amber-800 bg-amber-50' : 'border-slate-300 text-slate-700 bg-slate-50');
                  return `
                    <tr>
                      <td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">#ORD-${String(r.id_pesanan).padStart(4, '0')}</td>
                      <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200">
                        <div>${fmtDateShortId(r.tgl_order)}</div>
                        <div class="text-amber-700 font-semibold">EDF: ${fmtDateShortId(r.deadline_terbaru)}</div>
                      </td>
                      <td class="py-2.5 px-3 border-r border-slate-200">
                        <div class="font-bold text-slate-900">${esc(r.nama_pola)} <span class="font-mono text-[10px] text-slate-500">(${esc(r.kode_pola)})</span></div>
                        <div class="text-[11px] text-slate-600">${esc(r.nama_sumber)} &bull; ${esc(r.nama_mitra)}</div>
                      </td>
                      <td class="py-2.5 px-3 font-mono text-[11px] border-r border-slate-200">
                        <div class="font-semibold text-slate-800">${esc(r.rolls_used)}</div>
                        ${r.kain_terpakai > 0 ? `<div class="text-[10px] text-slate-500">${Number(r.kain_terpakai).toFixed(2)} Kg/Yd/M (${r.rolls_count} roll)</div>` : ''}
                      </td>
                      <td class="py-2.5 px-3 font-mono font-bold text-slate-900 text-right border-r border-slate-200">${fmtNum(r.actual_yield)} lbr</td>
                      <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200"><span class="text-emerald-700 font-bold">${r.qc_lolos}</span> / <span class="text-rose-700 font-bold">${r.qc_cacat}</span></td>
                      <td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold border ${stCls}">${esc(r.status_pesanan)}</span></td>
                    </tr>`;
                }).join('') : '<tr><td colspan="7" class="py-6 text-center text-slate-400">Tidak ada data pesanan yang sesuai filter.</td></tr>'}
              </tbody>
              <tfoot>
                <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                  <td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL KONSOLIDASI (${cOrders.length} PESANAN)</td>
                  <td class="py-2.5 px-3 font-mono border-r border-slate-200">${Number(sum(cOrders, (r) => r.kain_terpakai)).toFixed(2)} Kg/Yd/M</td>
                  <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${fmtNum(sum(cOrders, (r) => r.actual_yield))} lbr</td>
                  <td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${sum(cOrders, (r) => r.qc_lolos)} / ${sum(cOrders, (r) => r.qc_cacat)}</td>
                  <td class="py-2.5 px-3 text-center">${summary.pesanan_selesai} Selesai</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>`;
    } else if (jenis === 'STOK') {
      const sList = rep.stokBarangJadiList;
      const aList = rep.aksesorisList;
      const rList = rep.rollList;
      detailTablesHtml = `
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">A. Laporan Posisi Stok Gudang Barang Jadi (Produk Abadi)</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">KODE</th><th class="py-2.5 px-3 border-r border-slate-200">NAMA PRODUK / POLA</th><th class="py-2.5 px-3 border-r border-slate-200">KATEGORI</th><th class="py-2.5 px-3 border-r border-slate-200">UKURAN &amp; WARNA</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">STOK AKTUAL</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">BATAS MIN.</th><th class="py-2.5 px-3 text-center">STATUS</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${sList.length ? sList.map((st) => {
                  const pl = Q.pola(st.id_pola);
                  const kat = pl ? Q.kategori(pl.id_kategori) : null;
                  const sp = Q.spec(st.id_spesifikasi);
                  const qty = Number(st.jumlah_stok_pcs);
                  const habis = qty <= 0;
                  const min = !habis && qty <= sumStokMin;
                  const stCls = habis ? 'border-rose-400 bg-rose-50 text-rose-800' : (min ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700');
                  return `<tr><td class="py-2.5 px-3 font-mono font-bold text-slate-700 border-r border-slate-200">${esc(pl?.kode_pola || `SKU-${st.id_stok}`)}</td><td class="py-2.5 px-3 font-bold text-slate-900 border-r border-slate-200">${esc(pl?.nama_pola || '-')}</td><td class="py-2.5 px-3 border-r border-slate-200"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border border-indigo-200 bg-indigo-50 text-indigo-800">${esc(kat?.nama_kategori || 'Umum')}</span></td><td class="py-2.5 px-3 border-r border-slate-200">${esc(sp?.nama_ukuran || '-')} &bull; ${esc(st.nama_warna)}</td><td class="py-2.5 px-3 font-mono font-bold text-right border-r border-slate-200 ${habis || min ? 'text-rose-700' : 'text-slate-900'}">${fmtNum(qty)} pcs</td><td class="py-2.5 px-3 font-mono text-right text-slate-600 border-r border-slate-200">${fmtNum(sumStokMin)} pcs</td><td class="py-2.5 px-3 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${stCls}">${habis ? 'Habis' : (min ? 'Stok Minimum' : 'Normal')}</span></td></tr>`;
                }).join('') : '<tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada stok barang jadi di gudang.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL STOK BARANG JADI (${sList.length} SKU)</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${fmtNum(sum(sList, (s) => s.jumlah_stok_pcs))} pcs</td><td colspan="2" class="py-2.5 px-3 text-center text-rose-700">${summary.low_stock_jadi_count} SKU Minimum/Habis</td></tr></tfoot>
            </table>
          </div>
        </div>
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">B. Laporan Posisi Inventori Aksesoris CMT</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">KODE</th><th class="py-2.5 px-3 border-r border-slate-200">NAMA AKSESORIS &amp; UKURAN</th><th class="py-2.5 px-3 border-r border-slate-200">SUPPLIER REKANAN</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">STOK AKTUAL</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">BATAS MIN.</th><th class="py-2.5 px-3 text-center">STATUS</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${aList.length ? aList.map((acc) => {
                  const qty = Number(acc.stok);
                  const low = qty < sumAccMin;
                  return `<tr><td class="py-2.5 px-3 font-mono font-bold text-slate-700 border-r border-slate-200">ACC-${String(acc.id_aksesoris).padStart(3, '0')}</td><td class="py-2.5 px-3 border-r border-slate-200"><span class="font-bold text-slate-900">${esc(acc.nama_aksesoris)}</span>${acc.ukuran ? ` <span class="text-slate-500">(${esc(acc.ukuran)})</span>` : ''}</td><td class="py-2.5 px-3 border-r border-slate-200 text-slate-700">${esc(Q.supplier(acc.id_supplier)?.nama_supplier || '-')}</td><td class="py-2.5 px-3 font-mono font-bold text-right border-r border-slate-200 ${low ? 'text-rose-700' : 'text-slate-900'}">${fmtNum(qty)} ${esc(acc.satuan)}</td><td class="py-2.5 px-3 font-mono text-right text-slate-600 border-r border-slate-200">${fmtNum(sumAccMin)} ${esc(acc.satuan)}</td><td class="py-2.5 px-3 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${low ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-emerald-300 bg-emerald-50 text-emerald-700'}">${low ? 'Stok Minimum' : 'Normal'}</span></td></tr>`;
                }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada data aksesoris CMT.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL PERSEDIAAN AKSESORIS (${aList.length} ITEM)</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${fmtNum(sum(aList, (a) => a.stok))} Unit</td><td colspan="2" class="py-2.5 px-3 text-center text-rose-700">${summary.low_stock_aksesoris_count} Item Minimum</td></tr></tfoot>
            </table>
          </div>
        </div>
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">C. Laporan Posisi Sisa Roll Bahan Baku Kain (${rList.length} Roll)</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">ID ROLL</th><th class="py-2.5 px-3 border-r border-slate-200">NOTA &amp; SUPPLIER</th><th class="py-2.5 px-3 border-r border-slate-200">KAIN &amp; WARNA</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">UKURAN AWAL</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">SISA AKTUAL</th><th class="py-2.5 px-3 text-center">STATUS ROLL</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${rList.length ? rList.map((rl) => {
                  const w = Q.warna(rl.id_detail_warna);
                  const p = w ? Q.penerimaan(w.id_penerimaan) : null;
                  const s = p ? Q.supplier(p.id_supplier) : null;
                  const rlCls = rl.status_roll === 'Tersedia' ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : (rl.status_roll === 'Dipotong' ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-slate-300 bg-slate-100 text-slate-600');
                  return `<tr><td class="py-2 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">#${rl.id_roll} (Roll ${rl.no_roll_ke})</td><td class="py-2 px-3 border-r border-slate-200"><span class="font-mono font-semibold">${esc(p?.no_nota || '-')}</span><span class="text-slate-500"> &bull; ${esc(s?.nama_supplier || '-')}</span></td><td class="py-2 px-3 border-r border-slate-200">${esc(p?.nama_kain || '-')} (${esc(w?.nama_warna || '-')})</td><td class="py-2 px-3 font-mono text-right border-r border-slate-200">${Number(rl.ukuran_awal).toFixed(2)} ${esc(rl.satuan)}</td><td class="py-2 px-3 font-mono font-bold text-right border-r border-slate-200">${Number(rl.ukuran_sisa).toFixed(2)} ${esc(rl.satuan)}</td><td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold border ${rlCls}">${esc(rl.status_roll)}</span></td></tr>`;
                }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada gulungan roll kain terdaftar.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="3" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL GULUNGAN KAIN (${rList.length} ROLL)</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${Number(sum(rList, (r) => r.ukuran_awal)).toFixed(2)}</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${Number(sum(rList, (r) => r.ukuran_sisa)).toFixed(2)}</td><td class="py-2.5 px-3 text-center">${summary.roll_tersedia_count} Tersedia</td></tr></tfoot>
            </table>
          </div>
        </div>`;
    } else if (jenis === 'BAHAN_MASUK') {
      const pList = rep.penerimaanList;
      const totRoll = sum(pList, (pn) => sum(Q.warnaOfPenerimaan(pn.id_penerimaan), (w) => w.total_roll));
      const totUkr = sum(pList, (pn) => sum(Q.warnaOfPenerimaan(pn.id_penerimaan), (w) => w.total_ukuran));
      detailTablesHtml = `
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">Laporan Penerimaan Bahan Baku &amp; Registrasi Roll Supplier</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">NO. DOKUMEN / NOTA</th><th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th><th class="py-2.5 px-3 border-r border-slate-200">SUPPLIER / PEMASOK &amp; KAIN</th><th class="py-2.5 px-3 border-r border-slate-200">PENERIMA / KENDARAAN</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">JML ROLL</th><th class="py-2.5 px-3 text-right">TOTAL KUANTITAS</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${pList.length ? pList.map((pn) => {
                  const warnas = Q.warnaOfPenerimaan(pn.id_penerimaan);
                  const wSum = warnas.map((w) => `${w.nama_warna} (${w.total_roll} roll, ${Number(w.total_ukuran).toFixed(2)} ${w.satuan_ukur})`).join(', ');
                  const nRolls = sum(warnas, (w) => w.total_roll);
                  const nUkr = sum(warnas, (w) => w.total_ukuran);
                  const nSat = warnas[0]?.satuan_ukur || 'Kg';
                  return `<tr><td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">${esc(pn.no_nota)}</td><td class="py-2.5 px-3 font-mono border-r border-slate-200">${fmtDateShortId(pn.tgl_nota)}</td><td class="py-2.5 px-3 border-r border-slate-200"><div class="font-bold text-slate-900">${esc(Q.supplier(pn.id_supplier)?.nama_supplier || '-')}</div><div class="text-[11px] text-slate-700 font-medium">${esc(pn.nama_kain)}${pn.lebar_gramasi ? ` (${esc(pn.lebar_gramasi)})` : ''}</div>${wSum ? `<div class="text-[10px] text-slate-500 mt-0.5">${esc(wSum)}</div>` : ''}</td><td class="py-2.5 px-3 border-r border-slate-200 text-slate-600"><div>${esc(pn.penerima || '-')}</div>${pn.no_kendaraan ? `<div class="font-mono text-[10px]">${esc(pn.no_kendaraan)}</div>` : ''}</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${fmtNum(nRolls)} roll</td><td class="py-2.5 px-3 font-mono font-bold text-right text-slate-900">${Number(nUkr).toFixed(2)} ${esc(nSat)}</td></tr>`;
                }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Tidak ada nota penerimaan bahan baku pada periode ini.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL PENERIMAAN BAHAN BAKU (${pList.length} NOTA)</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${fmtNum(totRoll)} roll</td><td class="py-2.5 px-3 font-mono text-right">${Number(totUkr).toFixed(2)} Kg/Yd/M</td></tr></tfoot>
            </table>
          </div>
        </div>`;
    } else if (jenis === 'MUTASI_KELUAR') {
      const mList = rep.riwayatStokList;
      detailTablesHtml = `
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">Laporan Pengeluaran Barang Jadi (Outbound Mitra) &amp; Riwayat Mutasi Stok</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">NO. DOKUMEN</th><th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th><th class="py-2.5 px-3 border-r border-slate-200">MITRA / ALASAN &amp; PRODUK</th><th class="py-2.5 px-3 border-r border-slate-200">PETUGAS</th><th class="py-2.5 px-3 border-r border-slate-200 text-center">SALDO</th><th class="py-2.5 px-3 text-right">QTY MUTASI</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${mList.length ? mList.map((rw) => {
                  const isKeluar = rw.jenis_transaksi === 'KELUAR';
                  const pl = Q.pola(rw.id_pola);
                  const sp = Q.spec(rw.id_spesifikasi);
                  const usr = Q.user(rw.id_user);
                  return `<tr><td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">${isKeluar ? 'OUT' : 'INB'}-${String(rw.id_riwayat_stok).padStart(4, '0')}</td><td class="py-2.5 px-3 font-mono border-r border-slate-200">${fmtDateShortId(rw.tgl_transaksi)}</td><td class="py-2.5 px-3 border-r border-slate-200"><div class="font-bold text-slate-900">[${esc(rw.jenis_transaksi)}] ${rw.nama_mitra ? `${esc(rw.nama_mitra)} — ` : ''}${esc(rw.kategori_alasan)}</div><div class="text-[11px] text-slate-600">${esc(pl?.nama_pola || '-')} (${esc(sp?.nama_ukuran || '-')} / ${esc(rw.nama_warna)})${rw.keterangan ? ` &bull; ${esc(rw.keterangan)}` : ''}</div></td><td class="py-2.5 px-3 text-slate-600 border-r border-slate-200">${esc(usr?.username || '-')}</td><td class="py-2.5 px-3 font-mono text-center text-slate-600 border-r border-slate-200">${rw.stok_sebelum} &rarr; ${rw.stok_sesudah}</td><td class="py-2.5 px-3 font-mono font-bold text-right ${isKeluar ? 'text-rose-700' : 'text-emerald-700'}">${isKeluar ? '-' : '+'}${fmtNum(rw.jumlah_pcs)} pcs</td></tr>`;
                }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada catatan mutasi stok barang jadi pada periode ini.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL MUTASI STOK (${mList.length} TRANSAKSI)</td><td class="py-2.5 px-3 font-mono text-center border-r border-slate-200 text-emerald-700">+${fmtNum(summary.total_inbound_pcs)} In</td><td class="py-2.5 px-3 font-mono text-right text-rose-700">-${fmtNum(summary.total_outbound_pcs)} Out</td></tr></tfoot>
            </table>
          </div>
        </div>`;
    } else if (jenis === 'QC') {
      const df = rep.defectBreakdown;
      const stg = rep.stageBreakdown;
      const qList = rep.qcList;
      detailTablesHtml = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
          <div class="border border-slate-300 rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-xs uppercase">A1. Sebaran Kategori Kerusakan</h3></div>
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2 px-3 border-r border-slate-200">KATEGORI KERUSAKAN</th><th class="py-2 px-3 text-right">TOTAL CACAT</th></tr></thead>
              <tbody class="divide-y divide-slate-200">${df.map((d) => `<tr><td class="py-2 px-3 border-r border-slate-200 text-slate-800 font-medium">${esc(d.label_lengkap)}</td><td class="py-2 px-3 font-mono font-bold text-right text-rose-700">${fmtNum(d.total_cacat)} pcs</td></tr>`).join('')}</tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td class="py-2 px-3 border-r border-slate-200">TOTAL CACAT TERCATAT</td><td class="py-2 px-3 font-mono text-right text-rose-700">${fmtNum(sum(df, (x) => x.total_cacat))} pcs</td></tr></tfoot>
            </table>
          </div>
          <div class="border border-slate-300 rounded-xl overflow-hidden">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-xs uppercase">A2. Rekapitulasi per Tahapan QC</h3></div>
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2 px-3 border-r border-slate-200">TAHAP QC</th><th class="py-2 px-3 border-r border-slate-200 text-right">DIPERIKSA</th><th class="py-2 px-3 border-r border-slate-200 text-right">LOLOS</th><th class="py-2 px-3 text-right">CACAT</th></tr></thead>
              <tbody class="divide-y divide-slate-200">${stg.length ? stg.map((s) => `<tr><td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-800">${esc(s.tahap_qc)}</td><td class="py-2 px-3 font-mono text-right border-r border-slate-200">${fmtNum(s.total_diperiksa)} pcs</td><td class="py-2 px-3 font-mono font-bold text-right text-emerald-700 border-r border-slate-200">${fmtNum(s.total_lolos)} pcs</td><td class="py-2 px-3 font-mono font-bold text-right text-rose-700">${fmtNum(s.total_cacat)} pcs</td></tr>`).join('') : '<tr><td colspan="4" class="py-4 text-center text-slate-400">Belum ada data tahapan QC.</td></tr>'}</tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td class="py-2 px-3 border-r border-slate-200">TOTAL</td><td class="py-2 px-3 font-mono text-right border-r border-slate-200">${fmtNum(sum(stg, (x) => x.total_diperiksa))} pcs</td><td class="py-2 px-3 font-mono text-right text-emerald-700 border-r border-slate-200">${fmtNum(sum(stg, (x) => x.total_lolos))} pcs</td><td class="py-2 px-3 font-mono text-right text-rose-700">${fmtNum(sum(stg, (x) => x.total_cacat))} pcs</td></tr></tfoot>
            </table>
          </div>
        </div>
        <div class="border border-slate-300 rounded-xl overflow-hidden">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-300"><h3 class="font-bold text-slate-900 text-sm">B. Catatan Detail Log Inspeksi Quality Control &amp; Rework</h3></div>
          <div class="overflow-x-auto">
            <table class="doc-table w-full text-left text-xs border-collapse">
              <thead><tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300"><th class="py-2.5 px-3 border-r border-slate-200">NO. QC</th><th class="py-2.5 px-3 border-r border-slate-200">TANGGAL</th><th class="py-2.5 px-3 border-r border-slate-200">PESANAN, TAHAP &amp; CACAT</th><th class="py-2.5 px-3 border-r border-slate-200">PETUGAS</th><th class="py-2.5 px-3 border-r border-slate-200 text-right">DIPERIKSA / LOLOS / CACAT</th><th class="py-2.5 px-3 text-center">STATUS</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                ${qList.length ? qList.map((q) => {
                  const ord = Q.pesanan(q.id_pesanan);
                  const pl = ord ? Q.pola(ord.id_pola) : null;
                  const usr = Q.user(q.id_user_qc);
                  const passCls = (q.status_qc === 'Lolos QC' || q.status_qc === 'Rework Selesai') ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-rose-300 bg-rose-50 text-rose-700';
                  return `<tr><td class="py-2.5 px-3 font-mono font-bold text-indigo-700 border-r border-slate-200">QC-${String(q.id_qc).padStart(4, '0')}</td><td class="py-2.5 px-3 font-mono border-r border-slate-200">${fmtDateShortId(q.tgl_pemeriksaan)}</td><td class="py-2.5 px-3 border-r border-slate-200"><div class="font-bold text-slate-900">[${esc(q.tahap_qc)}] ${esc(pl?.nama_pola || ord?.jenis_pesanan || '-')}</div><div class="text-[11px] text-slate-600">Cacat: ${esc(q.jenis_cacat)}${q.catatan_qc ? ` &bull; ${esc(q.catatan_qc)}` : ''}</div></td><td class="py-2.5 px-3 border-r border-slate-200 text-slate-600">${esc(usr?.username || '-')}</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${q.jumlah_diperiksa} / <span class="text-emerald-700 font-bold">${q.jumlah_lolos}</span> / <span class="text-rose-700 font-bold">${q.jumlah_cacat}</span></td><td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold border ${passCls}">${esc(q.status_qc)}</span></td></tr>`;
                }).join('') : '<tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada log inspeksi QC pada periode ini.</td></tr>'}
              </tbody>
              <tfoot><tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400"><td colspan="4" class="py-2.5 px-3 border-r border-slate-200 uppercase">TOTAL INSPEKSI (${qList.length} LOG)</td><td class="py-2.5 px-3 font-mono text-right border-r border-slate-200">${sum(qList, (q) => q.jumlah_diperiksa)} / ${sum(qList, (q) => q.jumlah_lolos)} / ${sum(qList, (q) => q.jumlah_cacat)}</td><td class="py-2.5 px-3 text-center">${summary.pass_rate_pct}% Lolos</td></tr></tfoot>
            </table>
          </div>
        </div>`;
    }

    $('#laporan-dokumen-resmi').innerHTML = `
      <!-- KOP SURAT RESMI PERUSAHAAN -->
      <div class="border border-slate-300 rounded-xl p-5 bg-white print-avoid-break">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-100 shrink-0" style="background-color:#4f46e5; color:#ffffff;">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>
            </svg>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-2.5">
              <h1 class="font-serif text-xl md:text-2xl font-extrabold text-indigo-950 tracking-wide uppercase">${esc(company.nama_perusahaan)}</h1>
              <span class="text-[11px] font-bold text-rose-700 uppercase tracking-widest">${esc(company.tagline)}</span>
            </div>
            <p class="text-xs text-slate-600 mt-0.5">${esc(company.alamat)} | Telp: ${esc(company.telepon)}</p>
            <p class="text-[11px] text-slate-500">${esc(company.deskripsi_sistem)}</p>
          </div>
        </div>
        <div class="mt-4 bg-slate-50/70 border border-slate-200 rounded-lg px-4 py-3 text-xs space-y-1">
          <div><span class="font-semibold text-slate-700">Judul Laporan:</span> <strong class="text-slate-900">${esc(activeReportTitle)}</strong></div>
          <div><span class="font-semibold text-slate-700">Periode:</span> <span class="font-semibold text-indigo-700">${esc(periodeText)}</span></div>
          <div><span class="font-semibold text-slate-700">Diotorisasi / Dicetak Oleh:</span> <strong class="text-slate-900">${esc(meta.printer_username)} (${esc(meta.printer_name)} / ${esc(meta.printer_role)})</strong></div>
          <div><span class="font-semibold text-slate-700">Waktu Cetak:</span> <span class="font-mono text-slate-800">${esc(meta.printed_at_formatted)}</span></div>
        </div>
        <div class="border-t-2 border-slate-900 mt-4"></div>
      </div>

      ${kpiHtml}
      ${rekapanSheetHtml}

      <div class="print-page-break space-y-6">
        ${detailTablesHtml}
        <div class="signature-block pt-4 flex justify-end">
          <div class="w-72 text-center text-xs space-y-1">
            <p class="text-slate-700">${esc(company.kota_pengesahan || 'Jakarta')}, ${esc(meta.printed_date_short)}</p>
            <p class="font-bold text-slate-900">Diotorisasi Oleh &mdash; ${esc(meta.printer_role_label)}</p>
            <div class="h-14"></div>
            <p class="font-bold text-slate-900 uppercase underline underline-offset-4 tracking-wide">${esc(meta.printer_username)}</p>
            <p class="text-[11px] text-slate-500">${esc(meta.printer_name)} &bull; ${esc(meta.printer_role_label)}</p>
          </div>
        </div>
      </div>`;
  }

  function buildExportCsvRows(rep) {
    const jenis = rep.filters.jenis_laporan;
    const sumStokMin = rep.thresholds.min_stok_jadi;
    const sumAccMin = rep.thresholds.min_stok_aksesoris;
    if (jenis === 'KONSOLIDASI') {
      return rep.consolidatedOrders.map((r) => ({
        'No Order': `#ORD-${String(r.id_pesanan).padStart(4, '0')}`,
        'Kode Pola': r.kode_pola,
        'Nama Pola': r.nama_pola,
        'Sumber Inisiasi': r.nama_sumber,
        Mitra: r.nama_mitra,
        'Tgl Order': fmtDateDmy(r.tgl_order),
        'Deadline EDF': fmtDateDmy(r.deadline_terbaru),
        'Roll Terpakai': r.rolls_used,
        'Kain Terpakai': Number(r.kain_terpakai).toFixed(2),
        'Actual Yield (Lembar)': r.actual_yield,
        'QC Lolos (Pcs)': r.qc_lolos,
        'QC Cacat (Pcs)': r.qc_cacat,
        'Status Pesanan': r.status_pesanan,
      }));
    }
    if (jenis === 'STOK') {
      const rows = [];
      rep.stokBarangJadiList.forEach((st) => {
        const pl = Q.pola(st.id_pola);
        const kat = pl ? Q.kategori(pl.id_kategori) : null;
        const sp = Q.spec(st.id_spesifikasi);
        const qty = Number(st.jumlah_stok_pcs);
        rows.push({
          Tipe: 'Barang Jadi',
          Kode: pl?.kode_pola || `SKU-${st.id_stok}`,
          'Nama Item': `${pl?.nama_pola || '-'} (${sp?.nama_ukuran || '-'} / ${st.nama_warna})`,
          'Kategori / Supplier': kat?.nama_kategori || 'Umum',
          'Stok Aktual': `${qty} Pcs`,
          'Batas Min.': `${sumStokMin} Pcs`,
          Status: qty <= 0 ? 'Habis' : (qty <= sumStokMin ? 'Stok Minimum' : 'Normal'),
        });
      });
      rep.aksesorisList.forEach((acc) => {
        const qty = Number(acc.stok);
        rows.push({
          Tipe: 'Aksesoris CMT',
          Kode: `ACC-${String(acc.id_aksesoris).padStart(3, '0')}`,
          'Nama Item': acc.nama_aksesoris + (acc.ukuran ? ` (${acc.ukuran})` : ''),
          'Kategori / Supplier': Q.supplier(acc.id_supplier)?.nama_supplier || '-',
          'Stok Aktual': `${qty} ${acc.satuan}`,
          'Batas Min.': `${sumAccMin} ${acc.satuan}`,
          Status: qty < sumAccMin ? 'Stok Minimum' : 'Normal',
        });
      });
      return rows;
    }
    if (jenis === 'BAHAN_MASUK') {
      return rep.penerimaanList.map((pn) => {
        const warnas = Q.warnaOfPenerimaan(pn.id_penerimaan);
        return {
          'No Nota': pn.no_nota,
          Tanggal: fmtDateDmy(pn.tgl_nota),
          Supplier: Q.supplier(pn.id_supplier)?.nama_supplier || '-',
          'Nama Kain': pn.nama_kain,
          Penerima: pn.penerima || '-',
          'Jml Roll': sum(warnas, (w) => w.total_roll),
          'Total Ukuran': Number(sum(warnas, (w) => w.total_ukuran)).toFixed(2),
        };
      });
    }
    if (jenis === 'MUTASI_KELUAR') {
      return rep.riwayatStokList.map((rw) => ({
        'No Dokumen': (rw.jenis_transaksi === 'KELUAR' ? 'OUT-' : 'INB-') + String(rw.id_riwayat_stok).padStart(4, '0'),
        Tanggal: fmtDateTimeDmy(rw.tgl_transaksi),
        Jenis: rw.jenis_transaksi,
        'Pola & Ukuran & Warna': `${Q.pola(rw.id_pola)?.nama_pola || '-'} (${Q.spec(rw.id_spesifikasi)?.nama_ukuran || '-'} / ${rw.nama_warna})`,
        'Mitra / Alasan': (rw.nama_mitra ? `${rw.nama_mitra} - ` : '') + rw.kategori_alasan,
        Petugas: Q.user(rw.id_user)?.username || '-',
        'Saldo Sebelum': rw.stok_sebelum,
        'Saldo Sesudah': rw.stok_sesudah,
        'Qty (Pcs)': (rw.jenis_transaksi === 'KELUAR' ? '-' : '+') + rw.jumlah_pcs,
      }));
    }
    return rep.qcList.map((q) => {
      const ord = Q.pesanan(q.id_pesanan);
      const pl = ord ? Q.pola(ord.id_pola) : null;
      return {
        'No QC': `QC-${String(q.id_qc).padStart(4, '0')}`,
        Tanggal: fmtDateTimeDmy(q.tgl_pemeriksaan),
        'Pola / Pesanan': pl?.nama_pola || ord?.jenis_pesanan || '-',
        'Tahap QC': q.tahap_qc,
        'Petugas QC': Q.user(q.id_user_qc)?.username || '-',
        Diperiksa: q.jumlah_diperiksa,
        Lolos: q.jumlah_lolos,
        Cacat: q.jumlah_cacat,
        'Jenis Cacat': q.jenis_cacat,
        'Status QC': q.status_qc,
      };
    });
  }

  function exportLaporanExcel() {
    const rep = state.lastReportData || Svc.buildReportData(state.laporanFilters);
    const rows = buildExportCsvRows(rep);
    if (!rows || rows.length === 0) {
      showToast('Tidak ada data pada jenis laporan ini untuk diekspor ke CSV.', 'warning');
      return;
    }
    const headers = Object.keys(rows[0]);
    const csvLines = [
      headers.join(','),
      ...rows.map((row) => headers.map((h) => `"${String(row[h] ?? '').replace(/"/g, '""')}"`).join(',')),
    ];
    const blob = new Blob(['\uFEFF' + csvLines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Rekapan_Bong_Garment_${rep.filters.jenis_laporan}_${todayIso()}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
    showToast('File CSV berhasil diunduh.', 'success');
  }

  function exportLaporanWord() {
    const docRoot = $('#laporan-dokumen-resmi');
    const rep = state.lastReportData;
    if (!docRoot || !rep) return;

    const company = rep.companyProfile;
    const meta = rep.meta;
    let kpiTableHtml = '';
    const kpiGrid = docRoot.querySelector('.kpi-summary-grid');
    if (kpiGrid) {
      const kpiCells = Array.from(kpiGrid.children).map((card) => {
        const ps = Array.from(card.querySelectorAll('p')).map((p) => p.textContent.trim());
        return `<td style="width:33.33%; border:1px solid #cbd5e1; padding:7pt 9pt; vertical-align:top;">
          <div style="font-size:8pt; font-weight:bold; text-transform:uppercase; color:#475569;">${esc(ps[0] || '')}</div>
          <div style="font-size:13pt; font-weight:bold; color:#0f172a; margin:3pt 0;">${esc(ps[1] || '')}</div>
          <div style="font-size:8.5pt; color:#475569;">${esc(ps[2] || '')}</div>
        </td>`;
      }).join('');
      kpiTableHtml = `<table style="width:100%; border-collapse:separate; border-spacing:6pt 0; margin-bottom:12pt;"><tr>${kpiCells}</tr></table>`;
    }

    const tablesHtml = Array.from(docRoot.querySelectorAll('table.doc-table')).map((tbl) => {
      let headingHtml = '';
      const centerWrap = tbl.closest('.rekapan-sheet-block');
      const cardWrap = tbl.closest('.border.border-slate-300');
      if (centerWrap) {
        const lines = Array.from(centerWrap.querySelectorAll('h3, p')).map((el) => esc(el.textContent.trim()));
        headingHtml = `<div style="text-align:center; font-weight:bold; font-size:10.5pt; margin:10pt 0 6pt 0;">${lines.join('<br/>')}</div>`;
      } else if (cardWrap) {
        const h3 = cardWrap.querySelector('h3');
        if (h3) headingHtml = `<div style="font-weight:bold; font-size:10.5pt; color:#0f172a; margin:12pt 0 5pt 0;">${esc(h3.textContent.trim())}</div>`;
      }
      const clone = tbl.cloneNode(true);
      clone.removeAttribute('class');
      clone.setAttribute('style', 'width:100%; border-collapse:collapse; margin-bottom:10pt; font-family:"Times New Roman", serif; font-size:9pt;');
      clone.querySelectorAll('th, td').forEach((cell) => {
        const align = cell.classList.contains('text-right') ? 'text-align:right;' : (cell.classList.contains('text-center') ? 'text-align:center;' : 'text-align:left;');
        const isBold = cell.tagName === 'TH' || cell.classList.contains('font-bold') || cell.closest('tr')?.classList.contains('font-bold');
        const bg = cell.tagName === 'TH' || cell.closest('tr')?.classList.contains('bg-slate-100') ? 'background-color:#f1f5f9;' : '';
        cell.removeAttribute('class');
        cell.setAttribute('style', `border:1px solid #0f172a; padding:4pt 6pt; vertical-align:top; ${align} ${isBold ? 'font-weight:bold;' : ''} ${bg}`);
      });
      return headingHtml + clone.outerHTML;
    }).join('\n');

    const htmlDoc = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
  <meta charset="utf-8" />
  <title>${esc(rep.activeReportTitle)}</title>
  <style>
    @page { size: A4 landscape; margin: 1.2cm; }
    body { font-family: "Times New Roman", Times, serif; color: #0f172a; font-size: 10pt; }
    .kop-title { font-size: 15pt; font-weight: bold; color: #1e1b4b; margin: 0; }
    .kop-sub { font-size: 9.5pt; color: #475569; margin: 2pt 0 0 0; }
    .meta-box { border: 1px solid #cbd5e1; background: #f8fafc; padding: 8pt 10pt; margin: 10pt 0; font-size: 9.5pt; }
    .divider { border-top: 2px solid #0f172a; margin: 8pt 0 12pt 0; }
    .sig-wrap { width: 100%; border-collapse: collapse; margin-top: 18pt; }
    .sig-wrap td { border: none; vertical-align: top; }
  </style>
</head>
<body>
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:48pt; height:48pt; vertical-align:middle; text-align:center; background:#4f46e5; color:#ffffff; padding:6pt;"><div style="font-size:20pt; line-height:1; color:#ffffff;">&#9986;</div></td>
      <td style="padding-left:10pt; vertical-align:middle;">
        <p class="kop-title">${esc(company.nama_perusahaan)} <span style="font-size:9pt; color:#be123c;">${esc(company.tagline)}</span></p>
        <p class="kop-sub">${esc(company.alamat)} | Telp: ${esc(company.telepon)}</p>
        <p class="kop-sub">${esc(company.deskripsi_sistem)}</p>
      </td>
    </tr>
  </table>
  <div class="meta-box">
    <div><b>Judul Laporan:</b> ${esc(rep.activeReportTitle)}</div>
    <div><b>Periode:</b> ${esc(rep.periodeText)}</div>
    <div><b>Diotorisasi / Dicetak Oleh:</b> ${esc(meta.printer_username)} (${esc(meta.printer_name)} / ${esc(meta.printer_role)})</div>
    <div><b>Waktu Cetak:</b> ${esc(meta.printed_at_formatted)}</div>
  </div>
  <div class="divider"></div>
  ${kpiTableHtml}
  ${tablesHtml}
  <table class="sig-wrap">
    <tr>
      <td style="width:58%;"></td>
      <td style="width:42%; text-align:center; font-size:9.5pt;">
        <div>${esc(company.kota_pengesahan || 'Jakarta')}, ${esc(meta.printed_date_short)}</div>
        <div><b>Diotorisasi Oleh &mdash; ${esc(meta.printer_role_label)}</b></div>
        <br/><br/><br/>
        <div><b><u>${esc(meta.printer_username.toUpperCase())}</u></b></div>
        <div style="font-size:8.5pt; color:#475569;">${esc(meta.printer_name)} &bull; ${esc(meta.printer_role_label)}</div>
      </td>
    </tr>
  </table>
</body>
</html>`;

    const blob = new Blob(['\ufeff', htmlDoc], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Rekapan_Bong_Garment_${rep.filters.jenis_laporan}_${todayIso()}.doc`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
    showToast('Dokumen Word (.doc) berhasil diunduh.', 'success');
  }

  /* --------------------------------------------------------------------------
   * SCREEN 8: PENGATURAN & MANAJEMEN PENGGUNA
   * ------------------------------------------------------------------------ */
  function renderPengaturan() {
    const users = DB.all('User');
    const activeUserId = Auth.getUser()?.id_user;
    $('#pengaturan-user-count').textContent = `${users.length} Akun Terdaftar`;

    const roleAccessMap = {
      Admin: 'Akses Penuh Seluruh Modul & Pengaturan',
      Pemilik: 'Mode Audit (Read-Only): Dashboard, Gudang & Laporan Eksekutif',
      Pemotong: 'Dashboard, Antrean EDF & Pemotongan Seri',
      QC: 'Dashboard & Quality Control (Prototipe, Potong, Jahit Akhir)',
      Gudang: 'Dashboard, Penerimaan Bahan, Stok Barang Jadi & Aksesoris',
    };

    $('#pengaturan-users-tbody').innerHTML = users.map((u) => {
      const isMe = Number(u.id_user) === Number(activeUserId);
      const roleCls = u.role === 'Admin' ? 'bg-indigo-100 text-indigo-800' : (u.role === 'Pemilik' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700');
      return `
        <tr class="hover:bg-slate-50">
          <td class="py-3 font-mono font-semibold text-indigo-600">#${u.id_user}</td>
          <td class="py-3 font-semibold text-slate-800">${esc(u.nama_user)}${isMe ? '<span class="ml-1.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Sesi Anda</span>' : ''}</td>
          <td class="py-3 font-mono text-indigo-700 font-semibold">@ ${esc(u.username)}</td>
          <td class="py-3"><span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${roleCls}">${esc(u.role)}</span></td>
          <td class="py-3 text-[11px] text-slate-500">${esc(roleAccessMap[u.role] || '-')}</td>
          <td class="py-3 text-right">
            <div class="inline-flex items-center justify-end gap-1.5">
              <button type="button" data-action="edit-user" data-id="${u.id_user}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1"><i data-lucide="key-round" class="w-3 h-3"></i> Edit / Password</button>
              ${!isMe ? `<button type="button" data-action="delete-user" data-id="${u.id_user}" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-md font-semibold text-[11px] inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-3 h-3"></i> Hapus</button>` : ''}
            </div>
          </td>
        </tr>`;
    }).join('');

    const counters = [
      { label: 'Pesanan', val: DB.all('Pesanan').length, cls: 'text-slate-800' },
      { label: 'Nota Kain', val: DB.all('Penerimaan_Bahan').length, cls: 'text-slate-800' },
      { label: 'Roll Fisik', val: DB.all('Detail_Roll_Bahan').length, cls: 'text-slate-800' },
      { label: 'Seri Potong', val: DB.all('Pemotongan_Seri').length, cls: 'text-slate-800' },
      { label: 'Log QC', val: DB.all('QC').length, cls: 'text-slate-800' },
      { label: 'Stok Jadi', val: DB.all('Stok_Barang_Jadi').length, cls: 'text-slate-800' },
      { label: 'Riwayat Stok', val: DB.all('Riwayat_Stok_Barang_Jadi').length, cls: 'text-indigo-600' },
      { label: 'Master Pola', val: DB.all('Pola').length, cls: 'text-slate-800' },
      { label: 'Aksesoris', val: DB.all('Aksesoris').length, cls: 'text-slate-800' },
    ];
    $('#pengaturan-db-counts').innerHTML = counters.map((c) => `
      <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
        <div class="text-base font-bold font-mono ${c.cls}">${c.val}</div>
        <div class="text-[9px] text-slate-500 font-semibold uppercase">${c.label}</div>
      </div>`).join('');

    $('#pengaturan-kategori-list').innerHTML = DB.all('Kategori_Pola').map((k) => `
      <div class="py-2 flex items-center justify-between gap-2">
        <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#${k.id_kategori}</span> ${esc(k.nama_kategori)}</span>
        <div class="inline-flex items-center gap-1">
          <button type="button" data-action="edit-kat-inline" data-id="${k.id_kategori}" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="del-kat-inline" data-id="${k.id_kategori}" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('');

    $('#pengaturan-sumber-list').innerHTML = DB.all('Sumber_Inisiasi').map((s) => `
      <div class="py-2 flex items-center justify-between gap-2">
        <span class="font-medium text-slate-800"><span class="font-mono text-slate-400 mr-1">#${s.id_sumber}</span> ${esc(s.nama_sumber)}</span>
        <div class="inline-flex items-center gap-1">
          <button type="button" data-action="edit-sum-inline" data-id="${s.id_sumber}" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="del-sum-inline" data-id="${s.id_sumber}" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('');

    const suppliers = DB.all('Supplier');
    $('#pengaturan-supplier-list').innerHTML = suppliers.length ? suppliers.map((sp) => `
      <div class="py-2 flex items-center justify-between gap-2">
        <div>
          <span class="font-semibold text-slate-800 block">${esc(sp.nama_supplier)}</span>
          <span class="text-[10px] text-slate-400">${esc(sp.kontak || '-')}</span>
        </div>
        <div class="inline-flex items-center gap-1 shrink-0">
          <button type="button" data-action="edit-sup-inline" data-id="${sp.id_supplier}" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-semibold">Edit</button>
          <button type="button" data-action="del-sup-inline" data-id="${sp.id_supplier}" class="px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[10px] font-semibold">Hapus</button>
        </div>
      </div>`).join('') : '<p class="text-[11px] text-slate-400 italic py-3">Belum ada supplier terdaftar.</p>';
  }

  /* --------------------------------------------------------------------------
   * Event Binding & Delegated Controllers
   * ------------------------------------------------------------------------ */
  function bindEvents() {
    // Modal close buttons + backdrop + Escape
    document.addEventListener('click', (e) => {
      const closeTarget = e.target.closest('[data-close-modal]');
      if (closeTarget) {
        closeModalById(closeTarget.dataset.closeModal);
        return;
      }
      if (e.target.id && e.target.id.startsWith('modal-') && e.target.classList.contains('fixed')) {
        closeModalById(e.target.id);
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAllModals();
    });

    // Login overlay
    $('#btn-toggle-pw')?.addEventListener('click', () => {
      const inp = $('#inp-login-password');
      if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
    });
    $$('.btn-demo-login').forEach((btn) => {
      btn.addEventListener('click', () => {
        $('#inp-login-username').value = btn.dataset.demoUser;
        $('#inp-login-password').value = btn.dataset.demoPass;
        $('#form-login').requestSubmit();
      });
    });
    $('#form-login')?.addEventListener('submit', (e) => {
      e.preventDefault();
      try {
        const res = Auth.login($('#inp-login-username').value, $('#inp-login-password').value);
        $('#login-error-box').classList.add('hidden');
        syncAuthVisibility();
        switchTab(res.redirectTab);
        showToast(`Selamat datang, ${res.user.nama_user}!`, 'success');
      } catch (err) {
        $('#login-error-text').textContent = err.message;
        $('#login-error-box').classList.remove('hidden');
        $('#login-error-box').classList.add('flex');
      }
    });

    // Logout buttons
    document.addEventListener('click', (e) => {
      const logoutBtn = e.target.closest('[data-action="logout"]');
      if (logoutBtn) {
        Auth.logout();
        syncAuthVisibility();
        refreshIcons();
      }
    });

    // Sidebar navigation
    $$('.nav-item[data-tab]').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        switchTab(a.dataset.tab);
      });
    });

    // Admin Role View Simulator
    $('#role-select')?.addEventListener('change', function () {
      const newRole = this.value;
      Auth.setSimulatedRole(newRole);
      if (!Auth.canAccessTab(state.tab)) {
        showToast(`Mode tampilan diubah menjadi '${newRole}'. Dialihkan ke Dashboard karena peran ini tidak memiliki akses ke halaman sebelumnya.`, 'info');
        switchTab('dashboard');
      } else {
        renderActiveScreen();
        showToast(`Mode tampilan disimulasikan sebagai '${newRole}'.`, 'info');
      }
    });

    /* --- Screen 2: Pesanan & Pola Listeners --- */
    $('#pesanan-sumber')?.addEventListener('change', syncCreateMitraField);
    $('#edit-id-sumber')?.addEventListener('change', syncEditMitraField);
    $('#btn-mode-existing')?.addEventListener('click', () => setPolaMode('existing'));
    $('#btn-mode-new')?.addEventListener('click', () => setPolaMode('new'));
    $('#btn-switch-new-pola')?.addEventListener('click', () => setPolaMode('new'));
    $('#btn-add-pola-size-row')?.addEventListener('click', () => {
      state.newPolaSpecs.push({ nama_ukuran: '', lingkar_dada: '', panjang_baju_celana: '', lebar_baju: '', panjang_pundak: '', panjang_tangan: '', lingkar_lengan: '', diameter_leher: '', lingkar_pinggang: '', lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' });
      renderNewPolaSpecs();
    });
    $('#btn-add-pola-acc-row')?.addEventListener('click', () => {
      const accs = DB.all('Aksesoris');
      if (!accs.length) { showToast('Belum ada data aksesoris terdaftar di Gudang & Aksesoris.', 'warning'); return; }
      state.newPolaAccs.push({ id_aksesoris: accs[0].id_aksesoris, jumlah_digunakan: 1 });
      renderNewPolaAccs();
    });

    $('#tbody-new-pola-specs')?.addEventListener('input', (e) => {
      if (!e.target.classList.contains('inp-spec-metric')) return;
      const idx = Number(e.target.dataset.idx);
      const f = e.target.dataset.field;
      if (state.newPolaSpecs[idx]) {
        state.newPolaSpecs[idx][f] = f === 'nama_ukuran' ? e.target.value : (e.target.value !== '' ? parseFloat(e.target.value) : null);
      }
    });
    $('#container-new-pola-acc')?.addEventListener('change', (e) => {
      if (e.target.classList.contains('sel-new-acc')) {
        const idx = Number(e.target.dataset.idx);
        if (state.newPolaAccs[idx]) state.newPolaAccs[idx].id_aksesoris = Number(e.target.value);
      }
    });
    $('#container-new-pola-acc')?.addEventListener('input', (e) => {
      if (e.target.classList.contains('inp-new-acc-qty')) {
        const idx = Number(e.target.dataset.idx);
        if (state.newPolaAccs[idx]) state.newPolaAccs[idx].jumlah_digunakan = parseInt(e.target.value, 10) || 1;
      }
    });

    $('#btn-save-pola-standalone')?.addEventListener('click', () => {
      const pola = runAction(() => Svc.createPola({
        id_kategori: $('#new-pola-kategori').value,
        kode_pola: $('#new-pola-kode').value,
        nama_pola: $('#new-pola-nama').value,
        spesifikasi: state.newPolaSpecs,
        aksesoris: state.newPolaAccs,
      }));
      if (pola) {
        showToast(`Master Pola ${pola.kode_pola} berhasil didaftarkan!`, 'success');
        $('#new-pola-kode').value = '';
        $('#new-pola-nama').value = '';
        state.newPolaSpecs = [{ nama_ukuran: 'All', lingkar_dada: '', panjang_baju_celana: '', lebar_baju: '', panjang_pundak: '', panjang_tangan: '', lingkar_lengan: '', diameter_leher: '', lingkar_pinggang: '', lingkar_selangkang: '', lingkar_paha: '', lebar_kaki: '' }];
        state.newPolaAccs = [];
        setPolaMode('existing');
        renderPesanan();
        $('#sel-pola-existing').value = pola.id_pola;
      }
    });

    $('#form-pesanan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const order = runAction(() => Svc.createPesanan({
        pola_mode: state.polaMode,
        id_sumber: $('#pesanan-sumber').value,
        nama_mitra: $('#input-nama-mitra').value,
        id_pola: $('#sel-pola-existing').value,
        jenis_pesanan: $('#inp-jenis-pesanan').value,
        id_penerimaan: $('#sel-pesanan-penerimaan').value || null,
        tgl_order: $('#inp-tgl-order').value,
        deadline_awal: $('#inp-deadline-awal').value,
        new_pola: state.polaMode === 'new' ? {
          id_kategori: $('#new-pola-kategori').value,
          kode_pola: $('#new-pola-kode').value,
          nama_pola: $('#new-pola-nama').value,
          spesifikasi: state.newPolaSpecs,
          aksesoris: state.newPolaAccs,
        } : null,
      }));
      if (order) {
        showToast(`Pesanan #${order.id_pesanan} berhasil diinisiasi.`, 'success');
        $('#inp-jenis-pesanan').value = '';
        $('#input-nama-mitra').value = '';
        $('#new-pola-kode').value = '';
        $('#new-pola-nama').value = '';
        setPolaMode('existing');
        renderPesanan();
      }
    });

    $('#form-edit-pesanan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-id-pesanan').value;
      const res = runAction(() => Svc.updatePesanan(id, {
        id_sumber: $('#edit-id-sumber').value,
        nama_mitra: $('#edit-nama-mitra').value,
        id_pola: $('#edit-id-pola').value,
        jenis_pesanan: $('#edit-jenis-pesanan').value,
        id_penerimaan: $('#edit-id-penerimaan').value || null,
        tgl_order: $('#edit-tgl-order').value,
        deadline_awal: $('#edit-deadline-awal').value,
        deadline_terbaru: $('#edit-deadline-terbaru').value,
        status_pesanan: $('#edit-status-pesanan').value,
        alasan_perubahan_deadline: $('#edit-alasan-perubahan').value,
      }), `Pesanan #${id} berhasil diperbarui!`);
      if (res) {
        closeModalById('modal-edit-pesanan');
        renderPesanan();
      }
    });

    $('#btn-delete-pesanan-modal')?.addEventListener('click', () => {
      const id = $('#edit-id-pesanan').value;
      if (!id || !confirm(`Hapus Pesanan #${id}? Tindakan ini hanya berhasil jika pesanan belum memiliki catatan Pemotongan Seri atau QC.`)) return;
      const ok = runAction(() => { Svc.deletePesanan(id); return true; }, `Pesanan #${id} berhasil dihapus.`);
      if (ok) {
        closeModalById('modal-edit-pesanan');
        renderPesanan();
      }
    });

    $('#form-edit-pola')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-pola-id').value;
      const res = runAction(() => Svc.updatePola(id, {
        id_kategori: $('#edit-pola-kategori').value,
        kode_pola: $('#edit-pola-kode').value,
        nama_pola: $('#edit-pola-nama').value,
        nama_ukuran: $('#edit-pola-ukuran').value,
        lingkar_dada: $('#edit-pola-dada').value,
        panjang_baju_celana: $('#edit-pola-panjang').value,
        panjang_pundak: $('#edit-pola-pundak').value,
      }), 'Master Pola berhasil diperbarui.');
      if (res) {
        closeModalById('modal-edit-pola');
        renderPesanan();
      }
    });

    $('#btn-delete-pola-modal')?.addEventListener('click', () => {
      const id = $('#edit-pola-id').value;
      const kode = $('#edit-pola-kode').value;
      const nama = $('#edit-pola-nama').value;
      if (!id || !confirm(`Hapus Master Pola "${kode} - ${nama}"? Hanya berhasil jika belum digunakan oleh Pesanan atau Stok Barang Jadi.`)) return;
      const ok = runAction(() => { Svc.deletePola(id); return true; }, 'Master Pola berhasil dihapus.');
      if (ok) {
        closeModalById('modal-edit-pola');
        renderPesanan();
      }
    });

    // Kategori & Sumber modals
    const openKatModal = () => {
      $('#kat-edit-id').value = '';
      $('#kat-nama-input').value = '';
      $('#kat-form-label').innerHTML = 'Nama Kategori Pola Baru <span class="text-rose-500">*</span>';
      $('#kat-submit-btn').textContent = '+ Tambah';
      $('#kat-cancel-edit-btn').classList.add('hidden');
      renderKategoriModalList();
      openModalById('modal-kelola-kategori');
    };
    const openSumModal = () => {
      $('#sum-edit-id').value = '';
      $('#sum-nama-input').value = '';
      $('#sum-form-label').innerHTML = 'Nama Sumber Inisiasi Baru <span class="text-rose-500">*</span>';
      $('#sum-submit-btn').textContent = '+ Tambah';
      $('#sum-cancel-edit-btn').classList.add('hidden');
      renderSumberModalList();
      openModalById('modal-kelola-sumber');
    };
    $('#btn-open-modal-kategori')?.addEventListener('click', openKatModal);
    $('#btn-edit-pola-kategori')?.addEventListener('click', openKatModal);
    $('#btn-open-modal-sumber')?.addEventListener('click', openSumModal);
    $('#btn-edit-pesanan-sumber')?.addEventListener('click', openSumModal);
    $('#kat-cancel-edit-btn')?.addEventListener('click', openKatModal);
    $('#sum-cancel-edit-btn')?.addEventListener('click', openSumModal);

    $('#form-manage-kategori')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const editId = $('#kat-edit-id').value;
      const nama = $('#kat-nama-input').value;
      const res = runAction(() => editId ? Svc.updateKategori(editId, nama) : Svc.createKategori(nama), editId ? 'Kategori diperbarui.' : 'Kategori ditambahkan.');
      if (res) {
        refreshPesananDropdowns();
        if (!editId && $('#new-pola-kategori')) $('#new-pola-kategori').value = res.id_kategori;
        openKatModal();
      }
    });

    $('#form-manage-sumber')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const editId = $('#sum-edit-id').value;
      const nama = $('#sum-nama-input').value;
      const res = runAction(() => editId ? Svc.updateSumber(editId, nama) : Svc.createSumber(nama), editId ? 'Sumber inisiasi diperbarui.' : 'Sumber inisiasi ditambahkan.');
      if (res) {
        refreshPesananDropdowns();
        if (!editId && $('#pesanan-sumber')) $('#pesanan-sumber').value = res.id_sumber;
        openSumModal();
      }
    });

    /* --- Screen 3: Penerimaan & Supplier Listeners --- */
    $('#btn-add-color-group')?.addEventListener('click', () => {
      state.colorGroups.push({ id: state.nextColorGroupId++, namaWarna: '', kodeWarna: '', satuanUkur: 'Kg' });
      renderColorGroups();
      renderDraftRollsTable();
    });
    const addDraftRoll = (berat = 25.00) => {
      state.draftRolls.push({ colorGroupId: state.colorGroups[0]?.id || 1, berat });
      renderDraftRollsTable();
      renderColorGroups();
    };
    $('#btn-add-roll-row')?.addEventListener('click', () => addDraftRoll(25.00));
    $('#btn-add-roll-row-bottom')?.addEventListener('click', () => addDraftRoll(25.00));
    $('#btn-add-5-rolls')?.addEventListener('click', () => {
      const defId = state.colorGroups[0]?.id || 1;
      for (let i = 0; i < 5; i++) state.draftRolls.push({ colorGroupId: defId, berat: 25.00 });
      renderDraftRollsTable();
      renderColorGroups();
    });
    $('#btn-reset-penerimaan')?.addEventListener('click', () => {
      state.colorGroups = [{ id: 1, namaWarna: '', kodeWarna: '', satuanUkur: 'Kg' }];
      state.nextColorGroupId = 2;
      state.draftRolls = [{ colorGroupId: 1, berat: 25.00 }, { colorGroupId: 1, berat: 25.00 }, { colorGroupId: 1, berat: 25.00 }];
      $('#form-penerimaan').reset();
      $('#inp-tgl-nota').value = todayIso();
      renderColorGroups();
      renderDraftRollsTable();
    });

    $('#color-groups-container')?.addEventListener('input', (e) => {
      const id = Number(e.target.dataset.cgId);
      const cg = state.colorGroups.find((c) => c.id === id);
      if (!cg) return;
      if (e.target.classList.contains('inp-cg-nama')) { cg.namaWarna = e.target.value; renderDraftRollsTable(); }
      if (e.target.classList.contains('inp-cg-kode')) { cg.kodeWarna = e.target.value; renderDraftRollsTable(); }
    });
    $('#color-groups-container')?.addEventListener('change', (e) => {
      if (e.target.classList.contains('sel-cg-satuan')) {
        const cg = state.colorGroups.find((c) => c.id === Number(e.target.dataset.cgId));
        if (cg) { cg.satuanUkur = e.target.value; renderColorGroups(); renderDraftRollsTable(); }
      }
    });
    $('#tbl-draft-rolls-body')?.addEventListener('change', (e) => {
      if (e.target.classList.contains('sel-roll-color')) {
        const idx = Number(e.target.dataset.rollIdx);
        if (state.draftRolls[idx]) {
          state.draftRolls[idx].colorGroupId = Number(e.target.value);
          renderColorGroups();
          renderDraftRollsTable();
        }
      }
    });
    $('#tbl-draft-rolls-body')?.addEventListener('input', (e) => {
      if (e.target.classList.contains('inp-roll-weight')) {
        const idx = Number(e.target.dataset.rollIdx);
        if (state.draftRolls[idx]) {
          state.draftRolls[idx].berat = parseFloat(String(e.target.value).replace(',', '.')) || 0;
          updateDraftRollsSummary();
        }
      }
    });

    $('#form-penerimaan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      for (let i = 0; i < state.colorGroups.length; i++) {
        if (!state.colorGroups[i].namaWarna.trim()) {
          showToast(`Nama warna kain pada kelompok warna ke-${i + 1} wajib diisi.`, 'error');
          return;
        }
      }
      for (let i = 0; i < state.draftRolls.length; i++) {
        const w = parseFloat(state.draftRolls[i].berat);
        if (isNaN(w) || w <= 0) {
          showToast(`Ukuran roll pada baris Roll #${i + 1} tidak valid (harus > 0).`, 'error');
          return;
        }
      }
      const warnaPayload = state.colorGroups.map((cg) => ({
        nama_warna: cg.namaWarna.trim(),
        kode_warna: cg.kodeWarna ? cg.kodeWarna.trim() : null,
        satuan_ukur: cg.satuanUkur || 'Kg',
        rolls: state.draftRolls.filter((r) => Number(r.colorGroupId) === Number(cg.id)).map((r) => ({ ukuran_awal: parseFloat(r.berat) })),
      })).filter((w) => w.rolls.length > 0);
      if (!warnaPayload.length) {
        showToast('Tidak ada roll kain yang terdaftar pada kelompok warna.', 'error');
        return;
      }

      const res = runAction(() => Svc.createPenerimaan({
        id_supplier: $('#sel-supplier').value,
        no_nota: $('#inp-no-nota').value,
        tgl_nota: $('#inp-tgl-nota').value,
        nama_kain: $('#inp-nama-kain').value,
        lebar_gramasi: $('#inp-lebar-gramasi').value,
        no_kendaraan: $('#inp-no-kendaraan').value,
        penerima: $('#inp-penerima').value,
        warna: warnaPayload,
      }));
      if (res) {
        showToast(`Penerimaan nota ${res.no_nota} berhasil disimpan beserta roll bahan.`, 'success');
        $('#btn-reset-penerimaan').click();
        renderPenerimaan();
      }
    });

    $('#form-edit-nota')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-nota-id').value;
      const res = runAction(() => Svc.updatePenerimaan(id, {
        id_supplier: $('#edit-nota-supplier').value,
        no_nota: $('#edit-nota-no').value,
        tgl_nota: $('#edit-nota-tgl').value,
        nama_kain: $('#edit-nota-kain').value,
        lebar_gramasi: $('#edit-nota-gramasi').value,
        no_kendaraan: $('#edit-nota-kendaraan').value,
        penerima: $('#edit-nota-penerima').value,
      }), 'Nota penerimaan berhasil diperbarui.');
      if (res) { closeModalById('modal-edit-nota'); renderPenerimaan(); }
    });

    $('#form-edit-warna')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-warna-id').value;
      const res = runAction(() => {
        Svc.updateWarna(id, {
          nama_warna: $('#edit-warna-nama').value,
          kode_warna: $('#edit-warna-kode').value,
          satuan_ukur: $('#edit-warna-satuan').value,
        });
        return true;
      }, 'Kelompok warna berhasil diperbarui.');
      if (res) { closeModalById('modal-edit-warna'); renderPenerimaan(); }
    });

    $('#form-edit-roll')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-roll-id').value;
      const res = runAction(() => {
        Svc.updateRoll(id, {
          no_roll_ke: $('#edit-roll-no').value,
          satuan: $('#edit-roll-satuan').value,
          ukuran_awal: $('#edit-roll-awal').value,
          ukuran_sisa: $('#edit-roll-sisa').value,
          status_roll: $('#edit-roll-status').value,
        });
        return true;
      }, 'Data roll berhasil diperbarui.');
      if (res) { closeModalById('modal-edit-roll'); renderPenerimaan(); }
    });

    $('#form-add-roll-existing')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const wid = $('#add-roll-warna-id').value;
      const res = runAction(() => Svc.addRollToWarna(wid, $('#add-roll-ukuran-awal').value));
      if (res) {
        showToast(`Roll baru (#id_roll: ${res.id_roll}) berhasil ditambahkan.`, 'success');
        closeModalById('modal-add-roll-existing');
        renderPenerimaan();
      }
    });

    // Supplier Modal
    const resetSupForm = () => {
      $('#sup-edit-id').value = '';
      $('#sup-nama').value = '';
      $('#sup-kontak').value = '';
      $('#sup-alamat').value = '';
      $('#sup-modal-title').textContent = 'Tambah & Kelola Supplier';
      $('#sup-nama-label').innerHTML = 'Nama Supplier <span class="text-rose-500">*</span>';
      $('#sup-submit-btn').textContent = '+ Simpan Supplier Baru';
      $('#sup-cancel-edit-btn').classList.add('hidden');
    };
    const openSupModal = () => {
      resetSupForm();
      renderSupplierModalList();
      openModalById('modal-add-supplier');
    };
    $('#btn-open-supplier-modal')?.addEventListener('click', openSupModal);
    $('#btn-supplier-new-inline')?.addEventListener('click', openSupModal);
    $('#btn-gudang-supplier-modal')?.addEventListener('click', openSupModal);
    $('#btn-acc-add-supplier')?.addEventListener('click', openSupModal);
    $('#sup-cancel-edit-btn')?.addEventListener('click', resetSupForm);
    $('#btn-supplier-edit-inline')?.addEventListener('click', () => {
      const id = $('#sel-supplier')?.value;
      const sp = id ? Q.supplier(id) : null;
      if (!sp) { openSupModal(); return; }
      renderSupplierModalList();
      $('#sup-edit-id').value = sp.id_supplier;
      $('#sup-nama').value = sp.nama_supplier;
      $('#sup-kontak').value = sp.kontak || '';
      $('#sup-alamat').value = sp.alamat || '';
      $('#sup-modal-title').textContent = `Edit Data Supplier #${sp.id_supplier}`;
      $('#sup-nama-label').innerHTML = `Edit Nama Supplier <span class="font-mono text-indigo-600">#${sp.id_supplier}</span> <span class="text-rose-500">*</span>`;
      $('#sup-submit-btn').textContent = 'Simpan Perubahan Supplier';
      $('#sup-cancel-edit-btn').classList.remove('hidden');
      openModalById('modal-add-supplier');
    });

    $('#form-add-supplier')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const editId = $('#sup-edit-id').value;
      const payload = { nama_supplier: $('#sup-nama').value, kontak: $('#sup-kontak').value, alamat: $('#sup-alamat').value };
      const sp = runAction(() => editId ? Svc.updateSupplier(editId, payload) : Svc.createSupplier(payload), editId ? 'Data supplier berhasil diperbarui!' : 'Supplier baru berhasil ditambahkan!');
      if (sp) {
        refreshSupplierSelects();
        if (!editId) {
          if ($('#sel-supplier')) $('#sel-supplier').value = sp.id_supplier;
          if ($('#add-acc-supplier')) $('#add-acc-supplier').value = sp.id_supplier;
        }
        resetSupForm();
        renderSupplierModalList();
        if (state.tab === 'pengaturan') renderPengaturan();
      }
    });

    /* --- Screen 4: Pemotongan Listeners --- */
    $('#btn-add-gelaran-row')?.addEventListener('click', () => {
      const firstActive = DB.where('Detail_Roll_Bahan', (r) => r.status_roll !== 'Habis')[0];
      state.gelaranRows.push({ id_roll: firstActive?.id_roll || '', panjang_gelar: 2.50, jumlah_tumpuk: 50, total_lembar_gelar: 300, status_roll_baru: 'Dipotong' });
      renderGelaranRows();
    });

    $('#sel-pm-pesanan')?.addEventListener('change', function () {
      const defWarna = this.options[this.selectedIndex]?.getAttribute('data-default-warna');
      if (defWarna && $('#sel-warna-bahan')) {
        $('#sel-warna-bahan').value = defWarna;
        updateUnitBadgeFromWarna();
        const matchRoll = DB.where('Detail_Roll_Bahan', (r) => r.status_roll !== 'Habis' && Number(r.id_detail_warna) === Number(defWarna))[0];
        if (matchRoll && state.gelaranRows.length > 0) state.gelaranRows[0].id_roll = matchRoll.id_roll;
      }
      renderGelaranRows();
    });

    $('#sel-warna-bahan')?.addEventListener('change', function () {
      updateUnitBadgeFromWarna();
      const matchRoll = DB.where('Detail_Roll_Bahan', (r) => r.status_roll !== 'Habis' && Number(r.id_detail_warna) === Number(this.value))[0];
      if (matchRoll && state.gelaranRows.length > 0) state.gelaranRows[0].id_roll = matchRoll.id_roll;
      renderGelaranRows();
    });

    $('#gelaran-rows-body')?.addEventListener('change', (e) => {
      const idx = Number(e.target.dataset.idx);
      if (!state.gelaranRows[idx]) return;
      if (e.target.classList.contains('sel-gelar-roll')) state.gelaranRows[idx].id_roll = Number(e.target.value);
      if (e.target.classList.contains('sel-status-roll')) state.gelaranRows[idx].status_roll_baru = e.target.value;
    });
    $('#gelaran-rows-body')?.addEventListener('input', (e) => {
      const idx = Number(e.target.dataset.idx);
      const r = state.gelaranRows[idx];
      if (!r) return;
      if (e.target.classList.contains('inp-panjang-gelar')) {
        r.panjang_gelar = parseFloat(e.target.value) || 0;
        r.total_lembar_gelar = Math.max(1, Math.round(r.panjang_gelar * r.jumlah_tumpuk * 2.4));
        const lembarInp = $(`.inp-lembar-gelar[data-idx="${idx}"]`);
        if (lembarInp) lembarInp.value = r.total_lembar_gelar;
        syncTotalHasil();
      } else if (e.target.classList.contains('inp-jumlah-tumpuk')) {
        r.jumlah_tumpuk = parseInt(e.target.value, 10) || 1;
        r.total_lembar_gelar = Math.max(1, Math.round(r.panjang_gelar * r.jumlah_tumpuk * 2.4));
        const lembarInp = $(`.inp-lembar-gelar[data-idx="${idx}"]`);
        if (lembarInp) lembarInp.value = r.total_lembar_gelar;
        syncTotalHasil();
      } else if (e.target.classList.contains('inp-lembar-gelar')) {
        r.total_lembar_gelar = parseInt(e.target.value, 10) || 1;
        syncTotalHasil();
      }
    });

    $('#form-pemotongan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const res = runAction(() => Svc.createPemotongan({
        id_pesanan: $('#sel-pm-pesanan').value,
        id_user_pemotong: $('#sel-pm-cutter').value,
        kode_seri: $('#inp-kode-seri').value,
        jenis_variasi: $('#inp-jenis-variasi').value,
        tgl_potong: $('#inp-tgl-potong').value,
        total_lembar_hasil: $('#inp-total-hasil').value,
        potong_warna: [{
          id_detail_warna: $('#sel-warna-bahan').value,
          total_lembar_direncanakan: $('#inp-target-lembar').value,
          kain_terpakai: $('#inp-kain-terpakai').value,
          gelaran: state.gelaranRows.map((r) => ({ ...r })),
        }],
      }));
      if (res) {
        showToast(`Pemotongan seri ${res.kode_seri} berhasil diproses. Stok & status roll bahan telah diperbarui.`, 'success');
        $('#inp-kode-seri').value = '';
        $('#inp-jenis-variasi').value = '';
        renderPemotongan();
      }
    });

    $('#form-edit-pemotongan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-pm-id').value;
      const ok = runAction(() => {
        Svc.updatePemotongan(id, {
          id_pesanan: $('#edit-pm-pesanan').value,
          id_user_pemotong: $('#edit-pm-cutter').value,
          kode_seri: $('#edit-pm-kode').value,
          jenis_variasi: $('#edit-pm-variasi').value,
          tgl_potong: $('#edit-pm-tgl').value,
          id_detail_warna: $('#edit-pm-warna').value,
          total_lembar_direncanakan: $('#edit-pm-rencana').value,
          total_lembar_hasil: $('#edit-pm-hasil').value,
          kain_terpakai: $('#edit-pm-kain').value,
          id_roll: $('#edit-pm-roll').value,
          panjang_gelar: $('#edit-pm-panjang').value,
          jumlah_tumpuk: $('#edit-pm-tumpuk').value,
          total_lembar_gelar: $('#edit-pm-hasil').value,
          status_roll_baru: $('#edit-pm-status-roll').value,
        });
        return true;
      }, 'Data pemotongan seri berhasil diperbarui.');
      if (ok) { closeModalById('modal-edit-pemotongan'); renderPemotongan(); }
    });

    $('#form-delete-pemotongan')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#del-pm-id').value;
      const choice = $('input[name="reverse_roll_choice"]:checked')?.value === '1';
      const ok = runAction(() => { Svc.deletePemotongan(id, choice); return true; }, choice ? 'Pemotongan dihapus & stok roll dipulihkan.' : 'Catatan pemotongan dihapus.');
      if (ok) { closeModalById('modal-delete-pemotongan'); renderPemotongan(); }
    });

    /* --- Screen 5: QC Listeners --- */
    const autoCalcQc = () => {
      const d = parseInt($('#inp-qc-diperiksa').value, 10) || 0;
      const c = parseInt($('#inp-qc-cacat').value, 10) || 0;
      if (d >= c) $('#inp-qc-lolos').value = d - c;
      const stSel = $('#sel-status-qc');
      if (c > 0 && stSel.value === 'Lolos QC') stSel.value = 'Need Rework';
      else if (c === 0 && stSel.value === 'Need Rework') stSel.value = 'Lolos QC';
      updateQcStokIndicator();
    };
    $('#inp-qc-diperiksa')?.addEventListener('input', autoCalcQc);
    $('#inp-qc-cacat')?.addEventListener('input', autoCalcQc);
    $('#inp-qc-lolos')?.addEventListener('input', updateQcStokIndicator);
    $('#sel-tahap-qc')?.addEventListener('change', updateQcStokIndicator);
    $('#inp-qc-warna')?.addEventListener('input', updateQcStokIndicator);
    $('#sel-qc-spesifikasi')?.addEventListener('change', updateQcStokIndicator);
    $('#sel-qc-pesanan')?.addEventListener('change', syncQcOrderInfo);

    $('#sel-jenis-cacat')?.addEventListener('change', function () {
      const pic = $('#sel-pic-rework');
      if (this.value === 'Cacat Potong') pic.value = 'Tim Pemotong (Stasiun Cutting)';
      else if (this.value === 'Cacat Jahit') pic.value = 'Mitra Penjahit CMT';
      else if (this.value === 'Cacat Bahan') pic.value = 'Gudang Bahan (Klaim Retur Supplier)';
      else pic.value = '';
    });

    ['edit-qc-diperiksa', 'edit-qc-cacat'].forEach((id) => {
      $(`#${id}`)?.addEventListener('input', () => {
        const d = parseInt($('#edit-qc-diperiksa').value, 10) || 0;
        const c = parseInt($('#edit-qc-cacat').value, 10) || 0;
        if (d >= c) $('#edit-qc-lolos').value = d - c;
      });
    });

    $('#form-qc')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const idPesanan = $('#sel-qc-pesanan').value;
      const ord = idPesanan ? Q.pesanan(idPesanan) : null;
      const isAbadi = Q.isProdukAbadi(ord);
      const res = runAction(() => Svc.createQc({
        id_pesanan: idPesanan,
        id_user_qc: $('#sel-user-qc').value,
        tahap_qc: $('#sel-tahap-qc').value,
        tgl_pemeriksaan: $('#inp-tgl-pemeriksaan').value,
        jumlah_diperiksa: $('#inp-qc-diperiksa').value,
        jumlah_lolos: $('#inp-qc-lolos').value,
        jumlah_cacat: $('#inp-qc-cacat').value,
        jenis_cacat: $('#sel-jenis-cacat').value,
        status_qc: $('#sel-status-qc').value,
        pic_rework: $('#sel-pic-rework').value,
        tgl_target_perbaikan: $('#inp-target-perbaikan').value,
        catatan_qc: $('#inp-catatan-qc').value,
        id_spesifikasi: isAbadi ? $('#sel-qc-spesifikasi').value : null,
        nama_warna: isAbadi ? $('#inp-qc-warna').value : null,
      }));
      if (res) {
        $('#inp-catatan-qc').value = '';
        $('#inp-qc-cacat').value = 0;
        autoCalcQc();
        renderQc();
      }
    });

    $('#form-edit-qc')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-qc-id').value;
      const ok = runAction(() => {
        Svc.updateQc(id, {
          id_pesanan: $('#edit-qc-pesanan').value,
          id_user_qc: $('#edit-qc-user').value,
          tahap_qc: $('#edit-qc-tahap').value,
          tgl_pemeriksaan: $('#edit-qc-tgl').value,
          jumlah_diperiksa: $('#edit-qc-diperiksa').value,
          jumlah_lolos: $('#edit-qc-lolos').value,
          jumlah_cacat: $('#edit-qc-cacat').value,
          jenis_cacat: $('#edit-qc-jenis').value,
          status_qc: $('#edit-qc-status').value,
          tgl_target_perbaikan: $('#edit-qc-target').value,
          tgl_selesai_perbaikan: $('#edit-qc-selesai').value,
          catatan_qc: $('#edit-qc-catatan').value,
        });
        return true;
      }, `Catatan QC #${id} berhasil diperbarui.`);
      if (ok) { closeModalById('modal-edit-qc'); renderQc(); }
    });

    $('#form-complete-rework')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#rework-qc-id').value;
      const ok = runAction(() => Svc.completeRework(id, $('#rework-tgl-selesai').value), `Rework untuk QC #${id} telah diselesaikan.`);
      if (ok) { closeModalById('modal-complete-rework'); renderQc(); }
    });

    /* --- Screen 6: Gudang & Aksesoris Listeners --- */
    $('#form-stock-thresholds')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const t = runAction(() => Svc.updateThresholds($('#inp-threshold-jadi').value, $('#inp-threshold-aksesoris').value));
      if (t) {
        showToast(`Batas stok rendah global disimpan: Barang Jadi <= ${t.min_stok_jadi} Pcs, Aksesoris < ${t.min_stok_aksesoris} Unit.`, 'success');
        renderGudang();
      }
    });

    $('#btn-open-adjust-stok')?.addEventListener('click', () => {
      const polaSel = $('#adj-id-pola');
      if (polaSel && polaSel.value) populateModalSpecsForPola($('#adj-id-spesifikasi'), polaSel.value);
      toggleMitraFieldRequirement();
      openModalById('modal-adjust-stok');
    });
    $('#adj-id-pola')?.addEventListener('change', function () {
      populateModalSpecsForPola($('#adj-id-spesifikasi'), this.value);
    });
    $('#adj-alasan')?.addEventListener('change', toggleMitraFieldRequirement);
    $('#edit-stok-pola')?.addEventListener('change', function () {
      populateModalSpecsForPola($('#edit-stok-spesifikasi'), this.value);
    });
    $('#btn-open-add-aksesoris')?.addEventListener('click', () => {
      refreshSupplierSelects();
      openModalById('modal-add-aksesoris');
    });
    $('#btn-open-map-acc-pola')?.addEventListener('click', () => openModalById('modal-map-aksesoris-pola'));

    $('#form-adjust-stok')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const qty = Math.abs(parseInt($('#adj-jumlah-pcs').value, 10) || 0);
      const jenis = $('#adj-jenis').value;
      const delta = jenis === 'KURANG' ? -qty : qty;
      const res = runAction(() => Svc.adjustStokJadi({
        id_pola: $('#adj-id-pola').value,
        id_spesifikasi: $('#adj-id-spesifikasi').value,
        nama_warna: $('#adj-nama-warna').value,
        delta_pcs: delta,
        alasan_penyesuaian: $('#adj-alasan').value,
        nama_mitra: $('#adj-nama-mitra').value,
        keterangan: $('#adj-keterangan').value,
      }));
      if (res) {
        showToast(`Transaksi stok berhasil disimpan. Sisa stok: ${res.jumlah_stok_pcs} pcs.`, 'success');
        closeModalById('modal-adjust-stok');
        renderGudang();
      }
    });

    $('#form-edit-stok-jadi')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-stok-id').value;
      const ok = runAction(() => {
        Svc.updateStokJadi(id, {
          id_pola: $('#edit-stok-pola').value,
          id_spesifikasi: $('#edit-stok-spesifikasi').value,
          nama_warna: $('#edit-stok-warna').value,
          jumlah_stok_pcs: $('#edit-stok-pcs').value,
        });
        return true;
      }, 'Data stok barang jadi berhasil diperbarui.');
      if (ok) { closeModalById('modal-edit-stok-jadi'); renderGudang(); }
    });

    $('#form-add-aksesoris')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const res = runAction(() => Svc.createAksesoris({
        nama_aksesoris: $('#add-acc-nama').value,
        ukuran: $('#add-acc-ukuran').value,
        satuan: $('#add-acc-satuan').value,
        stok: $('#add-acc-stok').value,
        id_supplier: $('#add-acc-supplier').value,
      }));
      if (res) {
        showToast(`Aksesoris '${res.nama_aksesoris}' berhasil didaftarkan.`, 'success');
        $('#form-add-aksesoris').reset();
        closeModalById('modal-add-aksesoris');
        renderGudang();
      }
    });

    $('#form-edit-aksesoris')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-acc-id').value;
      const res = runAction(() => Svc.updateAksesoris(id, {
        nama_aksesoris: $('#edit-acc-nama').value,
        ukuran: $('#edit-acc-ukuran').value,
        satuan: $('#edit-acc-satuan').value,
        stok: $('#edit-acc-stok').value,
        id_supplier: $('#edit-acc-supplier').value,
      }), 'Data aksesoris berhasil diperbarui.');
      if (res) { closeModalById('modal-edit-aksesoris'); renderGudang(); }
    });

    $('#form-map-aksesoris-pola')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const ok = runAction(() => {
        Svc.savePolaAksesorisMapping($('#map-id-pola').value, $('#map-id-aksesoris').value, $('#map-jumlah-digunakan').value);
        return true;
      }, 'Kebutuhan aksesoris per pola berhasil disimpan.');
      if (ok) { closeModalById('modal-map-aksesoris-pola'); renderGudang(); }
    });

    /* --- Screen 7: Laporan Eksekutif Listeners --- */
    $('#form-laporan-filter')?.addEventListener('submit', (e) => {
      e.preventDefault();
      state.laporanFilters = {
        jenis_laporan: $('#lap-filter-jenis').value,
        id_sumber: $('#lap-filter-sumber').value,
        start_date: $('#lap-filter-start').value,
        end_date: $('#lap-filter-end').value,
      };
      renderLaporan();
    });
    $('#btn-reset-laporan-filter')?.addEventListener('click', () => {
      state.laporanFilters = { jenis_laporan: 'KONSOLIDASI', id_sumber: '', start_date: '', end_date: '' };
      renderLaporan();
    });
    $('#btn-export-word')?.addEventListener('click', exportLaporanWord);
    $('#btn-export-csv')?.addEventListener('click', exportLaporanExcel);
    $('#btn-print-laporan')?.addEventListener('click', () => window.print());
    $('#btn-edit-kop-surat')?.addEventListener('click', () => {
      const cp = DB.config().companyProfile;
      $('#prof-nama').value = cp.nama_perusahaan || '';
      $('#prof-tagline').value = cp.tagline || '';
      $('#prof-alamat').value = cp.alamat || '';
      $('#prof-telepon').value = cp.telepon || '';
      $('#prof-kota').value = cp.kota_pengesahan || '';
      $('#prof-deskripsi').value = cp.deskripsi_sistem || '';
      openModalById('modal-company-profile');
    });
    $('#form-company-profile')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const res = runAction(() => Svc.updateCompanyProfile({
        nama_perusahaan: $('#prof-nama').value,
        tagline: $('#prof-tagline').value,
        alamat: $('#prof-alamat').value,
        telepon: $('#prof-telepon').value,
        kota_pengesahan: $('#prof-kota').value,
        deskripsi_sistem: $('#prof-deskripsi').value,
      }), 'Profil & Kop Surat perusahaan berhasil diperbarui.');
      if (res) { closeModalById('modal-company-profile'); renderLaporan(); }
    });

    /* --- Screen 8: Pengaturan Listeners --- */
    $('#btn-open-add-user')?.addEventListener('click', () => {
      $('#form-add-user').reset();
      openModalById('modal-add-user');
    });
    $('#form-add-user')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const u = runAction(() => Svc.createUser({
        nama_user: $('#add-user-nama').value,
        username: $('#add-user-username').value,
        password: $('#add-user-password').value,
        role: $('#add-user-role').value,
      }));
      if (u) {
        showToast(`Akun pengguna ${u.nama_user} (@${u.username}) berhasil ditambahkan.`, 'success');
        closeModalById('modal-add-user');
        renderPengaturan();
      }
    });
    $('#form-edit-user')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const id = $('#edit-user-id').value;
      const u = runAction(() => Svc.updateUser(id, {
        nama_user: $('#edit-user-nama').value,
        username: $('#edit-user-username').value,
        password: $('#edit-user-password').value,
        role: $('#edit-user-role').value,
      }), 'Akun pengguna berhasil diperbarui.');
      if (u) {
        closeModalById('modal-edit-user');
        syncChrome();
        renderPengaturan();
      }
    });
    $('#btn-reset-operational')?.addEventListener('click', () => {
      const inc = $('#chk-reset-include-master')?.checked || false;
      const msg = inc
        ? 'PERINGATAN: Anda akan mengosongkan SELURUH data transaksi operasional DAN katalog master (Pola, Aksesoris, Supplier). Akun pengguna tetap disimpan. Lanjutkan?'
        : 'Konfirmasi: Kosongkan seluruh data transaksi operasional (Pesanan, Nota & Roll Bahan, Pemotongan, QC, Stok Barang Jadi) untuk memulai produksi bersih?';
      if (!confirm(msg)) return;
      const res = runAction(() => Svc.resetOperationalData(inc));
      if (res) { showToast(res, 'info'); renderPengaturan(); }
    });
    $('#btn-seed-sample')?.addEventListener('click', () => {
      if (!confirm('Muat ulang data contoh produksi CMT ke dalam basis data? (Akun pengguna Anda tidak akan diubah)')) return;
      const res = runAction(() => Svc.seedSampleData());
      if (res) { showToast(res, 'success'); renderPengaturan(); }
    });
    $('#form-add-kategori')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const res = runAction(() => Svc.createKategori($('#inp-new-kategori').value), 'Kategori pola ditambahkan.');
      if (res) { $('#inp-new-kategori').value = ''; renderPengaturan(); }
    });
    $('#form-add-sumber')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const res = runAction(() => Svc.createSumber($('#inp-new-sumber').value), 'Sumber inisiasi ditambahkan.');
      if (res) { $('#inp-new-sumber').value = ''; renderPengaturan(); }
    });

    /* --- Delegated Table & Card Actions --- */
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const act = btn.dataset.action;
      const id = btn.dataset.id;

      // Pesanan actions
      if (act === 'remove-pola-spec') {
        const idx = Number(btn.dataset.idx);
        if (state.newPolaSpecs.length > 1) { state.newPolaSpecs.splice(idx, 1); renderNewPolaSpecs(); }
      } else if (act === 'remove-pola-acc') {
        state.newPolaAccs.splice(Number(btn.dataset.idx), 1);
        renderNewPolaAccs();
      } else if (act === 'edit-pesanan') {
        const ord = Q.pesanan(id);
        if (!ord) return;
        refreshPesananDropdowns();
        $('#modal-edit-title').textContent = `Edit Pesanan #${ord.id_pesanan}`;
        $('#edit-id-pesanan').value = ord.id_pesanan;
        $('#edit-id-sumber').value = ord.id_sumber;
        $('#edit-nama-mitra').value = ord.nama_mitra || '';
        $('#edit-id-pola').value = ord.id_pola;
        $('#edit-jenis-pesanan').value = ord.jenis_pesanan || '';
        $('#edit-id-penerimaan').value = ord.id_penerimaan || '';
        $('#edit-tgl-order').value = datePart(ord.tgl_order) || todayIso();
        $('#edit-deadline-awal').value = datePart(ord.deadline_awal);
        $('#edit-deadline-terbaru').value = datePart(ord.deadline_terbaru || ord.deadline_awal);
        $('#edit-status-pesanan').value = ord.status_pesanan || 'Antrean';
        $('#edit-alasan-perubahan').value = ord.alasan_perubahan_deadline || '';
        syncEditMitraField();
        openModalById('modal-edit-pesanan');
      } else if (act === 'revise-deadline') {
        const ord = Q.pesanan(id);
        if (!ord) return;
        const currDisp = fmtDateDmy(ord.deadline_terbaru || ord.deadline_awal);
        const inp = prompt('Masukkan Deadline Terbaru (dd/mm/yyyy):', currDisp);
        if (!inp) return;
        const iso = displayToIsoDate(inp, false);
        if (!iso) { showToast('Format tanggal tidak valid. Gunakan dd/mm/yyyy (contoh: 15/11/2026).', 'error'); return; }
        const reason = prompt('Alasan Revisi Deadline:', 'Penyesuaian Antrean Pemotongan');
        if (!reason) return;
        if (runAction(() => Svc.reviseDeadline(id, iso, reason), `Deadline pesanan #${id} direvisi.`)) renderPesanan();
      } else if (act === 'delete-pesanan') {
        if (!confirm(`Hapus Pesanan #${id}? Tindakan ini hanya berhasil jika pesanan belum memiliki catatan Pemotongan Seri atau QC.`)) return;
        if (runAction(() => { Svc.deletePesanan(id); return true; }, `Pesanan #${id} berhasil dihapus.`)) renderPesanan();
      } else if (act === 'edit-pola') {
        const p = Q.pola(id);
        if (!p) return;
        const firstSpec = Q.specsOfPola(p.id_pola)[0];
        refreshPesananDropdowns();
        $('#edit-pola-id').value = p.id_pola;
        $('#edit-pola-kategori').value = p.id_kategori;
        $('#edit-pola-kode').value = p.kode_pola;
        $('#edit-pola-nama').value = p.nama_pola;
        $('#edit-pola-ukuran').value = firstSpec?.nama_ukuran || 'All';
        $('#edit-pola-dada').value = firstSpec?.lingkar_dada ?? '';
        $('#edit-pola-panjang').value = firstSpec?.panjang_baju_celana ?? '';
        $('#edit-pola-pundak').value = firstSpec?.panjang_pundak ?? '';
        openModalById('modal-edit-pola');
      } else if (act === 'delete-pola') {
        const p = Q.pola(id);
        if (!p || !confirm(`Hapus Master Pola "${p.kode_pola} - ${p.nama_pola}"?`)) return;
        if (runAction(() => { Svc.deletePola(id); return true; }, 'Master Pola berhasil dihapus.')) renderPesanan();
      } else if (act === 'edit-kategori-modal') {
        const k = Q.kategori(id);
        if (!k) return;
        $('#kat-edit-id').value = k.id_kategori;
        $('#kat-nama-input').value = k.nama_kategori;
        $('#kat-form-label').innerHTML = `Edit Kategori Pola <span class="font-mono text-indigo-600">#${k.id_kategori}</span>`;
        $('#kat-submit-btn').textContent = 'Simpan Perubahan';
        $('#kat-cancel-edit-btn').classList.remove('hidden');
      } else if (act === 'delete-kategori-modal') {
        if (!confirm('Hapus kategori pola ini?')) return;
        if (runAction(() => { Svc.deleteKategori(id); return true; }, 'Kategori dihapus.')) {
          refreshPesananDropdowns();
          renderKategoriModalList();
        }
      } else if (act === 'edit-sumber-modal') {
        const s = Q.sumber(id);
        if (!s) return;
        $('#sum-edit-id').value = s.id_sumber;
        $('#sum-nama-input').value = s.nama_sumber;
        $('#sum-form-label').innerHTML = `Edit Sumber Inisiasi <span class="font-mono text-indigo-600">#${s.id_sumber}</span>`;
        $('#sum-submit-btn').textContent = 'Simpan Perubahan';
        $('#sum-cancel-edit-btn').classList.remove('hidden');
      } else if (act === 'delete-sumber-modal') {
        if (!confirm('Hapus sumber inisiasi ini?')) return;
        if (runAction(() => { Svc.deleteSumber(id); return true; }, 'Sumber inisiasi dihapus.')) {
          refreshPesananDropdowns();
          renderSumberModalList();
        }
      }

      // Penerimaan actions
      else if (act === 'remove-color-group') {
        const cid = Number(btn.dataset.cgId);
        if (state.colorGroups.length <= 1) return;
        state.colorGroups = state.colorGroups.filter((c) => c.id !== cid);
        const fb = state.colorGroups[0].id;
        state.draftRolls.forEach((r) => { if (Number(r.colorGroupId) === cid) r.colorGroupId = fb; });
        renderColorGroups();
        renderDraftRollsTable();
      } else if (act === 'remove-draft-roll') {
        const idx = Number(btn.dataset.rollIdx);
        if (state.draftRolls.length <= 1) return;
        state.draftRolls.splice(idx, 1);
        renderDraftRollsTable();
        renderColorGroups();
      } else if (act === 'edit-nota') {
        const pn = Q.penerimaan(id);
        if (!pn) return;
        refreshSupplierSelects();
        $('#edit-nota-id').value = pn.id_penerimaan;
        $('#edit-nota-supplier').value = pn.id_supplier;
        $('#edit-nota-no').value = pn.no_nota;
        $('#edit-nota-tgl').value = datePart(pn.tgl_nota);
        $('#edit-nota-kain').value = pn.nama_kain;
        $('#edit-nota-gramasi').value = pn.lebar_gramasi || '';
        $('#edit-nota-kendaraan').value = pn.no_kendaraan || '';
        $('#edit-nota-penerima').value = pn.penerima || '';
        openModalById('modal-edit-nota');
      } else if (act === 'delete-nota') {
        const pn = Q.penerimaan(id);
        if (!pn || !confirm(`Hapus nota penerimaan #${pn.id_penerimaan} (${pn.no_nota}) beserta seluruh kelompok warna dan roll kain di dalamnya?`)) return;
        if (runAction(() => { Svc.deletePenerimaan(id); return true; }, 'Nota penerimaan berhasil dihapus.')) renderPenerimaan();
      } else if (act === 'edit-warna') {
        const w = Q.warna(id);
        if (!w) return;
        $('#edit-warna-id').value = w.id_detail_warna;
        $('#edit-warna-nama').value = w.nama_warna;
        $('#edit-warna-kode').value = w.kode_warna || '';
        $('#edit-warna-satuan').value = w.satuan_ukur || 'Kg';
        openModalById('modal-edit-warna');
      } else if (act === 'add-roll-existing') {
        const w = Q.warna(id);
        if (!w) return;
        $('#add-roll-warna-id').value = w.id_detail_warna;
        $('#add-roll-existing-subtitle').textContent = `Warna: ${w.nama_warna} (${w.satuan_ukur})`;
        openModalById('modal-add-roll-existing');
      } else if (act === 'delete-warna') {
        const w = Q.warna(id);
        if (!w || !confirm(`Hapus kelompok warna "${w.nama_warna}" beserta seluruh roll kain di dalamnya?`)) return;
        if (runAction(() => { Svc.deleteWarna(id); return true; }, 'Kelompok warna dihapus.')) renderPenerimaan();
      } else if (act === 'edit-roll') {
        const r = Q.roll(id);
        if (!r) return;
        $('#edit-roll-id').value = r.id_roll;
        $('#edit-roll-no').value = r.no_roll_ke;
        $('#edit-roll-satuan').value = r.satuan;
        $('#edit-roll-awal').value = Number(r.ukuran_awal).toFixed(2);
        $('#edit-roll-sisa').value = Number(r.ukuran_sisa).toFixed(2);
        $('#edit-roll-status').value = r.status_roll;
        openModalById('modal-edit-roll');
      } else if (act === 'delete-roll') {
        const r = Q.roll(id);
        if (!r || !confirm(`Hapus Roll #${r.no_roll_ke} (#${r.id_roll})?`)) return;
        if (runAction(() => { Svc.deleteRoll(id); return true; }, 'Roll dihapus.')) renderPenerimaan();
      } else if (act === 'edit-supplier-modal') {
        const sp = Q.supplier(id);
        if (!sp) return;
        $('#sup-edit-id').value = sp.id_supplier;
        $('#sup-nama').value = sp.nama_supplier;
        $('#sup-kontak').value = sp.kontak || '';
        $('#sup-alamat').value = sp.alamat || '';
        $('#sup-modal-title').textContent = `Edit Data Supplier #${sp.id_supplier}`;
        $('#sup-nama-label').innerHTML = `Edit Nama Supplier <span class="font-mono text-indigo-600">#${sp.id_supplier}</span> <span class="text-rose-500">*</span>`;
        $('#sup-submit-btn').textContent = 'Simpan Perubahan Supplier';
        $('#sup-cancel-edit-btn').classList.remove('hidden');
      } else if (act === 'delete-supplier-modal') {
        if (!confirm('Hapus supplier ini?')) return;
        if (runAction(() => { Svc.deleteSupplier(id); return true; }, 'Supplier dihapus.')) {
          refreshSupplierSelects();
          renderSupplierModalList();
          if (state.tab === 'pengaturan') renderPengaturan();
        }
      }

      // Pemotongan actions
      else if (act === 'select-order-queue') {
        const pid = btn.dataset.pesanan;
        const wid = btn.dataset.warna;
        const rid = btn.dataset.roll;
        if ($('#sel-pm-pesanan')) $('#sel-pm-pesanan').value = pid;
        if (wid && $('#sel-warna-bahan')) {
          $('#sel-warna-bahan').value = wid;
          updateUnitBadgeFromWarna();
        }
        if (rid && state.gelaranRows.length > 0) state.gelaranRows[0].id_roll = Number(rid);
        renderGelaranRows();
        $('#form-pemotongan')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else if (act === 'remove-gelaran-row') {
        const idx = Number(btn.dataset.idx);
        if (state.gelaranRows.length <= 1) return;
        state.gelaranRows.splice(idx, 1);
        renderGelaranRows();
      } else if (act === 'edit-pemotongan') {
        const pm = DB.find('Pemotongan_Seri', id);
        if (!pm) return;
        const pw = DB.where('Detail_Pemotongan_Warna', (w) => Number(w.id_potong) === Number(pm.id_potong))[0];
        const gk = pw ? DB.where('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna))[0] : null;
        const rl = gk ? Q.roll(gk.id_roll) : null;
        $('#edit-pm-id').value = pm.id_potong;
        $('#edit-pm-pesanan').value = pm.id_pesanan;
        $('#edit-pm-cutter').value = pm.id_user_pemotong;
        $('#edit-pm-kode').value = pm.kode_seri;
        $('#edit-pm-variasi').value = pm.jenis_variasi || '';
        $('#edit-pm-tgl').value = toInputDateTime(pm.tgl_potong);
        if (pw) $('#edit-pm-warna').value = pw.id_detail_warna;
        $('#edit-pm-rencana').value = pw?.total_lembar_direncanakan || pm.total_lembar_hasil;
        $('#edit-pm-hasil').value = pm.total_lembar_hasil;
        $('#edit-pm-kain').value = Number(pw?.kain_terpakai || 0).toFixed(2);
        if (gk) $('#edit-pm-roll').value = gk.id_roll;
        $('#edit-pm-panjang').value = Number(gk?.panjang_gelar || 2.5).toFixed(2);
        $('#edit-pm-tumpuk').value = gk?.jumlah_tumpuk || 50;
        $('#edit-pm-status-roll').value = rl?.status_roll || 'Dipotong';
        openModalById('modal-edit-pemotongan');
      } else if (act === 'delete-pemotongan') {
        const pm = DB.find('Pemotongan_Seri', id);
        if (!pm) return;
        const pws = DB.where('Detail_Pemotongan_Warna', (w) => Number(w.id_potong) === Number(pm.id_potong));
        const rolls = [];
        pws.forEach((pw) => {
          DB.where('Detail_Gelaran_Kain', (g) => Number(g.id_potong_warna) === Number(pw.id_potong_warna)).forEach((g) => {
            const rl = Q.roll(g.id_roll);
            rolls.push({ id_roll: g.id_roll, no_roll_ke: rl?.no_roll_ke ?? '?', status_roll: rl?.status_roll ?? '-' });
          });
        });
        $('#del-pm-id').value = pm.id_potong;
        $('#del-pm-kode').textContent = pm.kode_seri;
        $('#del-pm-rolls-info').innerHTML = rolls.length
          ? '<div class="font-semibold text-slate-800 mb-1">Roll terpakai pada seri ini:</div>' + rolls.map((r) => `<div class="font-mono text-[11px]">• Roll #${r.no_roll_ke} (#${r.id_roll}) — Status saat ini: <b>${esc(r.status_roll)}</b></div>`).join('')
          : '<div class="text-slate-500">Tidak ada roll gelaran tercatat pada seri ini.</div>';
        openModalById('modal-delete-pemotongan');
      }

      // QC actions
      else if (act === 'complete-rework') {
        $('#rework-qc-id').value = id;
        $('#rework-tgl-selesai').value = nowLocalIsoMin();
        openModalById('modal-complete-rework');
      } else if (act === 'edit-qc') {
        const qc = DB.find('QC', id);
        if (!qc) return;
        $('#edit-qc-id').value = qc.id_qc;
        $('#edit-qc-pesanan').value = qc.id_pesanan;
        $('#edit-qc-user').value = qc.id_user_qc;
        $('#edit-qc-tahap').value = qc.tahap_qc;
        $('#edit-qc-tgl').value = toInputDateTime(qc.tgl_pemeriksaan) || nowLocalIsoMin();
        $('#edit-qc-diperiksa').value = qc.jumlah_diperiksa;
        $('#edit-qc-lolos').value = qc.jumlah_lolos;
        $('#edit-qc-cacat').value = qc.jumlah_cacat;
        $('#edit-qc-jenis').value = qc.jenis_cacat || 'Tidak Ada';
        $('#edit-qc-status').value = qc.status_qc || 'Lolos QC';
        $('#edit-qc-target').value = datePart(qc.tgl_target_perbaikan);
        $('#edit-qc-selesai').value = toInputDateTime(qc.tgl_selesai_perbaikan);
        $('#edit-qc-catatan').value = qc.catatan_qc || '';
        openModalById('modal-edit-qc');
      } else if (act === 'delete-qc') {
        if (!confirm(`Hapus catatan inspeksi QC #${id}? Jika sebelumnya menambah Stok Barang Jadi (Produk Abadi), stok tersebut juga akan dikurangi kembali.`)) return;
        if (runAction(() => { Svc.deleteQc(id); return true; }, `Catatan QC #${id} berhasil dihapus.`)) renderQc();
      }

      // Gudang actions
      else if (act === 'edit-stok-jadi') {
        const st = DB.find('Stok_Barang_Jadi', id);
        if (!st) return;
        $('#edit-stok-id').value = st.id_stok;
        $('#edit-stok-pola').value = st.id_pola;
        populateModalSpecsForPola($('#edit-stok-spesifikasi'), st.id_pola, st.id_spesifikasi);
        $('#edit-stok-warna').value = st.nama_warna;
        $('#edit-stok-pcs').value = st.jumlah_stok_pcs;
        openModalById('modal-edit-stok-jadi');
      } else if (act === 'outbound-stok-jadi') {
        const st = DB.find('Stok_Barang_Jadi', id);
        if (!st) return;
        $('#adj-id-pola').value = st.id_pola;
        populateModalSpecsForPola($('#adj-id-spesifikasi'), st.id_pola, st.id_spesifikasi);
        $('#adj-nama-warna').value = st.nama_warna;
        $('#adj-jenis').value = 'KURANG';
        toggleMitraFieldRequirement();
        openModalById('modal-adjust-stok');
      } else if (act === 'delete-stok-jadi') {
        const st = DB.find('Stok_Barang_Jadi', id);
        if (!st || !confirm(`Hapus baris Stok Barang Jadi #${id} (${Q.pola(st.id_pola)?.nama_pola || 'Pola'} · ${st.nama_warna}) beserta riwayat mutasinya?`)) return;
        if (runAction(() => { Svc.deleteStokJadi(id); return true; }, 'Baris stok barang jadi berhasil dihapus.')) renderGudang();
      } else if (act === 'quick-acc') {
        const delta = Number(btn.dataset.delta);
        const label = delta > 0 ? `Tambahkan +${delta}` : `Kurangi ${delta} (Pemakaian Produksi)`;
        if (!confirm(`${label} unit pada stok aksesoris #${id}?`)) return;
        const acc = runAction(() => Svc.quickStockAksesoris(id, delta));
        if (acc) {
          showToast(`Stok ${acc.nama_aksesoris} diperbarui menjadi ${fmtNum(acc.stok)} ${acc.satuan}.`, 'success');
          renderGudang();
        }
      } else if (act === 'edit-acc') {
        const acc = Q.aksesoris(id);
        if (!acc) return;
        refreshSupplierSelects();
        $('#edit-acc-id').value = acc.id_aksesoris;
        $('#edit-acc-nama').value = acc.nama_aksesoris;
        $('#edit-acc-ukuran').value = acc.ukuran || '';
        $('#edit-acc-satuan').value = acc.satuan;
        $('#edit-acc-stok').value = acc.stok;
        $('#edit-acc-supplier').value = acc.id_supplier;
        openModalById('modal-edit-aksesoris');
      } else if (act === 'delete-acc') {
        const acc = Q.aksesoris(id);
        if (!acc || !confirm(`Hapus aksesoris "${acc.nama_aksesoris}" (#${id}) dari inventori?`)) return;
        if (runAction(() => { Svc.deleteAksesoris(id); return true; }, 'Aksesoris berhasil dihapus!')) renderGudang();
      } else if (act === 'edit-pola-map') {
        $('#map-id-pola').value = btn.dataset.pola;
        $('#map-id-aksesoris').value = btn.dataset.acc;
        $('#map-jumlah-digunakan').value = btn.dataset.qty;
        openModalById('modal-map-aksesoris-pola');
      } else if (act === 'delete-pola-map') {
        if (!confirm('Hapus hubungan aksesoris ini dari pola?')) return;
        if (runAction(() => { Svc.deletePolaAksesorisMapping(btn.dataset.pola, btn.dataset.acc); return true; }, 'Pemetaan aksesoris dihapus.')) renderGudang();
      } else if (act === 'delete-riwayat-stok') {
        const rw = DB.find('Riwayat_Stok_Barang_Jadi', id);
        if (!rw) return;
        const desc = rw.jenis_transaksi === 'KELUAR' ? `mengembalikan +${rw.jumlah_pcs} pcs ke stok` : `membatalkan/mengurangi -${rw.jumlah_pcs} pcs dari stok`;
        if (!confirm(`Hapus riwayat transaksi #${id} dan ${desc}?`)) return;
        if (runAction(() => { Svc.deleteRiwayatStok(id); return true; }, 'Riwayat transaksi dihapus dan stok berhasil direverse.')) renderGudang();
      }

      // Pengaturan actions
      else if (act === 'edit-user') {
        const u = Q.user(id);
        if (!u) return;
        $('#edit-user-id').value = u.id_user;
        $('#edit-user-nama').value = u.nama_user;
        $('#edit-user-username').value = u.username;
        $('#edit-user-password').value = '';
        $('#edit-user-role').value = u.role;
        openModalById('modal-edit-user');
      } else if (act === 'delete-user') {
        const u = Q.user(id);
        if (!u || !confirm(`Hapus akun pengguna "${u.nama_user}" (#${id})?`)) return;
        if (runAction(() => { Svc.deleteUser(id); return true; }, `Akun pengguna ${u.nama_user} berhasil dihapus.`)) renderPengaturan();
      } else if (act === 'edit-kat-inline') {
        const k = Q.kategori(id);
        if (!k) return;
        const val = prompt('Nama Kategori Pola:', k.nama_kategori);
        if (!val || !val.trim()) return;
        if (runAction(() => Svc.updateKategori(id, val), 'Kategori diperbarui.')) renderPengaturan();
      } else if (act === 'del-kat-inline') {
        if (!confirm('Hapus kategori pola ini?')) return;
        if (runAction(() => { Svc.deleteKategori(id); return true; }, 'Kategori dihapus.')) renderPengaturan();
      } else if (act === 'edit-sum-inline') {
        const s = Q.sumber(id);
        if (!s) return;
        const val = prompt('Nama Sumber Inisiasi:', s.nama_sumber);
        if (!val || !val.trim()) return;
        if (runAction(() => Svc.updateSumber(id, val), 'Sumber inisiasi diperbarui.')) renderPengaturan();
      } else if (act === 'del-sum-inline') {
        if (!confirm('Hapus sumber inisiasi ini?')) return;
        if (runAction(() => { Svc.deleteSumber(id); return true; }, 'Sumber inisiasi dihapus.')) renderPengaturan();
      } else if (act === 'edit-sup-inline') {
        const sp = Q.supplier(id);
        if (!sp) return;
        const newNama = prompt('Nama Supplier:', sp.nama_supplier);
        if (!newNama || !newNama.trim()) return;
        const newKontak = prompt('Kontak / Telepon Supplier:', sp.kontak || '');
        const newAlamat = prompt('Alamat Supplier:', sp.alamat || '');
        if (runAction(() => Svc.updateSupplier(id, { nama_supplier: newNama, kontak: newKontak, alamat: newAlamat }), 'Supplier diperbarui.')) renderPengaturan();
      } else if (act === 'del-sup-inline') {
        if (!confirm('Hapus supplier ini?')) return;
        if (runAction(() => { Svc.deleteSupplier(id); return true; }, 'Supplier dihapus.')) renderPengaturan();
      }
    });
  }

  /* --------------------------------------------------------------------------
   * Boot
   * ------------------------------------------------------------------------ */
  function boot() {
    DB.init();
    enhanceDateInputs(document);
    bindEvents();
    syncAuthVisibility();

    const hashTab = location.hash ? location.hash.slice(1) : 'dashboard';
    if (Auth.isLoggedIn()) {
      switchTab(TABS.includes(hashTab) ? hashTab : 'dashboard', { updateHash: false, scrollTop: false });
    } else {
      refreshIcons();
    }

    window.addEventListener('hashchange', () => {
      const t = location.hash ? location.hash.slice(1) : 'dashboard';
      if (TABS.includes(t) && t !== state.tab) switchTab(t, { updateHash: false });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
