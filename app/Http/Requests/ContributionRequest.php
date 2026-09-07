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
            'city' => [$required, 'nullable', 'string', 'exists:cities,name'],
            'address' => [$required, 'nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+۰-۹٠-٩0-9()\s-]+$/u'],
            'website' => ['nullable', 'url:http,https', 'max:500'],
            'opening_hours' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'phones' => ['nullable', 'array', 'max:5'],
            'phones.*.label' => ['required', 'string', 'max:40'],
            'phones.*.value' => ['required', 'string', 'max:40', 'regex:/^[+۰-۹٠-٩0-9()\s-]+$/u'],
            'websites' => ['nullable', 'array', 'max:5'],
            'websites.*.label' => ['required', 'string', 'max:40'],
            'websites.*.url' => ['required', 'url:http,https', 'max:500'],
            'weekly_hours' => ['nullable', 'array'],
            'weekly_hours.*.closed' => ['required_with:weekly_hours', 'boolean'],
            'weekly_hours.*.shifts' => ['nullable', 'array', 'max:4'],
            'weekly_hours.*.shifts.*.opens' => ['required', 'date_format:H:i'],
            'weekly_hours.*.shifts.*.closes' => ['required', 'date_format:H:i'],
            'weekly_hours.*.shifts.*.next_day' => ['required', 'boolean'],
            'featured_photo_ids' => ['nullable', 'array', 'max:5'],
            'featured_photo_ids.*' => ['uuid', 'distinct'],
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
