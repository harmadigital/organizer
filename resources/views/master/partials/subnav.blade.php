<div class="ui-subnav nav">
    @can('organization-units.view')
        <a class="nav-link {{ request()->routeIs('master.organization_unit.*') ? 'active' : '' }}"
            href="{{ route('master.organization_unit.index') }}">Unit Pelayanan</a>
    @endcan
    @can('committee-roles.view')
        <a class="nav-link {{ request()->routeIs('master.committee_role.*') ? 'active' : '' }}"
            href="{{ route('master.committee_role.index') }}">Jabatan Panitia</a>
    @endcan
    @can('division-templates.view')
        <a class="nav-link {{ request()->routeIs('master.division_template.*') ? 'active' : '' }}"
            href="{{ route('master.division_template.index') }}">Template Seksi</a>
    @endcan
    @can('event-categories.view')
        <a class="nav-link {{ request()->routeIs('master.event_category.*') ? 'active' : '' }}"
            href="{{ route('master.event_category.index') }}">Kategori Event</a>
    @endcan
    @can('venues.view')
        <a class="nav-link {{ request()->routeIs('master.venue.*') ? 'active' : '' }}"
            href="{{ route('master.venue.index') }}">Venue</a>
    @endcan
</div>
