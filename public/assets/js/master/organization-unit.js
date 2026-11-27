"use strict";

var KTOrganizationUnit = OrganizerMasterCrud.create({
    resource: 'master/organization-unit',
    entity: 'Unit',
    columns: [
        { data: 'name' },
        {
            data: 'parent.name',
            defaultContent: '',
            orderable: false,
            render: OrganizerMasterCrud.render.text,
        },
        { data: 'users_count', searchable: false, render: OrganizerMasterCrud.render.number },
    ],

    /**
     * Unit yang sedang diubah, beserta unit di bawahnya, tidak ditawarkan sebagai induknya sendiri.
     */
    beforeOpen: function (record, form) {
        OrganizerSelect.setParams(form.elements.parent_id, record ? { except: record.id } : {});
    },
});

jQuery(document).ready(function () {
    KTOrganizationUnit.init();
});
