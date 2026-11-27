"use strict";

/**
 * Media & Dokumen: saring per jenis, seksi, dan judul; tambah foto/dokumen atau link; hapus.
 */
(function () {
    var page = document.querySelector('[data-library-page]');

    if (!page) {
        return;
    }

    var filters = { kind: 'all', division: '', search: '' };
    var items = Array.from(page.querySelectorAll('[data-library-item]'));

    var applyFilters = function () {
        var shown = 0;

        items.forEach(function (item) {
            var matches = (filters.kind === 'all' || item.dataset.kind === filters.kind)
                && (filters.division === '' || item.dataset.division === filters.division)
                && (filters.search === '' || item.dataset.title.indexOf(filters.search) !== -1);

            item.classList.toggle('d-none', !matches);
            shown += matches ? 1 : 0;
        });

        page.querySelector('[data-library-no-match]').classList.toggle('d-none', items.length === 0 || shown > 0);
    };

    page.querySelectorAll('[data-library-kind]').forEach(function (button) {
        button.addEventListener('click', function () {
            filters.kind = button.dataset.libraryKind;
            page.querySelectorAll('[data-library-kind]').forEach(function (other) {
                other.classList.toggle('active', other === button);
            });
            applyFilters();
        });
    });

    $(page.querySelector('[data-library-division]')).on('change', function () {
        filters.division = this.value || '';
        applyFilters();
    });

    page.querySelector('[data-library-search]').addEventListener('input', function (inputEvent) {
        filters.search = inputEvent.target.value.trim().toLowerCase();
        applyFilters();
    });

    page.querySelectorAll('[data-library-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            OrganizerHttp.confirm({
                title: 'Hapus dari Media & Dokumen?',
                text: '"' + button.dataset.title + '" tidak akan tampil lagi untuk panitia.',
                confirmText: 'Ya, hapus',
            }).then(function (confirmed) {
                if (!confirmed) {
                    return null;
                }

                return OrganizerHttp.request({ url: button.dataset.libraryRemove, method: 'POST' })
                    .then(function () {
                        window.location.reload();
                    })
                    .catch(OrganizerHttp.showError);
            });
        });
    });

    var modalElement = document.getElementById('kt_modal_library');

    if (!modalElement) {
        return;
    }

    var form = modalElement.querySelector('form');
    var modal = new bootstrap.Modal(modalElement);
    var division = form.elements.division_id;

    var showKind = function () {
        var isLink = form.querySelector('input[name="kind"]:checked').value === 'link';

        form.querySelector('[data-library-files-row]').classList.toggle('d-none', isLink);
        form.querySelector('[data-library-url-row]').classList.toggle('d-none', !isLink);
        OrganizerHttp.clearFieldErrors(form);
    };

    form.querySelectorAll('input[name="kind"]').forEach(function (radio) {
        radio.addEventListener('change', showKind);
    });

    page.querySelector('[data-library-add]').addEventListener('click', function () {
        form.reset();
        OrganizerHttp.clearFieldErrors(form);
        $(division).val(division.dataset.default || '').trigger('change');
        showKind();
        modal.show();
    });

    modalElement.addEventListener('shown.bs.modal', function () {
        form.elements.title.focus();
    });

    form.addEventListener('submit', function (submitEvent) {
        submitEvent.preventDefault();

        OrganizerHttp.submit(form, form.querySelector('[data-library-submit]'), { url: page.dataset.insertUrl }).then(function (data) {
            modal.hide();

            return OrganizerHttp.showSuccess(data.count > 1 ? data.count + ' file berhasil dibagikan.' : 'Berhasil dibagikan ke semua panitia.').then(function () {
                window.location.reload();
            });
        }).catch(function () {});
    });
})();
