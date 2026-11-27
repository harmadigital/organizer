"use strict";

var KTUser = (function () {
    var form = document.querySelector('#kt_modal_master form');
    var access = form.querySelector('[data-user-access]');
    var currentRecord = null;

    var setAccessFieldsVisible = function () {
        var loginSwitch = form.querySelector('[data-user-login-switch]');

        form.querySelector('[data-user-access-fields]').classList.toggle('d-none', !loginSwitch.checked);
    };

    var showAccessNotice = function (text) {
        var notice = access.querySelector('[data-user-access-notice]');

        notice.classList.toggle('d-none', !text);
        access.querySelector('[data-user-access-notice-text]').textContent = text || '';
    };

    /**
     * Kata sandi wajib saat akses login pertama kali diaktifkan, dan opsional bila sudah ada.
     */
    var describePassword = function (hasPassword) {
        form.querySelector('[data-user-password-label]').classList.toggle('required', !hasPassword);
        form.querySelector('[data-user-password-hint]').textContent = hasPassword
            ? 'Kosongkan bila kata sandi tidak diubah.'
            : 'Minimal 8 karakter.';
    };

    var setTwoFactorVisible = function (isVisible) {
        var block = access && access.querySelector('[data-user-two-factor]');

        if (block) {
            block.classList.toggle('d-none', !isVisible);
            block.classList.toggle('d-flex', isVisible);
        }
    };

    var resetTwoFactor = function () {
        OrganizerHttp.confirm({
            title: 'Reset autentikasi dua langkah?',
            text: 'User akan masuk hanya dengan kata sandi sampai ia mengaktifkan autentikasi dua langkah kembali.',
            confirmText: 'Ya, Reset',
        }).then(function (isConfirmed) {
            if (!isConfirmed) {
                return;
            }

            OrganizerHttp.request({ url: base_url + '/master/user/reset_2fa/' + currentRecord.id, method: 'POST' }).then(function () {
                setTwoFactorVisible(false);
                OrganizerHttp.showSuccess('Autentikasi dua langkah berhasil direset.');
            }).catch(OrganizerHttp.showError);
        });
    };

    var crud = OrganizerMasterCrud.create({
        resource: 'master/user',
        entity: 'User',
        columns: [
            { data: 'name' },
            {
                data: 'email',
                orderable: false,
                render: function (data, type, row) {
                    var lines = [row.email, row.phone].filter(Boolean);

                    return lines.length ? lines.join('<br>') : '<span class="text-muted">-</span>';
                },
            },
            { data: 'unit_name', orderable: false, searchable: false },
            {
                data: 'username',
                render: function (data, type, row) {
                    return OrganizerMasterCrud.isTruthy(row.has_login)
                        ? '<span class="badge badge-light-primary">' + data + '</span>'
                        : '<span class="text-muted">Tanpa akses login</span>';
                },
            },
            { data: 'role_names', orderable: false, searchable: false },
        ],
        statusColumn: {
            data: 'status',
            render: function (data, type, row) {
                return '<span class="badge badge-light-' + (data === 'active' ? 'success' : 'danger') + '">' + row.status_label + '</span>';
            },
        },

        isDeletable: function (row) {
            return OrganizerMasterCrud.isTruthy(row.can_delete);
        },

        onData: function (response) {
            Object.keys(response.summary || {}).forEach(function (key) {
                var card = document.querySelector('[data-user-stat="' + key + '"]');

                if (card) {
                    card.textContent = Number(response.summary[key]).toLocaleString('id-ID');
                }
            });
        },

        onReset: function () {
            currentRecord = null;

            if (!access) {
                return;
            }

            form.querySelector('[data-user-access-fieldset]').disabled = false;
            showAccessNotice(null);
            setTwoFactorVisible(false);
            describePassword(false);
            setAccessFieldsVisible();
        },

        onFill: function (record) {
            currentRecord = record;

            if (!access) {
                return;
            }

            form.querySelector('[data-user-login-switch]').checked = record.login_enabled;
            describePassword(record.login_enabled);
            setAccessFieldsVisible();

            var isLocked = record.is_self || !record.can_manage_access;

            form.querySelector('[data-user-access-fieldset]').disabled = isLocked;
            showAccessNotice(record.is_self
                ? 'Akses login dan role akun Anda sendiri tidak dapat diubah dari sini.'
                : (record.can_manage_access ? null : 'Anda tidak dapat mengubah akses login user ini karena hak aksesnya melebihi hak akses Anda.'));
            setTwoFactorVisible(record.two_factor_enabled && record.can_manage_access);
        },
    });

    return {
        init: function () {
            crud.init();

            if (!access) {
                return;
            }

            form.querySelector('[data-user-login-switch]').addEventListener('change', setAccessFieldsVisible);

            var resetButton = access.querySelector('[data-user-reset-two-factor]');

            if (resetButton) {
                resetButton.addEventListener('click', resetTwoFactor);
            }
        },
    };
})();

jQuery(document).ready(function () {
    KTUser.init();
});
