// Dashboard Admin: popup "Buat Kegiatan", pencarian/filter kegiatan, dan tombol Share Link.
(() => {
  'use strict';

  /* ==========================================================
   * 1. POPUP BUAT KEGIATAN
   * ========================================================== */
  const modal = document.getElementById('kgCreateModal');
  let pembuka = null;

  // Popup konfirmasi (admin-confirm.js) tampil di atas popup ini.
  const konfirmasiTerbuka = () => {
    const c = document.getElementById('adminConfirmModal');
    return !!(c && c.classList.contains('is-open'));
  };

  if (modal) {
    // Pindahkan ke <body> agar position:fixed tidak terpotong oleh container dashboard.
    document.body.appendChild(modal);

    const buka = (dari) => {
      pembuka = dari || document.activeElement;
      modal.hidden = false;
      document.body.classList.add('kg-modal-terbuka');

      const target =
        modal.querySelector('.has-error input, .has-error select, .has-error textarea') ||
        modal.querySelector('input:not([type="hidden"])');
      if (target) target.focus();
    };

    const tutup = () => {
      if (modal.hidden) return;
      modal.hidden = true;
      document.body.classList.remove('kg-modal-terbuka');

      if (pembuka && typeof pembuka.focus === 'function' && document.contains(pembuka)) {
        pembuka.focus();
      }
    };

    document.addEventListener('click', (e) => {
      const pemicu = e.target.closest('[data-kg-open="kgCreateModal"]');
      if (pemicu) {
        e.preventDefault();
        buka(pemicu);
        return;
      }

      if (e.target.closest('#kgCreateModal [data-kg-close]')) {
        e.preventDefault();
        tutup();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (modal.hidden || konfirmasiTerbuka()) return;

      if (e.key === 'Escape') {
        if (e.defaultPrevented) return; // Esc ini sudah dipakai untuk menutup popup konfirmasi
        e.preventDefault();
        tutup();
        return;
      }

      if (e.key === 'Tab') {
        const fokusable = Array.from(modal.querySelectorAll(
          'button:not(:disabled), input:not([type="hidden"]):not(:disabled), ' +
          'select:not(:disabled), textarea:not(:disabled), a[href]'
        )).filter((el) => el.offsetParent !== null);

        if (!fokusable.length) return;

        const pertama = fokusable[0];
        const terakhir = fokusable[fokusable.length - 1];

        if (!modal.contains(document.activeElement)) {
          e.preventDefault();
          pertama.focus();
        } else if (e.shiftKey && document.activeElement === pertama) {
          e.preventDefault();
          terakhir.focus();
        } else if (!e.shiftKey && document.activeElement === terakhir) {
          e.preventDefault();
          pertama.focus();
        }
      }
    });

    // Validasi server gagal: buka kembali popup agar pesan error langsung terlihat.
    if (modal.dataset.autoOpen === 'true') buka(null);
  }

  /* ==========================================================
   * 2. CARI & FILTER KEGIATAN (tanpa memuat ulang halaman)
   * ========================================================== */
  const daftar = document.getElementById('kgDaftar');

  if (daftar) {
    const kartu = Array.from(daftar.querySelectorAll('[data-kg-item]'));
    const inputCari = document.getElementById('kgCari');
    const pilihStatus = document.getElementById('kgStatus');
    const tombolReset = document.getElementById('kgReset');
    const hitung = document.getElementById('kgHitung');
    const kosong = document.getElementById('kgKosong');

    // Huruf kecil, tanpa aksen, spasi dirapikan: "Rapat" cocok dengan "rapat".
    const normal = (s) => String(s || '')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ')
      .trim();

    kartu.forEach((el) => { el._teks = normal(el.dataset.cari); });

    const saring = () => {
      const kata = normal(inputCari ? inputCari.value : '');
      const kataKunci = kata ? kata.split(' ') : [];
      const status = pilihStatus ? pilihStatus.value : '';
      let tampil = 0;

      kartu.forEach((el) => {
        const cocok =
          kataKunci.every((k) => el._teks.includes(k)) &&
          (!status || el.dataset.status === status);

        el.hidden = !cocok;
        if (cocok) tampil++;
      });

      const aktif = kata !== '' || status !== '';

      if (kosong) kosong.hidden = tampil !== 0;
      if (tombolReset) tombolReset.hidden = !aktif;
      if (hitung) {
        hitung.textContent = aktif
          ? 'Menampilkan ' + tampil + ' dari ' + kartu.length + ' kegiatan.'
          : 'Total ' + kartu.length + ' kegiatan.';
      }
    };

    if (inputCari) inputCari.addEventListener('input', saring);
    if (pilihStatus) pilihStatus.addEventListener('change', saring);
    if (tombolReset) {
      tombolReset.addEventListener('click', () => {
        if (inputCari) inputCari.value = '';
        if (pilihStatus) pilihStatus.value = '';
        saring();
        if (inputCari) inputCari.focus();
      });
    }

    saring();
  }

  /* ==========================================================
   * 3. SHARE LINK PER KEGIATAN
   *    Ponsel: menu bagikan bawaan. Komputer: tautan disalin.
   * ========================================================== */
  document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-kg-copy]');
    if (!tombol) return;

    const url = tombol.dataset.kgCopy;
    const judul = tombol.dataset.kgTitle || 'Absensi';
    const label = tombol.dataset.label || tombol.textContent.trim();
    tombol.dataset.label = label;

    const umpan = (teks) => {
      tombol.textContent = teks;
      window.clearTimeout(tombol._pulih);
      tombol._pulih = window.setTimeout(() => { tombol.textContent = label; }, 2000);
    };

    const layarSentuh = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;

    if (navigator.share && layarSentuh) {
      navigator.share({ title: 'Absensi: ' + judul, text: 'Silakan isi absensi: ' + judul, url: url }).catch(() => {});
      return;
    }

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(
        () => umpan('Tautan disalin ✓'),
        () => window.prompt('Salin tautan absensi:', url)
      );
    } else {
      window.prompt('Salin tautan absensi:', url);
    }
  });
})();