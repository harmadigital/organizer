"use strict";

/**
 * Daftar rapat: tombol jadwalkan rapat membuka form, lalu menuju halaman rapat yang baru dibuat.
 */
(function () {
    var page = document.querySelector('[data-meetings-page]');
    var addButton = page ? page.querySelector('[data-meeting-add]') : null;

    if (!addButton || !MeetingForm) {
        return;
    }

    addButton.addEventListener('click', function () {
        MeetingForm.openForCreate(page.dataset.insertUrl, function (data) {
            window.location.href = data.url;
        });
    });
})();
