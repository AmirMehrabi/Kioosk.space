<?php

namespace App\Http\Requests\Auth;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => preg_replace('/[\s\p{Cf}]+/u', '', IranianMobile::digits($this->input('code'))) ?? '']);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'regex:/^[0-9]{5}$/D']];
    }

    public function messages(): array
    {
        return ['code.required' => 'کد پیامک‌شده را وارد کنید.', 'code.string' => 'کد باید ۵ رقم باشد.', 'code.regex' => 'کد ۵ رقمی پیامک‌شده را کامل وارد کنید.'];
    }
}
