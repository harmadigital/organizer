"use strict";

var KTVenue = OrganizerMasterCrud.create({
    resource: 'master/venue',
    entity: 'Venue',
    columns: [
        { data: 'name' },
        { data: 'address', orderable: false },
        { data: 'capacity', render: OrganizerMasterCrud.render.number },
    ],
});

jQuery(document).ready(function () {
    KTVenue.init();
});
