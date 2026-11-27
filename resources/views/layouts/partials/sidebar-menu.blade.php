{{--
    Urutan menu: Dashboard, User & Akses, Master Data, lalu grup Event paling akhir (Event,
    Kalender Saya, Kategori Event). Kalender Saya satu-satunya halaman pribadi lintas event;
    halaman di dalam sebuah event dibuka lewat tab di header event, bukan lewat sidebar.
    Setiap item dibungkus @can, kecuali Event dan Kalender Saya yang terbuka untuk semua yang masuk;
    menu hanya penunjuk, hak akses tetap diperiksa di server.
--}}
@php($menuLink = fn (bool $isActive) => 'menu-link'.($isActive ? ' active' : ''))

@can('dashboard.view')
    <div class="menu-item pt-5">
        <a class="{{ $menuLink(request()->routeIs('dashboard.index')) }}" href="{{ route('dashboard.index') }}">
            <span class="menu-icon"><i class="ki-duotone ki-home fs-2"><span class="path1"></span><span class="path2"></span></i></span>
            <span class="menu-title">Dashboard</span>
        </a>
    </div>
@endcan

@canany(['users.view', 'roles.view'])
    <div class="menu-item pt-3">
        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">User &amp; Akses</span></div>
    </div>
    @can('users.view')
        <div class="menu-item">
            <a class="{{ $menuLink(request()->routeIs('master.user.*')) }}" href="{{ route('master.user.index') }}">
                <span class="menu-icon"><i class="ki-duotone ki-profile-user fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span>
                <span class="menu-title">User</span>
            </a>
        </div>
    @endcan
    @can('roles.view')
        <div class="menu-item">
            <a class="{{ $menuLink(request()->routeIs('master.role.*', 'master.permission.*')) }}" href="{{ route('master.role.index') }}">
                <span class="menu-icon"><i class="ki-duotone ki-shield-tick fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>
                <span class="menu-title">Role &amp; Permission</span>
            </a>
        </div>
    @endcan
@endcanany

@canany(['organization.view', 'organization-units.view', 'committee-roles.view', 'division-templates.view', 'venues.view'])
    <div class="menu-item pt-3">
        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Master Data</span></div>
    </div>
    @foreach ([
        ['organization.view', 'organization.profile', 'Profil Organisasi', 'ki-bank', 2],
        ['organization-units.view', 'master.organization_unit', 'Unit Pelayanan', 'ki-abstract-26', 2],
        ['committee-roles.view', 'master.committee_role', 'Jabatan Panitia', 'ki-badge', 5],
        ['division-templates.view', 'master.division_template', 'Template Seksi', 'ki-element-11', 4],
        ['venues.view', 'master.venue', 'Venue', 'ki-geolocation', 2],
    ] as [$permission, $routeName, $label, $icon, $paths])
        @can($permission)
            <div class="menu-item">
                <a class="{{ $menuLink(request()->routeIs($routeName.'.*')) }}" href="{{ route($routeName.'.index') }}">
                    <span class="menu-icon">
                        <i class="ki-duotone {{ $icon }} fs-2">
                            @for ($path = 1; $path <= $paths; $path++)
                                <span class="path{{ $path }}"></span>
                            @endfor
                        </i>
                    </span>
                    <span class="menu-title">{{ $label }}</span>
                </a>
            </div>
        @endcan
    @endforeach
@endcanany

@if (app(\App\Services\CurrentOrganization::class)->exists())
    <div class="menu-item pt-3">
        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Event</span></div>
    </div>
    <div class="menu-item">
        <a class="{{ $menuLink(request()->routeIs('events.*')) }}" href="{{ route('events.index') }}">
            <span class="menu-icon"><i class="ki-duotone ki-calendar fs-2"><span class="path1"></span><span class="path2"></span></i></span>
            <span class="menu-title">Event</span>
        </a>
    </div>
    {{-- Seperti Event, tanpa @can: setiap orang yang masuk boleh melihat kalendernya sendiri. --}}
    <div class="menu-item">
        <a class="{{ $menuLink(request()->routeIs('calendar.*')) }}" href="{{ route('calendar.index') }}">
            <span class="menu-icon"><i class="ki-duotone ki-calendar-tick fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i></span>
            <span class="menu-title">Kalender Saya</span>
        </a>
    </div>
    @can('event-categories.view')
        <div class="menu-item">
            <a class="{{ $menuLink(request()->routeIs('master.event_category.*')) }}" href="{{ route('master.event_category.index') }}">
                <span class="menu-icon"><i class="ki-duotone ki-category fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span>
                <span class="menu-title">Kategori Event</span>
            </a>
        </div>
    @endcan

@endif
