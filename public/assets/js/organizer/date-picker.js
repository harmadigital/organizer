"use strict";

/**
 * Pemilih tanggal seragam untuk seluruh aplikasi, memakai flatpickr dari bundle Metronic.
 *
 *   <input type="text" name="starts_on" class="form-control form-control-solid" data-date-picker>
 *   <input type="text" name="ends_on" ... data-date-picker data-date-min="#event_starts_on">
 *
 * Nilai yang dikirim ke server tetap YYYY-MM-DD; yang dilihat pengguna "3 April 2027".
 * Jangan memakai <input type="date">. Di ponsel pun flatpickr yang dipakai, bukan pemilih bawaan.
 *
 *   OrganizerDatePicker.setValue(input, '2027-04-03');
 *   OrganizerDatePicker.clearAll(form);
 */
var OrganizerDatePicker = (function () {
    var defaults = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j F Y',
        allowInput: true,
        disableMobile: true,
        locale: 'id',
    };

    /**
     * Input yang batas awalnya mengikuti input ini (misalnya tanggal selesai mengikuti tanggal mulai).
     */
    var dependentsOf = function (source) {
        return source.id ? document.querySelectorAll('[data-date-min="#' + source.id + '"]') : [];
    };

    var syncDependents = function (source) {
        dependentsOf(source).forEach(function (target) {
            if (target._flatpickr) {
                target._flatpickr.set('minDate', source.value || null);
            }
        });
    };

    /**
     * Esc saat kalender terbuka hanya menutup kalender, bukan modal tempat form berada.
     */
    var keepEscapeForTheCalendar = function (instance) {
        var container = instance.config.static ? instance.calendarContainer.parentNode : instance.altInput;

        container.addEventListener('keydown', function (keyEvent) {
            if (keyEvent.key === 'Escape' && instance.isOpen) {
                keyEvent.preventDefault();
                keyEvent.stopPropagation();
                instance.close();
            }
        });
    };

    var attach = function (input) {
        if (input._flatpickr || typeof flatpickr === 'undefined') {
            return;
        }

        // flatpickr mengubah input asli menjadi hidden, dan input hidden tidak punya label.
        var labels = Array.from(input.labels || []);
        var accessibleName = input.getAttribute('aria-label') || (labels[0] ? labels[0].textContent.trim() : 'Tanggal');

        flatpickr(input, Object.assign({}, defaults, {
            altInputClass: input.className,
            // Di dalam modal, kalender diletakkan di samping input agar tetap berada di dalam
            // modal; kalau ditempel ke <body>, modal Bootstrap merebut fokusnya.
            static: input.closest('.modal') !== null,
            onChange: function () {
                syncDependents(input);
            },
            onReady: function (selectedDates, dateString, instance) {
                instance.altInput.setAttribute('aria-label', accessibleName);
                keepEscapeForTheCalendar(instance);

                if (input.id) {
                    instance.altInput.id = input.id + '_display';
                    labels.forEach(function (label) {
                        label.htmlFor = instance.altInput.id;
                    });
                }
            },
        }));
    };

    var init = function (root) {
        (root || document).querySelectorAll('input[data-date-picker]').forEach(attach);
    };

    var setValue = function (input, value) {
        if (!input._flatpickr) {
            input.value = value || '';

            return;
        }

        input._flatpickr.setDate(value || null, false);
        syncDependents(input);
    };

    var clearAll = function (root) {
        root.querySelectorAll('input[data-date-picker]').forEach(function (input) {
            if (input._flatpickr) {
                input._flatpickr.clear(false);
                input._flatpickr.set('minDate', null);
            }
        });
    };

    // Skrip ini dimuat di akhir <body>, jadi seluruh isi halaman (termasuk modal) sudah tersedia.
    init(document);

    return {
        init: init,
        setValue: setValue,
        clearAll: clearAll,
    };
})();
