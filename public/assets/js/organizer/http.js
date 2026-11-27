"use strict";

/**
 * Helper AJAX bersama untuk modul Organizer.
 *
 * Server menjawab dengan envelope { status, data } dan kode HTTP yang sesuai:
 * 2xx berhasil, 422 validasi (dengan `errors` per field), 403 tanpa akses,
 * 409 konflik data. Helper ini menyatukan penanganannya agar setiap halaman
 * cukup menulis alur suksesnya saja.
 *
 *   OrganizerHttp.submit(form, button, { url: base_url + '/...' })
 *       .then(function (data) { ... })
 *       .catch(function () {});   // pesan error sudah ditampilkan
 */
var OrganizerHttp = (function () {
    var fallbackMessages = {
        0: 'Tidak dapat terhubung ke server. Periksa koneksi Anda lalu coba lagi.',
        401: 'Sesi Anda telah berakhir. Silakan masuk kembali.',
        403: 'Anda tidak memiliki akses untuk melakukan tindakan ini.',
        404: 'Data tidak ditemukan atau sudah dihapus.',
        409: 'Data sudah berubah atau bertentangan dengan kondisi saat ini. Muat ulang halaman lalu coba lagi.',
        419: 'Sesi halaman ini sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        422: 'Periksa kembali data yang Anda isi.',
        429: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
    };
    var defaultMessage = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';

    var csrfToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');

        return meta ? meta.content : '';
    };

    var buildBody = function (data) {
        if (data === undefined || data === null) {
            return { body: undefined, contentType: null };
        }

        if (data instanceof FormData) {
            return { body: data, contentType: null };
        }

        return { body: JSON.stringify(data), contentType: 'application/json' };
    };

    var toError = function (status, payload) {
        var serverMessage = payload && typeof payload.data === 'string' ? payload.data : null;

        return {
            status: status,
            message: serverMessage || fallbackMessages[status] || defaultMessage,
            errors: (payload && payload.errors) || {},
            payload: payload || null,
        };
    };

    /**
     * Kirim request. Resolve dengan isi `data`; reject dengan { status, message, errors, payload }.
     */
    var request = function (options) {
        var method = (options.method || 'GET').toUpperCase();

        // PHP hanya membaca multipart pada POST, jadi PUT/PATCH/DELETE dikirim lewat _method.
        if (options.data instanceof FormData && method !== 'GET' && method !== 'POST') {
            options.data.set('_method', method);
            method = 'POST';
        }

        var prepared = buildBody(options.data);
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        };

        if (prepared.contentType) {
            headers['Content-Type'] = prepared.contentType;
        }

        return fetch(options.url, {
            method: method,
            headers: headers,
            body: prepared.body,
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (response.ok && payload && payload.status === true) {
                    return payload.data;
                }

                throw toError(response.status, payload);
            });
        }, function () {
            throw toError(0, null);
        });
    };

    var clearFieldErrors = function (form) {
        form.querySelectorAll('.is-invalid').forEach(function (field) {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('[data-organizer-error]').forEach(function (message) {
            message.remove();
        });
    };

    var findField = function (form, name) {
        var bracketed = name.split('.').map(function (part, index) {
            return index === 0 ? part : '[' + part + ']';
        }).join('');

        // "files.2" belongs to <input name="files[]">: the error of one item goes on the list field.
        return form.querySelector('[name="' + name + '"]')
            || form.querySelector('[name="' + bracketed + '"]')
            || form.querySelector('[name="' + bracketed + '[]"]')
            || form.querySelector('[name="' + name.split('.')[0] + '[]"]');
    };

    /**
     * Tandai field yang gagal validasi dan tampilkan pesannya di bawah field.
     */
    var applyFieldErrors = function (form, errors) {
        clearFieldErrors(form);

        Object.keys(errors || {}).forEach(function (name) {
            var field = findField(form, name);

            if (!field) {
                return;
            }

            var message = document.createElement('div');
            message.className = 'invalid-feedback d-block';
            message.setAttribute('data-organizer-error', name);
            message.textContent = errors[name][0];

            field.classList.add('is-invalid');
            (field.closest('.fv-row') || field.parentNode).appendChild(message);
        });
    };

    /**
     * Begitu field yang ditandai error diubah, tandanya hilang. Berlaku juga untuk flatpickr
     * dan select2, yang memicu event change pada field aslinya.
     */
    var clearErrorOnChange = function (changeEvent) {
        var field = changeEvent.target;

        if (!field.classList || !field.classList.contains('is-invalid')) {
            return;
        }

        field.classList.remove('is-invalid');

        var row = field.closest('.fv-row') || field.parentNode;

        row.querySelectorAll('[data-organizer-error]').forEach(function (message) {
            message.remove();
        });
    };

    document.addEventListener('input', clearErrorOnChange);
    document.addEventListener('change', clearErrorOnChange);

    // select2 memicu change lewat jQuery, yang tidak sampai ke listener addEventListener.
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('change', 'select', clearErrorOnChange);
    }

    /**
     * Tampilkan error sebagai dialog. Pesan dipasang sebagai teks, bukan HTML.
     */
    var showError = function (error) {
        var title = error.status === 422 ? 'Periksa kembali' : 'Gagal';

        return Swal.fire({ title: title, text: error.message, icon: error.status === 422 ? 'warning' : 'error' });
    };

    var showSuccess = function (message) {
        return Swal.fire({ title: 'Berhasil', text: message, icon: 'success', timer: 1500, showConfirmButton: false });
    };

    var setBusy = function (button, isBusy) {
        if (!button) {
            return;
        }

        if (isBusy) {
            button.setAttribute('data-kt-indicator', 'on');
        } else {
            button.removeAttribute('data-kt-indicator');
        }

        button.disabled = isBusy;
    };

    /**
     * Kirim sebuah form: indikator tombol, error per field, dan dialog error ditangani di sini.
     */
    var submit = function (form, button, options) {
        clearFieldErrors(form);
        setBusy(button, true);

        return request({
            url: options.url,
            method: options.method || 'POST',
            data: options.data || new FormData(form),
        }).catch(function (error) {
            applyFieldErrors(form, error.errors);
            showError(error);

            throw error;
        }).finally(function () {
            setBusy(button, false);
        });
    };

    /**
     * Minta konfirmasi sebelum aksi berisiko. Resolve true jika pengguna setuju.
     */
    var confirm = function (options) {
        return Swal.fire({
            title: options.title,
            text: options.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
        }).then(function (result) {
            return result.isConfirmed === true;
        });
    };

    /**
     * Pesan baku untuk sebuah kode HTTP, dipakai saat respons tidak membawa pesan sendiri.
     */
    var messageFor = function (status) {
        return fallbackMessages[status] || defaultMessage;
    };

    return {
        request: request,
        submit: submit,
        messageFor: messageFor,
        confirm: confirm,
        showError: showError,
        showSuccess: showSuccess,
        applyFieldErrors: applyFieldErrors,
        clearFieldErrors: clearFieldErrors,
    };
})();
