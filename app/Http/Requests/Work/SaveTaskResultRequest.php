<?php

namespace App\Http\Requests\Work;

use App\Http\Requests\JsonFormRequest;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;

class SaveTaskResultRequest extends JsonFormRequest
{
    public function authorize(TaskAuthority $authority): bool
    {
        return $authority->canWork($this->user(), $this->route('task'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return ['result' => ['required', 'string', 'max:5000']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['result' => 'hasil pekerjaan'];
    }
}
