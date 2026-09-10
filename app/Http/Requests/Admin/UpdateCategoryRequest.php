<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->route('category'))],
            'is_active' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'between:0,65535'],
        ];
    }
}
