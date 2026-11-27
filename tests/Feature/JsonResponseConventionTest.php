<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Http\Requests\JsonFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class JsonResponseConventionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function (): void {
            Route::post('/_test/items', [StubItemController::class, 'store']);
            Route::post('/_test/items/conflict', [StubItemController::class, 'conflict']);
        });
    }

    public function test_a_successful_ajax_request_is_answered_in_the_envelope(): void
    {
        $this->postJson('/_test/items', ['name' => 'Mikrofon', 'quantity' => 8])
            ->assertCreated()
            ->assertExactJson(['status' => true, 'data' => ['name' => 'Mikrofon', 'quantity' => 8]]);
    }

    public function test_a_failed_validation_answers_422_with_the_first_message_and_all_field_errors(): void
    {
        $this->postJson('/_test/items', ['name' => '', 'quantity' => 0])
            ->assertStatus(422)
            ->assertExactJson([
                'status' => false,
                'data' => 'Kolom nama wajib diisi.',
                'errors' => [
                    'name' => ['Kolom nama wajib diisi.'],
                    'quantity' => ['Kolom jumlah minimal bernilai 1.'],
                ],
            ]);
    }

    public function test_input_is_stored_as_typed_without_html_escaping(): void
    {
        $this->postJson('/_test/items', ['name' => '  Sound & Lighting <Utama>  ', 'quantity' => 1])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Sound & Lighting <Utama>');
    }

    public function test_a_failed_validation_on_a_regular_form_post_redirects_back_with_errors(): void
    {
        $this->from('/_test/form')
            ->post('/_test/items', ['name' => ''])
            ->assertRedirect('/_test/form')
            ->assertSessionHasErrors(['name' => 'Kolom nama wajib diisi.']);
    }

    public function test_a_request_the_form_request_does_not_authorize_answers_403(): void
    {
        $this->postJson('/_test/items', ['name' => 'Mikrofon', 'quantity' => 1, 'forbidden' => true])
            ->assertForbidden();
    }

    public function test_a_domain_failure_carries_its_own_status_and_message(): void
    {
        $this->postJson('/_test/items/conflict')
            ->assertStatus(409)
            ->assertExactJson([
                'status' => false,
                'data' => 'Data sudah diubah oleh pengguna lain.',
                'errors' => [],
            ]);
    }
}

class StubItemRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return ! $this->boolean('forbidden');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['quantity' => 'jumlah'];
    }
}

class StubItemController extends Controller
{
    public function store(StubItemRequest $request): JsonResponse
    {
        return $this->jsonSuccess($request->validated(), 201);
    }

    public function conflict(): JsonResponse
    {
        return $this->jsonFailure('Data sudah diubah oleh pengguna lain.', 409);
    }
}
