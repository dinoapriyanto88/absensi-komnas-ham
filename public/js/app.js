// Konfirmasi bawaan browser: hanya cadangan untuk halaman TANPA popup #adminConfirmModal.
document.addEventListener('submit', function (e) {
  // Halaman admin punya popup sendiri (admin-confirm.js): kotak bawaan browser tidak dipakai.
  if (document.getElementById('adminConfirmModal')) return;

  var form = e.target;
  if (form && form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    e.preventDefault();
  }
});