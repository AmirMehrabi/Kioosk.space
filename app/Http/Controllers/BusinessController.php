<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Review;
use App\Support\BusinessIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'integer']]);
        $businesses = $this->searchQuery($request)->with(['photos' => fn ($q) => $q->limit(1)])->withCount('reviews')->withAvg('reviews', 'rating')->latest('businesses.id')->paginate(12)->withQueryString();

        return view('welcome', ['businesses' => $businesses, 'categories' => DB::table('categories')->get()]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'max:100'], 'latitude' => ['nullable', 'numeric', 'between:24,41'], 'longitude' => ['nullable', 'numeric', 'between:43,64']]);
        $query = $this->searchQuery($request)->with(['photos' => fn ($q) => $q->limit(1)]);
        if ($request->filled(['latitude', 'longitude'])) {
            $query->orderByRaw('(COALESCE(latitude, 0) - ?) * (COALESCE(latitude, 0) - ?) + (COALESCE(longitude, 0) - ?) * (COALESCE(longitude, 0) - ?)', [$request->input('latitude'), $request->input('latitude'), $request->input('longitude'), $request->input('longitude')]);
        }
        $categories = DB::table('categories')->pluck('name', 'id');

        return response()->json($query->orderBy('businesses.id')->paginate(12)->through(fn ($business) => [
            'id' => $business->id, 'name' => $business->name, 'city' => $business->city, 'address' => $business->address, 'category' => $categories[$business->category_id] ?? '',
            'thumbnail' => $business->photos->first() ? route('media.show', [$business->photos->first(), 'thumbnail' => 1]) : null,
        ]));
    }

    public function show(Request $request, string $slug): View
    {
        $business = Business::where('slug', $slug)->where('status', 'approved')->withCount('reviews')->withAvg('reviews', 'rating')->firstOrFail();
        $request->validate(['rating' => ['nullable', 'integer', 'between:1,5'], 'sort' => ['nullable', 'in:newest,highest,lowest,helpful']]);
        $query = $business->reviews()->with(['author:id,name', 'photos' => fn ($q) => $q->published()])
            ->addSelect(['helpful_count' => DB::table('helpful_votes')->selectRaw('count(*)')->whereColumn('review_id', 'reviews.id')]);
        $query->when($request->integer('rating'), fn ($q, $rating) => $q->where('rating', $rating));
        match ($request->input('sort')) {
            'highest' => $query->orderByDesc('rating'), 'lowest' => $query->orderBy('rating'), 'helpful' => $query->orderByDesc('helpful_count'), default => null,
        };
        $reviews = $query->latest('reviews.id')->paginate(10)->withQueryString();

        return view('businesses.show', [
            'business' => $business, 'reviews' => $reviews,
            'photos' => $business->photos()->latest()->paginate(12, ['*'], 'photos'),
            'category' => DB::table('categories')->where('id', $business->category_id)->value('name'),
            'myReview' => $request->user() ? Review::withTrashed()->where('business_id', $business->id)->where('user_id', $request->user()->id)->first() : null,
            'saved' => DB::table('saved_businesses')->where('user_id', $request->user()?->id)->where('business_id', $business->id)->exists(),
            'votes' => DB::table('helpful_votes')->where('user_id', $request->user()?->id)->whereIn('review_id', $reviews->pluck('id'))->pluck('review_id')->all(),
            'ownerReplies' => DB::table('owner_replies')->where('status', 'published')->whereIn('review_id', $reviews->pluck('id'))->get()->keyBy('review_id'),
            'isOwner' => $request->user() && $business->owners()->whereKey($request->user()->id)->exists(),
        ]);
    }

    private function searchQuery(Request $request): Builder
    {
        return Business::where('status', 'approved')
            ->when($request->filled('query'), fn ($q) => $q->where('normalized_name', 'like', '%'.BusinessIdentity::normalize($request->input('query')).'%'))
            ->when($request->filled('city'), fn ($q) => $q->where('normalized_city', 'like', '%'.BusinessIdentity::normalize($request->input('city')).'%'))
            ->when($request->integer('category'), fn ($q, $category) => $q->where('category_id', $category));
    }
}
