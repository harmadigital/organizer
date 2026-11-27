{{--
    Ringkasan satu jadwal kalender (hari pelaksanaan, rapat, tenggat tugas, atau kegiatan),
    diisi dari item kalender lewat textContent oleh OrganizerCalendar.fillItemDetail.
    Baris, catatan, dan label disusun server (CalendarFeed). Tombol ke halamannya tampil bila
    jadwal itu punya halaman; Ubah dan Hapus hanya untuk kegiatan yang boleh diubah.
    $showEventLink: di Kalender Saya, kegiatan punya tautan ke kalender event-nya.
--}}
<div class="modal fade" id="kt_modal_calendar_item" tabindex="-1" aria-hidden="true" aria-labelledby="kt_modal_calendar_item_title">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div class="min-w-0">
                    <div class="text-muted fs-7 fw-semibold mb-1"><span class="ui-calendar-dot me-1" data-item-dot></span><span data-item-type></span></div>
                    <h3 class="modal-title text-break" id="kt_modal_calendar_item_title" data-item-title></h3>
                    <div class="ui-calendar-badges d-none" data-item-badges></div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2 flex-shrink-0" data-bs-dismiss="modal" aria-label="Tutup"><i class="las la-times fs-2"></i></button>
            </div>
            <div class="modal-body">
                <div data-item-rows></div>
                <div class="ui-calendar-note d-none" data-item-note>
                    <div class="ui-calendar-detail-label mb-1" data-item-note-label></div>
                    <div class="ui-pre-line text-gray-800" data-item-note-text></div>
                </div>
            </div>
            <div class="modal-footer flex-center ui-calendar-item-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-light-danger d-none" data-item-delete>Hapus</button>
                <button type="button" class="btn btn-light d-none" data-item-edit>Ubah</button>
                @if ($showEventLink)
                    <a href="#" class="btn btn-primary d-none" data-item-calendar-link>Buka kalender event</a>
                @endif
                <a href="#" class="btn btn-primary d-none" data-item-open></a>
            </div>
        </div>
    </div>
</div>
