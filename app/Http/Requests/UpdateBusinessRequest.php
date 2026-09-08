<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Models\Media;
use App\Services\BusinessHours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = $this->route('business');
        if (! $this->user() || ! $business instanceof Business) {
            return false;
        }
        if ($this->routeIs('admin.*')) {
            return $this->user()->hasStaffAccess();
        }

        return $business->owners()->whereKey($this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'], 'category_id' => ['required', 'integer', 'exists:categories,id'],
            'city' => ['required', 'string', 'exists:cities,name'], 'address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price_range' => ['nullable', 'integer', 'between:1,4'],
            'latitude' => ['present_with:longitude', 'nullable', 'required_with:longitude', 'numeric', 'between:24,41'],
            'longitude' => ['present_with:latitude', 'nullable', 'required_with:latitude', 'numeric', 'between:43,64'],
            'phones' => ['nullable', 'array', 'max:5'], 'phones.*.label' => ['required', 'string', 'max:40'], 'phones.*.value' => ['required', 'string', 'max:40', 'regex:/^[+۰-۹٠-٩0-9()\s-]+$/u'],
            'websites' => ['nullable', 'array', 'max:5'], 'websites.*.label' => ['required', 'string', 'max:40'], 'websites.*.url' => ['required', 'url:http,https', 'max:500'],
            'weekly_hours' => ['nullable', 'array'], 'weekly_hours.*.closed' => ['required_with:weekly_hours', 'boolean'], 'weekly_hours.*.shifts' => ['nullable', 'array', 'max:4'],
            'weekly_hours.*.shifts.*.opens' => ['required', 'date_format:H:i'], 'weekly_hours.*.shifts.*.closes' => ['required', 'date_format:H:i'], 'weekly_hours.*.shifts.*.next_day' => ['required', 'boolean'],
            'featured_media_ids' => ['nullable', 'array', 'max:5'], 'featured_media_ids.*' => ['uuid', 'distinct', 'exists:media,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['weekly_hours', 'weekly_hours.*'])) {
                try {
                    app(BusinessHours::class)->normalize($this->input('weekly_hours'));
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            }
            $ids = $this->input('featured_media_ids', []);
            if ($ids && Media::whereIn('id', $ids)->where('business_id', $this->route('business')->id)->where('status', 'published')->count() !== count($ids)) {
                $validator->errors()->add('featured_media_ids', 'همه تصاویر اصلی باید از گالری منتشرشده همین کسب‌وکار باشند.');
            }
        }];
    }
}
