<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->platform_role === PlatformRole::Superadmin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:24,41'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:43,64'],
            'position' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }
}
