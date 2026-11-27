<?php

namespace Tests\Feature\Library;

use App\Actions\Library\AddLibraryItems;
use App\Actions\Work\AttachTaskDocument;
use App\Enums\CommitteeRoleKind;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCommittees;
use Tests\Concerns\BuildsWork;
use Tests\TestCase;

/**
 * The event's shared media library: every committee member adds and sees photos,
 * documents and links.
 */
class LibraryTest extends TestCase
{
    use BuildsCommittees;
    use BuildsWork;
    use LazilyRefreshDatabase;

    private Event $event;

    private Division $dekorasi;

    private User $decorator;

    private User $readerOfAnotherDivision;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->setUpCommittees();
        $this->event = $this->makeEvent();
        $this->dekorasi = $this->makeDivision($this->event, 'Seksi Dekorasi');
        $this->decorator = User::factory()->create(['name' => 'Tim Dekorasi']);
        $this->seat($this->decorator, $this->event, CommitteeRoleKind::DivisionMember, $this->dekorasi);
        $this->readerOfAnotherDivision = User::factory()->create();
        $this->seat($this->readerOfAnotherDivision, $this->event, CommitteeRoleKind::DivisionMember, $this->makeDivision($this->event, 'Seksi Acara'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function add(User $actor, array $data)
    {
        return $this->actingAs($actor)->post(route('events.library.insert', $this->event), $data, ['Accept' => 'application/json']);
    }

    private function addPhotos(): void
    {
        $this->app->make(AddLibraryItems::class)->files($this->event, [UploadedFile::fake()->image('panggung.jpg')], 'Contoh dekorasi', $this->dekorasi->id, $this->decorator);
    }

    public function test_a_member_shares_photos_with_a_title_and_everyone_sees_them(): void
    {
        $this->add($this->decorator, [
            'kind' => 'file',
            'title' => 'Contoh dekorasi',
            'division_id' => $this->dekorasi->id,
            'files' => [UploadedFile::fake()->image('panggung.jpg'), UploadedFile::fake()->create('rencana.pdf', 50, 'application/pdf')],
        ])->assertCreated()->assertJsonPath('data.count', 2);

        $items = Document::query()->where('category', Document::LIBRARY)->get();
        $this->assertSame(['Contoh dekorasi', 'Contoh dekorasi'], $items->pluck('title')->all());
        $this->assertSame([$this->dekorasi->id], $items->pluck('division_id')->unique()->values()->all());
        $this->assertSame(1, AuditLog::query()->where('action', 'library.files-added')->count());

        $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library', $this->event))
            ->assertOk()
            ->assertSee('Contoh dekorasi')
            ->assertSee('Seksi Dekorasi · Tim Dekorasi');
    }

    public function test_a_member_shares_a_link(): void
    {
        $this->add($this->readerOfAnotherDivision, [
            'kind' => 'link', 'title' => 'Draft materi acara', 'url' => 'https://docs.google.com/document/d/contoh',
        ])->assertCreated();

        $link = Document::query()->sole();
        $this->assertTrue($link->isLink());
        $this->assertSame('https://docs.google.com/document/d/contoh', $link->url);
        $this->assertNull($link->division_id);

        $this->actingAs($this->decorator)->get(route('events.library', $this->event))
            ->assertSee('href="https://docs.google.com/document/d/contoh"', false)
            ->assertSee('Draft materi acara');
    }

    public function test_only_web_links_are_accepted(): void
    {
        $this->add($this->decorator, ['kind' => 'link', 'title' => 'Jahat', 'url' => 'javascript:alert(1)'])
            ->assertStatus(422)
            ->assertJsonPath('errors.url.0', 'Alamat link harus diawali http:// atau https://.');

        $this->assertSame(0, Document::query()->count());
    }

    public function test_only_photos_and_office_documents_can_be_uploaded(): void
    {
        $response = $this->add($this->decorator, ['kind' => 'file', 'title' => 'Catatan', 'files' => [UploadedFile::fake()->create('catatan.txt', 1, 'text/plain')]])
            ->assertStatus(422);

        $this->assertSame('Hanya foto (JPG/PNG/WEBP) dan dokumen (PDF, Word, Excel, PowerPoint) yang bisa diunggah.', $response->json('errors')['files.0'][0]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_title_and_at_least_one_file_are_needed(): void
    {
        $this->add($this->decorator, ['kind' => 'file'])
            ->assertStatus(422)
            ->assertJsonPath('errors.title.0', 'Beri judul, misalnya "Contoh dekorasi".')
            ->assertJsonPath('errors.files.0', 'Pilih minimal satu foto atau dokumen.');
    }

    public function test_photos_preview_on_the_page_and_files_download(): void
    {
        $this->addPhotos();
        $photo = Document::query()->sole();

        $preview = $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library.download', [$this->event, $photo, 'preview' => 1]))->assertOk();
        $this->assertStringStartsWith('inline', $preview->headers->get('Content-Disposition'));
        $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library.download', [$this->event, $photo]))->assertDownload('panggung.jpg');
    }

    public function test_a_pdf_opens_in_the_browser_and_an_office_file_is_downloaded(): void
    {
        $add = $this->app->make(AddLibraryItems::class);
        [$pdf] = $add->files($this->event, [UploadedFile::fake()->create('materi.pdf', 20, 'application/pdf')], 'Draft materi', null, $this->decorator);
        [$sheet] = $add->files($this->event, [UploadedFile::fake()->create('anggaran.xlsx', 20, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')], 'Anggaran', null, $this->decorator);

        $preview = $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library.download', [$this->event, $pdf, 'preview' => 1]))->assertOk();
        $this->assertStringStartsWith('inline', $preview->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $preview->headers->get('Content-Type'));

        $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library.download', [$this->event, $sheet, 'preview' => 1]))->assertDownload('anggaran.xlsx');

        $this->actingAs($this->readerOfAnotherDivision)->get(route('events.library', $this->event))
            ->assertSee(route('events.library.download', [$this->event, $pdf]).'?preview=1', false)
            ->assertDontSee(route('events.library.download', [$this->event, $sheet]).'?preview=1', false);
    }

    public function test_people_outside_the_event_see_nothing(): void
    {
        $this->addPhotos();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('events.library', $this->event))->assertNotFound();
        $this->actingAs($outsider)->get(route('events.library.download', [$this->event, Document::query()->sole()]))->assertNotFound();
    }

    public function test_task_evidence_is_not_served_through_the_library(): void
    {
        $task = $this->makeTask($this->event, ['division_id' => $this->dekorasi->id]);
        $evidence = $this->app->make(AttachTaskDocument::class)->handle($task, UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'), null, $this->organizer);

        $this->actingAs($this->decorator)->get(route('events.library.download', [$this->event, $evidence]))->assertNotFound();
        $this->actingAs($this->organizer)->postJson(route('events.library.delete', [$this->event, $evidence]))->assertNotFound();
    }

    public function test_the_uploader_or_a_document_manager_removes_an_item_others_cannot(): void
    {
        $this->addPhotos();
        $photo = Document::query()->sole();

        $this->actingAs($this->readerOfAnotherDivision)->postJson(route('events.library.delete', [$this->event, $photo]))->assertForbidden();
        $this->assertNotSoftDeleted($photo);

        $coordinator = User::factory()->create();
        $this->seat($coordinator, $this->event, CommitteeRoleKind::DivisionCoordinator, $this->dekorasi);
        $this->actingAs($coordinator)->postJson(route('events.library.delete', [$this->event, $photo]))->assertOk();
        $this->assertSoftDeleted($photo);
        $this->assertSame(1, AuditLog::query()->where('action', 'library.removed')->count());
    }

    public function test_nothing_can_be_added_once_the_event_is_over(): void
    {
        $this->event->forceFill(['status' => 'completed'])->save();

        $this->add($this->decorator, ['kind' => 'link', 'title' => 'Telat', 'url' => 'https://contoh.org'])->assertForbidden();
    }

    public function test_titles_are_escaped(): void
    {
        $this->app->make(AddLibraryItems::class)->link($this->event, 'https://contoh.org', '<script>alert(1)</script>', null, $this->decorator);

        $html = $this->actingAs($this->decorator)->get(route('events.library', $this->event))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }
}
