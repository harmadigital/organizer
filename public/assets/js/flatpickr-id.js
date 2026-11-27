"use strict";

/**
 * Bahasa Indonesia untuk flatpickr (bawaan bundle Metronic tidak menyertakannya),
 * sekaligus menjadikannya bahasa bawaan semua pemilih tanggal.
 */
(function () {
    if (typeof flatpickr === 'undefined') {
        return;
    }

    flatpickr.l10ns.id = {
        weekdays: {
            shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        },
        months: {
            shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        },
        firstDayOfWeek: 1,
        ordinal: function () {
            return '';
        },
        rangeSeparator: ' s.d. ',
        weekAbbreviation: 'Mg',
        scrollTitle: 'Gulir untuk mengubah',
        toggleTitle: 'Klik untuk mengganti',
        time_24hr: true,
    };

    flatpickr.localize(flatpickr.l10ns.id);
})();
