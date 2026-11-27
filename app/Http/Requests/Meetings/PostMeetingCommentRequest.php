<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\JsonFormRequest;
use App\Services\MeetingAuthority;
use Illuminate\Contracts\Validation\ValidationRule;

class PostMeetingCommentRequest extends JsonFormRequest
{
    public function authorize(MeetingAuthority $authority): bool
    {
        return $authority->canComment($this->user(), $this->route('meeting'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:2000']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['body.required' => 'Tulis komentar terlebih dahulu.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'komentar'];
    }
}
