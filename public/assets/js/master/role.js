var KTDatatablesContent = {
    initDatatable: function () {
        $('#kt_datatable_role').DataTable({
            responsive: !0,
            searchDelay: 500,
            processing: !0,
            serverSide: !0,
            ajax: {
                url: base_url + '/master/role/data',
                type: 'GET',
                data: function (data) {
                    data.name = $('#search_role_name').val();
                }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'created_at' },
                { data: 'updated_at' },
                { data: 'id' },
            ],
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    },
                },
                {
                    targets: [2, 3],
                    render: function (data) {
                        return moment(data).format('D MMMM YYYY');
                    }
                },
                {
                    targets: -1,
                    width: '75px',
                    data: null,
                    orderable: false,
                    render: function (data) {
                        return `<div class="d-flex flex-row align-items-center">
                            <a href="javascript:void(0)" onclick="KTDataEdit.open(${data});">
                                <button id="button_edit_category" class="btn p-1"><i class="las la-edit fs-2"></i></button>
                            </a>
                            <a href="javascript:void(0)" onclick="delete_role(${data});">
                                <button class="btn p-1"><i class="las la-trash fs-2"></i></button>
                            </a>
                        </div>`;
                    },
                },
            ]
        });
    }
};

const RolePermissionPicker = function (element) {
    const checkboxes = () => Array.from(element.querySelectorAll('[data-permission-checkbox]'));
    const modules = () => Array.from(element.querySelectorAll('[data-permission-module]'));

    const update = function () {
        const selectedCount = checkboxes().filter((checkbox) => checkbox.checked).length;
        element.querySelector('[data-permission-summary]').textContent = `Dipilih ${selectedCount} dari ${checkboxes().length} permission`;

        checkboxes().forEach(function (checkbox) {
            checkbox.closest('.permission-option').classList.toggle('border-primary', checkbox.checked);
            checkbox.closest('.permission-option').classList.toggle('bg-light-primary', checkbox.checked);
        });

        modules().forEach(function (module) {
            const moduleCheckboxes = Array.from(module.querySelectorAll('[data-permission-checkbox]'));
            const selectedModuleCount = moduleCheckboxes.filter((checkbox) => checkbox.checked).length;
            const moduleCheckbox = module.querySelector('[data-module-checkbox]');

            moduleCheckbox.checked = selectedModuleCount === moduleCheckboxes.length;
            moduleCheckbox.indeterminate = selectedModuleCount > 0 && selectedModuleCount < moduleCheckboxes.length;
            module.querySelector('[data-module-summary]').textContent = `${selectedModuleCount}/${moduleCheckboxes.length}`;
        });
    };

    const setSelected = function (permissions) {
        const selectedPermissions = new Set(permissions || []);
        checkboxes().forEach(function (checkbox) {
            checkbox.checked = selectedPermissions.has(checkbox.value);
        });
        update();
    };

    element.querySelector('[data-select-all]').addEventListener('click', function () {
        checkboxes().forEach((checkbox) => checkbox.checked = true);
        update();
    });

    element.querySelector('[data-clear-all]').addEventListener('click', function () {
        setSelected([]);
    });

    element.querySelector('[data-permission-search]').addEventListener('input', function (event) {
        const search = event.target.value.toLowerCase().trim();

        modules().forEach(function (module) {
            const items = Array.from(module.querySelectorAll('[data-permission-item]'));
            let hasVisibleItem = false;

            items.forEach(function (item) {
                const isVisible = item.dataset.search.includes(search);
                item.classList.toggle('d-none', !isVisible);
                hasVisibleItem = hasVisibleItem || isVisible;
            });

            module.classList.toggle('d-none', !hasVisibleItem);
        });
    });

    modules().forEach(function (module) {
        const moduleCheckbox = module.querySelector('[data-module-checkbox]');
        const permissions = Array.from(module.querySelectorAll('[data-permission-checkbox]'));
        const modulePermissions = module.querySelector('[data-module-permissions]');
        const moduleIcon = module.querySelector('[data-module-icon]');

        moduleCheckbox.addEventListener('click', function (event) {
            event.stopPropagation();
            permissions.forEach((checkbox) => checkbox.checked = moduleCheckbox.checked);
            update();
        });

        module.querySelector('[data-module-toggle]').addEventListener('click', function () {
            const isExpanded = modulePermissions.classList.toggle('d-none') === false;
            this.setAttribute('aria-expanded', isExpanded);
            moduleIcon.classList.toggle('rotate-180', !isExpanded);
        });
    });

    checkboxes().forEach((checkbox) => checkbox.addEventListener('change', update));

    return {
        getSelected: () => checkboxes().filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value),
        setSelected: setSelected,
        reset: () => setSelected([]),
    };
};

const buildValidator = function (form, fieldName) {
    return FormValidation.formValidation(form, {
        fields: {
            [fieldName]: {
                validators: {
                    notEmpty: { message: 'Nama Harus Diisi' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap: new FormValidation.plugins.Bootstrap5({
                rowSelector: '.fv-row',
                eleInvalidClass: '',
                eleValidClass: ''
            })
        }
    });
};

const submitRoleForm = function ({ form, submitButton, validator, picker, url, data, successMessage, modal }) {
    submitButton.addEventListener('click', function (event) {
        event.preventDefault();
        validator.validate().then(function (status) {
            const permissions = picker.getSelected();

            if (status !== 'Valid' || permissions.length === 0) {
                Swal.fire('Oops...', 'Nama role dan minimal satu permission wajib diisi.', 'error');
                return;
            }

            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;

            $.ajax({
                type: 'POST',
                url: url(),
                data: Object.assign(data(), { permission: permissions, _token: csrf_token }),
                success: function (response) {
                    if (response.status === true) {
                        Swal.fire({ title: 'Success', text: successMessage, icon: 'success', timer: 1500, showConfirmButton: false }).then(function () {
                            $('#kt_datatable_role').DataTable().ajax.reload();
                            modal.hide();
                            form.reset();
                            picker.reset();
                        });
                        return;
                    }

                    Swal.fire('Oops...', response.data, 'error');
                },
                error: function () {
                    Swal.fire('Error!', 'Terjadi kesalahan saat menyimpan.', 'error');
                },
                complete: function () {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;
                }
            });
        });
    });
};

const KTDataAdd = function () {
    const modalElement = document.getElementById('kt_modal_add_role');
    const form = document.getElementById('kt_docs_form_add_role');
    const submitButton = document.getElementById('kt_docs_form_add_role_submit');
    const modal = new bootstrap.Modal(modalElement);
    const picker = RolePermissionPicker(form.querySelector('[data-permission-picker]'));
    const validator = buildValidator(form, 'name');

    return {
        init: function () {
            submitRoleForm({
                form, submitButton, validator, picker, modal,
                url: () => base_url + '/master/role/insert',
                data: () => ({ name: $('#name').val() }),
                successMessage: 'Data Role Berhasil Ditambahkan!'
            });
        }
    };
}();

const KTDataEdit = function () {
    const modalElement = document.getElementById('kt_modal_edit_role');
    const form = document.getElementById('kt_docs_form_edit_role');
    const submitButton = document.getElementById('kt_docs_form_edit_role_submit');
    const modal = new bootstrap.Modal(modalElement);
    const picker = RolePermissionPicker(form.querySelector('[data-permission-picker]'));
    const validator = buildValidator(form, 'name_edit');

    return {
        init: function () {
            submitRoleForm({
                form, submitButton, validator, picker, modal,
                url: () => base_url + '/master/role/update/' + $('#role_id').val(),
                data: () => ({ name: $('#name_edit').val() }),
                successMessage: 'Data Role Berhasil Diupdate!'
            });
        },
        open: function (id) {
            form.reset();
            picker.reset();

            $.ajax({
                type: 'GET',
                url: base_url + '/master/role/detail/' + id,
                success: function (response) {
                    if (response.status === true) {
                        $('#role_id').val(response.data.detail_role.id);
                        $('#name_edit').val(response.data.detail_role.name);
                        picker.setSelected(response.data.role_permission);
                        modal.show();
                        return;
                    }

                    Swal.fire('Oops...', 'Ada yang salah, silakan coba lagi!', 'error');
                },
                error: function () {
                    Swal.fire('Error', 'Gagal mengambil data.', 'error');
                }
            });
        }
    };
}();

$('#search_role_name').keyup(delay(function () {
    $('#kt_datatable_role').DataTable().ajax.reload();
}, 500));

$('#filter_role_submit').on('click', function () {
    $('#kt_datatable_role').DataTable().ajax.reload();
});

jQuery(document).ready(function () {
    KTDatatablesContent.initDatatable();
    KTDataAdd.init();
    KTDataEdit.init();
});

function delete_role(idRole) {
    swal.fire({
        title: 'Apakah anda yakin?',
        text: 'Data Role akan dihapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        showLoaderOnConfirm: true,
        preConfirm: function () {
            return $.ajax({
                url: base_url + '/master/role/delete/' + idRole,
                type: 'POST',
                data: { _method: 'POST', _token: csrf_token },
                dataType: 'json'
            }).done(function () {
                swal.fire({ title: 'Sukses', text: 'Data Role berhasil di hapus!', timer: 1500, showConfirmButton: false, icon: 'success' }).then(function () {
                    $('#kt_datatable_role').DataTable().ajax.reload();
                });
            }).fail(function () {
                swal.fire('Oops...', 'Something went wrong, please try again !', 'error');
            });
        },
        allowOutsideClick: false
    });
}
