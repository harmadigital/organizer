<?php

namespace Database\Seeders;

use App\Actions\Organization\ProvisionDefaultCommitteeRoles;
use App\Enums\UserStatus;
use App\Models\DivisionTemplate;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Fictional sample data for trying the application: units, people with and without
 * login access, a sample role, division templates, event categories and venues.
 *
 * Run it on purpose with `php artisan db:seed --class=DemoSeeder`; DatabaseSeeder never
 * calls it. It only adds what is missing, so it is safe to run again, and it never
 * changes an organization profile that already exists. It refuses to run outside the
 * local and testing environments.
 */
class DemoSeeder extends Seeder
{
    /**
     * Password of every demo account that can sign in. Demo use only.
     */
    public const DEMO_PASSWORD = 'Demo-2026!';

    /**
     * Every demo person's email ends with this, which makes the demo data easy to find.
     */
    public const EMAIL_DOMAIN = 'demo.organizer.test';

    public const SAMPLE_ROLE = 'Admin Sekretariat';

    public function run(ProvisionDefaultCommitteeRoles $provisionRoles): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('Data demo hanya boleh dibuat di lingkungan local atau testing.');

            return;
        }

        $this->call(PermissionSeeder::class);

        DB::transaction(function () use ($provisionRoles): void {
            $organization = $this->organization();
            $provisionRoles->handle($organization);

            $units = $this->units($organization);
            $this->sampleRole();
            $this->people($units);
            $this->divisionTemplates($organization);
            $this->eventCategories($organization);
            $this->venues($organization);
        });

        $this->command?->info('Data demo siap. Akun demo memakai kata sandi: '.self::DEMO_PASSWORD);
    }

    /**
     * The existing organization, or a fictional one when the profile was never filled in.
     */
    private function organization(): Organization
    {
        return Organization::query()->oldest('id')->first() ?? Organization::query()->create([
            'name' => 'Gereja Kasih Karunia (Demo)',
            'short_name' => 'GKK',
            'timezone' => 'Asia/Jakarta',
            'address' => 'Jl. Damai Sejahtera No. 12',
            'city' => 'Medan',
            'phone' => '061-4567890',
            'email' => 'sekretariat@'.self::EMAIL_DOMAIN,
        ]);
    }

    /**
     * @return array<string, OrganizationUnit>
     */
    private function units(Organization $organization): array
    {
        $tree = [
            'Majelis Jemaat' => [null, 'Pimpinan dan pengambil keputusan gereja.', true],
            'Bidang Pembinaan' => [null, 'Membawahi komisi-komisi pembinaan jemaat.', true],
            'Komisi Pemuda' => ['Bidang Pembinaan', 'Pelayanan pemuda dan remaja.', true],
            'Komisi Anak' => ['Bidang Pembinaan', 'Sekolah Minggu dan pelayanan anak.', true],
            'Komisi Perempuan' => ['Bidang Pembinaan', 'Persekutuan dan pelayanan kaum perempuan.', true],
            'Tim Multimedia' => [null, 'Sound system, proyektor, dan siaran ibadah.', true],
            'Komisi Musik Lama' => [null, 'Sudah digabung ke Tim Multimedia.', false],
        ];

        $units = [];

        foreach ($tree as $name => [$parent, $description, $isActive]) {
            $units[$name] = OrganizationUnit::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                [
                    'parent_id' => $parent === null ? null : $units[$parent]->id,
                    'description' => $description,
                    'is_active' => $isActive,
                ],
            );
        }

        return $units;
    }

    private function sampleRole(): void
    {
        Role::query()->firstOrCreate(['name' => self::SAMPLE_ROLE, 'guard_name' => 'web'])->givePermissionTo([
            'dashboard.view',
            'organization.view',
            'users.view',
            'users.create',
            'users.update',
            'organization-units.view',
            'division-templates.view',
            'event-categories.view',
            'venues.view',
            'venues.create',
            'venues.update',
        ]);
    }

    /**
     * @param  array<string, OrganizationUnit>  $units
     */
    private function people(array $units): void
    {
        $people = [
            // [name, username or null, role or null, unit or null, phone, status]
            ['Yohanes Sitorus', 'demo.admin', PermissionSeeder::ORGANIZATION_ADMIN_ROLE, 'Majelis Jemaat', '081200000001', UserStatus::Active],
            ['Maria Lestari', 'demo.sekretariat', self::SAMPLE_ROLE, 'Majelis Jemaat', '081200000002', UserStatus::Active],
            ['Debora Simanjuntak', 'demo.bendahara', null, 'Komisi Perempuan', '081200000003', UserStatus::Active],
            ['Samuel Tanoto', 'demo.pemuda', null, 'Komisi Pemuda', '081200000004', UserStatus::Active],
            ['Ruth Hutapea', null, null, 'Komisi Anak', '081200000005', UserStatus::Active],
            ['Markus Wibowo', null, null, 'Tim Multimedia', '081200000006', UserStatus::Active],
            ['Ester Manurung', null, null, 'Komisi Perempuan', '081200000007', UserStatus::Active],
            ['Timotius Halim', null, null, 'Komisi Pemuda', '081200000008', UserStatus::Active],
            ['Priskila Gultom', null, null, 'Komisi Pemuda', '081200000009', UserStatus::Active],
            ['Daniel Kurniawan', null, null, 'Tim Multimedia', '081200000010', UserStatus::Active],
            ['Lidia Pardede', null, null, null, '081200000011', UserStatus::Active],
            ['Andreas Siregar', null, null, 'Komisi Musik Lama', '081200000012', UserStatus::Inactive],
        ];

        foreach ($people as [$name, $username, $role, $unit, $phone, $status]) {
            $email = str($name)->lower()->replace(' ', '.')->append('@'.self::EMAIL_DOMAIN)->value();

            $person = User::query()->firstOrCreate(['email' => $email], [
                'name' => $name,
                'username' => $username,
                'password' => $username === null ? null : self::DEMO_PASSWORD,
                'phone' => $phone,
                'organization_unit_id' => $unit === null ? null : $units[$unit]->id,
                'status' => $status,
                'notes' => 'Data demo.',
                'email_verified_at' => now(),
            ]);

            if ($person->wasRecentlyCreated && $role !== null) {
                $person->assignRole($role);
            }
        }
    }

    private function divisionTemplates(Organization $organization): void
    {
        $templates = [
            'Seksi Acara' => ['Menyusun konsep, rundown, dan pengisi acara.', "Menyusun konsep dan rundown acara\nMengoordinasikan MC, liturgi, dan pengisi acara\nMengatur latihan dan gladi bersih"],
            'Seksi Konsumsi' => ['Menyediakan makanan dan minuman peserta.', "Menyusun menu dan jumlah porsi\nMemilih katering\nMengatur distribusi konsumsi"],
            'Seksi Perlengkapan' => ['Menyiapkan peralatan dan logistik.', "Mendata kebutuhan perlengkapan\nMengatur peminjaman, sewa, atau pembelian\nMemastikan barang kembali setelah acara"],
            'Seksi Dekorasi' => ['Menata ruangan sesuai tema.', "Menyusun konsep dekorasi\nMemasang dan membongkar dekorasi"],
            'Seksi Multimedia & Sound' => ['Menangani audio visual dan teknis panggung.', "Menyiapkan sound system dan proyektor\nMenyusun cue list teknis\nMemimpin soundcheck"],
            'Seksi Dokumentasi & Publikasi' => ['Mendokumentasikan dan mempublikasikan kegiatan.', "Membuat materi publikasi\nMengambil foto dan video\nMenyusun galeri kegiatan"],
            'Seksi Dana Usaha & Sponsorship' => ['Menggalang dana dan sponsor.', "Menyusun proposal sponsor\nMenjalankan kegiatan dana usaha"],
            'Seksi Penerima Tamu & Registrasi' => ['Menyambut dan mendata peserta.', "Mengelola daftar tamu dan undangan\nMenjalankan registrasi peserta"],
            'Seksi Transportasi & Akomodasi' => ['Mengatur kendaraan dan penginapan.', "Menyusun jadwal kendaraan\nMengatur pembagian kamar"],
            'Seksi Keamanan & Kesehatan' => ['Menjaga keamanan dan pertolongan pertama.', "Menyusun jadwal petugas keamanan\nMenyiapkan P3K dan kontak darurat"],
        ];

        foreach ($templates as $name => [$description, $responsibilities]) {
            DivisionTemplate::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['description' => $description, 'default_responsibilities' => $responsibilities],
            );
        }
    }

    private function eventCategories(Organization $organization): void
    {
        $categories = [
            'Perayaan Hari Raya' => ['Natal, Paskah, dan hari raya gerejawi lain.', true],
            'Retret & Pembinaan' => ['Retret, kamp, dan pelatihan jemaat.', true],
            'Bakti Sosial' => ['Pelayanan sosial dan diakonia ke masyarakat.', true],
            'Ibadah Khusus' => ['Ibadah syukur, peneguhan, dan ibadah gabungan.', true],
            'Kegiatan Pemuda' => ['Konser, lomba, dan persekutuan pemuda.', true],
            'Bazar Tahunan' => ['Sudah tidak diadakan sejak 2024.', false],
        ];

        foreach ($categories as $name => [$description, $isActive]) {
            EventCategory::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['description' => $description, 'is_active' => $isActive],
            );
        }
    }

    private function venues(Organization $organization): void
    {
        $venues = [
            'Gedung Gereja Utama' => ['Jl. Damai Sejahtera No. 12, Medan', 800, 'Tersedia sound system dan proyektor permanen.', true],
            'Aula Serbaguna' => ['Jl. Damai Sejahtera No. 12, Medan', 350, 'Kursi lipat 300 buah. Parkir terbatas.', true],
            'Ruang Pertemuan Lantai 2' => ['Jl. Damai Sejahtera No. 12, Medan', 60, 'Cocok untuk rapat panitia.', true],
            'Wisma Retret Bukit Doa' => ['Jl. Lintas Berastagi Km. 5, Karo', 150, '40 kamar. Pemesanan minimal dua bulan sebelumnya.', true],
            'Lapangan Gereja' => ['Jl. Damai Sejahtera No. 12, Medan', 1000, 'Perlu tenda bila hujan.', true],
            'Gedung Pertemuan Lama' => ['Jl. Gereja No. 3, Medan', 200, 'Sedang direnovasi.', false],
        ];

        foreach ($venues as $name => [$address, $capacity, $notes, $isActive]) {
            Venue::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['address' => $address, 'capacity' => $capacity, 'notes' => $notes, 'is_active' => $isActive],
            );
        }
    }
}
