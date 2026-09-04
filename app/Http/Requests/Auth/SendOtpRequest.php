<?php

namespace App\Http\Requests\Auth;

use App\Support\IranianMobile;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => IranianMobile::normalize($this->input('mobile'))]);
    }

    public function rules(): array
    {
        return ['mobile' => ['required', 'string', 'regex:/^\+989[0-9]{9}$/D']];
    }

    public function messages(): array
    {
        return ['mobile.required' => 'شماره موبایل خود را وارد کنید.', 'mobile.string' => 'شماره موبایل معتبر وارد کنید.', 'mobile.regex' => 'شماره موبایل ایران را مانند ۰۹۱۲۳۴۵۶۷۸۹ وارد کنید.'];
    }
}
