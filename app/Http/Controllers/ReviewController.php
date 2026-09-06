<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Review;
use App\Services\SubmitContribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function show(Request $request, Review $review): View
    {
        $this->publicReview($review);
        $comments = $review->comments()->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('parent_id')->orWhereIn('parent_id', Comment::where('status', 'published')->select('id')))
            ->with('author:id,name')->orderBy('id')->paginate(20);

        return view('contributions.discussion', ['review' => $review->load('author:id,name', 'business'), 'comments' => $comments]);
    }

    public function edit(Request $request, int $review): RedirectResponse
    {
        $review = Review::withTrashed()->findOrFail($review);
        Gate::authorize('update', $review);

        return redirect()->route('contribute', ['review' => $review->id]);
    }

    public function destroy(Request $request, Review $review, SubmitContribution $service): RedirectResponse
    {
        Gate::authorize('update', $review);
        Cache::lock('contributions:write', 60)->block(10, fn () => DB::transaction(function () use ($review, $service, $request) {
            $review->refresh();
            $service->revision($review, $request->user()->id);
            $review->increment('version');
            $review->delete();
        }));

        return back()->with('status', 'تجربه حذف شد.');
    }

    public function helpful(Request $request, Review $review): RedirectResponse
    {
        $this->publicReview($review);
        abort_if($review->user_id === $request->user()->id, 403);
        $key = ['user_id' => $request->user()->id, 'review_id' => $review->id];
        if ($request->isMethod('delete')) {
            DB::table('helpful_votes')->where($key)->delete();
        } else {
            DB::table('helpful_votes')->insertOrIgnore($key + ['created_at' => now(), 'updated_at' => now()]);
        }

        return back();
    }

    public function save(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->status === 'approved', 404);
        $key = ['user_id' => $request->user()->id, 'business_id' => $business->id];
        if ($request->isMethod('delete')) {
            DB::table('saved_businesses')->where($key)->delete();
        } else {
            DB::table('saved_businesses')->insertOrIgnore($key + ['created_at' => now(), 'updated_at' => now()]);
        }

        return back();
    }

    public function comment(Request $request, Review $review): RedirectResponse
    {
        $this->publicReview($review);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:1000'], 'parent_id' => ['nullable', 'integer']]);
        if (! empty($data['parent_id'])) {
            Comment::where('review_id', $review->id)->whereNull('parent_id')->where('status', 'published')->findOrFail($data['parent_id']);
        }
        Comment::create($data + ['user_id' => $request->user()->id, 'review_id' => $review->id]);

        return back()->with('status', 'دیدگاه ثبت شد.');
    }

    public function updateComment(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 404);
        if ($request->isMethod('delete')) {
            $comment->delete();
        } else {
            $comment->update($request->validate(['body' => ['required', 'string', 'min:2', 'max:1000']]));
        }

        return back();
    }

    public function ownerReply(Request $request, Review $review): RedirectResponse
    {
        $this->publicReview($review);
        abort_unless($review->business->owners()->whereKey($request->user()->id)->exists(), 403);
        if ($request->isMethod('delete')) {
            DB::table('owner_replies')->where('review_id', $review->id)->delete();
        } else {
            $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);
            $existing = DB::table('owner_replies')->where('review_id', $review->id)->first();
            DB::table('owner_replies')->updateOrInsert(['review_id' => $review->id], $data + ['user_id' => $request->user()->id, 'status' => $existing?->status ?? 'published', 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now()]);
        }

        return back()->with('status', 'پاسخ مالک ذخیره شد.');
    }

    public function report(Request $request): RedirectResponse
    {
        $data = $request->validate(['content_type' => ['required', Rule::in(['review', 'comment', 'owner_reply', 'media'])], 'content_id' => ['required', 'string', 'max:40'], 'reason' => ['required', 'string', 'min:5', 'max:1000']]);
        if ($data['content_type'] === 'review') {
            Review::published()->findOrFail($data['content_id']);
        } elseif ($data['content_type'] === 'media') {
            Media::published()->findOrFail($data['content_id']);
        } elseif ($data['content_type'] === 'comment') {
            $comment = Comment::where('status', 'published')->whereHas('review', fn ($q) => $q->published())->findOrFail($data['content_id']);
            abort_if($comment->parent_id && ! Comment::whereKey($comment->parent_id)->where('status', 'published')->exists(), 404);
        } else {
            $reply = DB::table('owner_replies')->where('status', 'published')->find($data['content_id']);
            abort_unless($reply && Review::published()->whereKey($reply->review_id)->exists(), 404);
        }
        DB::table('reports')->insertOrIgnore($data + ['user_id' => $request->user()->id, 'open_key' => $request->user()->id.':'.$data['content_type'].':'.$data['content_id'], 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'گزارش برای بررسی ثبت شد.');
    }

    private function publicReview(Review $review): void
    {
        abort_unless(Review::published()->whereKey($review->id)->exists(), 404);
    }
}
