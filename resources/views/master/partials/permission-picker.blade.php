@php
    $moduleNames = [
        'dashboard' => 'Dashboard',
        'users' => 'User',
        'roles' => 'Role & Permission',
        'permissions' => 'Permission',
    ];
@endphp

<div class="permission-picker border rounded" data-permission-picker="{{ $pickerId }}">
    <div class="p-5 border-bottom">
        <div class="d-flex flex-stack flex-wrap gap-3 mb-4">
            <div>
                <div class="fw-bold fs-5" data-permission-summary>Dipilih 0 dari {{ $permissionGroups->flatten()->count() }} permission</div>
                <div class="text-muted fs-7">Pilih akses sesuai tanggung jawab role.</div>
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
        <div class="position-relative">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
            <input type="search" class="form-control form-control-solid ps-12" placeholder="Cari modul, aksi, atau kode permission..." data-permission-search>
        </div>
    </div>
    <div class="permission-picker-list scroll-y" style="max-height: 360px;" data-permission-list>
        @foreach ($permissionGroups as $module => $modulePermissions)
            <div class="permission-module border-bottom" data-permission-module>
                <button type="button" class="btn btn-flex flex-stack w-100 p-5 text-start" data-module-toggle aria-expanded="true">
                    <span class="d-flex align-items-center gap-3">
                        <span class="form-check form-check-custom form-check-solid mb-0">
                            <input class="form-check-input" type="checkbox" data-module-checkbox aria-label="Pilih semua permission {{ $moduleNames[$module] ?? \Illuminate\Support\Str::headline($module) }}">
                        </span>
                        <span>
                            <span class="text-muted text-uppercase fw-bold fs-8 d-block">Master & Pengaturan</span>
                            <span class="fw-bold fs-6">{{ $moduleNames[$module] ?? \Illuminate\Support\Str::headline($module) }}</span>
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
                            @php
                                $action = \Illuminate\Support\Str::after($permission->name, '.');
                                $actionNames = [
                                    'view' => 'Lihat Data',
                                    'create' => 'Tambah Data',
                                    'update' => 'Ubah Data',
                                    'delete' => 'Hapus Data',
                                    'reset-two-factor' => 'Reset Two Factor Authentication',
                                ];
                            @endphp
                            <div class="col-md-6" data-permission-item data-search="{{ strtolower($permission->name.' '.($actionNames[$action] ?? $action)) }}">
                                <label class="d-flex align-items-center gap-3 border rounded p-4 h-100 cursor-pointer permission-option">
                                    <span class="form-check form-check-custom form-check-solid mb-0">
                                        <input class="form-check-input" type="checkbox" value="{{ $permission->name }}" data-permission-checkbox>
                                    </span>
                                    <span>
                                        <span class="fw-semibold d-block">{{ $actionNames[$action] ?? \Illuminate\Support\Str::headline($action) }}</span>
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
