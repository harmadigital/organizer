"use strict";

/**
 * Halaman detail tugas: tandai selesai (hasil + foto/dokumen), ubah tugas, hasil,
 * dokumentasi, komentar, subtugas, dan prasyarat. Setiap perubahan berhasil memuat ulang halaman
 * agar semua bagian konsisten.
 */
(function () {
    var page = document.querySelector('[data-task-page]');

    if (!page) {
        return;
    }

    var reload = function () {
        window.location.reload();
    };

    /**
     * Tab yang terbuka disimpan di alamat (#hasil, #komentar, ...), sehingga setelah halaman
     * dimuat ulang karena sebuah aksi, orang tetap berada di tab yang sama.
     */
    // Alamat lengkap, karena layout memakai <base href> sehingga "#hasil" saja menunjuk ke halaman lain.
    var rememberTab = function (name) {
        window.history.replaceState(null, '', window.location.pathname + window.location.search + '#' + name);
    };

    var showTab = function (name) {
        var button = page.querySelector('[data-task-tabs] [data-tab="' + name + '"]');

        if (button) {
            bootstrap.Tab.getOrCreateInstance(button).show();
        }
    };

    page.querySelectorAll('[data-task-tabs] [data-tab]').forEach(function (button) {
        button.addEventListener('shown.bs.tab', function () {
            rememberTab(button.dataset.tab);
        });
    });

    if (window.location.hash) {
        showTab(window.location.hash.slice(1));
    }

    page.querySelectorAll('[data-task-complete]').forEach(function (completeButton) {
        completeButton.addEventListener('click', function () {
            var resultText = page.querySelector('[data-result-text]');

            TaskComplete.open({
                url: page.dataset.completeUrl,
                title: page.dataset.taskTitle,
                result: resultText ? resultText.textContent : '',
            }, function () {
                rememberTab('hasil');
                reload();
            });
        });
    });

    // Subtugas bisa diselesaikan langsung dari daftar, dengan form hasil yang sama.
    page.querySelectorAll('[data-subtask-complete]').forEach(function (completeButton) {
        completeButton.addEventListener('click', function () {
            TaskComplete.open({
                url: completeButton.dataset.url,
                title: completeButton.dataset.title,
                result: completeButton.dataset.result,
            }, function () {
                rememberTab('rincian');
                reload();
            });
        });
    });

    var editButton = page.querySelector('[data-task-edit]');

    if (editButton && TaskForm) {
        editButton.addEventListener('click', function () {
            TaskForm.openForEdit(page.dataset.showUrl, page.dataset.updateUrl, reload);
        });
    }

    var subtaskButton = page.querySelector('[data-subtask-add]');

    if (subtaskButton && TaskForm) {
        subtaskButton.addEventListener('click', function () {
            TaskForm.openForCreate(page.dataset.insertUrl, { parent_id: page.dataset.taskId, parent_label: page.dataset.taskTitle }, reload);
        });
    }

    var dependencyForm = page.querySelector('[data-dependency-form]');

    if (dependencyForm) {
        OrganizerSelect.setParams(dependencyForm.elements.depends_on_task_id, { exclude: page.dataset.taskId });

        dependencyForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(dependencyForm, dependencyForm.querySelector('[data-dependency-submit]'), { url: dependencyForm.dataset.url })
                .then(reload)
                .catch(function () {});
        });
    }

    var postAndReload = function (url, confirmOptions) {
        OrganizerHttp.confirm(confirmOptions).then(function (confirmed) {
            if (!confirmed) {
                return null;
            }

            return OrganizerHttp.request({ url: url, method: 'POST' }).then(reload).catch(OrganizerHttp.showError);
        });
    };

    page.querySelectorAll('[data-dependency-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            postAndReload(button.dataset.dependencyRemove, { title: 'Lepas prasyarat?', text: 'Tugas ini tidak lagi menunggu tugas tersebut.', confirmText: 'Ya, lepas' });
        });
    });

    var resultForm = page.querySelector('[data-result-form]');
    var resultEdit = page.querySelector('[data-result-edit]');

    if (resultForm && resultEdit) {
        var toggleResultForm = function (editing) {
            resultForm.classList.toggle('d-none', !editing);
            page.querySelector('[data-result-view]').classList.toggle('d-none', editing);
            resultEdit.classList.toggle('d-none', editing);
        };

        resultEdit.addEventListener('click', function () {
            toggleResultForm(true);
            resultForm.elements.result.focus();
        });
        resultForm.querySelector('[data-result-cancel]').addEventListener('click', function () {
            OrganizerHttp.clearFieldErrors(resultForm);
            toggleResultForm(false);
        });
        resultForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(resultForm, resultForm.querySelector('[data-result-submit]'), { url: resultForm.dataset.url })
                .then(reload)
                .catch(function () {});
        });
    }

    var commentForm = page.querySelector('[data-comment-form]');

    if (commentForm) {
        commentForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(commentForm, commentForm.querySelector('[data-comment-submit]'), { url: commentForm.dataset.url })
                .then(reload)
                .catch(function () {});
        });
    }

    var documentForm = page.querySelector('[data-document-form]');

    if (documentForm) {
        documentForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(documentForm, documentForm.querySelector('[data-document-submit]'), { url: documentForm.dataset.url })
                .then(function (data) {
                    return OrganizerHttp.showSuccess(data.count + ' file berhasil diunggah.').then(reload);
                })
                .catch(function () {});
        });
    }

    page.querySelectorAll('[data-comment-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            postAndReload(button.dataset.commentRemove, { title: 'Hapus komentar?', text: 'Komentar ini akan dihapus.', confirmText: 'Ya, hapus' });
        });
    });

    page.querySelectorAll('[data-document-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            postAndReload(button.dataset.documentRemove, { title: 'Hapus file?', text: 'File "' + button.dataset.title + '" akan dilepas dari tugas ini.', confirmText: 'Ya, hapus' });
        });
    });
})();
