@can('dashboard.view')
    <!--begin:Menu item-->
    <div class="menu-item pt-5">
        <!--begin:Menu link-->
        <a class="menu-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}"
            href="{{ route('dashboard.index') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-home fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Dashboard</span>
        </a>
        <!--end:Menu link-->
    </div>
    <!--end:Menu item-->
@endcan

@canany(['users.view', 'roles.view'])
    <!--begin:Menu item-->
    <div class="menu-item pt-3">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">User &amp; Akses</span>
        </div>
        <!--end:Menu content-->
    </div>
    <!--end:Menu item-->
    @can('users.view')
        <!--begin:Menu item-->
        <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ request()->routeIs('master.user.*') ? 'active' : '' }}"
                href="{{ route('master.user.index') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-profile-user fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </span>
                <span class="menu-title">User</span>
            </a>
            <!--end:Menu link-->
        </div>
        <!--end:Menu item-->
    @endcan
    @can('roles.view')
        <!--begin:Menu item-->
        <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ request()->routeIs('master.role.*', 'master.permission.*') ? 'active' : '' }}"
                href="{{ route('master.role.index') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-shield-tick fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                </span>
                <span class="menu-title">Role &amp; Permission</span>
            </a>
            <!--end:Menu link-->
        </div>
        <!--end:Menu item-->
    @endcan
@endcanany

@canany(['organization.view', 'organization-units.view', 'committee-roles.view', 'division-templates.view', 'event-categories.view', 'venues.view'])
    <!--begin:Menu item-->
    <div class="menu-item pt-3">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Organisasi</span>
        </div>
        <!--end:Menu content-->
    </div>
    <!--end:Menu item-->
    @can('organization.view')
        <!--begin:Menu item-->
        <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ request()->routeIs('organization.profile.*') ? 'active' : '' }}"
                href="{{ route('organization.profile.index') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-bank fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <span class="menu-title">Profil Organisasi</span>
            </a>
            <!--end:Menu link-->
        </div>
        <!--end:Menu item-->
    @endcan
    @canany(['organization-units.view', 'committee-roles.view', 'division-templates.view', 'event-categories.view', 'venues.view'])
        <div data-kt-menu-trigger="click"
            class="menu-item menu-accordion {{ request()->routeIs('master.organization_unit.*', 'master.committee_role.*', 'master.division_template.*', 'master.event_category.*', 'master.venue.*') ? 'here show' : '' }}">
            <!--begin:Menu link-->
            <span class="menu-link">
                <span class="menu-icon">
                    <i class="ki-duotone ki-element-11 fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </span>
                <span class="menu-title">Master Data</span>
                <span class="menu-arrow"></span>
            </span>
            <!--end:Menu link-->
            <!--begin:Menu sub-->
            <div class="menu-sub menu-sub-accordion">
                @foreach ([
                    ['organization-units.view', 'master.organization_unit', 'Unit Pelayanan'],
                    ['committee-roles.view', 'master.committee_role', 'Jabatan Panitia'],
                    ['division-templates.view', 'master.division_template', 'Template Seksi'],
                    ['event-categories.view', 'master.event_category', 'Kategori Event'],
                    ['venues.view', 'master.venue', 'Venue'],
                ] as [$permission, $routeName, $label])
                    @can($permission)
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs($routeName.'.*') ? 'active' : '' }}"
                                href="{{ route($routeName.'.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ $label }}</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                    @endcan
                @endforeach
            </div>
            <!--end:Menu sub-->
        </div>
    @endcanany
@endcanany
