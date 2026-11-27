"use strict";

var KTCommittee = (function () {
    var reload = function () {
        window.location.reload();
    };

    /**
     * Minta alasan tertulis, lalu kirim. Alasan ditampilkan sebagai teks biasa.
     */
    var askReasonAndPost = function (options) {
        Swal.fire({
            title: options.title,
            text: options.text,
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Alasan',
            inputAttributes: { maxlength: 1000, 'aria-label': 'Alasan' },
            showCancelButton: true,
            confirmButtonText: options.confirmText,
            cancelButtonText: 'Batal',
            inputValidator: function (value) {
                return value && value.trim() ? null : 'Alasan wajib diisi.';
            },
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            OrganizerHttp.request({ url: options.url, method: 'POST', data: { reason: result.value.trim() } }).then(function () {
                return OrganizerHttp.showSuccess(options.successText);
            }).then(reload).catch(OrganizerHttp.showError);
        });
    };

    var bindAssignment = function () {
        var modalElement = document.getElementById('kt_modal_assignment');
        var addButton = document.querySelector('[data-assignment-add]');

        if (!modalElement || !addButton) {
            return;
        }

        var form = modalElement.querySelector('form');
        var modal = new bootstrap.Modal(modalElement);
        var roles = JSON.parse(modalElement.dataset.assignableRoles);
        var roleSelect = form.elements.committee_role_id;
        var divisionSelect = form.elements.division_id;
        var personSelect = form.elements.user_id;
        var divisionField = form.querySelector('[data-assignment-division]');
        var primaryField = form.querySelector('[data-assignment-primary]');
        var confirmHint = form.querySelector('[data-assignment-confirm-hint]');
        var defaultHint = confirmHint.textContent;

        var roleById = function (id) {
            return roles.find(function (role) {
                return String(role.id) === String(id);
            });
        };

        /**
         * Seksi hanya diminta untuk jabatan seksi, dan hanya seksi yang boleh Anda isi yang ditawarkan.
         */
        var applyRole = function () {
            var role = roleById(roleSelect.value);
            var isDivisionRole = role && role.scope === 'division';

            divisionField.classList.toggle('d-none', !isDivisionRole);
            primaryField.classList.toggle('d-none', !(role && role.is_singleton));

            Array.from(divisionSelect.options).forEach(function (option) {
                if (option.value) {
                    option.disabled = !isDivisionRole || role.division_ids.indexOf(Number(option.value)) === -1;
                }
            });

            if (!isDivisionRole || (divisionSelect.selectedOptions[0] && divisionSelect.selectedOptions[0].disabled)) {
                $(divisionSelect).val('').trigger('change');
            }
        };

        /**
         * Hasil pencarian orang membawa tanda apakah ia punya akses login.
         */
        var applyPerson = function (person) {
            confirmHint.textContent = person && person.has_login === false
                ? 'Orang ini tidak memiliki akses login sehingga tidak dapat menjawab undangan sendiri. Centang bila ia sudah menyatakan bersedia.'
                : defaultHint;
        };

        $(roleSelect).on('change', applyRole);
        $(personSelect).on('select2:select', function (selectEvent) {
            applyPerson(selectEvent.params.data);
        });
        $(personSelect).on('select2:clear', function () {
            applyPerson(null);
        });

        addButton.addEventListener('click', function () {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);
            OrganizerSelect.reset(personSelect);
            $(roleSelect).val('').trigger('change');
            applyRole();
            applyPerson(null);
            modal.show();
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-assignment-submit]'), { url: modalElement.dataset.url }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Panitia berhasil ditambahkan.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    var bindAssignmentEdit = function () {
        var modalElement = document.getElementById('kt_modal_assignment_edit');
        var form = modalElement.querySelector('form');
        var modal = new bootstrap.Modal(modalElement);
        var updateUrl = null;

        document.querySelectorAll('[data-assignment-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                OrganizerHttp.request({ url: button.dataset.assignmentEdit }).then(function (assignment) {
                    form.reset();
                    OrganizerHttp.clearFieldErrors(form);
                    updateUrl = button.dataset.updateUrl;
                    modalElement.querySelector('[data-assignment-edit-title]').textContent = assignment.person + ' · ' + assignment.role + (assignment.division ? ' · ' + assignment.division : '');
                    form.querySelector('[data-assignment-edit-primary]').classList.toggle('d-none', !assignment.is_singleton);
                    form.elements.is_primary.checked = assignment.is_primary;
                    form.elements.job_description.value = assignment.job_description || '';
                    modal.show();
                }).catch(OrganizerHttp.showError);
            });
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-assignment-edit-submit]'), { url: updateUrl }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Jabatan berhasil diperbarui.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    var bindMembershipActions = function () {
        document.querySelectorAll('[data-assignment-end]').forEach(function (button) {
            button.addEventListener('click', function () {
                askReasonAndPost({
                    url: button.dataset.assignmentEnd,
                    title: 'Akhiri jabatan ' + button.dataset.role + '?',
                    text: button.dataset.person + ' tidak lagi memegang jabatan ini. Riwayatnya tetap tersimpan.',
                    confirmText: 'Ya, Akhiri',
                    successText: 'Jabatan berhasil diakhiri.',
                });
            });
        });

        document.querySelectorAll('[data-membership-revoke]').forEach(function (button) {
            button.addEventListener('click', function () {
                askReasonAndPost({
                    url: button.dataset.membershipRevoke,
                    title: 'Keluarkan ' + button.dataset.person + ' dari panitia?',
                    text: 'Semua jabatannya berakhir dan aksesnya ke event ini langsung ditutup.',
                    confirmText: 'Ya, Keluarkan',
                    successText: 'Keanggotaan berhasil dicabut.',
                });
            });
        });

        document.querySelectorAll('[data-membership-confirm]').forEach(function (button) {
            button.addEventListener('click', function () {
                OrganizerHttp.confirm({
                    title: 'Konfirmasi ' + button.dataset.person + ' sudah bersedia?',
                    text: 'Gunakan bila ia sudah menyatakan bersedia secara langsung. Konfirmasi tercatat atas nama Anda.',
                    confirmText: 'Ya, Konfirmasi',
                }).then(function (isConfirmed) {
                    if (!isConfirmed) {
                        return;
                    }

                    OrganizerHttp.request({ url: button.dataset.membershipConfirm, method: 'POST' }).then(function () {
                        return OrganizerHttp.showSuccess('Keanggotaan berhasil dikonfirmasi.');
                    }).then(reload).catch(OrganizerHttp.showError);
                });
            });
        });
    };

    var bindDivisions = function () {
        var modalElement = document.getElementById('kt_modal_division');

        if (!modalElement) {
            return;
        }

        var form = modalElement.querySelector('form');
        var modal = new bootstrap.Modal(modalElement);
        var submitUrl = null;
        var parentSelect = form.elements.parent_id;

        var open = function (title, url, division) {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);
            submitUrl = url;
            modalElement.querySelector('[data-division-title]').textContent = title;

            Array.from(parentSelect.options).forEach(function (option) {
                option.disabled = division !== null && option.value === String(division.id);
            });

            $(parentSelect).val(division && division.parent_id ? String(division.parent_id) : '').trigger('change');

            if (division) {
                ['name', 'description', 'responsibilities', 'sort_order'].forEach(function (name) {
                    form.elements[name].value = division[name] === null ? '' : division[name];
                });
            }

            modal.show();
        };

        document.querySelector('[data-division-add]').addEventListener('click', function () {
            open('Tambah Seksi', modalElement.dataset.url, null);
        });

        document.querySelectorAll('[data-division-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                OrganizerHttp.request({ url: button.dataset.divisionEdit }).then(function (division) {
                    open('Ubah Seksi', button.dataset.updateUrl, division);
                }).catch(OrganizerHttp.showError);
            });
        });

        document.querySelectorAll('[data-division-delete]').forEach(function (button) {
            button.addEventListener('click', function () {
                OrganizerHttp.confirm({
                    title: 'Hapus ' + button.dataset.name + '?',
                    text: 'Seksi yang pernah memiliki panitia atau masih memiliki sub-seksi tidak dapat dihapus.',
                    confirmText: 'Ya, Hapus',
                }).then(function (isConfirmed) {
                    if (!isConfirmed) {
                        return;
                    }

                    OrganizerHttp.request({ url: button.dataset.divisionDelete, method: 'POST' }).then(function () {
                        return OrganizerHttp.showSuccess('Seksi berhasil dihapus.');
                    }).then(reload).catch(OrganizerHttp.showError);
                });
            });
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-division-submit]'), { url: submitUrl }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Seksi berhasil disimpan.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    var bindTemplates = function () {
        var modalElement = document.getElementById('kt_modal_division_templates');
        var openButton = document.querySelector('[data-division-templates]');

        if (!modalElement || !openButton) {
            return;
        }

        var form = modalElement.querySelector('form');
        var modal = new bootstrap.Modal(modalElement);

        openButton.addEventListener('click', function () {
            OrganizerHttp.clearFieldErrors(form);
            modal.show();
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-division-templates-submit]'), { url: modalElement.dataset.url }).then(function (data) {
                modal.hide();

                return OrganizerHttp.showSuccess(data.created.length
                    ? data.created.length + ' seksi berhasil dibuat.'
                    : 'Semua seksi yang dipilih sudah ada.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    /**
     * Buka/tutup semua cabang tree view.
     */
    var bindTree = function () {
        var tree = document.querySelector('[data-committee-tree]');

        document.querySelectorAll('[data-tree-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var open = button.dataset.treeToggle === 'open';

                tree.querySelectorAll('details').forEach(function (node) {
                    // Rincian tanggung jawab tetap tertutup kecuali dibuka sendiri.
                    if (node.querySelector(':scope > summary > .ui-tree-caret')) {
                        node.open = open;
                    }
                });
            });
        });

        // Tombol di baris judul cabang menjalankan aksinya tanpa membuka/menutup cabang.
        tree.querySelectorAll('summary button').forEach(function (button) {
            button.addEventListener('click', function (clickEvent) {
                clickEvent.preventDefault();
            });
        });

    };

    return {
        init: function () {
            bindTree();
            bindAssignment();
            bindAssignmentEdit();
            bindMembershipActions();
            bindDivisions();
            bindTemplates();
        },
    };
})();

jQuery(document).ready(function () {
    KTCommittee.init();
});
