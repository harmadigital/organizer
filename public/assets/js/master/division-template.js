"use strict";

var KTDivisionTemplate = OrganizerMasterCrud.create({
    resource: 'master/division-template',
    entity: 'Template Seksi',
    columns: [
        { data: 'name' },
        { data: 'description', orderable: false },
    ],
});

jQuery(document).ready(function () {
    KTDivisionTemplate.init();
});
