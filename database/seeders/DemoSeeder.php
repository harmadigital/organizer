<?php

namespace Database\Seeders;

use App\Actions\Calendar\SaveCalendarEntry;
use App\Actions\Events\AssignCommitteeRole;
use App\Actions\Events\ChangeEventPhase;
use App\Actions\Events\ChangeEventStatus;
use App\Actions\Events\CreateDivisionsFromTemplates;
use App\Actions\Events\SaveDivision;
use App\Actions\Events\SaveEvent;
use App\Actions\Library\AddLibraryItems;
use App\Actions\Meetings\SaveMeeting;
use App\Actions\Meetings\SaveMinutes;
use App\Actions\Organization\ProvisionDefaultCommitteeRoles;
use App\Actions\Work\AddTaskDependency;
use App\Actions\Work\CompleteTask;
use App\Actions\Work\PostTaskComment;
use App\Actions\Work\SaveTask;
use App\Actions\Work\SaveWorkProgram;
use App\Enums\CommitteeRoleKind;
use App\Enums\EventPhase;
use App\Enums\EventStatus;
use App\Enums\UserStatus;
use App\Models\CalendarEntry;
use App\Models\CommitteeRole;
use App\Models\Division;
use App\Models\DivisionTemplate;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Task;
use App\Models\User;
use App\Models\Venue;
use App\Models\WorkProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Fictional sample data for trying the application: units, people with and without
 * login access, a sample role, division templates, event categories, venues, two events
 * with their committees (one active, one draft that still misses required roles), and work
 * programs, tasks, meetings with minutes, shared links and calendar kegiatan for the
 * active event.
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
            $this->events($organization);
            $this->work($organization);
            $this->meetings($organization);
            $this->library($organization);
            $this->calendar($organization);
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

    /**
     * Two events built through the application's own actions, so they obey every committee rule.
     */
    private function events(Organization $organization): void
    {
        $organizer = User::query()->where('username', 'demo.admin')->sole();
        $person = fn (string $name): User => User::query()->where('email', str($name)->lower()->replace(' ', '.')->append('@'.self::EMAIL_DOMAIN)->value())->sole();
        $role = fn (CommitteeRoleKind $kind): CommitteeRole => CommitteeRole::query()->forOrganization($organization)->where('kind', $kind)->sole();
        $assign = fn (Event $event, string $name, CommitteeRoleKind $kind, ?Division $division = null, bool $accepted = true) => app(AssignCommitteeRole::class)
            ->handle($event, $person($name), $role($kind), $division, true, null, $accepted, $organizer);

        if (! Event::query()->forOrganization($organization)->where('code', 'DEMO-NATAL-2026')->exists()) {
            $christmas = app(SaveEvent::class)->handle(new Event, [
                'code' => 'DEMO-NATAL-2026',
                'name' => 'Perayaan Natal 2026',
                'theme' => 'Damai di Bumi',
                'subtheme' => 'Lukas 2:14',
                'event_category_id' => EventCategory::query()->forOrganization($organization)->where('name', 'Perayaan Hari Raya')->value('id'),
                'venue_id' => Venue::query()->forOrganization($organization)->where('name', 'Gedung Gereja Utama')->value('id'),
                'starts_on' => '2026-12-24',
                'ends_on' => '2026-12-25',
                'committee_starts_on' => '2026-10-01',
                'committee_ends_on' => '2027-01-31',
                'target_participants' => 800,
                'budget_target_amount' => 75000000,
                'description' => 'Ibadah malam Natal dan perayaan Natal jemaat. Data demo.',
            ], [
                ['type' => 'goal', 'description' => 'Merayakan kelahiran Kristus bersama seluruh jemaat.', 'target' => null],
                ['type' => 'target', 'description' => 'Jemaat dewasa, remaja, dan anak.', 'target' => '800 orang'],
                ['type' => 'indicator', 'description' => 'Kehadiran pada ibadah malam Natal.', 'target' => '700 orang'],
            ], $organizer);

            $templates = DivisionTemplate::query()->forOrganization($organization)
                ->whereIn('name', ['Seksi Acara', 'Seksi Konsumsi', 'Seksi Perlengkapan', 'Seksi Multimedia & Sound'])
                ->get();
            app(CreateDivisionsFromTemplates::class)->handle($christmas, $templates, $organizer);
            $division = fn (string $name): Division => $christmas->divisions()->where('name', $name)->sole();

            $assign($christmas, 'Timotius Halim', CommitteeRoleKind::Chair);
            $assign($christmas, 'Maria Lestari', CommitteeRoleKind::Secretary);
            $assign($christmas, 'Debora Simanjuntak', CommitteeRoleKind::Treasurer);
            $assign($christmas, 'Ester Manurung', CommitteeRoleKind::Supervisor);
            $assign($christmas, 'Ruth Hutapea', CommitteeRoleKind::DivisionCoordinator, $division('Seksi Acara'));
            $assign($christmas, 'Priskila Gultom', CommitteeRoleKind::DivisionMember, $division('Seksi Acara'));
            $assign($christmas, 'Markus Wibowo', CommitteeRoleKind::DivisionCoordinator, $division('Seksi Multimedia & Sound'));
            $assign($christmas, 'Daniel Kurniawan', CommitteeRoleKind::DivisionMember, $division('Seksi Multimedia & Sound'));

            app(ChangeEventStatus::class)->handle($christmas, EventStatus::Active, 'Panitia inti sudah lengkap. Data demo.', $organizer);
            app(ChangeEventPhase::class)->handle($christmas, EventPhase::Preparation, 'Data demo.', $organizer);
        }

        if (! Event::query()->forOrganization($organization)->where('code', 'DEMO-RETRET-2027')->exists()) {
            $retreat = app(SaveEvent::class)->handle(new Event, [
                'code' => 'DEMO-RETRET-2027',
                'name' => 'Retret Pemuda 2027',
                'theme' => 'Berakar dan Bertumbuh',
                'event_category_id' => EventCategory::query()->forOrganization($organization)->where('name', 'Retret & Pembinaan')->value('id'),
                'venue_id' => Venue::query()->forOrganization($organization)->where('name', 'Wisma Retret Bukit Doa')->value('id'),
                'organization_unit_id' => OrganizationUnit::query()->forOrganization($organization)->where('name', 'Komisi Pemuda')->value('id'),
                'starts_on' => '2027-03-12',
                'ends_on' => '2027-03-14',
                'target_participants' => 120,
                'description' => 'Masih disusun: bendahara dan pengawas belum ditetapkan. Data demo.',
            ], [], $organizer);

            $program = app(SaveDivision::class)->handle($retreat, new Division, ['name' => 'Seksi Acara', 'sort_order' => 10], $organizer);

            $assign($retreat, 'Lidia Pardede', CommitteeRoleKind::Chair);
            $assign($retreat, 'Timotius Halim', CommitteeRoleKind::Secretary);
            $assign($retreat, 'Samuel Tanoto', CommitteeRoleKind::DivisionCoordinator, $program, accepted: false);
        }
    }

    /**
     * Programs and tasks for the Christmas event: open and finished tasks with their results,
     * subtasks, a prerequisite and comments. Added once: skipped when the event already has programs.
     */
    private function work(Organization $organization): void
    {
        $christmas = Event::query()->forOrganization($organization)->where('code', 'DEMO-NATAL-2026')->first();

        if ($christmas === null || $christmas->programs()->exists()) {
            return;
        }

        $organizer = User::query()->where('username', 'demo.admin')->sole();
        $division = fn (string $name): Division => $christmas->divisions()->where('name', $name)->sole();
        $member = fn (string $name): int => $christmas->memberships()->whereHas('user', fn ($user) => $user->where('name', $name))->value('id');
        $saveTask = app(SaveTask::class);
        $complete = fn (Task $task, string $result) => app(CompleteTask::class)->handle($task, $result.' Data demo.', [], $organizer);

        $program = fn (string $divisionName, string $title, string $pic, array $details) => app(SaveWorkProgram::class)->handle($christmas, new WorkProgram, [
            'division_id' => $division($divisionName)->id,
            'title' => $title,
            'pic_membership_id' => $member($pic),
            'status' => 'active',
            ...$details,
        ], $organizer);

        $task = fn (array $attributes, array $assignees = []): Task => $saveTask->handle($christmas, new Task, [
            'priority' => 'medium',
            'weight' => 1,
            ...$attributes,
        ], $assignees, $organizer);
        $comment = fn (Task $task, string $name, string $body) => app(PostTaskComment::class)->handle($task, User::query()->where('name', $name)->sole(), $body.' (Data demo)');

        $liturgy = $program('Seksi Acara', 'Liturgi dan rundown ibadah malam Natal', 'Ruth Hutapea', [
            'objective' => 'Ibadah malam Natal berjalan khidmat dan tepat waktu.',
            'output' => 'Liturgi final, rundown, dan daftar petugas.',
            'indicator' => 'Ibadah selesai dalam 120 menit tanpa jeda teknis.',
            'starts_on' => '2026-10-15',
            'ends_on' => '2026-12-24',
        ]);
        $sound = $program('Seksi Multimedia & Sound', 'Sistem suara dan tayangan', 'Markus Wibowo', [
            'objective' => 'Suara jelas dan tayangan lirik tepat di seluruh ruangan.',
            'output' => 'Sound system terpasang, cue list, dan slide lirik.',
            'indicator' => 'Soundcheck lulus H-1.',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-12-25',
        ]);

        $draftLiturgy = $task(['title' => 'Susun draf liturgi', 'work_program_id' => $liturgy->id, 'owner_membership_id' => $member('Ruth Hutapea'), 'priority' => 'high', 'weight' => 3, 'starts_on' => '2026-10-20', 'due_on' => '2026-11-10']);
        $complete($draftLiturgy, 'Urutan ibadah disepakati bersama pendeta pada rapat liturgi 8 November.');

        $rundown = $task(['title' => 'Rundown ibadah malam Natal', 'work_program_id' => $liturgy->id, 'owner_membership_id' => $member('Ruth Hutapea'), 'priority' => 'high', 'weight' => 2, 'starts_on' => '2026-11-11', 'due_on' => '2026-12-05'], [$member('Priskila Gultom')]);
        app(AddTaskDependency::class)->handle($rundown, $draftLiturgy, $organizer);
        $complete($rundown, 'Rundown 120 menit selesai: pukul 19.00 sampai 21.00, termasuk drama anak 15 menit.');
        $comment($rundown, 'Timotius Halim', 'Mohon drama anak dipastikan selesai sebelum pukul 20.30.');
        $comment($rundown, 'Ruth Hutapea', 'Siap, durasi drama sudah dipangkas menjadi 12 menit.');

        $officers = $task(['title' => 'Jadwal petugas ibadah', 'work_program_id' => $liturgy->id, 'owner_membership_id' => $member('Priskila Gultom'), 'weight' => 2, 'starts_on' => '2026-11-15', 'due_on' => '2026-12-12']);
        foreach (['Konfirmasi pemandu pujian', 'Konfirmasi pembaca Alkitab', 'Konfirmasi kolektan'] as $position => $title) {
            $subtask = $task(['title' => $title, 'parent_id' => $officers->id, 'owner_membership_id' => $member('Priskila Gultom'), 'due_on' => '2026-12-0'.($position + 5)]);

            if ($position === 0) {
                $complete($subtask, 'Dua pemandu pujian bersedia dan sudah menerima daftar lagu.');
            }
        }

        $task(['title' => 'Gladi bersih ibadah', 'work_program_id' => $liturgy->id, 'owner_membership_id' => $member('Ruth Hutapea'), 'weight' => 2, 'starts_on' => '2026-12-23', 'due_on' => '2026-12-23']);

        $soundRental = $task(['title' => 'Sewa sound system tambahan', 'work_program_id' => $sound->id, 'owner_membership_id' => $member('Markus Wibowo'), 'priority' => 'critical', 'weight' => 3, 'starts_on' => '2026-11-05', 'due_on' => '2026-11-25']);
        $comment($soundRental, 'Debora Simanjuntak', 'Anggaran sewa sudah disetujui, silakan lanjut pesan.');

        $task(['title' => 'Slide lirik lagu', 'work_program_id' => $sound->id, 'owner_membership_id' => $member('Daniel Kurniawan'), 'weight' => 2, 'starts_on' => '2026-12-01', 'due_on' => '2026-12-18']);
        $task(['title' => 'Soundcheck H-1', 'work_program_id' => $sound->id, 'owner_membership_id' => $member('Markus Wibowo'), 'priority' => 'high', 'weight' => 2, 'due_on' => '2026-12-23'], [$member('Daniel Kurniawan')]);
        $core = app(SaveWorkProgram::class)->handle($christmas, new WorkProgram, [
            'division_id' => null,
            'title' => 'Koordinasi pengurus inti',
            'pic_membership_id' => $member('Timotius Halim'),
            'status' => 'active',
            'objective' => 'Administrasi, undangan, dan anggaran panitia berjalan tertib.',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-12-31',
        ], $organizer);
        $task(['title' => 'Kirim undangan Natal ke jemaat', 'work_program_id' => $core->id, 'owner_membership_id' => $member('Maria Lestari'), 'due_on' => '2026-11-20']);
        $task(['title' => 'Susun RAB awal panitia', 'work_program_id' => $core->id, 'owner_membership_id' => $member('Debora Simanjuntak'), 'priority' => 'high', 'weight' => 3, 'due_on' => '2026-12-01']);
        $task(['title' => 'Ide dekorasi panggung anak', 'division_id' => $division('Seksi Acara')->id, 'priority' => 'low']);
    }

    /**
     * A past plenary meeting with its minutes and an upcoming one for the Christmas event.
     * Added once: skipped when the event already has meetings.
     */
    private function meetings(Organization $organization): void
    {
        $christmas = Event::query()->forOrganization($organization)->where('code', 'DEMO-NATAL-2026')->first();

        if ($christmas === null || $christmas->meetings()->exists()) {
            return;
        }

        $organizer = User::query()->where('username', 'demo.admin')->sole();
        $member = fn (string $name): int => $christmas->memberships()->whereHas('user', fn ($user) => $user->where('name', $name))->value('id');
        $save = app(SaveMeeting::class);

        $plenary = $save->handle($christmas, new Meeting, [
            'title' => 'Rapat pleno 1',
            'meets_on' => '2026-11-10',
            'starts_at' => '19:00',
            'ends_at' => '21:00',
            'location' => 'Ruang konsistori',
            'agenda' => "Pembukaan dan doa\nLaporan tiap seksi\nSusunan acara ibadah malam Natal\nLain-lain",
        ], $organizer);

        app(SaveMinutes::class)->handle(
            $plenary,
            '<p>Rapat dibuka oleh <strong>Ketua</strong>. Setiap seksi melaporkan persiapan.</p><ul><li>Seksi Acara menyampaikan draf liturgi.</li><li>Seksi Multimedia mengusulkan tambahan <em>sound system</em>.</li></ul><p>Data demo.</p>',
            ['Ibadah malam Natal dimulai pukul 19.00.', 'Anggaran sewa sound system tambahan maksimal Rp5.000.000.'],
            [['title' => 'Kirim draf liturgi ke pendeta', 'owner_membership_id' => $member('Ruth Hutapea'), 'due_on' => '2026-11-15']],
            0,
            $organizer,
        );

        // A past meeting: attendance is recorded directly, as it would have been on the day.
        $plenary->attendees()->attach(collect(['Timotius Halim', 'Maria Lestari', 'Debora Simanjuntak', 'Ruth Hutapea', 'Markus Wibowo'])
            ->mapWithKeys(fn (string $name) => [$member($name) => ['event_id' => $christmas->getKey()]])
            ->all());

        $save->handle($christmas, new Meeting, [
            'title' => 'Rapat koordinasi seksi',
            'meets_on' => '2026-12-03',
            'starts_at' => '19:30',
            'location' => 'Aula serbaguna',
            'agenda' => "Kesiapan tiap seksi\nGladi bersih",
        ], $organizer);
    }

    /**
     * Two shared links in the Christmas event's media library. Added once: skipped when the
     * library already has items.
     */
    private function library(Organization $organization): void
    {
        $christmas = Event::query()->forOrganization($organization)->where('code', 'DEMO-NATAL-2026')->first();

        if ($christmas === null || $christmas->documents()->where('category', Document::LIBRARY)->exists()) {
            return;
        }

        $add = app(AddLibraryItems::class);
        $person = fn (string $name): User => User::query()->where('name', $name)->sole();
        $division = fn (string $name): ?int => $christmas->divisions()->where('name', $name)->value('id');

        $add->link($christmas, 'https://www.example.com/demo/draft-materi-acara', 'Draft materi acara', $division('Seksi Acara'), $person('Ruth Hutapea'));
        $add->link($christmas, 'https://www.example.com/demo/referensi-dekorasi-natal', 'Referensi dekorasi Natal', $division('Seksi Perlengkapan'), $person('Maria Lestari'));
    }

    /**
     * Three kegiatan on the Christmas event's calendar, one of them over two days. Added
     * once: skipped when the calendar already has kegiatan.
     */
    private function calendar(Organization $organization): void
    {
        $christmas = Event::query()->forOrganization($organization)->where('code', 'DEMO-NATAL-2026')->first();

        if ($christmas === null || $christmas->calendarEntries()->exists()) {
            return;
        }

        $save = app(SaveCalendarEntry::class);
        $person = fn (string $name): User => User::query()->where('name', $name)->sole();
        $division = fn (string $name): ?int => $christmas->divisions()->where('name', $name)->value('id');

        $save->handle($christmas, new CalendarEntry, [
            'title' => 'Survei lokasi',
            'division_id' => $division('Seksi Perlengkapan'),
            'starts_on' => '2026-12-05',
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'location' => 'Gedung gereja',
            'notes' => 'Ukur panggung dan cek titik listrik untuk sound system.',
        ], $person('Maria Lestari'));

        $save->handle($christmas, new CalendarEntry, [
            'title' => 'Dekorasi gedung gereja',
            'division_id' => $division('Seksi Perlengkapan'),
            'starts_on' => '2026-12-21',
            'ends_on' => '2026-12-22',
            'location' => 'Gedung gereja',
        ], $person('Maria Lestari'));

        $save->handle($christmas, new CalendarEntry, [
            'title' => 'Latihan drama anak',
            'division_id' => $division('Seksi Acara'),
            'starts_on' => '2026-12-19',
            'starts_at' => '16:00',
            'ends_at' => '18:00',
            'location' => 'Aula serbaguna',
        ], $person('Ruth Hutapea'));
    }
}
