"use strict";

/**
 * Form tambah/ubah tugas (#kt_modal_task), dipakai halaman Program & Tugas dan halaman detail tugas.
 *
 *   TaskForm.openForCreate(insertUrl, { division_id, work_program_id, work_program_label, parent_id, parent_label, context }, onSaved);
 *   TaskForm.openForEdit(showUrl, updateUrl, onSaved);
 *
 * Yang terlihat hanya judul, seksi, penanggung jawab, dan tenggat; sisanya di "Pengaturan
 * lainnya", yang terbuka sendiri saat mengubah tugas yang memakainya. Tugas dalam program
 * mengikuti seksi programnya, dan subtugas mengikuti induknya, jadi pilihan itu dikunci.
 */
var TaskForm = (function () {
    var modalElement = document.getElementById('kt_modal_task');

    if (!modalElement) {
        return null;
    }

    var form = modalElement.querySelector('form');
    var submitButton = form.querySelector('[data-task-form-submit]');
    var modal = new bootstrap.Modal(modalElement);
    var division = form.elements.division_id;
    var program = form.elements.work_program_id;
    var parent = form.elements.parent_id;
    var owner = form.elements.owner_membership_id;
    var assignees = form.querySelector('[name="assignee_ids[]"]');
    var submitUrl = null;
    var afterSave = null;
    var isFilling = false;

    var refreshProgramScope = function () {
        OrganizerSelect.setParams(program, division.value ? { division_id: division.value } : {});
    };

    /**
     * Saat tugas induk dipilih, seksi dan program dikunci karena mengikuti induknya.
     */
    var refreshParentLock = function () {
        var hasParent = Boolean(parent.value);

        $(division).prop('disabled', hasParent || Boolean(program.value));
        $(program).prop('disabled', hasParent);
    };

    var advanced = form.querySelector('[data-task-advanced]');
    var advancedToggle = form.querySelector('[data-task-advanced-toggle]');

    var showAdvanced = function (isShown) {
        advanced.classList.toggle('d-none', !isShown);
        advancedToggle.setAttribute('aria-expanded', isShown ? 'true' : 'false');
        form.querySelector('[data-task-advanced-label]').textContent = isShown ? 'Sembunyikan pengaturan lainnya' : 'Pengaturan lainnya';
        form.querySelector('[data-task-advanced-icon]').classList.toggle('ki-up', isShown);
        form.querySelector('[data-task-advanced-icon]').classList.toggle('ki-down', !isShown);
    };

    advancedToggle.addEventListener('click', function () {
        showAdvanced(advanced.classList.contains('d-none'));
    });

    /**
     * Orang yang hanya boleh menambah tugas untuk dirinya sendiri (misalnya Bendahara):
     * penanggung jawab diisi dirinya dan tugas masuk Pengurus Inti, jadi kedua isian disembunyikan.
     */
    var selfId = modalElement.dataset.selfMembershipId;

    var applySelfOnlyMode = function () {
        if (!selfId) {
            return;
        }

        var existingContext = modalElement.querySelector('[data-task-form-context]').textContent;

        isFilling = true;
        OrganizerSelect.setValue(owner, selfId, modalElement.dataset.selfMembershipName);
        $(division).val('').trigger('change');
        isFilling = false;
        form.querySelector('[data-task-owner-row]').classList.add('d-none');
        form.querySelector('[data-task-division-row]').classList.add('d-none');
        OrganizerSelect.setParams(program, { core: 1 });
        setContext(existingContext ? existingContext + ' · tugas untuk Anda sendiri' : 'Tugas untuk Anda sendiri · Pengurus Inti');
    };

    var setContext = function (text) {
        var context = modalElement.querySelector('[data-task-form-context]');

        context.textContent = text || '';
        context.classList.toggle('d-none', !text);
    };

    $(division).on('change', function () {
        refreshProgramScope();

        if (!isFilling) {
            OrganizerSelect.reset(program);
        }
    });

    $(parent).on('change', refreshParentLock);
    $(program).on('change', refreshParentLock);

    var reset = function () {
        form.reset();
        OrganizerHttp.clearFieldErrors(form);
        OrganizerDatePicker.clearAll(form);
        isFilling = true;
        $(division).val('').trigger('change');
        $(form.elements.priority).val('medium').trigger('change');
        [program, parent, owner, assignees].forEach(OrganizerSelect.reset);
        isFilling = false;
        form.elements.weight.value = '2';
        refreshProgramScope();
        refreshParentLock();
        showAdvanced(false);
        setContext('');
    };

    var fill = function (task) {
        isFilling = true;
        form.elements.title.value = task.title || '';
        form.elements.description.value = task.description || '';
        form.elements.weight.value = String(task.weight || 2);
        $(division).val(task.division_id === null ? '' : String(task.division_id)).trigger('change');
        $(form.elements.priority).val(task.priority).trigger('change');
        OrganizerSelect.setValue(program, task.work_program_id, task.work_program_id_label);
        OrganizerSelect.setValue(parent, task.parent_id, task.parent_id_label);
        OrganizerSelect.setValue(owner, task.owner_membership_id, task.owner_membership_id_label);
        OrganizerSelect.setValues(assignees, task.assignees);
        OrganizerDatePicker.setValue(form.elements.starts_on, task.starts_on || '');
        OrganizerDatePicker.setValue(form.elements.due_on, task.due_on || '');
        isFilling = false;
        refreshProgramScope();
        refreshParentLock();
        showAdvanced(Boolean(task.work_program_id || task.parent_id || task.assignees.length || task.starts_on || task.description
            || task.priority !== 'medium' || Number(task.weight) !== 2));
    };

    var open = function (title, excludeTaskId) {
        modalElement.querySelector('[data-task-form-title]').textContent = title;
        OrganizerSelect.setParams(parent, Object.assign({ top_level: 1 }, excludeTaskId ? { exclude: excludeTaskId } : {}));
        modal.show();
    };

    modalElement.addEventListener('shown.bs.modal', function () {
        form.elements.title.focus();
    });

    form.addEventListener('submit', function (submitEvent) {
        submitEvent.preventDefault();

        // Field yang dinonaktifkan tidak ikut terkirim; server mengisinya dari tugas induk.
        OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function (data) {
            modal.hide();

            return OrganizerHttp.showSuccess('Tugas berhasil disimpan.').then(function () {
                if (afterSave) {
                    afterSave(data);
                }
            });
        }).catch(function () {
            // Error di bagian yang tersembunyi tetap harus terlihat.
            if (advanced.querySelector('.is-invalid')) {
                showAdvanced(true);
            }
        });
    });

    return {
        openForCreate: function (url, defaults, onSaved) {
            reset();
            submitUrl = url;
            afterSave = onSaved;

            defaults = defaults || {};
            isFilling = true;

            if (defaults.division_id) {
                $(division).val(String(defaults.division_id)).trigger('change');
            }

            if (defaults.work_program_id) {
                OrganizerSelect.setValue(program, defaults.work_program_id, defaults.work_program_label);
            }

            if (defaults.parent_id) {
                OrganizerSelect.setValue(parent, defaults.parent_id, defaults.parent_label);
            }

            isFilling = false;
            refreshProgramScope();
            refreshParentLock();
            setContext(defaults.context);
            applySelfOnlyMode();
            open(defaults.parent_id ? 'Tambah Subtugas' : 'Tambah Tugas', null);
        },
        openForEdit: function (showUrl, updateUrl, onSaved) {
            OrganizerHttp.request({ url: showUrl }).then(function (task) {
                reset();
                submitUrl = updateUrl;
                afterSave = onSaved;
                fill(task);
                applySelfOnlyMode();
                open('Ubah Tugas', task.id);
            }).catch(OrganizerHttp.showError);
        },
    };
})();
