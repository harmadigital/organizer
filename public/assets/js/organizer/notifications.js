"use strict";

/**
 * Lonceng: "Tandai semua dibaca" di dropdown lonceng dan di halaman Notifikasi. Setelah
 * berhasil, angka merah, titik biru, dan huruf tebal hilang tanpa memuat ulang halaman.
 * Tidak ada polling; daftar diperbarui setiap kali halaman dibuka.
 */
(function () {
    var clearUnreadMarks = function () {
        document.querySelectorAll('[data-bell-count], .ui-notice-dot').forEach(function (element) {
            element.remove();
        });
        document.querySelectorAll('.ui-notice.is-unread').forEach(function (element) {
            element.classList.remove('is-unread');
        });
        document.querySelectorAll('[data-notifications-read-all]').forEach(function (button) {
            button.remove();
        });

        // Di halaman "Belum dibaca", daftar yang sudah dibaca semua dimuat ulang menjadi kosong.
        var page = document.querySelector('[data-notifications-page]');

        if (page && !/tampil=semua/.test(window.location.search)) {
            window.location.reload();
        }
    };

    document.querySelectorAll('[data-notifications-read-all]').forEach(function (button) {
        button.addEventListener('click', function (clickEvent) {
            clickEvent.preventDefault();
            clickEvent.stopPropagation();
            button.disabled = true;

            OrganizerHttp.request({ url: button.dataset.url, method: 'POST' }).then(clearUnreadMarks).catch(function (error) {
                button.disabled = false;
                OrganizerHttp.showError(error);
            });
        });
    });
})();
