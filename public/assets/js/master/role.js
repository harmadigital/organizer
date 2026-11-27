"use strict";

/**
 * Pemilih permission: pilih semua, per modul, pencarian, dan ringkasan jumlah terpilih.
 * Permission yang tidak dimiliki pengguna ditampilkan tetapi tidak dapat dipilih.
 */
var RolePermissionPicker = function (element) {
    var checkboxes = function () {
        return Array.from(element.querySelectorAll('[data-permission-checkbox]'));
    };
    var selectable = function () {
        return checkboxes().filter(function (checkbox) {
            return !checkbox.disabled;
        });
    };
    var modules = function () {
        return Array.from(element.querySelectorAll('[data-permission-module]'));
    };

    var update = function () {
        var selectedCount = checkboxes().filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        element.querySelector('[data-permission-summary]').textContent = 'Dipilih ' + selectedCount + ' dari ' + checkboxes().length + ' permission';

        checkboxes().forEach(function (checkbox) {
            var option = checkbox.closest('.ui-permission-option');

            option.classList.toggle('border-primary', checkbox.checked);
            option.classList.toggle('bg-light-primary', checkbox.checked);
        });

        modules().forEach(function (module) {
            var moduleBoxes = Array.from(module.querySelectorAll('[data-permission-checkbox]'));
            var selectedInModule = moduleBoxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;
            var moduleCheckbox = module.querySelector('[data-module-checkbox]');

            moduleCheckbox.checked = selectedInModule === moduleBoxes.length;
            moduleCheckbox.indeterminate = selectedInModule > 0 && selectedInModule < moduleBoxes.length;
            moduleCheckbox.disabled = moduleBoxes.every(function (checkbox) {
                return checkbox.disabled;
            });
            module.querySelector('[data-module-summary]').textContent = selectedInModule + '/' + moduleBoxes.length;
        });
    };

    var setSelected = function (permissions) {
        var selected = new Set(permissions || []);

        checkboxes().forEach(function (checkbox) {
            checkbox.checked = selected.has(checkbox.value);
        });

        update();
    };

    element.querySelector('[data-select-all]').addEventListener('click', function () {
        selectable().forEach(function (checkbox) {
            checkbox.checked = true;
        });
        update();
    });

    element.querySelector('[data-clear-all]').addEventListener('click', function () {
        selectable().forEach(function (checkbox) {
            checkbox.checked = false;
        });
        update();
    });

    element.querySelector('[data-permission-search]').addEventListener('input', function (event) {
        var search = event.target.value.toLowerCase().trim();

        modules().forEach(function (module) {
            var hasVisibleItem = false;

            module.querySelectorAll('[data-permission-item]').forEach(function (item) {
                var isVisible = item.dataset.search.indexOf(search) !== -1;

                item.classList.toggle('d-none', !isVisible);
                hasVisibleItem = hasVisibleItem || isVisible;
            });

            module.classList.toggle('d-none', !hasVisibleItem);
        });
    });

    modules().forEach(function (module) {
        var moduleCheckbox = module.querySelector('[data-module-checkbox]');
        var modulePermissions = module.querySelector('[data-module-permissions]');
        var moduleIcon = module.querySelector('[data-module-icon]');

        moduleCheckbox.addEventListener('click', function (event) {
            event.stopPropagation();

            module.querySelectorAll('[data-permission-checkbox]').forEach(function (checkbox) {
                if (!checkbox.disabled) {
                    checkbox.checked = moduleCheckbox.checked;
                }
            });
            update();
        });

        module.querySelector('[data-module-toggle]').addEventListener('click', function () {
            var isExpanded = modulePermissions.classList.toggle('d-none') === false;

            this.setAttribute('aria-expanded', isExpanded);
            moduleIcon.classList.toggle('rotate-180', !isExpanded);
        });
    });

    checkboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', update);
    });

    return {
        setSelected: setSelected,
        reset: function () {
            setSelected([]);
        },
    };
};

var KTRole = (function () {
    var form = document.querySelector('#kt_modal_master form');
    var picker = RolePermissionPicker(form.querySelector('[data-permission-picker]'));
    var fieldset = form.querySelector('[data-role-fieldset]');
    var nameInput = form.elements.name;

    var showNotice = function (text) {
        form.querySelector('[data-role-notice]').classList.toggle('d-none', !text);
        form.querySelector('[data-role-notice-text]').textContent = text || '';
    };

    var crud = OrganizerMasterCrud.create({
        resource: 'master/role',
        entity: 'Role',
        statusColumn: false,
        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    return data + (OrganizerMasterCrud.isTruthy(row.is_system)
                        ? ' <span class="badge badge-light-info ms-2">Bawaan sistem</span>'
                        : '');
                },
            },
            {
                data: 'permissions_count',
                searchable: false,
                render: function (data) {
                    return Number(data) + ' permission';
                },
            },
            {
                data: 'users_count',
                searchable: false,
                render: function (data) {
                    return Number(data) ? Number(data) + ' user' : '<span class="text-muted">Belum dipakai</span>';
                },
            },
        ],

        isDeletable: function (row) {
            return OrganizerMasterCrud.isTruthy(row.can_delete);
        },

        onData: function (response) {
            Object.keys(response.summary || {}).forEach(function (key) {
                var card = document.querySelector('[data-role-stat="' + key + '"]');

                if (card) {
                    card.textContent = Number(response.summary[key]).toLocaleString('id-ID');
                }
            });
        },

        onReset: function () {
            fieldset.disabled = false;
            nameInput.readOnly = false;
            form.querySelector('[data-role-name-hint]').classList.add('d-none');
            showNotice(null);
            picker.reset();
        },

        onFill: function (record) {
            picker.setSelected(record.permissions);
            nameInput.readOnly = record.is_system;
            form.querySelector('[data-role-name-hint]').classList.toggle('d-none', !record.is_system);

            if (record.is_locked) {
                fieldset.disabled = true;
                showNotice('Role Super Admin selalu memiliki semua permission dan tidak dapat diubah.');
            } else if (!record.can_manage) {
                fieldset.disabled = true;
                showNotice('Anda tidak dapat mengubah role ini karena hak aksesnya melebihi hak akses Anda.');
            } else if (record.users_count > 0) {
                showNotice('Role ini dipegang oleh ' + record.users_count + ' user. Perubahan permission langsung berlaku bagi mereka.');
            }
        },
    });

    return {
        init: function () {
            crud.init();
        },
    };
})();

jQuery(document).ready(function () {
    KTRole.init();
});
