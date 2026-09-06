<?php

namespace App\Http\Requests;

use App\Support\PersianDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ! $this->user()->suspended_at;
    }

    public static function payloadRules(bool $complete = false): array
    {
        $required = $complete ? 'required_without:business_id' : 'nullable';

        return [
            'business_id' => ['nullable', 'integer'],
            'correction_business_id' => ['nullable', 'integer'],
            'name' => [$required, 'nullable', 'string', 'max:180'],
            'category_id' => [$required, 'nullable', 'integer', 'exists:categories,id'],
            'city' => [$required, 'nullable', 'string', 'max:100'],
            'address' => [$required, 'nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+۰-۹٠-٩0-9()\s-]+$/u'],
            'website' => ['nullable', 'url:http,https', 'max:500'],
            'opening_hours' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:24,41'],
            'longitude' => ['nullable', 'numeric', 'between:43,64'],
            'with_review' => ['required', 'boolean'],
            'rating' => [Rule::requiredIf($complete && request()->boolean('with_review')), 'nullable', 'integer', 'between:1,5'],
            'body' => [Rule::requiredIf($complete && request()->boolean('with_review')), 'nullable', 'string', $complete ? 'min:10' : 'min:0', 'max:2000'],
            'visit_date' => ['nullable', 'string', 'max:20'],
            'display_name' => ['nullable', 'string', 'min:2', 'max:60', 'not_regex:/[۰-۹٠-٩0-9]{6}/u'],
            'confirm_distinct' => ['sometimes', 'boolean'],
            'edit_review_id' => ['nullable', 'integer'],
            'review_version' => ['nullable', 'integer', 'min:1'],
            'photo_ids' => ['present', 'array', 'max:6'],
            'photo_ids.*' => ['uuid', 'distinct'],
        ];
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1']] + self::payloadRules();
    }

    public static function visitDate(?string $date): string
    {
        try {
            return $date ? PersianDate::toGregorian($date) : PersianDate::today();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['visit_date' => $exception->getMessage()]);
        }
    }
}
