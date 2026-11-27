<?php

namespace App\Http\Requests\Work;

use App\Http\Requests\JsonFormRequest;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AddTaskDependencyRequest extends JsonFormRequest
{
    public function authorize(TaskAuthority $authority): bool
    {
        return $authority->canManage($this->user(), $this->route('task'));
    }

    /**
     * Only tasks of this event exist as far as this request is concerned.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'depends_on_task_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where('event_id', $this->route('event')->getKey())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['depends_on_task_id.exists' => 'Tugas prasyarat harus berasal dari event yang sama.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['depends_on_task_id' => 'tugas prasyarat'];
    }
}
