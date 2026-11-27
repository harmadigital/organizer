"use strict";

/**
 * Halaman Program & Tugas, dibuat sesederhana mungkin untuk panitia:
 *
 *   - Tugas Saya: hanya tugas orang yang membuka halaman, masing-masing dengan satu tombol
 *     "Tandai Selesai" (isi hasil dan foto/dokumen).
 *   - Per Program: setiap program beserta daftar tugasnya sebagai checklist, dengan tombol
 *     "Tambah tugas" di dalam program sehingga seksi dan program terisi sendiri.
 *   - Kalender untuk melihat tenggat.
 *
 * Semua panitia melihat semua tugas dan bisa berkomentar; tidak ada review atau persetujuan.
 *
 * Semua data diambil dari satu endpoint lalu digambar ulang setelah setiap perubahan.
 * Teks dari server dipasang lewat textContent.
 */
var WorkPage = (function () {
    var page = document.querySelector('[data-work-page]');

    if (!page) {
        return null;
    }

    var state = { programs: [], tasks: [], today: null, canCreateTasks: false };
    var filters = { search: '', division: '', hideDone: false };
    var currentView = null;
    var calendar = null;
    var VIEW_KEY = 'organizer.work.view';
    var OPEN_STATUSES = ['todo', 'in_progress', 'blocked'];

    var el = function (tag, className, text) {
        var element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = text;
        }

        return element;
    };

    var icon = function (name, paths) {
        var element = el('i', 'ki-duotone ' + name + ' fs-4');

        for (var path = 1; path <= (paths || 2); path++) {
            element.appendChild(el('span', 'path' + path));
        }

        return element;
    };

    var badge = function (item) {
        return el('span', 'badge badge-light-' + item.tone, item.label);
    };

    var isOpen = function (task) {
        return OPEN_STATUSES.indexOf(task.status.value) !== -1;
    };

    var canComplete = function (task) {
        return task.can_complete;
    };

    var progressBar = function (progress) {
        var wrapper = el('div', 'ui-progress');
        var bar = el('div', 'progress');
        var fill = el('div', 'progress-bar bg-primary');

        fill.style.width = (progress.percent || 0) + '%';
        bar.appendChild(fill);
        wrapper.append(bar, el('span', 'ui-progress-value', progress.percent === null ? 'N/A' : progress.percent + '%'));
        wrapper.title = progress.percent === null ? 'Belum ada tugas' : 'Dihitung dari tugas yang selesai; tugas berat dihitung lebih besar';

        return wrapper;
    };

    /**
     * Tenggat dalam bahasa sehari-hari: "Terlambat 2 hari", "Hari ini", "Besok", "5 hari lagi".
     */
    var dueChip = function (task) {
        if (!task.due_on) {
            return null;
        }

        var days = Math.round((new Date(task.due_on + 'T00:00:00') - new Date(state.today + 'T00:00:00')) / 86400000);
        var text = task.due_label;
        var tone = '';

        if (isOpen(task)) {
            if (days < 0) {
                text = 'Terlambat ' + Math.abs(days) + ' hari';
                tone = ' ui-due--late';
            } else if (days === 0) {
                text = 'Hari ini';
                tone = ' ui-due--soon';
            } else if (days === 1) {
                text = 'Besok';
                tone = ' ui-due--soon';
            } else if (days <= 7) {
                text = days + ' hari lagi';
                tone = ' ui-due--soon';
            }
        }

        var chip = el('span', 'ui-due' + tone);

        chip.append(icon('ki-calendar'), document.createTextNode(text));
        chip.title = 'Tenggat ' + task.due_label;

        return chip;
    };

    var openComplete = function (task) {
        TaskComplete.open({
            url: page.dataset.taskBaseUrl + '/' + task.id + '/complete',
            title: task.title,
            result: task.result,
        }, load);
    };

    var completeButton = function (task, label) {
        var button = el('button', 'btn btn-sm btn-success btn-complete');

        button.type = 'button';
        button.append(icon('ki-check'), document.createTextNode(' ' + (label || 'Tandai Selesai')));
        button.setAttribute('aria-label', 'Tandai selesai: ' + task.title);
        button.addEventListener('click', function () {
            openComplete(task);
        });

        return button;
    };

    var taskLink = function (task) {
        var link = el('a', 'ui-task-title', task.title);

        link.href = task.url;

        return link;
    };

    // ---- Ringkasan -------------------------------------------------------------------------

    var renderSummary = function (summary) {
        var progress = summary.progress;

        page.querySelector('[data-summary="progress"]').textContent = progress.percent === null ? 'N/A' : progress.percent + '%';
        page.querySelector('[data-summary-bar]').style.width = (progress.percent || 0) + '%';
        page.querySelector('[data-summary-footer]').textContent = progress.percent === null
            ? 'Progres event: belum ada tugas'
            : 'pekerjaan event selesai (' + summary.completed + ' dari ' + (summary.open + summary.completed) + ' tugas)';
        page.querySelector('[data-summary-footer]').title = 'Tugas berat dihitung lebih besar daripada tugas ringan';

        ['open', 'overdue', 'completed'].forEach(function (key) {
            page.querySelector('[data-summary="' + key + '"]').textContent = summary[key];
        });
    };

    // ---- Tugas Saya ------------------------------------------------------------------------

    var mineCard = function (task) {
        var card = el('div', 'ui-task-card' + (task.is_overdue ? ' is-overdue' : ''));
        var body = el('div', 'ui-task-card-body');
        var meta = el('div', 'ui-task-meta mt-2');
        var due = dueChip(task);

        card.setAttribute('data-mine-card', task.id);
        body.appendChild(taskLink(task));

        if (due) {
            meta.appendChild(due);
        }

        meta.appendChild(el('span', null, task.program || task.division));

        if (task.assignees.length) {
            meta.appendChild(el('span', null, 'Bersama ' + task.assignees.join(', ')));
        }

        if (task.comments > 0) {
            meta.appendChild(el('span', null, task.comments + ' komentar'));
        }

        body.appendChild(meta);
        card.appendChild(body);

        if (isOpen(task) && canComplete(task)) {
            card.appendChild(completeButton(task));
        } else if (!isOpen(task)) {
            card.appendChild(badge(task.status));
        }

        return card;
    };

    var renderMine = function () {
        var mine = state.tasks.filter(function (task) {
            return task.is_mine && task.status.value !== 'cancelled';
        });
        var open = mine.filter(isOpen);
        var done = mine.filter(function (task) {
            return !isOpen(task);
        });
        var openList = page.querySelector('[data-mine-open]');
        var doneList = page.querySelector('[data-mine-done]');

        openList.replaceChildren.apply(openList, open.map(mineCard));
        doneList.replaceChildren.apply(doneList, done.map(mineCard));
        page.querySelector('[data-mine-empty]').classList.toggle('d-none', open.length > 0);
        page.querySelector('[data-mine-done-wrapper]').classList.toggle('d-none', done.length === 0);
        page.querySelector('[data-mine-done-count]').textContent = done.length;
        page.querySelector('[data-mine-count]').textContent = open.length;

        return open.length;
    };

    // ---- Per Program -----------------------------------------------------------------------

    var matchesFilters = function (task) {
        if (filters.search && task.title.toLowerCase().indexOf(filters.search) === -1) {
            return false;
        }

        if (filters.division === 'none' && task.division_id !== null) {
            return false;
        }

        if (filters.division && filters.division !== 'none' && String(task.division_id) !== filters.division) {
            return false;
        }

        return !filters.hideDone || ['completed', 'cancelled'].indexOf(task.status.value) === -1;
    };

    var checkButton = function (task) {
        var button = el('button', 'ui-check');

        button.type = 'button';
        button.appendChild(el('i', 'las la-check fs-5'));

        if (task.status.value === 'completed') {
            button.classList.add('is-done');
            button.disabled = true;
            button.title = 'Selesai';
        } else if (isOpen(task) && canComplete(task)) {
            button.title = 'Tandai selesai';
            button.setAttribute('aria-label', 'Tandai selesai: ' + task.title);
            button.addEventListener('click', function () {
                openComplete(task);
            });
        } else {
            button.disabled = true;
            button.title = task.status.label;
        }

        return button;
    };

    var checklistRow = function (task, isSubtask) {
        var row = el('div', 'ui-checklist-row' + (isSubtask ? ' is-subtask' : '') + (task.status.value === 'completed' ? ' is-done' : ''));
        var body = el('div', 'flex-grow-1 min-w-0');
        var meta = el('div', 'ui-task-meta');
        var due = dueChip(task);

        row.setAttribute('data-task-row', task.id);
        body.appendChild(taskLink(task));

        if (['high', 'critical'].indexOf(task.priority.value) !== -1 && isOpen(task)) {
            body.appendChild(el('span', 'badge badge-light-' + task.priority.tone + ' ms-2', task.priority.label));
        }

        if (task.status.value === 'cancelled') {
            body.appendChild(el('span', 'badge badge-light-' + task.status.tone + ' ms-2', task.status.label));
        }

        meta.appendChild(el('span', null, task.owner || 'Belum ada penanggung jawab'));

        if (due) {
            meta.appendChild(due);
        }

        if (task.subtasks > 0 && task.subtask_progress) {
            meta.appendChild(el('span', null, task.subtasks + ' subtugas · ' + (task.subtask_progress.percent === null ? 'N/A' : task.subtask_progress.percent + '%')));
        }

        if (task.open_dependencies > 0) {
            meta.appendChild(el('span', 'text-warning', 'Menunggu ' + task.open_dependencies + ' tugas lain'));
        }

        if (task.comments > 0) {
            meta.appendChild(el('span', null, task.comments + ' komentar'));
        }

        body.appendChild(meta);
        row.append(checkButton(task), body);

        return row;
    };

    /**
     * Tugas berurutan dengan subtugas tepat di bawah induknya.
     */
    var withSubtasks = function (tasks) {
        var ids = {};
        var ordered = [];

        tasks.forEach(function (task) {
            ids[task.id] = true;
        });
        tasks.forEach(function (task) {
            if (task.parent_id && ids[task.parent_id]) {
                return;
            }

            ordered.push([task, false]);
            tasks.forEach(function (child) {
                if (child.parent_id === task.id) {
                    ordered.push([child, true]);
                }
            });
        });

        return ordered;
    };

    var iconButton = function (className, iconName, paths, label, onClick) {
        var button = el('button', 'btn btn-sm btn-icon ' + className);

        button.type = 'button';
        button.title = label;
        button.setAttribute('aria-label', label);
        button.appendChild(icon(iconName, paths));
        button.addEventListener('click', onClick);

        return button;
    };

    var programGroup = function (program, tasks) {
        var group = el('section', 'ui-program-group');
        var header = el('div', 'ui-program-group-header');
        var heading = el('div', 'flex-grow-1 min-w-0');
        var details = [program ? program.division : null, program && program.pic ? 'PJ ' + program.pic : null, program ? program.period : null].filter(Boolean);
        var list = el('div');

        group.setAttribute('data-program-group', program ? program.id : 'none');
        heading.appendChild(el('div', 'ui-program-group-title', program ? program.title : 'Tugas di luar program'));
        heading.appendChild(el('div', 'ui-program-group-meta', program ? details.join(' · ') : 'Tugas pengurus inti atau seksi yang tidak masuk program kerja'));
        header.appendChild(heading);

        if (program) {
            var progress = el('div');

            progress.style.minWidth = '160px';
            progress.appendChild(progressBar(program.progress));
            header.append(progress, badge(program.status));

            if (program.can_manage) {
                var tools = el('div', 'd-flex gap-2');

                tools.append(
                    iconButton('btn-light-primary', 'ki-pencil', 2, 'Ubah program ' + program.title, function () {
                        ProgramForm.openForEdit(program.id);
                    }),
                    iconButton('btn-light-danger', 'ki-trash', 5, 'Hapus program ' + program.title, function () {
                        ProgramForm.remove(program);
                    })
                );
                header.appendChild(tools);
            }
        }

        withSubtasks(tasks).forEach(function (entry) {
            list.appendChild(checklistRow(entry[0], entry[1]));
        });

        if (tasks.length === 0) {
            list.appendChild(el('div', 'ui-checklist-row text-muted fs-7', 'Belum ada tugas.'));
        }

        group.append(header, list);

        var canAdd = program ? program.can_add_tasks : state.canCreateTasks;

        if (canAdd && TaskForm) {
            var add = el('button', 'ui-checklist-add');

            add.type = 'button';
            add.setAttribute('data-group-add-task', program ? program.id : 'none');
            add.append(icon('ki-plus', 0), document.createTextNode(' Tambah tugas' + (program ? ' di program ini' : '')));
            add.addEventListener('click', function () {
                TaskForm.openForCreate(page.dataset.taskInsertUrl, program ? {
                    division_id: program.division_id,
                    work_program_id: program.id,
                    work_program_label: program.title,
                    context: 'Program: ' + program.title + ' · ' + program.division,
                } : null, load);
            });
            group.appendChild(add);
        }

        return group;
    };

    var renderPrograms = function () {
        var container = page.querySelector('[data-program-groups]');
        var tasks = state.tasks.filter(matchesFilters);
        var isFiltering = Boolean(filters.search || filters.division || filters.hideDone);
        var groups = [];

        state.programs.forEach(function (program) {
            if (filters.division === 'none' && program.division_id !== null) {
                return;
            }

            if (filters.division && filters.division !== 'none' && String(program.division_id) !== filters.division) {
                return;
            }

            var inProgram = tasks.filter(function (task) {
                return task.work_program_id === program.id;
            });

            if (!filters.search || inProgram.length) {
                groups.push(programGroup(program, inProgram));
            }
        });

        var outside = tasks.filter(function (task) {
            return task.work_program_id === null;
        });

        if (outside.length || (!isFiltering && state.canCreateTasks)) {
            groups.push(programGroup(null, outside));
        }

        container.replaceChildren.apply(container, groups);
        page.querySelector('[data-programs-empty]').classList.toggle('d-none', groups.length > 0);
    };

    // ---- Kalender --------------------------------------------------------------------------

    var toneColor = function (tone) {
        return getComputedStyle(document.documentElement).getPropertyValue('--bs-' + tone).trim() || '#2271b1';
    };

    var dateOnly = function (date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    };

    var calendarEvents = function () {
        return state.tasks.filter(function (task) {
            return task.due_on || task.starts_on;
        }).map(function (task) {
            var end = new Date((task.due_on || task.starts_on) + 'T00:00:00');

            // Tanggal akhir di FullCalendar bersifat eksklusif.
            end.setDate(end.getDate() + 1);

            return {
                id: String(task.id),
                title: task.title,
                start: task.starts_on || task.due_on,
                end: dateOnly(end),
                allDay: true,
                url: task.url,
                backgroundColor: toneColor(task.status.tone),
                borderColor: toneColor(task.status.tone),
            };
        });
    };

    var renderCalendar = function () {
        if (currentView !== 'calendar') {
            return;
        }

        if (!calendar) {
            calendar = new FullCalendar.Calendar(page.querySelector('[data-calendar]'), {
                initialView: 'dayGridMonth',
                locale: 'id',
                firstDay: 1,
                height: 'auto',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                buttonText: { today: 'Hari ini', month: 'Bulan', list: 'Daftar' },
                noEventsText: 'Tidak ada tugas pada periode ini.',
                initialDate: state.today || undefined,
            });
            calendar.render();
        }

        calendar.removeAllEvents();
        calendar.addEventSource(calendarEvents());
    };

    // ---- Tampilan --------------------------------------------------------------------------

    var render = function () {
        renderMine();
        renderPrograms();
        renderCalendar();
    };

    var showView = function (view) {
        currentView = view;
        page.querySelectorAll('[data-view]').forEach(function (button) {
            var isActive = button.getAttribute('data-view') === view;

            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        page.querySelectorAll('[data-view-panel]').forEach(function (panel) {
            panel.classList.toggle('d-none', panel.getAttribute('data-view-panel') !== view);
        });

        try {
            localStorage.setItem(VIEW_KEY, view);
        } catch (storageError) {
            // Penyimpanan peramban tidak tersedia; tampilan tetap berfungsi.
        }

        renderCalendar();
    };

    page.querySelectorAll('[data-view]').forEach(function (button) {
        button.addEventListener('click', function () {
            showView(button.getAttribute('data-view'));
        });
    });

    page.querySelector('[data-filter="search"]').addEventListener('input', function (inputEvent) {
        filters.search = inputEvent.target.value.trim().toLowerCase();
        renderPrograms();
    });

    $(page.querySelector('[data-filter="division"]')).on('change', function () {
        filters.division = this.value || '';
        renderPrograms();
    });

    page.querySelector('[data-filter="hide-done"]').addEventListener('change', function (changeEvent) {
        filters.hideDone = changeEvent.target.checked;
        renderPrograms();
    });

    /**
     * Tampilan pertama: pilihan terakhir orang ini, atau Tugas Saya bila ada yang menunggunya.
     */
    var initialView = function (openMine) {
        try {
            var saved = localStorage.getItem(VIEW_KEY);

            if (saved && page.querySelector('[data-view="' + saved + '"]')) {
                return saved;
            }
        } catch (storageError) {
            // Abaikan: pakai pilihan bawaan.
        }

        return openMine > 0 ? 'mine' : 'programs';
    };

    var load = function () {
        return OrganizerHttp.request({ url: page.dataset.dataUrl }).then(function (data) {
            state.programs = data.programs;
            state.tasks = data.tasks;
            state.today = data.today;
            state.canCreateTasks = data.can_create_tasks;
            renderSummary(data.summary);

            var openMine = renderMine();

            if (currentView === null) {
                showView(initialView(openMine));
            }

            render();
        }).catch(OrganizerHttp.showError);
    };

    // ---- Form program kerja ----------------------------------------------------------------

    var ProgramForm = (function () {
        var modalElement = document.getElementById('kt_modal_program');

        if (!modalElement) {
            return null;
        }

        var form = modalElement.querySelector('form');
        var submitButton = form.querySelector('[data-program-form-submit]');
        var modal = new bootstrap.Modal(modalElement);
        var advanced = form.querySelector('[data-program-advanced]');
        var advancedToggle = form.querySelector('[data-program-advanced-toggle]');
        var submitUrl = null;
        var baseUrl = page.dataset.programBaseUrl;

        var showAdvanced = function (isShown) {
            advanced.classList.toggle('d-none', !isShown);
            advancedToggle.setAttribute('aria-expanded', isShown ? 'true' : 'false');
            form.querySelector('[data-program-advanced-label]').textContent = isShown ? 'Sembunyikan detail' : 'Detail lainnya';
        };

        advancedToggle.addEventListener('click', function () {
            showAdvanced(advanced.classList.contains('d-none'));
        });

        var reset = function () {
            form.reset();
            OrganizerHttp.clearFieldErrors(form);
            OrganizerDatePicker.clearAll(form);
            $(form.elements.division_id).val('').trigger('change');
            $(form.elements.status).val('active').trigger('change');
            OrganizerSelect.reset(form.elements.pic_membership_id);
            form.elements.sort_order.value = '0';
            showAdvanced(false);
        };

        var open = function (title) {
            modalElement.querySelector('[data-program-form-title]').textContent = title;
            modal.show();
        };

        form.addEventListener('submit', function (submitEvent) {
            submitEvent.preventDefault();

            OrganizerHttp.submit(form, submitButton, { url: submitUrl }).then(function () {
                modal.hide();

                return OrganizerHttp.showSuccess('Program kerja berhasil disimpan.').then(load);
            }).catch(function () {
                // Error di bagian yang tersembunyi tetap harus terlihat.
                if (advanced.querySelector('.is-invalid')) {
                    showAdvanced(true);
                }
            });
        });

        return {
            openForCreate: function () {
                reset();
                submitUrl = page.dataset.programInsertUrl;
                open('Tambah Program Kerja');
            },
            openForEdit: function (id) {
                OrganizerHttp.request({ url: baseUrl + '/' + id + '/show' }).then(function (program) {
                    reset();
                    submitUrl = baseUrl + '/' + id + '/update';
                    ['title', 'objective', 'output', 'indicator', 'sort_order'].forEach(function (name) {
                        form.elements[name].value = program[name] === null ? '' : program[name];
                    });
                    $(form.elements.division_id).val(String(program.division_id)).trigger('change');
                    $(form.elements.status).val(program.status).trigger('change');
                    OrganizerSelect.setValue(form.elements.pic_membership_id, program.pic_membership_id, program.pic_membership_id_label);
                    OrganizerDatePicker.setValue(form.elements.starts_on, program.starts_on || '');
                    OrganizerDatePicker.setValue(form.elements.ends_on, program.ends_on || '');
                    showAdvanced(Boolean(program.objective || program.output || program.indicator || program.status !== 'active'));
                    open('Ubah Program Kerja');
                }).catch(OrganizerHttp.showError);
            },
            remove: function (program) {
                OrganizerHttp.confirm({
                    title: 'Hapus program kerja?',
                    text: 'Program "' + program.title + '" akan dihapus. Program yang sudah memiliki tugas tidak dapat dihapus.',
                    confirmText: 'Ya, hapus',
                }).then(function (confirmed) {
                    if (!confirmed) {
                        return null;
                    }

                    return OrganizerHttp.request({ url: baseUrl + '/' + program.id + '/delete', method: 'POST' })
                        .then(function () {
                            return OrganizerHttp.showSuccess('Program kerja dihapus.').then(load);
                        })
                        .catch(OrganizerHttp.showError);
                });
            },
        };
    })();

    var addProgramButton = page.querySelector('[data-program-add]');

    if (addProgramButton && ProgramForm) {
        addProgramButton.addEventListener('click', ProgramForm.openForCreate);
    }

    var addTaskButton = page.querySelector('[data-task-add]');

    if (addTaskButton && TaskForm) {
        addTaskButton.addEventListener('click', function () {
            TaskForm.openForCreate(page.dataset.taskInsertUrl, null, load);
        });
    }

    load();

    return { reload: load };
})();
