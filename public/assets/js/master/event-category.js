"use strict";

var KTEventCategory = OrganizerMasterCrud.create({
    resource: 'master/event-category',
    entity: 'Kategori',
    columns: [
        { data: 'name' },
        { data: 'description', orderable: false },
    ],
});

jQuery(document).ready(function () {
    KTEventCategory.init();
});
