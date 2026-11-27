"use strict";

/**
 * Laporan Kondisi (#kt_modal_event_summary): keadaan seluruh event untuk Pengurus Inti dan
 * Pengawas. Data dimuat ulang setiap kali jendela dibuka atau tombol Muat ulang ditekan.
 *
 *   - Angka utama: kondisi, hari menuju acara, pekerjaan selesai dibanding waktu persiapan,
 *     tugas terlambat.
 *   - Progres per seksi (klik untuk menyaring), status semua tugas (diagram donat).
 *   - Perlu perhatian dan program kerja, keduanya ikut saringan seksi.
 *   - Rapat & tindak lanjut (diagram kehadiran), jadwal dua minggu, kepanitiaan.
 *
 * Semua teks dari server dipasang lewat textContent, tidak pernah lewat innerHTML.
 */
(function () {
    var modalElement = document.getElementById('kt_modal_event_summary');

    if (!modalElement) {
        return;
    }

    var modal = new bootstrap.Modal(modalElement);
    var part = function (name) {
        return modalElement.querySelector('[data-summary-' + name + ']');
    };
    var divisionSelect = part('division');
    var charts = [];
    var report = null;
    var requestNumber = 0;

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

    var badge = function (label, tone) {
        return el('span', 'badge badge-light-' + tone, label);
    };

    var link = function (url, text, className) {
        var anchor = el('a', className || 'ui-task-title', text);

        anchor.href = url;

        return anchor;
    };

    var empty = function (text) {
        return el('div', 'text-muted fs-6 py-2', text);
    };

    var percentText = function (percent) {
        return percent === null ? 'N/A' : percent + '%';
    };

    var color = function (name) {
        return getComputedStyle(document.documentElement).getPropertyValue('--bs-' + name).trim() || '#a1a5b7';
    };

    var destroyCharts = function () {
        charts.forEach(function (chart) {
            chart.destroy();
        });
        charts = [];
    };

    var drawChart = function (target, options) {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var chart = new ApexCharts(target, options);

        chart.render();
        charts.push(chart);
    };

    /**
     * Satu kotak angka utama.
     */
    var tile = function (label, value, note, tone, extra) {
        var column = el('div', 'col-sm-6 col-xl-3');
        var box = el('div', 'ui-summary-tile');

        box.appendChild(el('div', 'ui-summary-tile-label', label));
        box.appendChild(el('div', 'ui-summary-tile-value' + (tone ? ' text-' + tone : ''), value));

        if (note) {
            box.appendChild(el('div', 'ui-summary-tile-note', note));
        }

        if (extra) {
            box.appendChild(extra);
        }

        column.appendChild(box);

        return column;
    };

    var compareBar = function (label, percent, tone) {
        var row = el('div', 'ui-summary-compare');
        var track = el('div', 'progress h-6px bg-light flex-grow-1');
        var bar = el('div', 'progress-bar bg-' + tone);

        bar.style.width = (percent || 0) + '%';
        track.appendChild(bar);
        row.append(el('span', 'ui-summary-compare-label', label), track, el('span', 'ui-summary-compare-value', percentText(percent)));

        return row;
    };

    var renderKpis = function () {
        var target = part('kpis');
        var work = report.work;
        var event = report.event;
        var comparison = el('div', 'mt-3');

        comparison.append(compareBar('Pekerjaan', work.percent, 'primary'), compareBar('Waktu', report.elapsed_percent, 'gray-400'));

        target.replaceChildren(
            tile('Kondisi', work.health.label, work.health.reason, work.health.tone),
            tile('Menuju hari pelaksanaan', event.countdown, [event.date_label, event.venue].filter(Boolean).join(' · ')),
            tile('Pekerjaan selesai', percentText(work.percent), work.total > 0 ? work.done + ' dari ' + work.total + ' tugas selesai' : 'Belum ada tugas', null, comparison),
            tile('Tugas terlambat', String(work.overdue), work.due_soon + ' tenggat dalam 7 hari · ' + work.open + ' belum selesai', work.overdue > 0 ? 'danger' : 'success')
        );
    };

    /**
     * Batang bertumpuk: selesai, belum selesai, terlambat.
     */
    var stackedBar = function (division) {
        var bar = el('div', 'ui-summary-stack');
        var parts = [
            ['success', division.done, 'Selesai'],
            ['primary', division.open - division.overdue, 'Belum selesai'],
            ['danger', division.overdue, 'Terlambat'],
        ];

        parts.forEach(function (piece) {
            if (piece[1] > 0) {
                var segment = el('span', 'ui-summary-stack-part bg-' + piece[0]);

                segment.style.flexGrow = piece[1];
                segment.title = piece[2] + ': ' + piece[1];
                bar.appendChild(segment);
            }
        });

        return bar;
    };

    var renderDivisions = function () {
        var target = part('divisions');

        target.replaceChildren();

        if (report.divisions.length === 0) {
            target.appendChild(empty('Belum ada seksi.'));

            return;
        }

        report.divisions.forEach(function (division) {
            var row = el('button', 'ui-summary-division');
            var head = el('div', 'ui-summary-division-head');
            var name = el('span', 'ui-summary-division-name', division.name);
            var counts = division.total > 0
                ? division.done + '/' + division.total + ' selesai' + (division.overdue > 0 ? ' · ' + division.overdue + ' terlambat' : '')
                : 'Belum ada tugas';

            row.type = 'button';
            row.dataset.division = division.key;
            row.setAttribute('aria-pressed', 'false');
            head.append(name, badge(division.health.label, division.health.tone), el('span', 'ui-summary-division-percent', percentText(division.percent)));
            row.append(head, division.total > 0 ? stackedBar(division) : el('div', 'ui-summary-stack'), el('div', 'ui-summary-division-meta', counts));
            row.addEventListener('click', function () {
                selectDivision(divisionSelect.value === division.key ? '' : division.key);
            });
            target.appendChild(row);
        });

        var legend = el('div', 'ui-summary-legend');

        [['success', 'Selesai'], ['primary', 'Belum selesai'], ['danger', 'Terlambat']].forEach(function (item) {
            var entry = el('span');

            entry.append(el('span', 'ui-calendar-dot ui-calendar-dot--' + item[0]), document.createTextNode(' ' + item[1]));
            legend.appendChild(entry);
        });
        target.appendChild(legend);
    };

    var renderStatusChart = function () {
        var target = part('status-chart');
        var work = report.work;
        var values = [work.done, work.open - work.overdue, work.overdue];

        target.replaceChildren();

        if (work.total === 0) {
            target.appendChild(empty('Belum ada tugas.'));

            return;
        }

        drawChart(target, {
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            series: values,
            labels: ['Selesai', 'Belum selesai', 'Terlambat'],
            colors: [color('success'), color('primary'), color('danger')],
            legend: { position: 'bottom', formatter: function (name, options) {
                return name + ': ' + values[options.seriesIndex];
            } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Tugas', formatter: function () {
                return String(work.total);
            } } } } } },
            tooltip: { y: { formatter: function (value) {
                return value + ' tugas';
            } } },
        });
    };

    var inSelectedDivision = function (item) {
        return !divisionSelect.value || item.division_key === divisionSelect.value;
    };

    var renderAttention = function () {
        ['overdue', 'upcoming'].forEach(function (kind) {
            var target = part('list="' + kind + '"');
            var items = report.attention[kind].items.filter(inSelectedDivision);

            target.replaceChildren();
            part('count="' + kind + '"').textContent = divisionSelect.value ? items.length : report.attention[kind].total;

            if (items.length === 0) {
                target.appendChild(empty(kind === 'overdue' ? 'Tidak ada tugas yang terlambat.' : 'Tidak ada tenggat dalam dua minggu ke depan.'));

                return;
            }

            items.forEach(function (item) {
                var row = el('div', 'ui-summary-row');
                var body = el('div', 'flex-grow-1 min-w-0');
                var meta = el('div', 'ui-task-meta');

                body.appendChild(link(item.url, item.title));
                [item.parent ? 'Bagian dari: ' + item.parent : null, item.division, item.owner || 'Belum ada penanggung jawab', item.due_label]
                    .filter(Boolean)
                    .forEach(function (text) {
                        meta.appendChild(el('span', null, text));
                    });
                body.appendChild(meta);
                row.append(body, badge(item.when, kind === 'overdue' ? 'danger' : 'warning'));
                target.appendChild(row);
            });

            if (!divisionSelect.value && report.attention[kind].total > items.length) {
                target.appendChild(empty('Dan ' + (report.attention[kind].total - items.length) + ' tugas lainnya di Program & Tugas.'));
            }
        });
    };

    var renderPrograms = function () {
        var target = part('programs');
        var programs = report.programs.filter(inSelectedDivision);

        target.replaceChildren();

        if (programs.length === 0) {
            target.appendChild(empty('Belum ada program kerja.'));

            return;
        }

        programs.forEach(function (program) {
            var row = el('div', 'ui-summary-row flex-column align-items-stretch');
            var head = el('div', 'd-flex align-items-start justify-content-between gap-3');
            var title = el('div', 'min-w-0');
            var badges = el('div', 'd-flex flex-wrap justify-content-end gap-1 flex-shrink-0');
            var meta = el('div', 'ui-task-meta');
            var progress = el('div', 'd-flex align-items-center gap-3 mt-2');
            var track = el('div', 'progress h-6px bg-light flex-grow-1');
            var bar = el('div', 'progress-bar ' + (program.needs_attention ? 'bg-danger' : 'bg-primary'));

            title.appendChild(el('div', 'ui-task-title', program.title));
            [program.division, program.pic ? 'PJ ' + program.pic : null, program.period, program.tasks + ' tugas']
                .filter(Boolean)
                .forEach(function (text) {
                    meta.appendChild(el('span', null, text));
                });
            title.appendChild(meta);

            if (program.is_past_period) {
                badges.appendChild(badge('Lewat periode', 'danger'));
            }

            if (program.overdue > 0) {
                badges.appendChild(badge(program.overdue + ' terlambat', 'danger'));
            }

            badges.appendChild(badge(program.status.label, program.status.tone));
            head.append(title, badges);

            bar.style.width = (program.percent || 0) + '%';
            track.appendChild(bar);
            progress.append(track, el('span', 'fw-semibold fs-7 text-gray-800 ui-summary-compare-value', percentText(program.percent)));
            row.append(head, progress);
            target.appendChild(row);
        });
    };

    var statLine = function (label, value, tone) {
        var row = el('div', 'ui-summary-stat');

        row.append(el('span', 'text-gray-700', label), el('span', 'fw-bold ' + (tone ? 'text-' + tone : 'text-gray-900'), String(value)));

        return row;
    };

    var heading = function (text) {
        return el('div', 'ui-info-label mt-5 mb-2', text);
    };

    var renderMeetings = function () {
        var target = part('meetings');
        var meetings = report.meetings;
        var followUps = meetings.follow_ups;

        target.replaceChildren();

        var next = el('div', 'ui-summary-next');

        next.appendChild(el('div', 'ui-info-label mb-1', 'Rapat berikutnya'));

        if (meetings.next) {
            next.appendChild(link(meetings.next.url, meetings.next.title));
            next.appendChild(el('div', 'text-muted fs-7 mt-1', [meetings.next.date_label, meetings.next.time_label, meetings.next.location].filter(Boolean).join(' · ')));
        } else {
            next.appendChild(el('div', 'text-muted fs-6', 'Belum ada rapat yang dijadwalkan.'));
        }

        target.appendChild(next);
        target.append(
            statLine('Rapat yang sudah berlangsung', meetings.held),
            statLine('Belum ada notulen', meetings.without_minutes.length, meetings.without_minutes.length > 0 ? 'warning' : null),
            statLine('Tindak lanjut belum selesai', followUps.open + ' dari ' + followUps.total, followUps.overdue > 0 ? 'danger' : null)
        );

        if (meetings.attendance.length > 0) {
            target.appendChild(heading('Kehadiran rapat (dari ' + meetings.members + ' panitia aktif)'));
            var chartTarget = el('div');

            target.appendChild(chartTarget);
            drawChart(chartTarget, {
                chart: { type: 'bar', height: 180, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Hadir', data: meetings.attendance.map(function (meeting) {
                    return meeting.present;
                }) }],
                xaxis: { categories: meetings.attendance.map(function (meeting) {
                    return meeting.date_label;
                }) },
                yaxis: { max: Math.max(meetings.members, 1), tickAmount: 4, labels: { formatter: function (value) {
                    return Math.round(value);
                } } },
                colors: [color('primary')],
                plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
                dataLabels: { enabled: true },
                grid: { strokeDashArray: 4 },
                tooltip: { x: { formatter: function (value, options) {
                    return meetings.attendance[options.dataPointIndex].title + ' · ' + value;
                } }, y: { formatter: function (value) {
                    return value + ' dari ' + meetings.members + ' orang';
                } } },
            });
        }

        if (followUps.items.length > 0) {
            target.appendChild(heading('Tindak lanjut yang belum selesai'));
            followUps.items.forEach(function (item) {
                var row = el('div', 'ui-summary-row');
                var body = el('div', 'flex-grow-1 min-w-0');
                var meta = el('div', 'ui-task-meta');

                body.appendChild(link(item.url, item.title));
                [item.meeting, item.owner, item.due_label].filter(Boolean).forEach(function (text) {
                    meta.appendChild(el('span', null, text));
                });
                body.appendChild(meta);
                row.appendChild(body);

                if (item.is_overdue) {
                    row.appendChild(badge('Terlambat', 'danger'));
                }

                target.appendChild(row);
            });
        }

        if (meetings.without_minutes.length > 0) {
            target.appendChild(heading('Rapat yang belum ada notulennya'));
            meetings.without_minutes.forEach(function (meeting) {
                var row = el('div', 'ui-summary-row');

                row.append(link(meeting.url, meeting.title, 'ui-task-title flex-grow-1'), el('span', 'text-muted fs-7', meeting.date_label));
                target.appendChild(row);
            });
        }
    };

    var renderSchedule = function () {
        var target = part('schedule');

        target.replaceChildren();

        if (report.schedule.length === 0) {
            target.appendChild(empty('Tidak ada jadwal dalam dua minggu ke depan.'));

            return;
        }

        report.schedule.forEach(function (day) {
            target.appendChild(el('div', 'ui-info-label mt-4 mb-2' + (day.is_today ? ' text-primary' : ''), day.is_today ? 'Hari ini · ' + day.date_label : day.date_label));

            day.items.forEach(function (item) {
                var row = el('div', 'ui-summary-row ui-summary-schedule-row' + (item.is_done ? ' is-done' : ''));
                var body = el('div', 'flex-grow-1 min-w-0');

                body.appendChild(item.url ? link(item.url, item.title) : el('span', 'ui-task-title', item.title));
                body.appendChild(el('div', 'ui-task-meta', [item.type_label, item.time_label, item.is_done ? 'Selesai' : null].filter(Boolean).join(' · ')));
                row.append(el('span', 'ui-calendar-dot ui-calendar-dot--' + item.tone + ' mt-2'), body);
                target.appendChild(row);
            });
        });
    };

    var warningList = function (title, names, emptyText) {
        var block = el('div', 'mt-5');

        block.appendChild(el('div', 'ui-info-label mb-2', title));

        if (names.length === 0) {
            block.appendChild(el('div', 'text-success fs-6', emptyText));

            return block;
        }

        var list = el('div', 'd-flex flex-wrap gap-2');

        names.forEach(function (name) {
            list.appendChild(badge(name, 'warning'));
        });
        block.appendChild(list);

        return block;
    };

    var renderCommittee = function () {
        var target = part('committee');
        var committee = report.committee;

        target.replaceChildren(
            statLine('Panitia aktif', committee.active + ' orang'),
            statLine('Undangan belum dijawab', committee.invited, committee.invited > 0 ? 'warning' : null),
            statLine('Berkas di Media & Dokumen', committee.documents),
            warningList('Jabatan wajib yang belum terisi', committee.missing_roles, 'Semua jabatan wajib sudah terisi.'),
            warningList('Seksi tanpa koordinator', committee.without_coordinator, 'Semua seksi punya koordinator.'),
            warningList('Seksi yang belum punya tugas', committee.without_tasks, 'Semua seksi sudah punya tugas.')
        );

        var targets = [report.event.target_participants ? 'Target peserta ' + report.event.target_participants : null, report.event.budget_target ? 'Target anggaran ' + report.event.budget_target : null].filter(Boolean);

        if (targets.length > 0) {
            target.appendChild(el('div', 'text-muted fs-7 mt-5', targets.join(' · ')));
        }
    };

    var fillDivisionOptions = function () {
        var keep = divisionSelect.value;

        divisionSelect.replaceChildren(new Option('', ''));
        report.divisions.forEach(function (division) {
            divisionSelect.appendChild(new Option(division.name, division.key));
        });
        divisionSelect.value = report.divisions.some(function (division) {
            return division.key === keep;
        }) ? keep : '';
        $(divisionSelect).trigger('change.select2');
    };

    var markSelectedDivision = function () {
        part('divisions').querySelectorAll('[data-division]').forEach(function (row) {
            var isSelected = row.dataset.division === divisionSelect.value;

            row.classList.toggle('is-selected', isSelected);
            row.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });
    };

    var renderFiltered = function () {
        markSelectedDivision();
        renderAttention();
        renderPrograms();
    };

    var selectDivision = function (key) {
        divisionSelect.value = key;
        $(divisionSelect).trigger('change.select2');
        renderFiltered();
    };

    $(divisionSelect).on('change', renderFiltered);

    var show = function (state) {
        part('loading').classList.toggle('d-none', state !== 'loading');
        part('failed').classList.toggle('d-none', state !== 'failed');
        part('content').classList.toggle('d-none', state !== 'ready');
    };

    var render = function () {
        destroyCharts();
        part('generated').textContent = 'Per ' + report.generated_at;
        fillDivisionOptions();
        renderKpis();
        renderDivisions();
        renderStatusChart();
        renderFiltered();
        renderMeetings();
        renderSchedule();
        renderCommittee();
    };

    var load = function () {
        var number = ++requestNumber;

        if (!report) {
            show('loading');
        }

        modalElement.querySelectorAll('[data-summary-refresh]').forEach(function (button) {
            button.disabled = true;
        });

        OrganizerHttp.request({ url: modalElement.dataset.summaryUrl }).then(function (data) {
            if (number !== requestNumber) {
                return;
            }

            report = data;
            show('ready');
            render();
        }).catch(function (error) {
            if (number !== requestNumber) {
                return;
            }

            if (!report) {
                show('failed');
            }

            OrganizerHttp.showError(error);
        }).finally(function () {
            modalElement.querySelectorAll('[data-summary-refresh]').forEach(function (button) {
                button.disabled = false;
            });
        });
    };

    modalElement.querySelectorAll('[data-summary-refresh]').forEach(function (button) {
        button.addEventListener('click', load);
    });

    document.querySelectorAll('[data-summary-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            modal.show();
            load();
        });
    });

    // Diagram digambar ulang dari data terakhir saat jendela dibuka lagi, agar ukurannya pas.
    modalElement.addEventListener('hidden.bs.modal', destroyCharts);
})();
