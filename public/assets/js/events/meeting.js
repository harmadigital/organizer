"use strict";

/**
 * Halaman rapat: tab (diingat di alamat), ubah atau hapus rapat, absensi (klik nama untuk
 * memindahkannya ke kolom Hadir, langsung tersimpan), form notulen layar penuh dengan editor
 * dokumen (TinyMCE), keputusan dan tindak lanjut, serta komentar. Perubahan selain absensi
 * memuat ulang halaman di tab yang sama.
 */
(function () {
    var page = document.querySelector('[data-meeting-page]');

    if (!page) {
        return;
    }

    // Alamat lengkap, karena layout memakai <base href> sehingga "#notulen" saja menunjuk ke halaman lain.
    var rememberTab = function (name) {
        window.history.replaceState(null, '', window.location.pathname + window.location.search + '#' + name);
    };

    var reload = function () {
        window.location.reload();
    };

    page.querySelectorAll('[data-meeting-tabs] [data-tab]').forEach(function (button) {
        button.addEventListener('shown.bs.tab', function () {
            rememberTab(button.dataset.tab);
        });
    });

    if (window.location.hash) {
        var target = page.querySelector('[data-meeting-tabs] [data-tab="' + window.location.hash.slice(1) + '"]');

        if (target) {
            bootstrap.Tab.getOrCreateInstance(target).show();
        }
    }

    var editButton = page.querySelector('[data-meeting-edit]');

    if (editButton && MeetingForm) {
        editButton.addEventListener('click', function () {
            MeetingForm.openForEdit(page.dataset.showUrl, page.dataset.updateUrl, reload);
        });
    }

    var deleteButton = page.querySelector('[data-meeting-delete]');

    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            OrganizerHttp.confirm({
                title: 'Hapus rapat?',
                text: 'Rapat "' + page.dataset.meetingTitle + '" akan dihapus dari jadwal.',
                confirmText: 'Ya, hapus',
            }).then(function (confirmed) {
                if (!confirmed) {
                    return null;
                }

                return OrganizerHttp.request({ url: page.dataset.deleteUrl, method: 'POST' }).then(function (data) {
                    window.location.href = data.url;
                }).catch(OrganizerHttp.showError);
            });
        });
    }

    // ---- Notulen ---------------------------------------------------------------------------

    var minutesModal = document.getElementById('kt_modal_minutes');

    if (minutesModal) {
        var minutesForm = minutesModal.querySelector('form');
        var decisionRows = minutesForm.querySelector('[data-decision-rows]');
        var actionRows = minutesForm.querySelector('[data-action-rows]');
        // Tanpa focus trap Bootstrap agar dialog editor (tautan, tabel) bisa diketik; tidak menutup
        // lewat Esc atau klik di luar, supaya notulen yang panjang tidak hilang tak sengaja.
        var modal = new bootstrap.Modal(minutesModal, { focus: false, keyboard: false, backdrop: 'static' });
        var editorReady = null;

        /**
         * Editor dokumen dengan toolbar lengkap: gaya paragraf, huruf, warna, perataan, daftar,
         * tabel, tautan, cari-ganti, dan layar penuh. Gambar tidak didukung; isi dibersihkan
         * lagi di server.
         */
        var startEditor = function () {
            if (editorReady || typeof tinymce === 'undefined') {
                return editorReady || Promise.resolve();
            }

            editorReady = tinymce.init({
                target: minutesForm.querySelector('[data-rich-editor]'),
                license_key: 'gpl',
                base_url: page.dataset.editorBase,
                suffix: '.min',
                height: Math.max(420, window.innerHeight - 270),
                menubar: 'edit view insert format table',
                plugins: 'advlist autolink lists link charmap searchreplace visualblocks fullscreen insertdatetime table wordcount help pagebreak',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link hr | removeformat searchreplace fullscreen',
                toolbar_mode: 'wrap',
                block_formats: 'Paragraf=p; Judul 1=h1; Judul 2=h2; Judul 3=h3; Kutipan=blockquote',
                font_size_formats: '10pt 11pt 12pt 14pt 16pt 18pt 24pt',
                paste_data_images: false,
                branding: false,
                promotion: false,
                convert_urls: false,
                link_default_target: '_blank',
                content_style: 'body { font-family: Inter, Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.6; } table { border-collapse: collapse; } td, th { border: 1px solid #c3c4c7; padding: 4px 6px; }',
                setup: function (editor) {
                    // Pesan error di bawah editor hilang begitu isinya diubah.
                    editor.on('input change', function () {
                        var textarea = minutesForm.querySelector('[data-rich-editor]');

                        textarea.classList.remove('is-invalid');
                        textarea.closest('.fv-row').querySelectorAll('[data-organizer-error]').forEach(function (message) {
                            message.remove();
                        });
                    });
                },
            });

            return editorReady;
        };

        /**
         * Nama field tindak lanjut mengikuti urutan baris: action_items[0][title], dan seterusnya.
         */
        var renumberActions = function () {
            actionRows.querySelectorAll('[data-action-row]').forEach(function (row, index) {
                row.querySelectorAll('[data-action-field]').forEach(function (field) {
                    field.name = 'action_items[' + index + '][' + field.dataset.actionField + ']';
                });
            });
        };

        var existingActions = minutesForm.querySelectorAll('.ui-check-row').length;

        /**
         * Angka di tab Keputusan dan Tindak Lanjut mengikuti jumlah baris.
         */
        var refreshTabCounts = function () {
            var decisions = minutesModal.querySelector('[data-minutes-count="keputusan"]');
            var actions = minutesModal.querySelector('[data-minutes-count="tindak-lanjut"]');
            var newActions = actionRows.querySelectorAll('[data-action-row]').length;

            decisions.textContent = Array.from(decisionRows.querySelectorAll('input')).filter(function (input) {
                return input.value.trim() !== '';
            }).length;
            actions.textContent = existingActions + newActions;
            minutesForm.querySelector('[data-action-empty]').classList.toggle('d-none', existingActions + newActions > 0);
        };

        decisionRows.addEventListener('input', refreshTabCounts);

        var showTabOfFirstError = function () {
            var firstError = minutesForm.querySelector('[data-organizer-error]');
            var pane = firstError ? firstError.closest('.tab-pane') : null;
            var button = pane ? minutesModal.querySelector('[data-bs-target="#' + pane.id + '"]') : null;

            if (button) {
                bootstrap.Tab.getOrCreateInstance(button).show();
            }
        };

        var addRow = function (container, template) {
            var row = template.content.firstElementChild.cloneNode(true);

            container.appendChild(row);
            OrganizerSelect.init(row);
            OrganizerDatePicker.init(row);
            renumberActions();
            refreshTabCounts();

            var first = row.querySelector('input[type="text"]');

            if (first) {
                first.focus();
            }
        };

        minutesForm.querySelector('[data-decision-add]').addEventListener('click', function () {
            addRow(decisionRows, minutesForm.querySelector('[data-decision-template]'));
        });

        minutesForm.querySelector('[data-action-add]').addEventListener('click', function () {
            addRow(actionRows, minutesForm.querySelector('[data-action-template]'));
        });

        minutesForm.addEventListener('click', function (clickEvent) {
            var remove = clickEvent.target.closest('[data-row-remove]');

            if (remove) {
                remove.closest('[data-decision-row], [data-action-row]').remove();
                renumberActions();
                refreshTabCounts();
            }
        });

        page.querySelector('[data-minutes-open]').addEventListener('click', function () {
            if (decisionRows.children.length === 0) {
                addRow(decisionRows, minutesForm.querySelector('[data-decision-template]'));
            }

            refreshTabCounts();
            bootstrap.Tab.getOrCreateInstance(minutesModal.querySelector('[data-minutes-tab="isi"]')).show();
            modal.show();
        });

        minutesModal.addEventListener('shown.bs.modal', function () {
            startEditor().then(function () {
                var editor = typeof tinymce !== 'undefined' ? tinymce.get(minutesForm.querySelector('[data-rich-editor]').id) : null;

                if (editor) {
                    editor.focus();
                }
            });
        });

        minutesForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();
            renumberActions();

            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            OrganizerHttp.submit(minutesForm, minutesForm.querySelector('[data-minutes-submit]'), { url: minutesForm.dataset.url }).then(function (data) {
                modal.hide();

                var message = 'Notulen tersimpan dan dapat dibaca semua panitia.';

                if (data.tasks_created > 0) {
                    message += ' ' + data.tasks_created + ' tindak lanjut menjadi tugas.';
                }

                return OrganizerHttp.showSuccess(message).then(function () {
                    rememberTab('notulen');
                    reload();
                });
            }).catch(showTabOfFirstError);
        });
    }

    // ---- Absensi ---------------------------------------------------------------------------

    var attendance = page.querySelector('[data-attendance]');

    if (attendance) {
        var columnOf = function (name) {
            return attendance.querySelector('[data-attendance-column="' + name + '"]');
        };

        var refreshCounts = function () {
            var present = columnOf('present').querySelectorAll('[data-attendance-person]').length;
            var absent = columnOf('absent').querySelectorAll('[data-attendance-person]').length;
            var total = present + absent;
            var badge = page.querySelector('[data-attendance-badge]');

            columnOf('present').querySelector('[data-attendance-count]').textContent = present;
            columnOf('absent').querySelector('[data-attendance-count]').textContent = absent;
            attendance.querySelector('[data-attendance-present]').textContent = present;
            attendance.querySelector('[data-attendance-bar]').style.width = (total ? Math.round(present * 100 / total) : 0) + '%';

            if (badge) {
                badge.textContent = present + '/' + total;
            }
        };

        /**
         * Pindahkan kartu ke kolom lain, tetap urut nama, dan sesuaikan tombolnya.
         */
        var place = function (person, columnName) {
            var list = columnOf(columnName).querySelector('[data-attendance-list]');
            var next = Array.from(list.querySelectorAll('[data-attendance-person]')).find(function (other) {
                return other.dataset.name > person.dataset.name;
            });
            var toggle = person.querySelector('[data-attendance-toggle]');
            var name = person.querySelector('[data-attendance-name]').textContent;

            list.insertBefore(person, next || list.querySelector('[data-attendance-empty]'));

            if (toggle) {
                toggle.querySelector('[data-toggle-label]').textContent = columnName === 'present' ? 'Batal' : 'Hadir';
                toggle.setAttribute('aria-label', (columnName === 'present' ? 'Batalkan hadir: ' : 'Tandai hadir: ') + name);
            }

            refreshCounts();
        };

        if (attendance.hasAttribute('data-attendance-editable')) {
            attendance.addEventListener('click', function (clickEvent) {
                var person = clickEvent.target.closest('[data-attendance-person]');

                if (!person || person.classList.contains('is-moving')) {
                    return;
                }

                var wasPresent = person.closest('[data-attendance-column]').dataset.attendanceColumn === 'present';

                person.classList.add('is-moving');
                place(person, wasPresent ? 'absent' : 'present');

                OrganizerHttp.request({
                    url: page.dataset.attendanceUrl,
                    method: 'POST',
                    data: { membership_id: person.dataset.attendancePerson, present: !wasPresent },
                }).catch(function (error) {
                    place(person, wasPresent ? 'present' : 'absent');
                    OrganizerHttp.showError(error);
                }).finally(function () {
                    person.classList.remove('is-moving');
                    var toggle = person.querySelector('[data-attendance-toggle]');

                    if (toggle) {
                        toggle.focus();
                    }
                });
            });
        }

        attendance.querySelector('[data-attendance-search]').addEventListener('input', function (inputEvent) {
            var term = inputEvent.target.value.trim().toLowerCase();

            attendance.querySelectorAll('[data-attendance-person]').forEach(function (person) {
                person.classList.toggle('d-none', term !== '' && person.dataset.name.indexOf(term) === -1);
            });
        });
    }

    // ---- Komentar --------------------------------------------------------------------------

    var commentForm = page.querySelector('[data-comment-form]');

    if (commentForm) {
        commentForm.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(commentForm, commentForm.querySelector('[data-comment-submit]'), { url: commentForm.dataset.url })
                .then(reload)
                .catch(function () {});
        });
    }

    page.querySelectorAll('[data-comment-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            OrganizerHttp.confirm({ title: 'Hapus komentar?', text: 'Komentar ini akan dihapus.', confirmText: 'Ya, hapus' }).then(function (confirmed) {
                if (!confirmed) {
                    return null;
                }

                return OrganizerHttp.request({ url: button.dataset.commentRemove, method: 'POST' }).then(reload).catch(OrganizerHttp.showError);
            });
        });
    });
})();
