"use strict";

/**
 * Kalender bersama untuk tab Kalender event dan Kalender Saya, di atas FullCalendar 5 yang
 * sudah dimuat oleh layout.
 *
 *   var calendar = OrganizerCalendar.create(element, {
 *       feedUrl: 'https://.../calendar/feed',
 *       params: function () { return 'division=3'; },   // parameter tambahan (opsional)
 *       today: '2026-11-27',                              // tanggal hari ini menurut organisasi
 *       storageKey: 'organizer.calendar.view',            // tampilan terakhir diingat
 *       emptyNote: element,                               // catatan "belum ada jadwal" (opsional)
 *       showEventName: false,                             // nama event di baris daftar
 *       onItemClick: function (item, element) { ... },    // klik jadwal apa pun
 *       onDateClick: function (dateString) { ... },       // klik tanggal kosong (opsional)
 *   });
 *   calendar.refetch();
 *
 *   OrganizerCalendar.fillItemDetail(modalElement, item);   // isi jendela ringkasan
 *
 * Klik biasa pada jadwal tidak pindah halaman: onItemClick dipanggil untuk membuka jendela
 * ringkasan, yang punya tombol ke halaman rapat, tugas, atau event. Ctrl/Cmd/Shift+klik
 * (atau klik tengah) pada jadwal yang punya halaman tetap membukanya di tab baru. onItemClick
 * juga menerima bagian jadwal yang bisa difokus, agar halaman mengembalikan fokus ke sana
 * setelah jendela ditutup. Semua teks
 * dari server dipasang sebagai teks, tidak pernah lewat innerHTML.
 */
var OrganizerCalendar = (function () {
    var PHONE_QUERY = '(max-width: 767.98px)';
    var WIDE_VIEWS = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listMonth'];
    var PHONE_VIEWS = ['listMonth', 'dayGridMonth'];
    var TIME_FORMAT = { hour: '2-digit', minute: '2-digit', hour12: false };

    var isPhone = function () {
        return window.matchMedia(PHONE_QUERY).matches;
    };

    var toolbarsFor = function (phone) {
        return phone
            ? { header: { left: 'prev,next', center: 'title', right: 'today' }, footer: { center: 'listMonth,dayGridMonth' } }
            : { header: { left: 'prev,next today', center: 'title', right: WIDE_VIEWS.join(',') }, footer: false };
    };

    var readStoredView = function (storageKey) {
        try {
            return localStorage.getItem(storageKey);
        } catch (storageError) {
            return null;
        }
    };

    var storeView = function (storageKey, view) {
        try {
            localStorage.setItem(storageKey, view);
        } catch (storageError) {
            // Penyimpanan peramban tidak tersedia; kalender tetap berfungsi.
        }
    };

    /**
     * Tampilan pertama: pilihan terakhir bila tersedia di lebar layar ini.
     */
    var initialView = function (storageKey, phone) {
        var allowed = phone ? PHONE_VIEWS : WIDE_VIEWS;
        var stored = readStoredView(storageKey);

        return allowed.indexOf(stored) !== -1 ? stored : allowed[0];
    };

    var classNamesFor = function (item) {
        var names = ['ui-cal-event', 'ui-cal-type-' + item.type, 'ui-cal-tone-' + item.tone];

        if (item.is_done) {
            names.push('is-done');
        }

        if (item.is_overdue) {
            names.push('is-overdue');
        }

        return names;
    };

    /**
     * Hanya jadwal yang punya halaman diberi url, supaya kegiatan tidak mendapat tautan kosong.
     */
    var toCalendarEvent = function (item) {
        var calendarEvent = {
            id: item.id,
            title: item.title,
            start: item.start,
            end: item.end || undefined,
            allDay: item.all_day,
            classNames: classNamesFor(item),
            extendedProps: { item: item },
        };

        if (item.url) {
            calendarEvent.url = item.url;
        }

        return calendarEvent;
    };

    var tooltipFor = function (item, showEventName) {
        return [
            item.title,
            item.type_label + ' · ' + item.date_label + (item.all_day ? '' : ' · ' + item.time_label),
            item.division,
            item.location ? 'Tempat: ' + item.location : null,
            showEventName ? 'Event: ' + item.event_name : null,
            item.is_done ? 'Selesai' : null,
            item.is_overdue ? 'Terlambat' : null,
        ].filter(Boolean).join('\n');
    };

    var listMetaFor = function (item, showEventName) {
        return [
            item.type === 'event' ? null : item.division,
            item.location,
            showEventName && item.type !== 'event' ? item.event_name : null,
            item.is_done ? 'Selesai' : null,
            item.is_overdue ? 'Terlambat' : null,
        ].filter(Boolean).join(' · ');
    };

    /**
     * Bagian jadwal yang bisa difokus, supaya fokus bisa kembali ke sana setelah jendela
     * ringkasan ditutup. Di tampilan Daftar, jadwal adalah baris tabel dengan tautan di dalamnya.
     */
    var focusTargetOf = function (eventElement) {
        var focusable = 'a[href], [tabindex]';

        if (eventElement.contains(document.activeElement)) {
            return document.activeElement;
        }

        return eventElement.matches(focusable) ? eventElement : (eventElement.querySelector(focusable) || eventElement);
    };

    var create = function (element, options) {
        var params = options.params || function () {
            return '';
        };
        var phone = isPhone();
        var toolbars = toolbarsFor(phone);
        var calendar = null;

        var feedUrlFor = function (info) {
            var query = 'start=' + info.startStr.slice(0, 10) + '&end=' + info.endStr.slice(0, 10);
            var extra = params();

            return options.feedUrl + (options.feedUrl.indexOf('?') === -1 ? '?' : '&') + query + (extra ? '&' + extra : '');
        };

        /**
         * Di tampilan kotak, catatan "belum ada jadwal" muncul di bawah kalender bila periode
         * yang terlihat kosong. Tampilan daftar menampilkan pesannya sendiri.
         */
        var toggleEmptyNote = function () {
            if (!options.emptyNote || !calendar) {
                return;
            }

            var view = calendar.view;
            var hasItems = calendar.getEvents().some(function (calendarEvent) {
                var end = calendarEvent.end || calendarEvent.start;

                return calendarEvent.start < view.activeEnd && end >= view.activeStart;
            });

            options.emptyNote.classList.toggle('d-none', hasItems || view.type.indexOf('list') === 0);
        };

        calendar = new FullCalendar.Calendar(element, {
            locale: 'id',
            firstDay: 1,
            now: options.today,
            initialDate: options.today,
            initialView: initialView(options.storageKey, phone),
            height: 'auto',
            headerToolbar: toolbars.header,
            footerToolbar: toolbars.footer,
            buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', day: 'Hari', list: 'Daftar' },
            allDayText: 'Seharian',
            dayMaxEvents: 3,
            moreLinkContent: function (arg) {
                return '+' + arg.num + ' lainnya';
            },
            noEventsText: 'Tidak ada jadwal pada periode ini.',
            eventTimeFormat: TIME_FORMAT,
            slotLabelFormat: TIME_FORMAT,
            slotMinTime: '06:00:00',
            slotMaxTime: '23:00:00',
            events: function (info, success, failure) {
                OrganizerHttp.request({ url: feedUrlFor(info) }).then(function (data) {
                    success(data.items.map(toCalendarEvent));
                }).catch(function (error) {
                    failure(error);
                    OrganizerHttp.showError(error);
                });
            },
            eventClick: function (info) {
                var item = info.event.extendedProps.item;
                var click = info.jsEvent;

                // Mencegah FullCalendar pindah halaman sendiri.
                click.preventDefault();

                if (item.url && (click.ctrlKey || click.metaKey || click.shiftKey)) {
                    window.open(item.url, '_blank', 'noopener');

                    return;
                }

                if (options.onItemClick) {
                    options.onItemClick(item, focusTargetOf(info.el));
                }
            },
            eventDidMount: function (info) {
                var item = info.event.extendedProps.item;

                info.el.setAttribute('title', tooltipFor(item, options.showEventName));

                if (info.view.type.indexOf('list') !== 0) {
                    return;
                }

                var meta = listMetaFor(item, options.showEventName);
                var titleCell = info.el.querySelector('.fc-list-event-title');

                if (meta && titleCell) {
                    var span = document.createElement('span');

                    span.className = 'ui-calendar-meta';
                    span.textContent = meta;
                    titleCell.appendChild(span);
                }
            },
            dateClick: options.onDateClick ? function (info) {
                if (info.view.type === 'dayGridMonth') {
                    options.onDateClick(info.dateStr.slice(0, 10));
                }
            } : undefined,
            datesSet: function (info) {
                storeView(options.storageKey, info.view.type);
                toggleEmptyNote();
            },
            eventsSet: toggleEmptyNote,
        });

        calendar.render();

        // Saat layar melewati batas ponsel (misalnya diputar), susunan tombol ikut berganti.
        window.matchMedia(PHONE_QUERY).addEventListener('change', function (change) {
            var next = toolbarsFor(change.matches);

            calendar.setOption('headerToolbar', next.header);
            calendar.setOption('footerToolbar', next.footer);

            if (change.matches && PHONE_VIEWS.indexOf(calendar.view.type) === -1) {
                calendar.changeView('listMonth');
            }
        });

        return {
            calendar: calendar,
            refetch: function () {
                calendar.refetchEvents();
            },
            setParams: function (nextParams) {
                params = nextParams;
                calendar.refetchEvents();
            },
        };
    };

    var TONES = ['info', 'primary', 'success', 'warning', 'danger', 'secondary'];

    var toneOf = function (tone) {
        return TONES.indexOf(tone) === -1 ? 'secondary' : tone;
    };

    var clear = function (element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    };

    /**
     * Isi jendela ringkasan dari item kalender: jenis, judul, label status, baris ringkasan,
     * catatan, dan tombol ke halamannya. Baris disusun server dan hanya yang berisi.
     */
    var fillItemDetail = function (modalElement, item) {
        var dot = modalElement.querySelector('[data-item-dot]');
        var badges = modalElement.querySelector('[data-item-badges]');
        var rows = modalElement.querySelector('[data-item-rows]');
        var note = modalElement.querySelector('[data-item-note]');
        var openLink = modalElement.querySelector('[data-item-open]');

        dot.className = 'ui-calendar-dot ui-calendar-dot--' + toneOf(item.tone) + ' me-1';
        modalElement.querySelector('[data-item-type]').textContent = item.type_label;
        modalElement.querySelector('[data-item-title]').textContent = item.detail_title || item.title;

        clear(badges);
        (item.badges || []).forEach(function (badge) {
            var span = document.createElement('span');

            span.className = 'badge badge-light-' + toneOf(badge.tone);
            span.textContent = badge.label;
            badges.appendChild(span);
        });
        badges.classList.toggle('d-none', !badges.firstChild);

        clear(rows);
        (item.summary || []).forEach(function (summaryRow) {
            var row = document.createElement('div');
            var label = document.createElement('span');
            var value = document.createElement('span');

            row.className = 'ui-calendar-detail-row';
            label.className = 'ui-calendar-detail-label';
            label.textContent = summaryRow.label;
            value.className = 'ui-calendar-detail-value';
            value.textContent = summaryRow.value;
            row.appendChild(label);
            row.appendChild(value);
            rows.appendChild(row);
        });

        note.classList.toggle('d-none', !item.note);
        modalElement.querySelector('[data-item-note-label]').textContent = item.note ? item.note.label : '';
        modalElement.querySelector('[data-item-note-text]').textContent = item.note ? item.note.text : '';

        openLink.classList.toggle('d-none', !item.url);
        openLink.setAttribute('href', item.url || '#');
        openLink.textContent = item.open_label || 'Buka halaman';
    };

    return {
        create: create,
        fillItemDetail: fillItemDetail,
    };
})();
