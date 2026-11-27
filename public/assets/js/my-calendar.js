"use strict";

/**
 * Kalender Saya: jadwal dari semua event yang diikuti, hanya untuk dilihat. Pemegang
 * events.view bisa beralih ke "Semua event organisasi". Klik jadwal apa pun membuka
 * ringkasannya dulu: rapat, tugas, dan hari pelaksanaan dengan tombol ke halamannya, kegiatan
 * dengan tautan ke kalender event-nya.
 */
(function () {
    var page = document.querySelector('[data-my-calendar-page]');

    if (!page) {
        return;
    }

    var scope = page.querySelector('[data-my-calendar-scope]');
    var calendarElement = page.querySelector('[data-calendar]');
    var detailElement = document.getElementById('kt_modal_calendar_item');
    var detailModal = new bootstrap.Modal(detailElement);
    var hasEvents = page.dataset.hasEvents === '1';
    var calendar = null;
    var returnFocus = null;

    var isOrganization = function () {
        return Boolean(scope) && scope.value === 'organization';
    };

    /**
     * Tanpa event yang diikuti, kalender diganti ajakan membuka daftar event, kecuali saat
     * melihat semua event organisasi.
     */
    var refreshEmptyState = function () {
        var showCalendar = hasEvents || isOrganization();

        calendarElement.classList.toggle('d-none', !showCalendar);
        page.querySelector('[data-my-calendar-hint]').classList.toggle('d-none', !showCalendar);
        page.querySelector('[data-my-calendar-no-events]').classList.toggle('d-none', showCalendar);

        if (!showCalendar) {
            page.querySelector('[data-calendar-empty]').classList.add('d-none');
        }

        return showCalendar;
    };

    var showItem = function (item, itemElement) {
        var calendarLink = detailElement.querySelector('[data-item-calendar-link]');

        returnFocus = itemElement || null;
        OrganizerCalendar.fillItemDetail(detailElement, item);
        calendarLink.classList.toggle('d-none', !item.entry);
        calendarLink.setAttribute('href', item.entry ? item.entry.calendar_url : '#');
        detailModal.show();
    };

    // Setelah ditutup, fokus kembali ke jadwal yang dibuka.
    detailElement.addEventListener('hidden.bs.modal', function () {
        if (returnFocus && document.body.contains(returnFocus)) {
            returnFocus.focus();
        }

        returnFocus = null;
    });

    var start = function () {
        if (calendar) {
            calendar.refetch();

            return;
        }

        calendar = OrganizerCalendar.create(calendarElement, {
            feedUrl: page.dataset.feedUrl,
            today: page.dataset.today,
            storageKey: 'organizer.my-calendar.view',
            emptyNote: page.querySelector('[data-calendar-empty]'),
            showEventName: true,
            params: function () {
                return isOrganization() ? 'scope=organization' : '';
            },
            onItemClick: showItem,
        });
    };

    if (scope) {
        $(scope).on('change', function () {
            if (refreshEmptyState()) {
                start();
            }
        });
    }

    if (refreshEmptyState()) {
        start();
    }
})();
