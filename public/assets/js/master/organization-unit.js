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
     * Isi pilihan unit induk. Unit yang sedang diubah tidak ditawarkan sebagai induknya sendiri.
     */
    beforeOpen: function (record, form) {
        var select = form.elements.parent_id;
        var url = base_url + '/master/organization-unit/options' + (record ? '?except=' + record.id : '');

        return OrganizerHttp.request({ url: url }).then(function (units) {
            select.replaceChildren(new Option('', ''));

            units.forEach(function (unit) {
                select.appendChild(new Option(unit.name + (unit.is_active ? '' : ' (nonaktif)'), unit.id));
            });

            $(select).val('').trigger('change');
        });
    },
});

jQuery(document).ready(function () {
    KTOrganizationUnit.init();
});
