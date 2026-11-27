"use strict";

/**
 * Form tambah/ubah event (#kt_modal_event) berupa stepper Metronic (KTStepper), lima langkah
 * per kategori, termasuk baris tujuan, sasaran, dan indikator.
 *
 *   EventForm.openForCreate(url, onSaved);
 *   EventForm.openForEdit(showUrl, updateUrl, onSaved);
 *
 * Saat menambah, field wajib di langkah yang sedang dibuka diperiksa sebelum lanjut. Saat
 * mengubah, semua langkah bisa dibuka langsung dan tombol Simpan tersedia di setiap langkah.
 * Bila server menolak, form pindah ke langkah yang memuat field bermasalah pertama.
 */
var EventForm = (function () {
    var modalElement = document.getElementById('kt_modal_event');

    if (!modalElement) {
        return null;
    }

    var stepperElement = modalElement.querySelector('#kt_event_stepper');
    var form = modalElement.querySelector('form');
    var submitButton = form.querySelector('[data-event-form-submit]');
    var objectivesContainer = form.querySelector('[data-objectives]');
    var template = form.querySelector('[data-objective-template]');
    var modal = new bootstrap.Modal(modalElement);
    var stepper = new KTStepper(stepperElement);
    var steps = Array.from(form.querySelectorAll('[data-kt-stepper-element="content"]'));
    var submitUrl = null;
    var afterSave = null;
    var isEditing = false;

    var formatNumber = function (input) {
        var digits = input.value.replace(/\D+/g, '');

        input.value = digits ? Number(digits).toLocaleString('id-ID') : '';
    };

    var refreshEmptyState = function () {
        var isEmpty = objectivesContainer.children.length === 0;

        form.querySelector('[data-objectives-empty]').classList.toggle('d-none', !isEmpty);
        form.querySelector('[data-objectives-header]').classList.toggle('d-md-flex', !isEmpty);
    };

    /**
     * Nama field mengikuti urutan baris agar PHP menerima objectives[0][type], objectives[1][type], dan seterusnya.
     */
    var renumberObjectives = function () {
        objectivesContainer.querySelectorAll('[data-objective-row]').forEach(function (row, index) {
            row.querySelectorAll('[data-objective-field]').forEach(function (field) {
                field.name = 'objectives[' + index + '][' + field.dataset.objectiveField + ']';
            });
        });
        refreshEmptyState();
    };

    var addObjective = function (objective) {
        var row = template.content.firstElementChild.cloneNode(true);

        if (objective) {
            row.querySelector('[data-objective-field="type"]').value = objective.type;
            row.querySelector('[data-objective-field="description"]').value = objective.description || '';
            row.querySelector('[data-objective-field="target"]').value = objective.target || '';
        }

        objectivesContainer.appendChild(row);
        OrganizerSelect.init(row);
        renumberObjectives();
    };

    /**
     * Field wajib di satu langkah, dengan pesan yang sama seperti pesan server.
     *
     * @return {Object} errors per nama field; kosong bila langkah ini lengkap
     */
    var errorsInStep = function (stepIndex) {
        var errors = {};

        steps[stepIndex].querySelectorAll('[data-step-required]').forEach(function (field) {
            if (!field.value.trim()) {
                errors[field.name] = ['Kolom ' + field.dataset.stepLabel + ' wajib diisi.'];
            }
        });

        var startsOn = form.elements.starts_on.value;
        var endsOn = form.elements.ends_on.value;

        if (steps[stepIndex].contains(form.elements.ends_on) && startsOn && endsOn && endsOn < startsOn) {
            errors.ends_on = ['Tanggal selesai tidak boleh sebelum tanggal mulai.'];
        }

        return errors;
    };

    var goToStepOfFirstError = function () {
        var firstError = form.querySelector('[data-organizer-error]');
        var step = firstError ? firstError.closest('[data-kt-stepper-element="content"]') : null;

        if (step) {
            stepper.goTo(steps.indexOf(step) + 1);
        }
    };

    var reset = function () {
        form.reset();
        OrganizerHttp.clearFieldErrors(form);
        OrganizerDatePicker.clearAll(form);
        objectivesContainer.replaceChildren();
        form.querySelectorAll('select[data-control="select2"]').forEach(function (select) {
            $(select).val('').trigger('change');
        });
        form.querySelectorAll('select[data-select-remote]').forEach(OrganizerSelect.reset);
        refreshEmptyState();
        stepper.goFirst();
    };

    var fill = function (event) {
        Object.keys(event).forEach(function (name) {
            var field = form.elements[name];

            if (!field || field instanceof RadioNodeList) {
                return;
            }

            var value = event[name] === null || event[name] === undefined ? '' : String(event[name]);

            if (field.hasAttribute('data-date-picker')) {
                OrganizerDatePicker.setValue(field, value);
            } else if (field.hasAttribute('data-select-remote')) {
                OrganizerSelect.setValue(field, value, event[name + '_label']);
            } else if (field.tagName === 'SELECT') {
                $(field).val(value).trigger('change');
            } else {
                field.value = value;
            }
        });

        form.querySelectorAll('[data-number-input]').forEach(formatNumber);
        (event.objectives || []).forEach(addObjective);
    };

    var open = function (title, editing) {
        isEditing = editing;
        stepperElement.classList.toggle('ui-stepper--editing', editing);
        modalElement.querySelector('[data-event-form-title]').textContent = title;
        modal.show();
    };

    stepper.on('kt.stepper.next', function () {
        var errors = errorsInStep(stepper.getCurrentStepIndex() - 1);

        OrganizerHttp.applyFieldErrors(form, errors);

        if (Object.keys(errors).length === 0) {
            stepper.goNext();
        }
    });

    stepper.on('kt.stepper.previous', function () {
        stepper.goPrevious();
    });

    /**
     * Saat mengubah, langkah mana pun boleh dibuka. Saat menambah, hanya langkah yang sudah
     * dilewati atau langkah berikutnya bila langkah sekarang sudah lengkap.
     */
    stepper.on('kt.stepper.click', function () {
        var target = stepper.getClickedStepIndex();
        var current = stepper.getCurrentStepIndex();

        if (isEditing || target <= current) {
            stepper.goTo(target);

            return;
        }

        for (var step = current; step < target; step++) {
            var errors = errorsInStep(step - 1);

            if (Object.keys(errors).length > 0) {
                stepper.goTo(step);
                OrganizerHttp.applyFieldErrors(form, errors);

                return;
            }
        }

        stepper.goTo(target);
    });

    form.querySelector('[data-objective-add]').addEventListener('click', function () {
        addObjective(null);
    });

    objectivesContainer.addEventListener('click', function (clickEvent) {
        var removeButton = clickEvent.target.closest('[data-objective-remove]');

        if (removeButton) {
            removeButton.closest('[data-objective-row]').remove();
            renumberObjectives();
        }
    });

    form.querySelectorAll('[data-number-input]').forEach(function (input) {
        input.addEventListener('blur', function () {
            formatNumber(input);
        });
    });

    var save = function () {
        renumberObjectives();

        OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function (data) {
            modal.hide();

            return OrganizerHttp.showSuccess('Event berhasil disimpan.').then(function () {
                if (afterSave) {
                    afterSave(data);
                }
            });
        }).catch(goToStepOfFirstError);
    };

    // KTStepper menahan klik tombol Simpan dan memancarkan event ini sebagai gantinya.
    stepper.on('kt.stepper.submit', save);

    form.addEventListener('submit', function (submitEvent) {
        submitEvent.preventDefault();

        // Enter di langkah tengah saat menambah berarti "lanjut", bukan "simpan".
        if (!isEditing && stepper.getCurrentStepIndex() < steps.length) {
            stepper.goNext();

            return;
        }

        save();
    });

    return {
        openForCreate: function (url, onSaved) {
            reset();
            submitUrl = url;
            afterSave = onSaved;
            addObjective({ type: 'goal', description: '', target: '' });
            open('Tambah Event', false);
        },
        openForEdit: function (showUrl, updateUrl, onSaved) {
            OrganizerHttp.request({ url: showUrl }).then(function (event) {
                reset();
                submitUrl = updateUrl;
                afterSave = onSaved;
                fill(event);
                open('Ubah Event', true);
            }).catch(OrganizerHttp.showError);
        },
    };
})();
