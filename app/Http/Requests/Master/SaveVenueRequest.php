<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\MasterRecordRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SaveVenueRequest extends MasterRecordRequest
{
    protected function routeParameter(): string
    {
        return 'venue';
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', $this->uniqueInOrganization('venues', 'name')],
            'address' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama venue',
            'address' => 'alamat',
            'capacity' => 'kapasitas',
            'notes' => 'catatan',
            'is_active' => 'status',
        ];
    }
}
