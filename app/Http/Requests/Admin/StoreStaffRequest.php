<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformRole;
use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! IranianMobile::valid(IranianMobile::normalize((string) $value))) {
                    $fail('شماره موبایل معتبر نیست.');
                }
            }],
            'platform_role' => ['required', 'in:admin,superadmin'],
        ];
    }
}
