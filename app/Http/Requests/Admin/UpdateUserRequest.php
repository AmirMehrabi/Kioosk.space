<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'platform_role' => ['required', 'in:user,admin,superadmin'],
            'status' => ['required', 'in:active,suspended'],
        ];
    }
}
