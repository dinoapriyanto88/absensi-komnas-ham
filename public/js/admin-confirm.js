(() => {
    'use strict';

    const modal = document.getElementById('adminConfirmModal');

    if (!modal) {
        return;
    }

    const dialog = modal.querySelector('.admin-confirm-dialog');
    const title = document.getElementById('adminConfirmTitle');
    const message = document.getElementById('adminConfirmMessage');
    const icon = document.getElementById('adminConfirmIcon');
    const proceedButton = document.getElementById('adminConfirmProceed');
    const cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');

    let pendingForm = null;
    let pendingSubmitter = null;
    let previousFocus = null;
    let isSubmitting = false;

    function openModal(form, submitter) {
        pendingForm = form;
        pendingSubmitter = submitter;
        previousFocus = document.activeElement;
        isSubmitting = false;

        const action = form.dataset.confirmAction || '';

        // Bawaan umum (dipakai form Superadmin yang hanya punya data-confirm).
        let defaults = {
            title: 'Apakah Anda yakin?',
            message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
            ok: 'Ya, Lanjutkan',
            symbol: '?',
            danger: form.dataset.confirmTone === 'danger'
        };

        if (action === 'delete') {
            defaults = {
                title: 'Hapus Kegiatan?',
                message: 'Kegiatan ini akan dihapus. Tindakan ini tidak bisa dibatalkan.',
                ok: 'Ya, Hapus',
                symbol: '!',
                danger: true
            };
        } else if (action === 'edit') {
            defaults = {
                title: 'Simpan Perubahan?',
                message: 'Pastikan perubahan informasi kegiatan sudah benar.',
                ok: 'Ya, Simpan',
                symbol: '?',
                danger: false
            };
        } else if (action === 'create' || action === 'save') {
            defaults = {
                title: 'Simpan Kegiatan?',
                message: 'Apakah Anda yakin ingin menyimpan kegiatan ini?',
                ok: 'Ya, Simpan',
                symbol: '?',
                danger: false
            };
        }

        // Atribut di <form> selalu menang atas bawaan di atas.
        title.textContent = form.dataset.confirmTitle || defaults.title;
        message.textContent = form.dataset.confirm || defaults.message;
        icon.textContent = defaults.danger ? '!' : defaults.symbol;
        proceedButton.textContent = form.dataset.confirmOk || defaults.ok;
        proceedButton.classList.toggle('is-danger', defaults.danger);

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        window.setTimeout(() => {
            cancelButtons[0]?.focus();
        }, 0);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        pendingForm = null;
        pendingSubmitter = null;
        isSubmitting = false;

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (!form.hasAttribute('data-confirm')) {
            return;
        }

        // Form ini sudah dikonfirmasi. Izinkan pengiriman satu kali.
        if (form.dataset.confirmApproved === 'true') {
            delete form.dataset.confirmApproved;
            return;
        }

        event.preventDefault();

        // Hindari membuka ulang modal saat proses konfirmasi sedang berjalan.
        if (pendingForm === form) {
            return;
        }

        openModal(form, event.submitter || null);
    });

    proceedButton.addEventListener('click', () => {
        if (!pendingForm || isSubmitting) {
            return;
        }

        const form = pendingForm;
        const submitter = pendingSubmitter;

        isSubmitting = true;
        form.dataset.confirmApproved = 'true';

        // Tutup modal sebelum mengirim formulir.
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        pendingForm = null;
        pendingSubmitter = null;

        // requestSubmit mempertahankan validasi HTML dan alur submit.
        if (submitter && submitter.form === form) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }

        // Jika validasi gagal, browser tidak mengirim formulir.
        // Pastikan status konfirmasi tidak tertinggal.
        window.setTimeout(() => {
            delete form.dataset.confirmApproved;
            isSubmitting = false;
        }, 0);
    });

    cancelButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (!modal.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key === 'Tab') {
            const focusable = modal.querySelectorAll(
                'button:not(:disabled), [href], input:not(:disabled), ' +
                'select:not(:disabled), textarea:not(:disabled), ' +
                '[tabindex]:not([tabindex="-1"])'
            );

            if (!focusable.length) {
                event.preventDefault();
                dialog.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
})();