<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContributionRequest;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\ContributionDraft;
use App\Models\Review;
use App\Services\SubmitContribution;
use App\Support\PersianDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContributionController extends Controller
{
    public function create(Request $request): View
    {
        $initial = ['with_review' => true, 'photo_ids' => [], 'visit_date' => PersianDate::format(PersianDate::today())];
        if ($request->integer('review')) {
            $review = Review::withTrashed()->with('business')->findOrFail($request->integer('review'));
            Gate::authorize('update', $review);
            abort_if($review->business->merged_into_id, 409, 'برای تجربه ادغام‌شده از پیش‌نویس اصلاحی استفاده کنید.');
            $initial = array_merge($initial, ['business_id' => $review->business_id, 'name' => $review->business->name, 'city' => $review->business->city, 'edit_review_id' => $review->id, 'review_version' => $review->version, 'body' => $review->body, 'rating' => $review->rating, 'visit_date' => PersianDate::format($review->visit_date)]);
        } elseif ($request->integer('business')) {
            $business = Business::where('status', 'approved')->findOrFail($request->integer('business'));
            $initial += ['business_id' => $business->id, 'name' => $business->name, 'city' => $business->city];
            $review = $request->user() ? Review::withTrashed()->where('user_id', $request->user()->id)->where('business_id', $business->id)->first() : null;
            if ($review) {
                $initial = array_merge($initial, ['edit_review_id' => $review->id, 'review_version' => $review->version, 'body' => $review->body, 'rating' => $review->rating, 'visit_date' => PersianDate::format($review->visit_date)]);
            }
        }

        return view('contributions.create', ['initial' => $initial, 'categories' => Category::where('is_active', true)->orderBy('position')->orderBy('name')->get(), 'cities' => City::where('is_active', true)->orderBy('position')->orderBy('name')->get()]);
    }

    public function index(Request $request): View
    {
        return view('contributions.index', [
            'drafts' => ContributionDraft::where('user_id', $request->user()->id)->where('status', '!=', 'submitted')->where('expires_at', '>', now())->latest()->paginate(10, ['*'], 'drafts'),
            'businesses' => Business::where('contributor_id', $request->user()->id)->latest()->paginate(10, ['*'], 'businesses'),
            'reviews' => Review::withTrashed()->with(['business', 'photos' => fn ($query) => $query->where('status', '!=', 'removed')])->where('user_id', $request->user()->id)->latest()->paginate(10, ['*'], 'reviews'),
            'saved' => Business::where('status', 'approved')->whereIn('id', DB::table('saved_businesses')->where('user_id', $request->user()->id)->select('business_id'))->latest()->paginate(10, ['*'], 'saved'),
            'notifications' => $request->user()->notifications()->latest()->paginate(10, ['*'], 'notifications'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'uuid']]);

        return Cache::lock('draft:'.$data['id'], 30)->block(5, function () use ($data, $request) {
            $draft = ContributionDraft::find($data['id']);
            if (! $draft) {
                abort_if(ContributionDraft::where('user_id', $request->user()->id)->where('status', 'draft')->count() >= 30, 429);
                $draft = new ContributionDraft;
                $draft->id = $data['id'];
                $draft->fill(['user_id' => $request->user()->id, 'payload' => ['with_review' => true, 'photo_ids' => []], 'expires_at' => now()->addDays(7)])->save();
            }
            $this->authorizeDraft($request, $draft);

            return response()->json($draft->refresh()->load('photos'));
        });
    }

    public function show(Request $request, ContributionDraft $draft): JsonResponse
    {
        $this->authorizeDraft($request, $draft);

        return response()->json($draft->refresh()->load('photos'));
    }

    public function update(ContributionRequest $request, ContributionDraft $draft): JsonResponse
    {
        $this->authorizeDraft($request, $draft);
        $data = $request->validated();
        $version = $data['version'];
        unset($data['version']);

        return Cache::lock('contributions:write', 60)->block(10, function () use ($draft, $data, $version) {
            $updated = ContributionDraft::whereKey($draft->id)->where('status', 'draft')->where('version', $version)
                ->where('expires_at', '>', now())
                ->update(['payload' => json_encode($data), 'version' => $version + 1, 'expires_at' => now()->addDays(7), 'updated_at' => now()]);
            abort_unless($updated, 409, 'پیش‌نویس تغییر کرده است؛ نسخه ذخیره‌شده را دوباره باز کنید.');

            return response()->json($draft->fresh()->load('photos'));
        });
    }

    public function destroy(Request $request, ContributionDraft $draft): JsonResponse
    {
        $this->authorizeDraft($request, $draft);

        return Cache::lock('contributions:write', 60)->block(10, function () use ($draft) {
            abort_if($draft->fresh()->status === 'submitted', 409);
            $draft->update(['expires_at' => now(), 'status' => 'discarded']);

            return response()->json(['discarded' => true]);
        });
    }

    public function submit(Request $request, ContributionDraft $draft, SubmitContribution $submit): JsonResponse
    {
        $this->authorizeDraft($request, $draft);
        $staff = $request->user()->hasStaffAccess() && $request->session()->get('staff_auth.user_id') === $request->user()->id && $request->session()->get('staff_auth.verified_at', 0) > now()->timestamp - config('otp.staff_session_seconds');

        return response()->json($submit->handle($draft, $request->user(), $staff));
    }

    private function authorizeDraft(Request $request, ContributionDraft $draft): void
    {
        Gate::authorize('update', $draft);
        abort_if($draft->expires_at->isPast() && $draft->status !== 'submitted', 410, 'مهلت نگهداری پیش‌نویس تمام شده است.');
    }
}
