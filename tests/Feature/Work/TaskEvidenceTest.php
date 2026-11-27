<?php

namespace Tests\Feature\Work;

use App\Actions\Work\AttachTaskDocument;
use App\Enums\CommitteeRoleKind;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The result of a task and the photos and documents that show it.
 */
class TaskEvidenceTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $acara;

    private User $worker;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo('2026-12-05 10:00:00');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->acara = $this->makeDivision($this->event, 'Seksi Acara');
        $this->worker = User::factory()->create(['name' => 'Petugas Acara']);
        $this->seat($this->worker, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);
        $this->task = $this->makeTask($this->event, ['division_id' => $this->acara->id, 'owner_membership_id' => $this->membershipOf($this->worker, $this->event)->id]);
    }

    private function upload(User $actor, UploadedFile ...$files)
    {
        return $this->actingAs($actor)->post(route('events.tasks.documents.insert', [$this->event, $this->task]), ['files' => $files], ['Accept' => 'application/json']);
    }

    public function test_the_people_working_on_a_task_can_correct_its_result(): void
    {
        $this->actingAs($this->worker)->postJson(route('events.tasks.result', [$this->event, $this->task]), ['result' => '  Daftar lagu sudah final.  '])->assertOk();

        $this->assertSame('Daftar lagu sudah final.', $this->task->fresh()->result);
        $this->assertSame('Daftar lagu sudah final.', AuditLog::query()->where('action', 'tasks.result-saved')->sole()->after['result']);
    }

    public function test_a_result_cannot_be_emptied(): void
    {
        $this->actingAs($this->worker)->postJson(route('events.tasks.result', [$this->event, $this->task]), ['result' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.result.0', 'Kolom hasil pekerjaan wajib diisi.');
    }

    public function test_someone_who_does_not_work_on_the_task_cannot_change_its_result_or_files(): void
    {
        $colleague = User::factory()->create();
        $this->seat($colleague, $this->event, CommitteeRoleKind::DivisionMember, $this->acara);

        $this->actingAs($colleague)->postJson(route('events.tasks.result', [$this->event, $this->task]), ['result' => 'x'])->assertForbidden();
        $this->upload($colleague, UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'))->assertForbidden();

        $this->assertNull($this->task->fresh()->result);
        $this->assertSame(0, Document::query()->count());
    }

    public function test_evidence_is_stored_privately_linked_to_the_task_and_audited(): void
    {
        $this->upload($this->worker, UploadedFile::fake()->create('kontrak-sound.pdf', 120, 'application/pdf'))->assertCreated()->assertJsonPath('data.count', 1);

        $document = Document::query()->sole();
        $version = $document->latestVersion;
        $this->assertSame('kontrak-sound', $document->title);
        $this->assertSame($this->event->id, $document->event_id);
        $this->assertSame('local', $version->disk);
        $this->assertSame('kontrak-sound.pdf', $version->original_name);
        $this->assertSame(64, strlen($version->sha256));
        Storage::disk('local')->assertExists($version->path);
        $this->assertTrue($this->task->documents()->whereKey($document->id)->exists());
        $this->assertSame('kontrak-sound.pdf', AuditLog::query()->where('action', 'documents.uploaded')->sole()->after['original_name']);
    }

    public function test_a_file_of_an_unsupported_type_is_refused(): void
    {
        $this->upload($this->worker, UploadedFile::fake()->create('skrip.php', 1, 'application/x-php'))
            ->assertStatus(422)
            ->assertJsonPath('errors', ['files.0' => ['Jenis file tidak didukung. Gunakan foto (JPG/PNG/WEBP), PDF, dokumen Office, TXT, atau CSV.']]);
    }

    public function test_a_file_renamed_to_look_like_a_pdf_is_refused(): void
    {
        // A real file, so the type is read from its content rather than from its name.
        $path = tempnam(sys_get_temp_dir(), 'evidence');
        file_put_contents($path, '<?php echo "halo";');

        $this->upload($this->worker, new UploadedFile($path, 'bukti.pdf', null, null, true))
            ->assertStatus(422)
            ->assertJsonPath('errors', ['files.0' => ['Jenis file tidak didukung. Gunakan foto (JPG/PNG/WEBP), PDF, dokumen Office, TXT, atau CSV.']]);

        unlink($path);
    }

    public function test_a_file_over_ten_megabytes_is_refused(): void
    {
        $this->upload($this->worker, UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonPath('errors', ['files.0' => ['Ukuran setiap file maksimal 10 MB.']]);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_several_photos_can_be_added_at_once_but_not_more_than_ten(): void
    {
        $this->upload($this->worker, UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png'))->assertCreated()->assertJsonPath('data.count', 2);

        $tooMany = array_map(fn (int $number) => UploadedFile::fake()->image("foto-{$number}.jpg"), range(1, 11));
        $this->upload($this->worker, ...$tooMany)
            ->assertStatus(422)
            ->assertJsonPath('errors.files.0', 'Maksimal 10 file sekali unggah.');

        $this->assertSame(2, $this->task->documents()->count());
    }

    public function test_a_photo_can_be_shown_on_the_page_while_other_files_are_always_downloaded(): void
    {
        $attach = $this->app->make(AttachTaskDocument::class);
        $photo = $attach->handle($this->task, UploadedFile::fake()->image('panggung.jpg'), null, $this->organizer);
        $pdf = $attach->handle($this->task, UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'), null, $this->organizer);

        $preview = $this->actingAs($this->worker)->get(route('events.tasks.documents.download', [$this->event, $this->task, $photo, 'preview' => 1]))->assertOk();
        $this->assertStringStartsWith('inline', $preview->headers->get('Content-Disposition'));

        $this->actingAs($this->worker)->get(route('events.tasks.documents.download', [$this->event, $this->task, $pdf, 'preview' => 1]))->assertDownload('nota.pdf');
    }

    public function test_every_committee_member_downloads_task_files_and_nobody_else(): void
    {
        $document = $this->app->make(AttachTaskDocument::class)->handle($this->task, UploadedFile::fake()->create('rundown.pdf', 10, 'application/pdf'), null, $this->organizer);
        $url = route('events.tasks.documents.download', [$this->event, $this->task, $document]);

        $this->actingAs($this->worker)->get($url)->assertOk()->assertDownload('rundown.pdf');

        $otherDivisionMember = User::factory()->create();
        $this->seat($otherDivisionMember, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Konsumsi'));
        $this->actingAs($otherDivisionMember)->get($url)->assertOk();

        $notYetAccepted = User::factory()->create();
        $this->seat($notYetAccepted, $this->event, CommitteeRoleKind::DivisionMember, $this->acara, accepted: false);
        $this->actingAs($notYetAccepted)->get($url)->assertNotFound();

        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    }

    public function test_removing_a_file_takes_it_off_the_task_and_keeps_the_stored_version(): void
    {
        $this->upload($this->worker, UploadedFile::fake()->create('foto.png', 50, 'image/png'))->assertCreated();
        $document = Document::query()->sole();

        $this->actingAs($this->worker)->postJson(route('events.tasks.documents.delete', [$this->event, $this->task, $document]))->assertOk();

        $this->assertSoftDeleted($document);
        $this->assertSame(0, $this->task->documents()->count());
        Storage::disk('local')->assertExists($document->versions()->sole()->path);
        $this->assertSame(1, AuditLog::query()->where('action', 'documents.removed')->count());
    }

    public function test_a_collaborator_cannot_remove_a_file_someone_else_uploaded(): void
    {
        $document = $this->app->make(AttachTaskDocument::class)->handle($this->task, UploadedFile::fake()->create('rundown.pdf', 10, 'application/pdf'), null, $this->organizer);

        $this->actingAs($this->worker)->postJson(route('events.tasks.documents.delete', [$this->event, $this->task, $document]))->assertForbidden();

        $this->assertNotSoftDeleted($document);
    }
}
