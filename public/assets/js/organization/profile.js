"use strict";

var KTOrganizationProfile = (function () {
    var form = document.getElementById('kt_form_organization_profile');
    var submitButton = document.getElementById('kt_form_organization_profile_submit');

    return {
        init: function () {
            if (!submitButton) {
                return;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                OrganizerHttp.submit(form, submitButton, { url: form.dataset.url }).then(function () {
                    return OrganizerHttp.showSuccess('Profil organisasi berhasil disimpan.');
                }).then(function () {
                    // Simpan pertama membuka menu anggota dan master data, jadi halaman dimuat ulang.
                    window.location.reload();
                }).catch(function () {
                    // Pesan dan penanda field sudah ditampilkan oleh OrganizerHttp.submit.
                });
            });
        },
    };
})();

jQuery(document).ready(function () {
    KTOrganizationProfile.init();
});
