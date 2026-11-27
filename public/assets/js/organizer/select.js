"use strict";

/**
 * Dropdown seragam untuk seluruh aplikasi, memakai select2 dari bundle Metronic.
 *
 * Pilihan yang sudah ada di halaman:
 *   <select data-control="select2" data-placeholder="Pilih ..." data-allow-clear="true" data-hide-search="true">
 *
 * Pilihan yang diambil dari server (autocomplete dengan pencarian dan paging):
 *   <select data-select-remote="/url" data-placeholder="Ketik nama" data-allow-clear="true">
 *   Server menjawab { status: true, data: { results: [{ id, text, description? }], more: bool } }
 *   dan menerima parameter search dan page.
 *
 *   OrganizerSelect.setValue(select, id, text);   // isi nilai, termasuk pilihan remote yang belum dimuat
 *   OrganizerSelect.setParams(select, { except: 5 });   // parameter tambahan untuk pencarian remote
 *   OrganizerSelect.reset(select);
 *   OrganizerSelect.init(element);   // untuk dropdown yang dibuat setelah halaman dimuat
 *
 * Di dalam modal, daftar pilihan otomatis dipasang di modal tersebut.
 */
var OrganizerSelect = (function () {
    var language = {
        noResults: function () {
            return 'Tidak ada hasil';
        },
        searching: function () {
            return 'Mencari...';
        },
        loadingMore: function () {
            return 'Memuat data lainnya...';
        },
        errorLoading: function () {
            return 'Data tidak dapat dimuat.';
        },
        inputTooShort: function (args) {
            return 'Ketik minimal ' + (args.minimum - args.input.length) + ' karakter lagi';
        },
        removeAllItems: function () {
            return 'Hapus semua pilihan';
        },
    };

    if (window.jQuery && $.fn.select2) {
        $.fn.select2.defaults.set('language', language);
        $.fn.select2.defaults.set('width', '100%');
    }

    var isInitialized = function (select) {
        return $(select).hasClass('select2-hidden-accessible');
    };

    var dropdownParentOf = function (select) {
        var selector = select.getAttribute('data-dropdown-parent');
        var parent = selector ? document.querySelector(selector) : select.closest('.modal');

        return parent ? $(parent) : $(document.body);
    };

    var baseOptions = function (select) {
        return {
            dropdownParent: dropdownParentOf(select),
            placeholder: select.getAttribute('data-placeholder') || '',
            allowClear: select.getAttribute('data-allow-clear') === 'true',
        };
    };

    /**
     * Pilihan remote menampilkan keterangan kecil di bawah nama, bila server mengirimkannya.
     * Teks dipasang sebagai teks, bukan HTML.
     */
    var renderResult = function (item) {
        if (item.loading || !item.description) {
            return item.text;
        }

        return $('<div>').append(
            $('<div>').text(item.text),
            $('<div class="text-muted fs-7">').text(item.description)
        );
    };

    var initStatic = function (select) {
        var options = baseOptions(select);

        if (select.getAttribute('data-hide-search') === 'true') {
            options.minimumResultsForSearch = Infinity;
        }

        $(select).select2(options);
    };

    var initRemote = function (select) {
        $(select).select2(Object.assign(baseOptions(select), {
            minimumInputLength: Number(select.getAttribute('data-minimum-input') || 0),
            templateResult: renderResult,
            ajax: {
                url: function () {
                    return select.getAttribute('data-select-remote');
                },
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return Object.assign({ search: params.term || '', page: params.page || 1 }, select._remoteParams || {});
                },
                processResults: function (response) {
                    return {
                        results: response.data.results,
                        pagination: { more: response.data.more },
                    };
                },
            },
        }));
    };

    /**
     * Esc saat daftar pilihan terbuka hanya menutup daftar, bukan modal tempat dropdown berada.
     * Didengar pada fase capture agar modal Bootstrap tidak ikut menerimanya.
     */
    var keepEscapeForTheDropdown = function (select) {
        var instance = $(select).data('select2');

        [instance.$container[0], instance.$dropdown[0]].forEach(function (element) {
            element.addEventListener('keydown', function (keyEvent) {
                if (keyEvent.key === 'Escape' && instance.isOpen()) {
                    keyEvent.preventDefault();
                    keyEvent.stopPropagation();
                    $(select).select2('close');
                    instance.$selection.trigger('focus');
                }
            }, true);
        });
    };

    var init = function (root) {
        (root || document).querySelectorAll('select[data-select-remote], select[data-control="select2"]').forEach(function (select) {
            if (isInitialized(select)) {
                return;
            }

            if (select.hasAttribute('data-select-remote')) {
                initRemote(select);
            } else {
                initStatic(select);
            }

            keepEscapeForTheDropdown(select);

            // Metronic melewati dropdown yang sudah ditandai, jadi pengaturan di atas tidak ditimpa.
            select.setAttribute('data-kt-initialized', '1');
        });
    };

    var setValue = function (select, id, text) {
        if (id === null || id === undefined || id === '') {
            $(select).val(null).trigger('change');

            return;
        }

        var value = String(id);
        var exists = Array.from(select.options).some(function (option) {
            return option.value === value;
        });

        if (!exists) {
            select.appendChild(new Option(text || value, value, true, true));
        }

        $(select).val(value).trigger('change');
    };

    var reset = function (select) {
        if (select.hasAttribute('data-select-remote')) {
            Array.from(select.options).forEach(function (option) {
                if (option.value !== '') {
                    option.remove();
                }
            });
        }

        $(select).val(null).trigger('change');
    };

    var setParams = function (select, params) {
        select._remoteParams = params;
    };

    // Skrip ini dimuat di akhir <body>, sebelum Metronic menginisialisasi select2.
    init(document);

    return {
        init: init,
        setValue: setValue,
        reset: reset,
        setParams: setParams,
    };
})();
