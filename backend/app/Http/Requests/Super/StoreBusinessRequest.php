<?php

namespace App\Http\Requests\Super;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware EnsureSuperAdmin
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120']];
    }

    public function messages(): array
    {
        return ['name.required' => 'Nama restoran wajib diisi.'];
    }
}
