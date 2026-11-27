"use strict";

/**
 * Form "Tandai Tugas Selesai" (#kt_modal_task_complete): hasil pekerjaan dan foto/dokumen
 * dikirim dalam satu request, lalu tugas langsung Selesai.
 *
 *   TaskComplete.open({ url, title, result }, onDone);
 */
var TaskComplete = (function () {
    var modalElement = document.getElementById('kt_modal_task_complete');

    if (!modalElement) {
        return null;
    }

    var form = modalElement.querySelector('form');
    var submitButton = form.querySelector('[data-complete-submit]');
    var fileInput = form.querySelector('#complete_files');
    var fileList = form.querySelector('[data-complete-file-list]');
    var modal = new bootstrap.Modal(modalElement);
    var submitUrl = null;
    var afterDone = null;

    var showChosenFiles = function () {
        fileList.replaceChildren();
        Array.from(fileInput.files).forEach(function (file) {
            var item = document.createElement('li');

            item.textContent = '• ' + file.name + ' (' + Math.max(1, Math.round(file.size / 1024)).toLocaleString('id-ID') + ' KB)';
            fileList.appendChild(item);
        });
    };

    fileInput.addEventListener('change', showChosenFiles);

    form.addEventListener('submit', function (submitEvent) {
        submitEvent.preventDefault();

        OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function (data) {
            modal.hide();

            return OrganizerHttp.showSuccess('Tugas selesai dan hasilnya tersimpan.').then(function () {
                if (afterDone) {
                    afterDone(data);
                }
            });
        }).catch(function () {});
    });

    return {
        open: function (options, onDone) {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);
            fileList.replaceChildren();
            submitUrl = options.url;
            afterDone = onDone;
            form.elements.result.value = options.result || '';
            modalElement.querySelector('[data-complete-task-title]').textContent = options.title;
            modal.show();
        },
    };
})();
