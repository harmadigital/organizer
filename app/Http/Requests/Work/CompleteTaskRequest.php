<?php

namespace App\Http\Requests\Work;

use App\Http\Requests\JsonFormRequest;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Marking a task finished: its result, plus any photos or documents that show it.
 */
class CompleteTaskRequest extends JsonFormRequest
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
        return [
            'result' => ['required', 'string', 'max:5000'],
            ...UploadTaskDocumentRequest::fileRules(required: false),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'result.required' => 'Tuliskan hasil pekerjaan sebelum menandai tugas selesai.',
            ...UploadTaskDocumentRequest::fileMessages(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['result' => 'hasil pekerjaan'];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        return array_values($this->file('files') ?? []);
    }
}
