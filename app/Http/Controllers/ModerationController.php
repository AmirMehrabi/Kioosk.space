<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\City;
use App\Models\ContributionDraft;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use App\Notifications\SubmissionUpdated;
use App\Services\SubmitContribution;
use App\Support\BusinessIdentity;
use App\Support\PersianDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(): View
    {
        return view('contributions.moderation', ['businesses' => Business::whereIn('status', ['pending', 'corrections', 'incomplete', 'rejected'])->latest()->paginate(15), 'categories' => DB::table('categories')->get(), 'cities' => City::orderBy('name')->get()]);
    }

    public function show(Business $business): View
    {
        return view('contributions.preview', [
            'business' => $business,
            'reviews' => Review::withTrashed()->where('business_id', $business->id)->with('author:id,name')->latest()->paginate(10),
            'photos' => Media::where('business_id', $business->id)->latest()->paginate(12, ['*'], 'photos'),
        ]);
    }

    public function update(Request $request, Business $business, SubmitContribution $service): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['approve', 'corrections', 'reject', 'edit', 'merge'])], 'reason' => ['required', 'string', 'min:3', 'max:1000'], 'target_id' => ['required_if:action,merge', 'nullable', 'integer']]);
        Cache::lock('contributions:write', 60)->block(10, fn () => DB::transaction(function () use ($request, $business, $data, $service) {
            $business->refresh();
            $snapshot = $business->getAttributes();
            $userIds = Review::withTrashed()->where('business_id', $business->id)->pluck('user_id')->push($business->contributor_id)->filter()->unique();
            abort_if($business->merged_into_id, 409);
            if ($data['action'] === 'edit') {
                $fields = $request->validate(['name' => ['required', 'string', 'max:180'], 'category_id' => ['required', 'exists:categories,id'], 'city' => ['required', 'string', 'exists:cities,name'], 'address' => ['required', 'string', 'max:500'], 'phone' => ['nullable', 'string', 'max:40'], 'website' => ['nullable', 'url:http,https', 'max:500'], 'opening_hours' => ['nullable', 'string', 'max:1000']]);
                $fingerprint = BusinessIdentity::fingerprint($fields);
                abort_if(Business::where('fingerprint', $fingerprint)->whereKeyNot($business->id)->exists(), 409, 'مکان تکراری است؛ از ادغام استفاده کنید.');
                $business->update($service->businessData($fields) + ['fingerprint' => $fingerprint, 'slug' => $business->slug ?? (string) Str::uuid()]);
            } elseif ($data['action'] === 'merge') {
                abort_if($business->status === 'approved', 422);
                $target = Business::where('status', 'approved')->whereKeyNot($business->id)->findOrFail($data['target_id']);
                foreach (Review::withTrashed()->where('business_id', $business->id)->get() as $review) {
                    $conflict = Review::withTrashed()->where('business_id', $target->id)->where('user_id', $review->user_id)->first();
                    $owned = $target->owners()->whereKey($review->user_id)->exists();
                    $service->revision($review, $request->user()->id);
                    if ($conflict || $owned) {
                        $draft = $this->correctionDraft($review->user_id, ['business_id' => $target->id, 'name' => $target->name, 'city' => $target->city, 'with_review' => true, 'body' => $review->body, 'rating' => $review->rating, 'visit_date' => PersianDate::format($review->visit_date), 'photo_ids' => [], 'edit_review_id' => $conflict?->id, 'review_version' => $conflict?->version]);
                        $photos = $review->photos()->get();
                        foreach ($photos as $photo) {
                            $photo->update(['contribution_draft_id' => $draft->id, 'business_id' => null, 'review_id' => null, 'status' => 'pending']);
                        }
                        $draft->update(['payload' => array_merge($draft->payload, ['photo_ids' => $photos->pluck('client_id')->all()])]);
                        $review->update(['status' => 'corrections', 'version' => $review->version + 1]);
                    } else {
                        $review->update(['business_id' => $target->id, 'status' => $review->status === 'pending' ? 'published' : $review->status, 'version' => $review->version + 1]);
                        $review->photos()->update(['business_id' => $target->id]);
                    }
                }
                Media::where('business_id', $business->id)->whereNull('review_id')->update(['business_id' => $target->id]);
                $business->update(['status' => 'merged', 'merged_into_id' => $target->id]);
            } else {
                $status = ['approve' => 'approved', 'corrections' => 'corrections', 'reject' => 'rejected'][$data['action']];
                if ($status === 'approved') {
                    abort_unless($business->category_id && $business->city && $business->address && $business->fingerprint && $business->slug, 422, 'ابتدا اطلاعات ضروری مکان را کامل کنید.');
                    Review::where('business_id', $business->id)->where('status', 'pending')->update(['status' => 'published']);
                } else {
                    if ($business->contributor_id) {
                        $payload = collect($business->getAttributes())->only(['name', 'category_id', 'city', 'address', 'phone', 'website', 'opening_hours', 'latitude', 'longitude'])->all();
                        $this->correctionDraft($business->contributor_id, $payload + ['with_review' => false, 'photo_ids' => [], 'correction_business_id' => $business->id]);
                    }
                }
                $business->update(['status' => $status, 'moderation_reason' => $data['reason']]);
            }
            $this->audit($request, 'business', (string) $business->id, $data['action'], $data['reason'], $snapshot);
            foreach (User::whereIn('id', $userIds)->get() as $user) {
                $user->notify(new SubmissionUpdated($business->id, $business->status, $data['reason']));
            }
        }));

        return back()->with('status', 'تصمیم مدیریت ذخیره شد.');
    }

    public function reports(): View
    {
        $reports = DB::table('reports')->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")->orderByDesc('id')->paginate(20);
        $tables = ['review' => 'reviews', 'comment' => 'comments', 'owner_reply' => 'owner_replies', 'media' => 'media'];
        $contents = [];
        foreach ($tables as $type => $table) {
            $contents[$type] = DB::table($table)->whereIn('id', $reports->where('content_type', $type)->pluck('content_id'))->get()->keyBy('id');
        }

        return view('contributions.reports', compact('reports', 'contents'));
    }

    public function report(Request $request, int $report): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['hide', 'restore', 'dismiss'])], 'reason' => ['required', 'string', 'min:3', 'max:1000']]);
        Cache::lock('contributions:write', 60)->block(10, fn () => DB::transaction(function () use ($request, $report, $data) {
            $report = DB::table('reports')->where('id', $report)->lockForUpdate()->first();
            abort_unless($report, 404);
            $table = ['review' => 'reviews', 'comment' => 'comments', 'owner_reply' => 'owner_replies', 'media' => 'media'][$report->content_type];
            $snapshot = DB::table($table)->find($report->content_id);
            if ($data['action'] !== 'dismiss' && ! ($report->content_type === 'media' && $snapshot?->status === 'removed')) {
                DB::table($table)->where('id', $report->content_id)->update(['status' => $data['action'] === 'hide' ? 'hidden' : 'published', 'updated_at' => now()]);
            }
            DB::table('reports')->where('id', $report->id)->update(['status' => $data['action'], 'open_key' => null, 'updated_at' => now()]);
            $this->audit($request, $report->content_type, $report->content_id, $data['action'], $data['reason'], (array) $snapshot);
        }));

        return back()->with('status', 'گزارش بررسی شد.');
    }

    private function correctionDraft(int $userId, array $payload): ContributionDraft
    {
        $draft = new ContributionDraft;
        $draft->id = (string) Str::uuid();
        $draft->fill(['user_id' => $userId, 'payload' => $payload, 'expires_at' => now()->addDays(7)])->save();

        return $draft;
    }

    private function audit(Request $request, string $type, string $id, string $action, string $reason, array $snapshot): void
    {
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => $type, 'content_id' => $id, 'action' => $action, 'reason' => $reason, 'snapshot' => json_encode($snapshot), 'created_at' => now()]);
    }
}
