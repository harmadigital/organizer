"use strict";

var KTCommitteeRole = (function () {
    var abilityBoxes = function (form) {
        return Array.from(form.querySelectorAll('input[name="abilities[]"]'));
    };

    var updateAbilityCount = function (form) {
        var selected = abilityBoxes(form).filter(function (box) {
            return box.checked;
        }).length;

        form.querySelector('[data-role-ability-count]').textContent = selected + ' dipilih';
    };

    /**
     * Jabatan pengawas tidak boleh diberi hak mengubah pekerjaan panitia; server menolaknya juga.
     */
    var applySupervisorLimits = function (form, isSupervisory) {
        form.querySelector('[data-role-supervisor-notice]').classList.toggle('d-none', !isSupervisory);

        abilityBoxes(form).forEach(function (box) {
            var isBlocked = isSupervisory && box.dataset.supervisorAllowed !== '1';

            box.disabled = isBlocked;

            if (isBlocked) {
                box.checked = false;
            }
        });
    };

    var crud = OrganizerMasterCrud.create({
        resource: 'master/committee-role',
        entity: 'Jabatan',
        order: [],
        columns: [
            { data: 'name' },
            { data: 'kind_label', orderable: false, searchable: false },
            { data: 'scope_label', orderable: false, searchable: false },
            {
                data: 'is_required_default',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    var badges = [];

                    if (OrganizerMasterCrud.isTruthy(row.is_required_default)) {
                        badges.push('<span class="badge badge-light-warning me-1">Wajib ada</span>');
                    }

                    if (OrganizerMasterCrud.isTruthy(row.is_singleton_default)) {
                        badges.push('<span class="badge badge-light-info">Satu pemegang utama</span>');
                    }

                    return badges.join('') || '<span class="text-muted">-</span>';
                },
            },
            {
                data: 'abilities_count',
                searchable: false,
                render: function (data) {
                    return Number(data) + ' hak akses';
                },
            },
        ],

        isDeletable: function (row) {
            return !OrganizerMasterCrud.isTruthy(row.is_built_in);
        },

        onReset: function (form) {
            var scope = form.elements.scope;

            scope.disabled = false;
            form.querySelector('[data-role-scope-hint]').textContent
                = 'Menentukan apakah hak akses berlaku untuk seluruh event atau hanya seksi yang ditugaskan.';
            applySupervisorLimits(form, false);
            updateAbilityCount(form);
        },

        onFill: function (record, form) {
            var scope = form.elements.scope;

            scope.disabled = record.is_built_in;

            if (record.is_built_in) {
                form.querySelector('[data-role-scope-hint]').textContent = 'Lingkup jabatan bawaan tidak dapat diubah.';
            }

            abilityBoxes(form).forEach(function (box) {
                box.checked = record.abilities.indexOf(box.value) !== -1;
            });

            applySupervisorLimits(form, record.is_supervisory);
            updateAbilityCount(form);
        },
    });

    return {
        init: function () {
            crud.init();

            document.querySelector('#kt_modal_master [data-role-abilities]').addEventListener('change', function (event) {
                updateAbilityCount(event.target.form);
            });
        },
    };
})();

jQuery(document).ready(function () {
    KTCommitteeRole.init();
});
