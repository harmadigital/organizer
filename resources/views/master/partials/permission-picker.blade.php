@php
    $moduleNames = [
        'dashboard' => 'Dashboard',
        'users' => 'User',
        'roles' => 'Role & Permission',
        'permissions' => 'Permission',
        'organization' => 'Profil Organisasi',
        'organization-units' => 'Unit Pelayanan',
        'committee-roles' => 'Jabatan Panitia',
        'division-templates' => 'Template Seksi',
        'event-categories' => 'Kategori Event',
        'venues' => 'Venue',
    ];
    $moduleAreas = [
        'dashboard' => 'Umum',
        'users' => 'User & Akses',
        'roles' => 'User & Akses',
        'permissions' => 'User & Akses',
    ];
    $actionNames = [
        'view' => 'Lihat Data',
        'create' => 'Tambah Data',
        'update' => 'Ubah Data',
        'delete' => 'Hapus Data',
        'reset-two-factor' => 'Reset Autentikasi Dua Langkah',
        'manage-access' => 'Kelola Akses Login & Role',
    ];
@endphp

<div class="border border-gray-300 rounded" data-permission-picker>
    <div class="p-5 border-bottom border-gray-300">
        <div class="d-flex flex-stack flex-wrap gap-3 mb-4">
            <div>
                <div class="fw-bold fs-6 text-gray-800" data-permission-summary>Dipilih 0 dari {{ $permissionGroups->flatten()->count() }} permission</div>
                <div class="text-muted fs-7">Pilih akses sesuai tanggung jawab role. Permission yang tidak Anda miliki tidak dapat diberikan.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-primary btn-sm" data-select-all>
                    <i class="ki-duotone ki-check-square fs-4"><span class="path1"></span><span class="path2"></span></i>
                    Pilih Semua
                </button>
                <button type="button" class="btn btn-light btn-sm" data-clear-all>
                    <i class="ki-duotone ki-arrow-circle-left fs-4"><span class="path1"></span><span class="path2"></span></i>
                    Reset Pilihan
                </button>
            </div>
        </div>
        <div class="ui-search-box">
            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
            <input type="search" class="form-control form-control-solid w-100" placeholder="Cari modul, aksi, atau kode permission..."
                aria-label="Cari permission" data-permission-search>
        </div>
    </div>
    <div class="ui-permission-list scroll-y">
        @foreach ($permissionGroups as $module => $modulePermissions)
            @php($moduleName = $moduleNames[$module] ?? \Illuminate\Support\Str::headline($module))
            <div class="border-bottom border-gray-300" data-permission-module>
                <button type="button" class="btn btn-flex flex-stack w-100 p-5 text-start" data-module-toggle aria-expanded="true">
                    <span class="d-flex align-items-center gap-3">
                        <span class="form-check form-check-custom form-check-solid mb-0">
                            <input class="form-check-input" type="checkbox" data-module-checkbox aria-label="Pilih semua permission {{ $moduleName }}">
                        </span>
                        <span>
                            <span class="ui-info-label d-block">{{ $moduleAreas[$module] ?? 'Organisasi' }}</span>
                            <span class="fw-bold fs-6 text-gray-800">{{ $moduleName }}</span>
                        </span>
                    </span>
                    <span class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary" data-module-summary>0/{{ $modulePermissions->count() }}</span>
                        <i class="ki-duotone ki-down fs-3" data-module-icon><span class="path1"></span><span class="path2"></span></i>
                    </span>
                </button>
                <div class="px-5 pb-5" data-module-permissions>
                    <div class="row g-3">
                        @foreach ($modulePermissions as $permission)
                            @php($action = \Illuminate\Support\Str::after($permission->name, '.'))
                            @php($actionName = $actionNames[$action] ?? \Illuminate\Support\Str::headline($action))
                            @php($isHeld = in_array($permission->name, $actorPermissions, true))
                            <div class="col-md-6" data-permission-item data-search="{{ \Illuminate\Support\Str::lower($permission->name.' '.$moduleName.' '.$actionName) }}">
                                <label @class(['d-flex align-items-center gap-3 border border-gray-300 rounded p-4 h-100 ui-permission-option', 'cursor-pointer' => $isHeld, 'opacity-50' => ! $isHeld])>
                                    <span class="form-check form-check-custom form-check-solid mb-0">
                                        <input class="form-check-input" type="checkbox" name="permission[]" value="{{ $permission->name }}"
                                            data-permission-checkbox data-actor-holds="{{ $isHeld ? '1' : '0' }}" @disabled(! $isHeld)>
                                    </span>
                                    <span>
                                        <span class="fw-semibold d-block text-gray-800">{{ $actionName }}</span>
                                        <span class="text-muted fs-8">{{ $permission->name }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
