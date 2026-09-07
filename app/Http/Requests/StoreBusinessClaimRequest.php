<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user() && ! $this->user()->suspended_at && $this->user()->mobile_verified_at);
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'note' => ['required', 'string', 'min:20', 'max:2000'],
            'proofs' => ['required', 'array', 'between:1,3'],
            'proofs.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000'],
        ];
    }
}
