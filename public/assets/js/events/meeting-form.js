"use strict";

/**
 * Form jadwal rapat (#kt_modal_meeting), dipakai di daftar rapat dan halaman detail rapat.
 *
 *   MeetingForm.openForCreate(insertUrl, onSaved);
 *   MeetingForm.openForEdit(showUrl, updateUrl, onSaved);
 */
var MeetingForm = (function () {
    var modalElement = document.getElementById('kt_modal_meeting');

    if (!modalElement) {
        return null;
    }

    var form = modalElement.querySelector('form');
    var submitButton = form.querySelector('[data-meeting-form-submit]');
    var modal = new bootstrap.Modal(modalElement);
    var submitUrl = null;
    var afterSave = null;

    var reset = function () {
        form.reset();
        OrganizerHttp.clearFieldErrors(form);
        OrganizerDatePicker.clearAll(form);
        $(form.elements.division_id).val('').trigger('change');
    };

    var open = function (title) {
        modalElement.querySelector('[data-meeting-form-title]').textContent = title;
        modal.show();
    };

    modalElement.addEventListener('shown.bs.modal', function () {
        form.elements.title.focus();
    });

    form.addEventListener('submit', function (submitEvent) {
        submitEvent.preventDefault();

        OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function (data) {
            modal.hide();

            return OrganizerHttp.showSuccess('Rapat tersimpan dan terlihat oleh semua panitia.').then(function () {
                if (afterSave) {
                    afterSave(data);
                }
            });
        }).catch(function () {});
    });

    return {
        openForCreate: function (url, onSaved) {
            reset();
            submitUrl = url;
            afterSave = onSaved;
            open('Jadwalkan Rapat');
        },
        openForEdit: function (showUrl, updateUrl, onSaved) {
            OrganizerHttp.request({ url: showUrl }).then(function (meeting) {
                reset();
                submitUrl = updateUrl;
                afterSave = onSaved;
                form.elements.title.value = meeting.title;
                form.elements.location.value = meeting.location || '';
                form.elements.agenda.value = meeting.agenda || '';
                $(form.elements.division_id).val(meeting.division_id === null ? '' : String(meeting.division_id)).trigger('change');
                OrganizerDatePicker.setValue(form.elements.meets_on, meeting.meets_on);
                OrganizerDatePicker.setValue(form.elements.starts_at, meeting.starts_at);
                OrganizerDatePicker.setValue(form.elements.ends_at, meeting.ends_at || '');
                open('Ubah Rapat');
            }).catch(OrganizerHttp.showError);
        },
    };
})();
