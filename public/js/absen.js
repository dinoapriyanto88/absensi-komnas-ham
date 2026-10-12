
/* ============================================================
 * ABSEN DIGITAL KOMNAS HAM
 *
 * Fungsi:
 * - Tanda tangan digital
 * - Reset form
 * - CAPTCHA
 * - Kirim salinan email
 * - Pencarian NIP melalui SIMPEG
 * - Validasi formulir
 * - Konfirmasi pengiriman menggunakan modal di halaman
 * ============================================================ */

(function () {
    'use strict';

    var canvas = document.getElementById('sigCanvas');
    var form = document.getElementById('absenForm');

    if (!form) {
        return;
    }

    function byId(id) {
        return document.getElementById(id);
    }

    /* ==========================================================
     * PESAN ERROR
     * ========================================================== */

    function setError(key, message) {
        var el = document.querySelector(
            '[data-errorfor="' + key + '"]'
        );

        if (el) {
            el.textContent = message || '';
            el.classList.toggle('show', !!message);
        }

        var field = byId('f-' + key);

        if (field) {
            var wrapper = field.closest('.field');

            if (wrapper) {
                wrapper.classList.toggle('has-error', !!message);
            }
        }
    }

    /* ==========================================================
     * MODAL KONFIRMASI
     * ========================================================== */

    var modal = byId('konfirmasiModal');
    var tombolBatal = byId('batalKonfirmasi');
    var tombolLanjut = byId('lanjutKonfirmasi');
    var tombolSubmit = form.querySelector('button[type="submit"]');

    var menungguKonfirmasi = false;
    var sedangMengirim = false;

    function bukaModal() {
        if (!modal) {
            return false;
        }

        modal.hidden = false;
        document.body.classList.add('modal-open');

        if (tombolBatal) {
            tombolBatal.focus();
        }

        return true;
    }

    function tutupModal() {
        if (modal) {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        }

        if (tombolSubmit && !sedangMengirim) {
            tombolSubmit.focus();
        }
    }

    function resetStatusPengiriman() {
        sedangMengirim = false;
        menungguKonfirmasi = false;

        if (tombolLanjut) {
            tombolLanjut.disabled = false;
            tombolLanjut.textContent = 'Ya, Kirim Absensi';
        }
    }

    if (modal) {
        var tombolBackdrop = modal.querySelector(
            '[data-close-confirm]'
        );

        if (tombolBatal) {
            tombolBatal.addEventListener('click', function () {
                if (sedangMengirim) {
                    return;
                }

                menungguKonfirmasi = false;
                tutupModal();
            });
        }

        if (tombolBackdrop) {
            tombolBackdrop.addEventListener('click', function () {
                if (sedangMengirim) {
                    return;
                }

                menungguKonfirmasi = false;
                tutupModal();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (modal.hidden || sedangMengirim) {
                return;
            }

            if (event.key === 'Escape') {
                menungguKonfirmasi = false;
                tutupModal();
            }
        });

        if (tombolLanjut) {
            tombolLanjut.addEventListener('click', function () {
                if (!menungguKonfirmasi || sedangMengirim) {
                    return;
                }

                /*
                 * Validasi sudah berhasil dilakukan sebelum modal
                 * dibuka. Tutup modal dan kirim formulir satu kali
                 * tanpa memicu event submit untuk kedua kalinya.
                 */
                sedangMengirim = true;
                menungguKonfirmasi = false;

                tombolLanjut.disabled = true;
                tombolLanjut.textContent = 'Mengirim...';

                tutupModal();

                HTMLFormElement.prototype.submit.call(form);
            });
        }
    }

    /* ==========================================================
     * TANDA TANGAN DIGITAL
     * ========================================================== */

    var pad = null;

    if (canvas && typeof SignaturePad !== 'undefined') {
        pad = new SignaturePad(canvas, {
            penColor: '#1f2430'
        });
    }

    function resizeCanvas() {
        if (!canvas || !pad) {
            return;
        }

        var ratio = Math.min(
            Math.max(window.devicePixelRatio || 1, 1),
            2
        );

        var width = canvas.offsetWidth;
        var height = canvas.offsetHeight;

        if (!width || !height) {
            return;
        }

        var signatureData = pad.isEmpty() ? null : pad.toData();

        canvas.width = width * ratio;
        canvas.height = height * ratio;

        var context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        context.setTransform(1, 0, 0, 1, 0, 0);
        context.scale(ratio, ratio);

        if (signatureData && signatureData.length) {
            pad.fromData(signatureData);
        } else {
            pad.clear();
        }
    }

    if (pad) {
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    }

    var clearSig = byId('clearSig');

    if (clearSig) {
        clearSig.addEventListener('click', function () {
            if (pad) {
                pad.clear();
            }

            var signatureInput = byId('tandaTanganInput');

            if (signatureInput) {
                signatureInput.value = '';
            }

            setError('tanda_tangan', '');
        });
    }

    /* ==========================================================
     * CAPTCHA
     * ========================================================== */

    function gantiCaptcha() {
        var img = byId('captchaImg');

        if (img) {
            var url = new URL(img.src, window.location.href);
            url.searchParams.set('t', Date.now().toString());
            img.src = url.toString();
        }

        var input = byId('captcha');

        if (input) {
            input.value = '';
        }
    }

    var refreshCaptcha = byId('refreshCaptcha');

    if (refreshCaptcha) {
        refreshCaptcha.addEventListener('click', gantiCaptcha);
    }

    /* ==========================================================
     * KIRIM SALINAN EMAIL
     * ========================================================== */

    var kirimSalinan = byId('kirim_salinan');
    var emailWrap = byId('emailWrap');
    var emailInput = byId('email_salinan');

    function updateEmailVisibility() {
        if (!emailWrap || !kirimSalinan) {
            return;
        }

        var aktif = kirimSalinan.checked;

        emailWrap.classList.toggle('open', aktif);

        if (emailInput) {
            emailInput.disabled = !aktif;

            if (aktif) {
                emailInput.removeAttribute('aria-hidden');
            } else {
                emailInput.setAttribute('aria-hidden', 'true');
                setError('email_salinan', '');
            }
        }
    }

    if (kirimSalinan) {
        kirimSalinan.addEventListener(
            'change',
            updateEmailVisibility
        );
    }

    updateEmailVisibility();

    /* ==========================================================
     * SIMPEG / PENCARIAN NIP
     * ========================================================== */

    var cariNipButton = byId('cariNip');
    var nipInput = byId('f-nip_nik');
    var nipStatus = byId('nipStatus');
    var simpegResult = byId('simpegResult');

    var profilSimpeg = {
        nama: {
            hidden: 'f-nama',
            display: 'hasilNama'
        },
        unit_kerja: {
            hidden: 'f-unit_kerja',
            display: 'hasilUnitKerja'
        },
        jabatan: {
            hidden: 'f-jabatan',
            display: 'hasilJabatan'
        }
    };

    function setNipStatus(message, type) {
        if (!nipStatus) {
            return;
        }

        nipStatus.textContent = message || '';

        nipStatus.classList.remove(
            'success',
            'error',
            'loading'
        );

        if (type) {
            nipStatus.classList.add(type);
        }
    }

    function setFieldValue(key, value) {
        var config = profilSimpeg[key];

        if (!config) {
            return;
        }

        var text = value == null ? '' : String(value);
        var hiddenField = byId(config.hidden);
        var display = byId(config.display);

        if (hiddenField) {
            hiddenField.value = text;
        }

        if (display) {
            display.textContent = text || '—';
        }
    }

    function clearSimpegFields() {
        Object.keys(profilSimpeg).forEach(function (key) {
            setFieldValue(key, '');
        });

        if (simpegResult) {
            simpegResult.hidden = true;
        }
    }

    function setCariButtonState(loading) {
        if (!cariNipButton) {
            return;
        }

        if (loading) {
            if (!cariNipButton.dataset.originalText) {
                cariNipButton.dataset.originalText =
                    cariNipButton.textContent.trim();
            }

            cariNipButton.disabled = true;
            cariNipButton.textContent = 'Mencari...';
        } else {
            cariNipButton.disabled = false;
            cariNipButton.textContent =
                cariNipButton.dataset.originalText || 'Cari Data';
        }
    }

    function validasiNip() {
        if (!nipInput) {
            return false;
        }

        var nip = nipInput.value.replace(/\D/g, '').trim();
        nipInput.value = nip;

        if (!nip) {
            clearSimpegFields();

            setNipStatus(
                'Silakan masukkan NIP terlebih dahulu.',
                'error'
            );

            nipInput.focus();
            return false;
        }

        if (!/^[0-9]{10,20}$/.test(nip)) {
            clearSimpegFields();

            setNipStatus(
                'NIP harus berupa 10–20 digit angka.',
                'error'
            );

            nipInput.focus();
            return false;
        }

        return true;
    }

    function getCsrfToken() {
        var token = form.querySelector('input[name="_token"]');

        if (token) {
            return token.value;
        }

        var metaToken = document.querySelector(
            'meta[name="csrf-token"]'
        );

        return metaToken ? metaToken.content : '';
    }

    async function cariDataPegawai() {
        if (!cariNipButton || !nipInput) {
            return;
        }

        if (!validasiNip()) {
            return;
        }

        var cariUrl = form.dataset.cariUrl;

        if (!cariUrl) {
            setNipStatus(
                'URL pencarian NIP tidak tersedia.',
                'error'
            );

            return;
        }

        clearSimpegFields();
        setCariButtonState(true);

        setNipStatus(
            'Sedang mencari data kepegawaian...',
            'loading'
        );

        try {
            var response = await fetch(cariUrl, {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    nip_nik: nipInput.value
                }).toString()
            });

            var data;

            try {
                data = await response.json();
            } catch (parseError) {
                clearSimpegFields();

                setNipStatus(
                    'Respons server tidak valid. Silakan coba lagi.',
                    'error'
                );

                return;
            }

            if (
                !data ||
                typeof data !== 'object' ||
                !response.ok ||
                data.ok !== true ||
                !data.data
            ) {
                clearSimpegFields();

                setNipStatus(
                    data && (data.pesan || data.message)
                        ? (data.pesan || data.message)
                        : 'Pencarian NIP gagal. Silakan coba lagi.',
                    'error'
                );

                return;
            }

            var pegawai = data.data;

            if (
                !pegawai.nama ||
                !pegawai.unit_kerja ||
                !pegawai.jabatan
            ) {
                clearSimpegFields();

                setNipStatus(
                    'Data pegawai ditemukan, tetapi data profil belum lengkap.',
                    'error'
                );

                return;
            }

            setFieldValue('nama', pegawai.nama);
            setFieldValue('unit_kerja', pegawai.unit_kerja);
            setFieldValue('jabatan', pegawai.jabatan);

            if (simpegResult) {
                simpegResult.hidden = false;
            }

            setNipStatus(
                'Data pegawai ditemukan dan berhasil diisi dari SIMPEG.',
                'success'
            );
        } catch (error) {
            console.error('Pencarian SIMPEG gagal:', error);

            clearSimpegFields();

            setNipStatus(
                'Tidak dapat menghubungi server pencarian NIP. Silakan coba lagi.',
                'error'
            );
        } finally {
            setCariButtonState(false);
        }
    }

    if (cariNipButton) {
        cariNipButton.addEventListener(
            'click',
            cariDataPegawai
        );
    }

    if (nipInput) {
        nipInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                cariDataPegawai();
            }
        });

        nipInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');

            clearSimpegFields();

            setNipStatus(
                'Klik "Cari Data" untuk menampilkan profil pegawai.',
                ''
            );
        });
    }

    /* ==========================================================
     * RESET FORM
     * ========================================================== */

    form.addEventListener('reset', function () {
        window.setTimeout(function () {
            if (pad) {
                pad.clear();
            }

            var signatureInput = byId('tandaTanganInput');

            if (signatureInput) {
                signatureInput.value = '';
            }

            resetStatusPengiriman();
            tutupModal();

            setError('tanda_tangan', '');
            setError('konfirmasi', '');
            setError('email_salinan', '');

            clearSimpegFields();

            Array.prototype.forEach.call(
                form.querySelectorAll('.has-error'),
                function (el) {
                    el.classList.remove('has-error');
                }
            );

            Array.prototype.forEach.call(
                form.querySelectorAll('.error-text'),
                function (el) {
                    el.textContent = '';
                    el.classList.remove('show');
                }
            );

            if (nipStatus) {
                nipStatus.textContent =
                    'Masukkan NIP, lalu klik Cari Data untuk menampilkan profil pegawai.';

                nipStatus.classList.remove(
                    'success',
                    'error',
                    'loading'
                );
            }

            updateEmailVisibility();
            gantiCaptcha();
        }, 0);
    });

    /* ==========================================================
     * VALIDASI FORMULIR
     * ========================================================== */

    function validasiFormulir() {
        var ok = true;

        // Validasi tanda tangan.
        if (!pad || pad.isEmpty()) {
            setError(
                'tanda_tangan',
                'Tanda tangan wajib diisi.'
            );

            ok = false;
        } else {
            setError('tanda_tangan', '');

            var signatureInput = byId('tandaTanganInput');

            if (signatureInput) {
                signatureInput.value = pad.toDataURL('image/png');
            } else {
                ok = false;
            }
        }

        // Validasi persetujuan data.
        var konfirmasi = byId('konfirmasi');

        if (!konfirmasi || !konfirmasi.checked) {
            setError(
                'konfirmasi',
                'Anda harus mencentang kotak konfirmasi sebelum mengirim.'
            );

            ok = false;
        } else {
            setError('konfirmasi', '');
        }

        // Validasi email salinan.
        if (kirimSalinan && kirimSalinan.checked) {
            if (!emailInput || !emailInput.value.trim()) {
                setError(
                    'email_salinan',
                    'Alamat email wajib diisi jika salinan dipilih.'
                );

                if (emailInput) {
                    emailInput.focus();
                }

                ok = false;
            } else {
                setError('email_salinan', '');
            }
        } else {
            setError('email_salinan', '');
        }

        // Pastikan data SIMPEG sudah ditemukan jika fitur aktif.
        if (
            cariNipButton &&
            nipInput &&
            form.dataset.cariUrl
        ) {
            var namaInput = byId('f-nama');
            var unitInput = byId('f-unit_kerja');
            var jabatanInput = byId('f-jabatan');

            if (
                !namaInput ||
                !namaInput.value.trim() ||
                !unitInput ||
                !unitInput.value.trim() ||
                !jabatanInput ||
                !jabatanInput.value.trim() ||
                !simpegResult ||
                simpegResult.hidden
            ) {
                setNipStatus(
                    'Silakan cari NIP terlebih dahulu sampai profil pegawai berhasil ditampilkan.',
                    'error'
                );

                if (cariNipButton) {
                    cariNipButton.focus();
                }

                ok = false;
            }
        }

        return ok;
    }

    /* ==========================================================
     * PROSES PENGIRIMAN DAN KONFIRMASI MODAL
     * ========================================================== */

    form.addEventListener('submit', function (event) {
        /*
         * Cegah pengiriman langsung.
         * Form hanya dikirim setelah validasi dan konfirmasi.
         */
        event.preventDefault();

        // Cegah klik submit berulang saat proses berlangsung.
        if (sedangMengirim) {
            return;
        }

        // Jalankan validasi sebelum membuka modal.
        if (!validasiFormulir()) {
            return;
        }

        // Modal tidak tersedia: tampilkan konfirmasi cadangan.
        if (!modal) {
            var setuju = window.confirm(
                'Apakah data Anda sudah benar? ' +
                'Pastikan informasi dan tanda tangan sesuai sebelum mengirim absensi.'
            );

            if (!setuju) {
                return;
            }

            sedangMengirim = true;

            HTMLFormElement.prototype.submit.call(form);
            return;
        }

        // Validasi berhasil, minta persetujuan peserta.
        menungguKonfirmasi = true;
        bukaModal();
    });

})();