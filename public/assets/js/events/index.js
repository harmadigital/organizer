"use strict";

var KTEvents = (function () {
    var tableElement = document.getElementById('kt_datatable_events');
    var searchInput = document.querySelector('[data-events-search]');
    var filters = document.querySelectorAll('[data-events-filter]');
    var dataTable = null;

    var initTable = function () {
        if (!tableElement) {
            return;
        }

        dataTable = $(tableElement).DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            info: false,
            lengthChange: false,
            order: [],
            language: {
                processing: '<span class="spinner-border spinner-border-sm align-middle me-2"></span>Memuat data...',
                emptyTable: 'Belum ada event.',
                zeroRecords: 'Tidak ditemukan event yang cocok dengan pencarian atau filter.',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
            },
            ajax: {
                url: base_url + '/events/data',
                data: function (params) {
                    params.search = searchInput ? searchInput.value : '';
                    filters.forEach(function (filter) {
                        params[filter.dataset.eventsFilter] = filter.value;
                    });
                },
                error: function (xhr) {
                    OrganizerHttp.showError({ status: xhr.status, message: OrganizerHttp.messageFor(xhr.status) });
                },
            },
            columns: [
                {
                    data: 'name',
                    render: function (data, type, row) {
                        return '<a href="' + row.url + '" class="text-gray-800 text-hover-primary fw-bold">' + data + '</a>'
                            + '<div class="text-muted fs-7">' + row.code + '</div>';
                    },
                },
                { data: 'category_name', orderable: false, render: function (data) { return data || '<span class="text-muted">-</span>'; } },
                { data: 'starts_on', render: function (data, type, row) { return row.date_range; } },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        return '<span class="badge badge-light-' + row.status_tone + '">' + row.status_label + '</span>';
                    },
                },
                { data: 'phase', orderable: false, render: function (data, type, row) { return row.phase_label; } },
                { data: 'active_members_count', searchable: false, render: function (data) { return Number(data) + ' orang'; } },
                {
                    data: 'url',
                    orderable: false,
                    className: 'text-end',
                    render: function (data) {
                        return '<a href="' + data + '" class="btn btn-sm btn-light">Buka</a>';
                    },
                },
            ],
        });
    };

    var bindFilters = function () {
        if (!dataTable) {
            return;
        }

        searchInput.addEventListener('keyup', delay(function () {
            dataTable.ajax.reload();
        }, 400));

        filters.forEach(function (filter) {
            $(filter).on('change', function () {
                dataTable.ajax.reload();
            });
        });
    };

    var bindInvitations = function () {
        document.querySelectorAll('[data-invitation-response]').forEach(function (button) {
            button.addEventListener('click', function () {
                var accepts = button.dataset.invitationResponse === 'accept';

                OrganizerHttp.confirm({
                    title: accepts ? 'Terima undangan kepanitiaan?' : 'Tolak undangan kepanitiaan?',
                    text: accepts ? 'Anda akan tercatat sebagai panitia dan dapat membuka event ini.' : 'Jabatan yang ditawarkan kepada Anda akan dibatalkan.',
                    confirmText: accepts ? 'Ya, Terima' : 'Ya, Tolak',
                }).then(function (isConfirmed) {
                    if (!isConfirmed) {
                        return;
                    }

                    OrganizerHttp.request({
                        url: base_url + '/events/' + button.dataset.eventId + '/invitation/' + button.dataset.invitationResponse,
                        method: 'POST',
                    }).then(function (data) {
                        window.location.href = data.url || window.location.href;
                    }).catch(OrganizerHttp.showError);
                });
            });
        });
    };

    var bindCreate = function () {
        var createButton = document.querySelector('[data-event-create]');

        if (!createButton || !EventForm) {
            return;
        }

        createButton.addEventListener('click', function () {
            EventForm.openForCreate(base_url + '/events/insert', function (data) {
                window.location.href = data.url;
            });
        });
    };

    return {
        init: function () {
            initTable();
            bindFilters();
            bindInvitations();
            bindCreate();
        },
    };
})();

jQuery(document).ready(function () {
    KTEvents.init();
});
