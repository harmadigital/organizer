@extends('layouts.main_layout', ['title' => 'Profil Organisasi', 'breadcrum' => ['Organisasi', 'Profil Organisasi']])

@section('content')
    <div class="ui-page">
        <x-ui.page-header title="Profil Organisasi" subtitle="Identitas organisasi yang menaungi seluruh event dan kepanitiaan." />

        @unless ($organization->exists)
            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5">
                <i class="ki-duotone ki-information-2 fs-2tx text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <div class="fw-semibold text-gray-700 fs-6">
                    Profil organisasi belum diisi. Lengkapi dan simpan profil ini terlebih dahulu; setelah itu master data dan jabatan panitia bawaan dapat digunakan.
                </div>
            </div>
        @endunless

        <div class="card ui-panel">
            <div class="card-header">
                <div class="card-title">
                    <h3>Identitas dan Kontak</h3>
                </div>
            </div>
            <form id="kt_form_organization_profile" class="form" autocomplete="off" novalidate
                data-url="{{ route('organization.profile.update') }}" data-is-new="{{ $organization->exists ? '0' : '1' }}">
                <div class="card-body">
                    <fieldset @cannot('organization.update') disabled @endcannot>
                        <div class="row g-7 mb-7">
                            <div class="col-md-8 fv-row">
                                <label class="required fw-semibold fs-6 mb-2" for="organization_name">Nama Organisasi</label>
                                <input type="text" name="name" id="organization_name" class="form-control form-control-solid"
                                    placeholder="Nama resmi gereja atau organisasi" maxlength="255" value="{{ $organization->name }}">
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="organization_short_name">Nama Singkat</label>
                                <input type="text" name="short_name" id="organization_short_name" class="form-control form-control-solid"
                                    placeholder="Singkatan" maxlength="50" value="{{ $organization->short_name }}">
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2" for="organization_address">Alamat</label>
                            <textarea name="address" id="organization_address" class="form-control form-control-solid" rows="3" maxlength="1000"
                                placeholder="Alamat lengkap">{{ $organization->address }}</textarea>
                        </div>

                        <div class="row g-7 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="organization_city">Kota</label>
                                <input type="text" name="city" id="organization_city" class="form-control form-control-solid"
                                    placeholder="Kota atau kabupaten" maxlength="100" value="{{ $organization->city }}">
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fw-semibold fs-6 mb-2" for="organization_timezone">Zona Waktu</label>
                                <select name="timezone" id="organization_timezone" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                    @foreach ($timezones as $value => $label)
                                        <option value="{{ $value }}" @selected($organization->timezone === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="fs-7 text-muted mt-2">Dipakai untuk menampilkan jadwal, tenggat, dan waktu pencatatan.</div>
                            </div>
                        </div>

                        <div class="row g-7">
                            <div class="col-md-4 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="organization_phone">Telepon</label>
                                <input type="tel" name="phone" id="organization_phone" class="form-control form-control-solid"
                                    placeholder="Nomor telepon kantor" maxlength="30" value="{{ $organization->phone }}">
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="organization_email">Email</label>
                                <input type="email" name="email" id="organization_email" class="form-control form-control-solid"
                                    placeholder="sekretariat@contoh.org" maxlength="255" value="{{ $organization->email }}">
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fw-semibold fs-6 mb-2" for="organization_website">Situs Web</label>
                                <input type="url" name="website" id="organization_website" class="form-control form-control-solid"
                                    placeholder="https://" maxlength="255" value="{{ $organization->website }}">
                            </div>
                        </div>
                    </fieldset>
                </div>
                @can('organization.update')
                    <div class="card-footer d-flex justify-content-end py-5 px-9">
                        <button type="submit" class="btn btn-primary" id="kt_form_organization_profile_submit">
                            <span class="indicator-label">Simpan Profil</span>
                            <span class="indicator-progress">Menyimpan...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                @endcan
            </form>
        </div>

        @if ($organization->exists)
            <div class="card ui-panel">
                <div class="card-body">
                    <div class="row g-7">
                        <div class="col-md-4 ui-info-item">
                            <span class="ui-info-label">Mata Uang</span>
                            <span class="ui-info-value">{{ $organization->currency_code }}</span>
                        </div>
                        <div class="col-md-4 ui-info-item">
                            <span class="ui-info-label">Terakhir Diperbarui</span>
                            <span class="ui-info-value">{{ $lastUpdatedAt ?? '-' }}</span>
                        </div>
                        <div class="col-md-4 ui-info-item">
                            <span class="ui-info-label">Diperbarui Oleh</span>
                            <span class="ui-info-value">{{ $organization->editor?->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/js/organization/profile.js') }}"></script>
@endsection
