<?php

namespace App\Http\Requests\Events;

use App\Enums\ObjectiveType;
use App\Enums\UserStatus;
use App\Http\Requests\JsonFormRequest;
use App\Models\Event;
use App\Services\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Access is checked by the controller through EventPolicy (create or update).
 */
class SaveEventRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digitsOnly = fn (string $field): ?string => $this->filled($field)
            ? (preg_replace('/\D+/', '', (string) $this->input($field)) ?: null)
            : null;

        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper($this->string('code')->trim()->value()) : null,
            'budget_target_amount' => $digitsOnly('budget_target_amount'),
            'target_participants' => $digitsOnly('target_participants'),
            'objectives' => array_values(array_filter(
                (array) $this->input('objectives', []),
                fn ($objective): bool => is_array($objective) && filled($objective['description'] ?? null),
            )),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $organizationId = app(CurrentOrganization::class)->id();
        $inOrganization = fn (string $table) => Rule::exists($table, 'id')->where('organization_id', $organizationId);
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:200'],
            'code' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('events', 'code')->where('organization_id', $organizationId)->ignore($event instanceof Event ? $event : null),
            ],
            'event_category_id' => ['nullable', 'integer', $inOrganization('event_categories')],
            'theme' => ['nullable', 'string', 'max:255'],
            'subtheme' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'committee_starts_on' => ['nullable', 'date', 'before_or_equal:ends_on'],
            'committee_ends_on' => ['nullable', 'date', 'after_or_equal:committee_starts_on'],
            'venue_id' => ['nullable', 'integer', $inOrganization('venues')],
            'location_text' => ['nullable', 'string', 'max:255'],
            'organization_unit_id' => ['nullable', 'integer', $inOrganization('organization_units')],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('status', UserStatus::Active->value)],
            'target_participants' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'budget_target_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'background' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['array', 'max:30'],
            'objectives.*.type' => ['required', Rule::enum(ObjectiveType::class)],
            'objectives.*.description' => ['required', 'string', 'max:1000'],
            'objectives.*.target' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Kode event hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'code.unique' => 'Kode event ini sudah dipakai event lain.',
            'ends_on.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'committee_starts_on.before_or_equal' => 'Masa kepanitiaan harus dimulai sebelum event berakhir.',
            'committee_ends_on.after_or_equal' => 'Akhir masa kepanitiaan tidak boleh sebelum awalnya.',
            'objectives.*.description.required' => 'Uraian tujuan atau indikator wajib diisi.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama event',
            'code' => 'kode event',
            'event_category_id' => 'kategori',
            'theme' => 'tema',
            'subtheme' => 'subtema',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal selesai',
            'committee_starts_on' => 'awal masa kepanitiaan',
            'committee_ends_on' => 'akhir masa kepanitiaan',
            'venue_id' => 'venue',
            'location_text' => 'lokasi lain',
            'organization_unit_id' => 'unit penanggung jawab',
            'responsible_user_id' => 'penanggung jawab',
            'target_participants' => 'target peserta',
            'budget_target_amount' => 'target anggaran',
            'description' => 'deskripsi',
            'background' => 'latar belakang',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eventAttributes(): array
    {
        return $this->safe()->except('objectives');
    }

    /**
     * @return list<array{type: string, description: string, target: ?string}>
     */
    public function objectives(): array
    {
        return array_map(fn (array $objective): array => [
            'type' => $objective['type'],
            'description' => $objective['description'],
            'target' => $objective['target'] ?? null,
        ], $this->validated('objectives', []));
    }
}
