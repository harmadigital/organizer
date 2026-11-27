<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\JsonFormRequest;
use App\Services\MeetingAuthority;
use App\Services\RichText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveMinutesRequest extends JsonFormRequest
{
    public const MAX_ROWS = 30;

    public function authorize(MeetingAuthority $authority): bool
    {
        return $authority->canManage($this->user(), $this->route('meeting'));
    }

    /**
     * Empty decision and follow-up rows are ignored, so the form may keep a blank row.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'decisions' => array_values(array_filter(array_map(
                fn ($decision) => is_string($decision) ? trim($decision) : '',
                (array) $this->input('decisions', []),
            ), fn (string $decision) => $decision !== '')),
            'action_items' => array_values(array_filter(
                (array) $this->input('action_items', []),
                fn ($item) => is_array($item) && (filled($item['title'] ?? null) || filled($item['owner_membership_id'] ?? null) || filled($item['due_on'] ?? null)),
            )),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $member = Rule::exists('event_memberships', 'id')->where('event_id', $this->route('event')->getKey())->whereIn('status', ['invited', 'active']);

        return [
            'minutes' => ['required', 'string', 'max:200000'],
            'minutes_version' => ['required', 'integer', 'min:0'],
            'decisions' => ['array', 'max:'.self::MAX_ROWS],
            'decisions.*' => ['string', 'max:2000'],
            'action_items' => ['array', 'max:'.self::MAX_ROWS],
            'action_items.*.title' => ['required', 'string', 'max:200'],
            'action_items.*.owner_membership_id' => ['nullable', 'integer', $member],
            'action_items.*.due_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minutes.required' => 'Tuliskan isi notulen.',
            'action_items.*.title.required' => 'Tuliskan apa yang harus ditindaklanjuti.',
            'action_items.*.owner_membership_id.exists' => 'Penanggung jawab harus panitia aktif atau yang sedang diundang di event ini.',
            'minutes.max' => 'Isi notulen terlalu panjang.',
            'decisions.max' => 'Maksimal '.self::MAX_ROWS.' keputusan.',
            'action_items.max' => 'Maksimal '.self::MAX_ROWS.' tindak lanjut sekali simpan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['minutes' => 'isi notulen', 'action_items.*.due_on' => 'tenggat'];
    }

    /**
     * @return list<array{title: string, owner_membership_id: ?int, due_on: ?string}>
     */
    public function newActionItems(): array
    {
        return array_map(fn (array $item): array => [
            'title' => trim($item['title']),
            'owner_membership_id' => filled($item['owner_membership_id'] ?? null) ? (int) $item['owner_membership_id'] : null,
            'due_on' => filled($item['due_on'] ?? null) ? $item['due_on'] : null,
        ], $this->validated('action_items') ?? []);
    }

    /**
     * An editor holding only empty paragraphs counts as empty.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('minutes') || ! is_string($this->input('minutes'))) {
                return;
            }

            if (app(RichText::class)->plainText($this->input('minutes')) === '') {
                $validator->errors()->add('minutes', 'Tuliskan isi notulen.');
            }
        }];
    }
}
