"use strict";

/**
 * Tab Kalender event: hari pelaksanaan, rapat, tenggat tugas, dan kegiatan panitia.
 *
 *   - Saring per seksi (Pengurus Inti = "none").
 *   - Tambah Kegiatan (tombol, atau klik tanggal kosong di tampilan Bulan): judul, tanggal,
 *     jam, tempat; seksi, sampai tanggal, dan keterangan di "Pengaturan lainnya".
 *   - Klik jadwal apa pun membuka ringkasannya dulu. Rapat, tugas, dan hari pelaksanaan punya
 *     tombol "Buka halaman ..."; kegiatan punya Ubah dan Hapus bila boleh mengubahnya.
 *
 * Teks dari server dipasang lewat textContent.
 */
(function () {
    var page = document.querySelector('[data-calendar-page]');

    if (!page) {
        return;
    }

    var divisionFilter = page.querySelector('[data-calendar-division]');
    var detailElement = document.getElementById('kt_modal_calendar_item');
    var detailModal = new bootstrap.Modal(detailElement);
    var currentItem = null;
    var returnFocus = null;

    var EntryForm = (function () {
        var modalElement = document.getElementById('kt_modal_calendar_entry');

        if (!modalElement) {
            return null;
        }

        var form = modalElement.querySelector('form');
        var submitButton = form.querySelector('[data-entry-form-submit]');
        var modal = new bootstrap.Modal(modalElement);
        var division = form.elements.division_id;
        var advanced = form.querySelector('[data-entry-advanced]');
        var advancedToggle = form.querySelector('[data-entry-advanced-toggle]');
        var submitUrl = null;
        var successMessage = null;

        var showAdvanced = function (isShown) {
            var icon = form.querySelector('[data-entry-advanced-icon]');

            advanced.classList.toggle('d-none', !isShown);
            advancedToggle.setAttribute('aria-expanded', isShown ? 'true' : 'false');
            form.querySelector('[data-entry-advanced-label]').textContent = isShown ? 'Sembunyikan pengaturan lainnya' : 'Pengaturan lainnya';
            icon.classList.toggle('ki-up', isShown);
            icon.classList.toggle('ki-down', !isShown);
        };

        advancedToggle.addEventListener('click', function () {
            showAdvanced(advanced.classList.contains('d-none'));
        });

        /**
         * Kegiatan beberapa hari dicatat seharian: jam disembunyikan dan dikosongkan selama
         * "Sampai tanggal" lebih akhir dari "Tanggal".
         */
        var refreshTimeFields = function () {
            var startsOn = form.elements.starts_on.value;
            var endsOn = form.elements.ends_on.value;
            var isMultiDay = Boolean(startsOn && endsOn && endsOn > startsOn);

            form.querySelectorAll('[data-entry-time-row]').forEach(function (row) {
                row.classList.toggle('d-none', isMultiDay);
            });
            form.querySelector('[data-entry-time-help]').classList.toggle('d-none', isMultiDay);
            form.querySelector('[data-entry-multi-day-help]').classList.toggle('d-none', !isMultiDay);

            if (isMultiDay) {
                OrganizerDatePicker.setValue(form.elements.starts_at, '');
                OrganizerDatePicker.setValue(form.elements.ends_at, '');
            }
        };

        form.elements.starts_on.addEventListener('change', refreshTimeFields);
        form.elements.ends_on.addEventListener('change', refreshTimeFields);

        var reset = function () {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);
            OrganizerDatePicker.clearAll(form);
            OrganizerSelect.setValue(division, null);
            showAdvanced(false);
        };

        var open = function (title) {
            modalElement.querySelector('[data-entry-form-title]').textContent = title;
            refreshTimeFields();
            modal.show();
        };

        modalElement.addEventListener('shown.bs.modal', function () {
            form.elements.title.focus();
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function () {
                modal.hide();
                calendar.refetch();

                return OrganizerHttp.showSuccess(successMessage);
            }).catch(function () {
                // Error di bagian yang tersembunyi tetap harus terlihat.
                if (advanced.querySelector('.is-invalid')) {
                    showAdvanced(true);
                }
            });
        });

        return {
            openForCreate: function (dateString) {
                reset();
                submitUrl = page.dataset.insertUrl;
                successMessage = 'Kegiatan tersimpan dan terlihat oleh semua panitia.';
                OrganizerSelect.setValue(division, division.dataset.default || null);
                OrganizerDatePicker.setValue(form.elements.starts_on, dateString || '');
                open('Tambah Kegiatan');
            },
            openForEdit: function (entry) {
                OrganizerHttp.request({ url: entry.show_url }).then(function (data) {
                    reset();
                    submitUrl = entry.update_url;
                    successMessage = 'Perubahan kegiatan tersimpan.';
                    form.elements.title.value = data.title;
                    form.elements.location.value = data.location || '';
                    form.elements.notes.value = data.notes || '';
                    OrganizerSelect.setValue(division, data.division_id);
                    OrganizerDatePicker.setValue(form.elements.starts_on, data.starts_on);
                    OrganizerDatePicker.setValue(form.elements.ends_on, data.ends_on || '');
                    OrganizerDatePicker.setValue(form.elements.starts_at, data.starts_at || '');
                    OrganizerDatePicker.setValue(form.elements.ends_at, data.ends_at || '');
                    showAdvanced(Boolean(data.ends_on || data.notes || String(data.division_id || '') !== String(division.dataset.default || '')));
                    open('Ubah Kegiatan');
                }).catch(OrganizerHttp.showError);
            },
        };
    })();

    var showItem = function (item, itemElement) {
        var canChange = Boolean(item.entry && item.entry.can_change);

        currentItem = item;
        returnFocus = itemElement || null;
        OrganizerCalendar.fillItemDetail(detailElement, item);
        detailElement.querySelector('[data-item-edit]').classList.toggle('d-none', !canChange || !EntryForm);
        detailElement.querySelector('[data-item-delete]').classList.toggle('d-none', !canChange);
        detailModal.show();
    };

    var calendar = OrganizerCalendar.create(page.querySelector('[data-calendar]'), {
        feedUrl: page.dataset.feedUrl,
        today: page.dataset.today,
        storageKey: 'organizer.calendar.view',
        emptyNote: page.querySelector('[data-calendar-empty]'),
        params: function () {
            return divisionFilter.value ? 'division=' + encodeURIComponent(divisionFilter.value) : '';
        },
        onItemClick: showItem,
        onDateClick: EntryForm ? EntryForm.openForCreate : null,
    });

    $(divisionFilter).on('change', function () {
        calendar.refetch();
    });

    var addButton = page.querySelector('[data-entry-add]');

    if (addButton && EntryForm) {
        addButton.addEventListener('click', function () {
            EntryForm.openForCreate(null);
        });
    }

    // Setelah ditutup, fokus kembali ke jadwal yang dibuka, kecuali form Ubah atau hapus
    // sudah memindahkannya.
    detailElement.addEventListener('hidden.bs.modal', function () {
        if (returnFocus && document.body.contains(returnFocus)) {
            returnFocus.focus();
        }

        returnFocus = null;
    });

    detailElement.querySelector('[data-item-edit]').addEventListener('click', function () {
        var entry = currentItem.entry;

        returnFocus = null;
        detailModal.hide();
        EntryForm.openForEdit(entry);
    });

    detailElement.querySelector('[data-item-delete]').addEventListener('click', function () {
        var entry = currentItem.entry;

        OrganizerHttp.confirm({
            title: 'Hapus kegiatan ini?',
            text: '"' + currentItem.title + '" akan hilang dari kalender semua panitia.',
            confirmText: 'Ya, hapus',
        }).then(function (confirmed) {
            if (!confirmed) {
                return null;
            }

            return OrganizerHttp.request({ url: entry.delete_url, method: 'POST' }).then(function () {
                returnFocus = null;
                detailModal.hide();
                calendar.refetch();

                return OrganizerHttp.showSuccess('Kegiatan dihapus.');
            }).catch(OrganizerHttp.showError);
        });
    });
})();
