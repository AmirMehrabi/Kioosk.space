<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Services\BusinessHours;
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
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'exists:cities,name'], 'category' => ['nullable', 'integer']]);
        $businesses = $this->searchQuery($request)->with(['featuredPhotos', 'photos' => fn ($query) => $query->limit(1)])
            ->withCount('reviews')->withAvg('reviews', 'rating')->latest('businesses.id')->paginate(12)->withQueryString();
        $recentReviews = Review::published()->whereIn('business_id', $this->searchQuery($request)->select('businesses.id'))
            ->with(['author:id,name', 'business:id,name,slug,city', 'photos' => fn ($query) => $query->published()->latest()->limit(3)])
            ->latest('created_at')->latest('id')->limit(6)->get();

        return view('welcome', [
            'businesses' => $businesses, 'recentReviews' => $recentReviews,
            'categories' => DB::table('categories')->get(), 'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function discovery(Request $request): View
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'exists:cities,name'], 'category' => ['nullable', 'integer', 'exists:categories,id']]);
        $cities = City::orderBy('name')->get();
        $city = $cities->firstWhere('name', $request->input('city') ?: $request->session()->get('discovery.city'))
            ?? $cities->firstWhere('name', 'تهران') ?? $cities->first();
        $request->session()->put('discovery.city', $city?->name);
        $categories = DB::table('categories')->get();
        $term = BusinessIdentity::normalize($request->input('query') ?? '');
        $matchingCategories = $categories->filter(fn ($category) => $term !== '' && str_contains(BusinessIdentity::normalize($category->name), $term))->pluck('id');
        $businesses = Business::where('status', 'approved')
            ->where('normalized_city', $city?->normalized_name)
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term, $matchingCategories): void {
                $query->where('normalized_name', 'like', '%'.$term.'%')
                    ->orWhere('address', 'like', '%'.$term.'%')
                    ->orWhereIn('category_id', $matchingCategories);
            }))
            ->when($request->integer('category'), fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->with(['featuredPhotos', 'photos' => fn ($query) => $query->limit(1)])
            ->withCount('reviews')->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_count')->orderByDesc('reviews_avg_rating')->orderBy('businesses.id')
            ->paginate(12)->appends([...$request->only('query', 'category'), 'city' => $city?->name]);
        $mapBusinesses = $businesses->map(fn (Business $business) => [
            'id' => $business->id, 'name' => $business->name, 'address' => $business->address,
            'latitude' => $business->latitude, 'longitude' => $business->longitude,
            'url' => route('businesses.show', $business->slug),
            'rating' => $business->reviews_count ? round($business->reviews_avg_rating, 1) : null,
            'reviews' => $business->reviews_count, 'price' => $business->price_range,
        ]);

        return view('discovery', compact('businesses', 'categories', 'cities', 'city', 'mapBusinesses'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'exists:cities,name'], 'latitude' => ['nullable', 'numeric', 'between:24,41'], 'longitude' => ['nullable', 'numeric', 'between:43,64']]);
        $query = $this->searchQuery($request)->with(['featuredPhotos', 'photos' => fn ($q) => $q->limit(1)]);
        if ($request->filled(['latitude', 'longitude'])) {
            $query->orderByRaw('(COALESCE(latitude, 0) - ?) * (COALESCE(latitude, 0) - ?) + (COALESCE(longitude, 0) - ?) * (COALESCE(longitude, 0) - ?)', [$request->input('latitude'), $request->input('latitude'), $request->input('longitude'), $request->input('longitude')]);
        }
        $categories = DB::table('categories')->pluck('name', 'id');

        return response()->json($query->orderBy('businesses.id')->paginate(12)->through(fn ($business) => [
            'id' => $business->id, 'name' => $business->name, 'city' => $business->city, 'address' => $business->address, 'category' => $categories[$business->category_id] ?? '',
            'thumbnail' => ($business->featuredPhotos->first() ?? $business->photos->first()) ? route('media.show', [$business->featuredPhotos->first() ?? $business->photos->first(), 'thumbnail' => 1]) : null,
        ]));
    }

    public function show(Request $request, string $slug, BusinessHours $hours): View
    {
        $business = Business::where('slug', $slug)->where('status', 'approved')->with('featuredPhotos')->withCount('reviews')->withAvg('reviews', 'rating')->firstOrFail();
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
            'featuredPhotos' => $business->featuredPhotos->isNotEmpty() ? $business->featuredPhotos : $business->photos()->latest()->limit(5)->get(),
            'hoursStatus' => $hours->status($business->weekly_hours),
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
