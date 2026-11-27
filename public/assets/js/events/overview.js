"use strict";

var KTEventOverview = (function () {
    var page = document.querySelector('[data-event-page]');

    var reload = function () {
        window.location.reload();
    };

    var bindEdit = function () {
        var editButton = document.querySelector('[data-event-edit]');

        if (!editButton || !EventForm) {
            return;
        }

        editButton.addEventListener('click', function () {
            EventForm.openForEdit(editButton.dataset.eventEdit, editButton.dataset.updateUrl, reload);
        });
    };

    var bindStatus = function () {
        var modalElement = document.getElementById('kt_modal_event_status');

        if (!modalElement) {
            return;
        }

        var form = modalElement.querySelector('form');
        var modal = new bootstrap.Modal(modalElement);
        var reasonLabel = form.querySelector('[data-status-reason-label]');

        document.querySelectorAll('[data-status-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var requiresReason = button.dataset.requiresReason === '1';

                form.reset();
                OrganizerHttp.clearFieldErrors(form);
                form.elements.status.value = button.dataset.statusTarget;
                modalElement.querySelector('[data-status-title]').textContent = button.dataset.statusLabel;
                reasonLabel.textContent = requiresReason ? 'Alasan' : 'Catatan';
                reasonLabel.classList.toggle('required', requiresReason);
                modal.show();
            });
        });

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-status-submit]'), { url: page.dataset.statusUrl }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Status event berhasil diubah.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    var bindPhase = function () {
        var modalElement = document.getElementById('kt_modal_event_phase');

        if (!modalElement) {
            return;
        }

        var form = modalElement.querySelector('form');
        var modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, form.querySelector('[data-phase-submit]'), { url: page.dataset.phaseUrl }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Fase event berhasil diubah.');
            }).then(reload).catch(function () {
                // Pesan sudah ditampilkan oleh OrganizerHttp.submit.
            });
        });
    };

    return {
        init: function () {
            bindEdit();
            bindStatus();
            bindPhase();
        },
    };
})();

jQuery(document).ready(function () {
    KTEventOverview.init();
});
