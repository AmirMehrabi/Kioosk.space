<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Support\BusinessIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBusinessProfile
{
    public function __construct(private BusinessHours $hours) {}

    public function handle(Business $business, User $actor, array $data): void
    {
        DB::transaction(function () use ($business, $actor, $data): void {
            $business->refresh();
            $snapshot = $business->getAttributes();
            $fingerprint = BusinessIdentity::fingerprint($data);
            if (Business::where('fingerprint', $fingerprint)->whereKeyNot($business->id)->exists()) {
                throw ValidationException::withMessages(['name' => 'کسب‌وکاری با همین نام و آدرس ثبت شده است.']);
            }
            $phones = array_values($data['phones'] ?? []);
            $websites = array_values($data['websites'] ?? []);
            $hours = $this->hours->normalize($data['weekly_hours'] ?? null);
            $business->update(collect($data)->only(['name', 'category_id', 'city', 'address', 'description', 'latitude', 'longitude'])->all() + [
                'normalized_name' => BusinessIdentity::normalize($data['name']), 'normalized_city' => BusinessIdentity::normalize($data['city']), 'fingerprint' => $fingerprint,
                'phones' => $phones, 'websites' => $websites, 'weekly_hours' => $hours, 'phone' => $phones[0]['value'] ?? null, 'website' => $websites[0]['url'] ?? null,
                'opening_hours' => $hours ? null : $business->opening_hours,
            ]);
            $featured = [];
            foreach ($data['featured_media_ids'] ?? [] as $position => $mediaId) {
                $featured[$mediaId] = ['position' => $position + 1];
            }
            $business->featuredPhotos()->sync($featured);
            DB::table('moderation_history')->insert(['actor_id' => $actor->id, 'content_type' => 'business', 'content_id' => (string) $business->id, 'action' => 'profile_update', 'reason' => 'ویرایش مستقیم نمایه کسب‌وکار', 'snapshot' => json_encode($snapshot), 'created_at' => now()]);
        });
    }
}
