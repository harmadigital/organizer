<?php

namespace App\Http\Requests\Work;

use App\Http\Requests\JsonFormRequest;
use App\Services\TaskAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Photos or documents added to a task's results.
 */
class UploadTaskDocumentRequest extends JsonFormRequest
{
    /**
     * Extensions accepted as evidence; the file's content must match them, not just its name.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

    public const MAX_KILOBYTES = 10240;

    public const MAX_FILES = 10;

    public function authorize(TaskAuthority $authority): bool
    {
        return $authority->canWork($this->user(), $this->route('task'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return self::fileRules(required: true);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::fileMessages();
    }

    /**
     * Rules for the files[] field, shared with the request that completes a task.
     *
     * @return array<string, array<int, string>>
     */
    public static function fileRules(bool $required): array
    {
        return [
            'files' => [$required ? 'required' : 'nullable', 'array', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS), 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fileMessages(): array
    {
        return [
            'files.required' => 'Pilih minimal satu foto atau dokumen.',
            'files.max' => 'Maksimal '.self::MAX_FILES.' file sekali unggah.',
            'files.*.file' => 'File gagal diunggah. Coba pilih ulang.',
            'files.*.mimes' => 'Jenis file tidak didukung. Gunakan foto (JPG/PNG/WEBP), PDF, dokumen Office, TXT, atau CSV.',
            'files.*.max' => 'Ukuran setiap file maksimal 10 MB.',
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        return array_values($this->file('files') ?? []);
    }
}
