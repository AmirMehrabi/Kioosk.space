<?php

namespace App\Services;

use App\Http\Requests\ContributionRequest;
use App\Models\Business;
use App\Models\ContributionDraft;
use App\Models\Review;
use App\Models\User;
use App\Support\BusinessIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitContribution
{
    public function __construct(private BusinessHours $hours) {}

    public function handle(ContributionDraft $draft, User $user, bool $staff): array
    {
        return Cache::lock('contributions:write', 60)->block(10, fn () => DB::transaction(function () use ($draft, $user, $staff) {
            $draft = ContributionDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            abort_unless($draft->user_id === $user->id, 404);
            if ($draft->status === 'submitted') {
                return $draft->result;
            }
            abort_unless($draft->status === 'draft', 409);
            abort_unless($draft->expires_at->isFuture(), 410);
            $data = $draft->payload;
            $rules = ContributionRequest::payloadRules(true);
            if ($data['with_review'] ?? false) {
                $rules['rating'] = ['required', 'integer', 'between:1,5'];
                $rules['body'] = ['required', 'string', 'min:10', 'max:2000'];
            }
            $data = Validator::make($data, $rules)->validate();
            if ($user->name === 'کاربر کیوسک' || preg_match('/^(کاربر|User)(\s|$)/u', $user->name)) {
                if (empty($data['display_name'])) {
                    throw ValidationException::withMessages(['display_name' => 'نام نمایشی عمومی خود را وارد کنید.']);
                }
                $user->update(['name' => $data['display_name']]);
            }
            $photos = $draft->photos()->lockForUpdate()->get();
            if ($photos->pluck('client_id')->sort()->values()->all() !== collect($data['photo_ids'])->sort()->values()->all()) {
                throw ValidationException::withMessages(['photo_ids' => 'بارگذاری همه عکس‌ها را کامل کنید یا عکس ناموفق را حذف کنید.']);
            }
            if (array_diff($data['featured_photo_ids'] ?? [], $data['photo_ids'])) {
                throw ValidationException::withMessages(['featured_photo_ids' => 'تصاویر نمای اصلی باید از عکس‌های همین مشارکت باشند.']);
            }
            if (! empty($data['business_id'])) {
                $business = Business::findOrFail($data['business_id']);
                $ownReview = ! empty($data['edit_review_id']) && Review::withTrashed()->whereKey($data['edit_review_id'])->where('user_id', $user->id)->where('business_id', $business->id)->exists();
                abort_unless($business->status === 'approved' || ($ownReview && ! $business->merged_into_id), 404);
                if (! $data['with_review']) {
                    throw ValidationException::withMessages(['body' => 'برای این مکان تجربه خود را بنویسید.']);
                }
            } else {
                $fingerprint = BusinessIdentity::fingerprint($data);
                $business = Business::where('fingerprint', $fingerprint)->first();
                if (! empty($data['correction_business_id'])) {
                    $correction = Business::where('contributor_id', $user->id)->whereIn('status', ['corrections', 'rejected'])->findOrFail($data['correction_business_id']);
                    abort_if($business && $business->id !== $correction->id, 409, 'مکان تکراری است؛ مدیریت باید آن را ادغام کند.');
                    $business = $correction;
                    $business->fingerprint = $fingerprint;
                }
                if ($business?->merged_into_id) {
                    $business = Business::findOrFail($business->merged_into_id);
                }
                if ($business && in_array($business->status, ['rejected', 'corrections', 'incomplete'], true)) {
                    if ($business->contributor_id !== $user->id) {
                        throw ValidationException::withMessages(['name' => 'این مکان نیازمند بررسی مدیریت است. اطلاعات پیشنهادی شما در پیش‌نویس محفوظ است.']);
                    }
                    $business->update($this->businessData($data) + ['status' => 'pending', 'moderation_reason' => null]);
                }
                if (! $business) {
                    $matches = Business::where('status', 'approved')->where('normalized_city', BusinessIdentity::normalize($data['city']))
                        ->where('normalized_name', 'like', '%'.BusinessIdentity::normalize($data['name']).'%')->exists();
                    if ($matches && empty($data['confirm_distinct'])) {
                        throw ValidationException::withMessages(['confirm_distinct' => 'مکان مشابهی وجود دارد؛ نتیجه جست‌وجو را بررسی و متفاوت بودن شعبه را تأیید کنید.']);
                    }
                    $business = Business::create($this->businessData($data) + [
                        'slug' => Str::uuid()->toString(), 'fingerprint' => $fingerprint,
                        'contributor_id' => $user->id, 'status' => $staff ? 'approved' : 'pending',
                    ]);
                }
            }
            $review = null;
            if ($data['with_review']) {
                abort_if($business->owners()->whereKey($user->id)->exists(), 403, 'مالک نمی‌تواند به کسب‌وکار خود امتیاز دهد.');
                $review = Review::withTrashed()->where('business_id', $business->id)->where('user_id', $user->id)->lockForUpdate()->first();
                if ($review && ($data['edit_review_id'] ?? null) !== $review->id) {
                    abort(response()->json(['message' => 'شما قبلاً تجربه‌ای ثبت کرده‌اید. پیش‌نویس تازه محفوظ است.', 'edit_url' => route('reviews.edit', $review)], 409));
                }
                if (! $review && ! empty($data['edit_review_id'])) {
                    abort(409, 'تجربه انتخاب‌شده با این مکان مطابقت ندارد.');
                }
                $attributes = ['rating' => $data['rating'], 'body' => $data['body'], 'visit_date' => ContributionRequest::visitDate($data['visit_date'] ?? null)];
                if ($review) {
                    abort_unless(($data['review_version'] ?? null) === $review->version, 409, 'این تجربه در دستگاه دیگری تغییر کرده است.');
                    $this->revision($review, $user->id);
                    $review->fill($attributes);
                    $review->version++;
                    $review->deleted_at = null;
                    if (! in_array($review->status, ['hidden', 'corrections'], true)) {
                        $review->status = $business->status === 'approved' ? 'published' : 'pending';
                    }
                    $review->save();
                } else {
                    $review = Review::create($attributes + ['business_id' => $business->id, 'user_id' => $user->id, 'status' => $business->status === 'approved' ? 'published' : 'pending']);
                }
                if ($review->photos()->where('status', '!=', 'removed')->count() + $photos->count() > 6) {
                    throw ValidationException::withMessages(['photo_ids' => 'هر تجربه حداکثر شش عکس دارد. ابتدا عکس‌های قبلی را حذف کنید.']);
                }
            }
            foreach ($photos as $photo) {
                $photo->update(['business_id' => $business->id, 'review_id' => $review?->id, 'status' => 'published']);
            }
            if (empty($data['business_id']) && ! empty($data['featured_photo_ids'])) {
                $featured = [];
                foreach ($data['featured_photo_ids'] as $position => $clientId) {
                    $photo = $photos->firstWhere('client_id', $clientId);
                    if ($photo) {
                        $featured[$photo->id] = ['position' => $position + 1];
                    }
                }
                $business->featuredPhotos()->sync($featured);
            }
            $result = ['status' => $business->status === 'approved' ? ($review?->status ?? 'published') : 'pending', 'business_id' => $business->id, 'review_id' => $review?->id, 'url' => $business->status === 'approved' ? route('businesses.show', $business->slug) : route('contributions.index')];
            $draft->update(['status' => 'submitted', 'result' => $result]);

            return $result;
        }, 3));
    }

    public function businessData(array $data): array
    {
        $phones = array_values($data['phones'] ?? (! empty($data['phone']) ? [['label' => 'اصلی', 'value' => $data['phone']]] : []));
        $websites = array_values($data['websites'] ?? (! empty($data['website']) ? [['label' => 'وب‌سایت اصلی', 'url' => $data['website']]] : []));

        return collect($data)->only(['name', 'category_id', 'city', 'address', 'description', 'opening_hours', 'latitude', 'longitude'])->all() + [
            'phones' => $phones, 'websites' => $websites, 'weekly_hours' => $this->hours->normalize($data['weekly_hours'] ?? null),
            'phone' => $phones[0]['value'] ?? null, 'website' => $websites[0]['url'] ?? null,
            'normalized_name' => BusinessIdentity::normalize($data['name']),
            'normalized_city' => BusinessIdentity::normalize($data['city']),
        ];
    }

    public function revision(Review $review, int $actor): void
    {
        DB::table('review_revisions')->insert(['review_id' => $review->id, 'actor_id' => $actor, 'snapshot' => json_encode($review->getAttributes()), 'created_at' => now()]);
    }
}
