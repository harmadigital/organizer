<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\JsonFormRequest;
use App\Services\LibraryAuthority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Photos and documents, or a link, for the event's shared media library.
 */
class AddLibraryItemRequest extends JsonFormRequest
{
    /**
     * Images and office documents only; the content must match, not just the name.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public const MAX_KILOBYTES = 10240;

    public const MAX_FILES = 10;

    public function authorize(LibraryAuthority $authority): bool
    {
        return $authority->canAdd($this->user(), $this->route('event'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['file', 'link'])],
            'title' => ['required', 'string', 'max:200'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('event_id', $this->route('event')->getKey())],
            'files' => ['exclude_unless:kind,file', 'required', 'array', 'max:'.self::MAX_FILES],
            'files.*' => ['exclude_unless:kind,file', 'file', 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS), 'max:'.self::MAX_KILOBYTES],
            'url' => ['exclude_unless:kind,link', 'required', 'string', 'max:2000', 'url:http,https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Beri judul, misalnya "Contoh dekorasi".',
            'files.required' => 'Pilih minimal satu foto atau dokumen.',
            'files.max' => 'Maksimal '.self::MAX_FILES.' file sekali unggah.',
            'files.*.mimes' => 'Hanya foto (JPG/PNG/WEBP) dan dokumen (PDF, Word, Excel, PowerPoint) yang bisa diunggah.',
            'files.*.max' => 'Ukuran setiap file maksimal 10 MB.',
            'url.required' => 'Tempelkan alamat link.',
            'url.url' => 'Alamat link harus diawali http:// atau https://.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'judul', 'division_id' => 'seksi', 'url' => 'link'];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        return array_values($this->file('files') ?? []);
    }

    public function divisionId(): ?int
    {
        return $this->filled('division_id') ? $this->integer('division_id') : null;
    }
}
