"use strict";

/**
 * Perilaku bersama halaman daftar + modal (pola docs/ui-ux_brief.md bagian 6):
 * tabel DataTables server-side, pencarian, filter, tambah/ubah lewat satu modal,
 * dan hapus dengan konfirmasi. Setiap halaman cukup mendeskripsikan kolom dan
 * hal yang khas baginya.
 *
 *   var KTVenue = OrganizerMasterCrud.create({
 *       resource: 'master/venue',
 *       entity: 'Venue',
 *       columns: [{ data: 'name' }, { data: 'address' }, { data: 'capacity' }],
 *   });
 *
 * Opsi lain: `order` (urutan awal; [] berarti mengikuti urutan dari server), `statusColumn` (false bila tanpa kolom status),
 * `isDeletable(row)`, `beforeOpen(record, form)`, `onFill(record, form)`, `onReset(form)`,
 * dan `onData(response)` untuk memakai data tambahan dari endpoint `data`.
 *   jQuery(document).ready(function () { KTVenue.init(); });
 *
 * Nilai teks dari endpoint `data` sudah di-escape oleh server (yajra), sehingga
 * aman disisipkan sebagai HTML sel. Nilai dari endpoint `show` tidak di-escape
 * dan hanya boleh dipasang lewat `.value` atau `textContent`.
 */
var OrganizerMasterCrud = (function () {
    var language = {
        processing: '<span class="spinner-border spinner-border-sm align-middle me-2"></span>Memuat data...',
        emptyTable: 'Belum ada data.',
        zeroRecords: 'Tidak ditemukan data yang cocok dengan pencarian atau filter.',
        paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
    };

    var isTruthy = function (value) {
        return value === true || value === 1 || value === '1';
    };

    var dash = '<span class="text-muted">-</span>';

    var renderers = {
        /** Teks biasa; kosong ditampilkan sebagai tanda hubung. */
        text: function (data) {
            return data === null || data === undefined || data === '' ? dash : data;
        },
        /** Lencana Aktif / Nonaktif dari nilai boolean. */
        activeBadge: function (data) {
            return isTruthy(data)
                ? '<span class="badge badge-light-success">Aktif</span>'
                : '<span class="badge badge-light-danger">Nonaktif</span>';
        },
        /** Angka dengan pemisah ribuan Indonesia. */
        number: function (data) {
            return data === null || data === undefined || data === '' ? dash : Number(data).toLocaleString('id-ID');
        },
    };

    var create = function (config) {
        var tableElement = document.querySelector('#kt_datatable_master');
        var modalElement = document.querySelector('#kt_modal_master');
        var form = modalElement.querySelector('form');
        var submitButton = form.querySelector('[data-master-submit]');
        var modalTitle = modalElement.querySelector('[data-master-modal-title]');
        var searchInput = document.querySelector('[data-master-search]');
        var filterInputs = document.querySelectorAll('[data-master-filter]');
        var addButton = document.querySelector('[data-master-add]');
        var baseUrl = base_url + '/' + config.resource;
        var canUpdate = tableElement.dataset.canUpdate === '1';
        var canDelete = tableElement.dataset.canDelete === '1';
        var modal = new bootstrap.Modal(modalElement);
        var editingId = null;
        var dataTable;

        var statusColumn = config.statusColumn === false ? null : (config.statusColumn || { data: 'is_active', render: renderers.activeBadge });

        var actionsColumn = {
            data: 'id',
            orderable: false,
            searchable: false,
            className: 'text-end',
            render: function (id, type, row) {
                var buttons = '';

                if (canUpdate) {
                    buttons += '<button type="button" class="btn p-1" data-master-edit="' + id + '" title="Ubah" aria-label="Ubah">'
                        + '<i class="las la-edit fs-2"></i></button>';
                }

                if (canDelete && !(config.isDeletable && config.isDeletable(row) === false)) {
                    buttons += '<button type="button" class="btn p-1" data-master-delete="' + id + '" title="Hapus" aria-label="Hapus">'
                        + '<i class="las la-trash fs-2"></i></button>';
                }

                return buttons || dash;
            },
        };

        var numberColumn = {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            },
        };

        var columns = [numberColumn].concat(config.columns.map(function (column) {
            return Object.assign({ render: renderers.text }, column);
        }), statusColumn ? [statusColumn, actionsColumn] : [actionsColumn]);

        var hasActiveCriteria = function () {
            return (searchInput && searchInput.value.trim() !== '') || Array.from(filterInputs).some(function (filter) {
                return filter.value !== '';
            });
        };

        /**
         * Pencarian dan filter dikirim sebagai parameter sendiri, sehingga tabel tidak tahu bedanya
         * "belum ada data" dan "tidak ada yang cocok". Pesannya ditentukan di sini.
         */
        var describeEmptyTable = function () {
            var emptyCell = tableElement.querySelector('tbody td.dt-empty');

            if (!emptyCell) {
                return;
            }

            if (hasActiveCriteria()) {
                emptyCell.textContent = language.zeroRecords;
            } else if (addButton) {
                emptyCell.textContent = 'Belum ada data. Klik "' + addButton.textContent.trim() + '" untuk memulai.';
            } else {
                emptyCell.textContent = language.emptyTable;
            }
        };

        var initDatatable = function () {
            dataTable = $(tableElement).DataTable({
                responsive: true,
                searchDelay: 500,
                processing: true,
                serverSide: true,
                info: false,
                lengthChange: false,
                order: config.order || [[1, 'asc']],
                language: language,
                ajax: {
                    url: baseUrl + '/data',
                    type: 'GET',
                    data: function (params) {
                        params.search = searchInput ? searchInput.value : '';

                        filterInputs.forEach(function (filter) {
                            params[filter.dataset.masterFilter] = filter.value;
                        });
                    },
                    dataSrc: function (response) {
                        if (config.onData) {
                            config.onData(response);
                        }

                        return response.data;
                    },
                    error: function (xhr) {
                        OrganizerHttp.showError({ status: xhr.status, message: OrganizerHttp.messageFor(xhr.status) });
                    },
                },
                columns: columns,
                drawCallback: describeEmptyTable,
            });
        };

        var reload = function () {
            dataTable.ajax.reload(null, false);
        };

        var setFieldValue = function (field, value, record) {
            if (field instanceof RadioNodeList) {
                return;
            }

            if (field.hasAttribute('data-select-remote')) {
                // Pilihan remote belum ada di halaman; label dikirim server sebagai <field>_label.
                OrganizerSelect.setValue(field, value, record[field.name + '_label']);
            } else if (field.type === 'checkbox') {
                field.checked = isTruthy(value);
            } else if (field.tagName === 'SELECT') {
                $(field).val(value === null || value === undefined ? '' : String(value)).trigger('change');
            } else {
                field.value = value === null || value === undefined ? '' : value;
            }
        };

        var resetForm = function () {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);

            form.querySelectorAll('select').forEach(function (select) {
                if (select.hasAttribute('data-select-remote')) {
                    OrganizerSelect.reset(select);
                } else {
                    $(select).trigger('change');
                }
            });

            if (config.onReset) {
                config.onReset(form);
            }
        };

        var fillForm = function (record) {
            Object.keys(record).forEach(function (name) {
                var field = form.elements[name];

                if (field) {
                    setFieldValue(field, record[name], record);
                }
            });

            if (config.onFill) {
                config.onFill(record, form);
            }
        };

        /**
         * Bootstrap mengabaikan show() selama animasi menutup masih berjalan, jadi tunggu sampai selesai.
         */
        var showModal = function () {
            var isClosing = !modalElement.classList.contains('show') && modalElement.style.display === 'block';

            if (isClosing) {
                modalElement.addEventListener('hidden.bs.modal', function () {
                    modal.show();
                }, { once: true });

                return;
            }

            modal.show();
        };

        var prepare = function (record) {
            return Promise.resolve(config.beforeOpen ? config.beforeOpen(record, form) : null);
        };

        var openForCreate = function () {
            editingId = null;
            resetForm();
            modalTitle.textContent = 'Tambah ' + config.entity;

            prepare(null).then(showModal).catch(OrganizerHttp.showError);
        };

        var openForEdit = function (id) {
            OrganizerHttp.request({ url: baseUrl + '/show/' + id }).then(function (record) {
                editingId = id;
                resetForm();
                modalTitle.textContent = 'Ubah ' + config.entity;

                return prepare(record).then(function () {
                    fillForm(record);
                    showModal();
                });
            }).catch(OrganizerHttp.showError);
        };

        var remove = function (id) {
            OrganizerHttp.confirm({
                title: 'Hapus ' + config.entity.toLowerCase() + ' ini?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
                confirmText: 'Ya, Hapus',
            }).then(function (isConfirmed) {
                if (!isConfirmed) {
                    return;
                }

                OrganizerHttp.request({ url: baseUrl + '/delete/' + id, method: 'POST' }).then(function () {
                    OrganizerHttp.showSuccess(config.entity + ' berhasil dihapus.');
                    reload();
                }).catch(OrganizerHttp.showError);
            });
        };

        var bindEvents = function () {
            if (searchInput) {
                searchInput.addEventListener('keyup', delay(function () {
                    dataTable.ajax.reload();
                }, 400));
            }

            filterInputs.forEach(function (filter) {
                $(filter).on('change', function () {
                    dataTable.ajax.reload();
                });
            });

            if (addButton) {
                addButton.addEventListener('click', openForCreate);
            }

            tableElement.addEventListener('click', function (event) {
                var editButton = event.target.closest('[data-master-edit]');
                var deleteButton = event.target.closest('[data-master-delete]');

                if (editButton) {
                    openForEdit(editButton.dataset.masterEdit);
                } else if (deleteButton) {
                    remove(deleteButton.dataset.masterDelete);
                }
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var isEditing = editingId !== null;

                OrganizerHttp.submit(form, submitButton, {
                    url: isEditing ? baseUrl + '/update/' + editingId : baseUrl + '/insert',
                }).then(function () {
                    modal.hide();
                    OrganizerHttp.showSuccess(config.entity + (isEditing ? ' berhasil diperbarui.' : ' berhasil ditambahkan.'));
                    reload();
                }).catch(function () {
                    // Pesan dan penanda field sudah ditampilkan oleh OrganizerHttp.submit.
                });
            });
        };

        return {
            init: function () {
                initDatatable();
                bindEvents();
            },
            reload: reload,
        };
    };

    return {
        create: create,
        render: renderers,
        isTruthy: isTruthy,
    };
})();
