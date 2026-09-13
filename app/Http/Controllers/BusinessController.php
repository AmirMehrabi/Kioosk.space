<?php

namespace App\Http\Controllers;

use App\Enums\MediaCategory;
use App\Models\Business;
use App\Models\BusinessSpecification;
use App\Models\Category;
use App\Models\City;
use App\Models\Review;
use App\Services\BusinessHours;
use App\Support\BusinessIdentity;
use App\Support\SchemaOrg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'exists:cities,name'], 'category' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1']]);
        $recentReviews = Review::published()->whereIn('business_id', $this->searchQuery($request)->select('businesses.id'))
            ->with(['author:id,name', 'business:id,name,slug,city', 'photos' => fn ($query) => $query->published()->latest()->orderBy('id')->limit(4)])
            ->latest('created_at')->latest('id')->simplePaginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('home-review-cards', compact('recentReviews'))->render(),
                'next' => $recentReviews->nextPageUrl(),
            ]);
        }

        return view('welcome', [
            'featuredBusiness' => Business::where('status', 'approved')->where('is_featured', true)
                ->whereHas('heroPhoto', fn (Builder $query) => $query->whereColumn('media.business_id', 'businesses.id'))
                ->with('heroPhoto')->inRandomOrder()->first(),
            'recentReviews' => $recentReviews,
            'categories' => Category::where('is_active', true)->orderBy('position')->orderBy('name')->get(), 'cities' => City::where('is_active', true)->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function discovery(Request $request, BusinessHours $hours): View
    {
        $request->validate([
            'query' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'exists:cities,name'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'sort' => ['nullable', 'string', Rule::in(['recommended', 'rating', 'reviews', 'newest'])],
            'price' => ['nullable', 'array'],
            'price.*' => ['integer', Rule::in([1, 2, 3, 4])],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'open_now' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['integer', Rule::exists('business_specifications', 'id')->where('is_active', true)],
        ]);
        $cities = City::where('is_active', true)->orderBy('position')->orderBy('name')->get();
        $city = $cities->firstWhere('name', $request->input('city') ?: $request->session()->get('discovery.city'))
            ?? $cities->firstWhere('name', 'تهران') ?? $cities->first();
        $request->session()->put('discovery.city', $city?->name);
        $categories = Category::where('is_active', true)->orderBy('position')->orderBy('name')->get();
        $specifications = BusinessSpecification::where('is_active', true)->orderBy('position')->get();
        $term = BusinessIdentity::normalize($request->input('query') ?? '');
        $matchingCategories = $categories->filter(fn ($category) => $term !== '' && str_contains(BusinessIdentity::normalize($category->name), $term))->pluck('id');
        $sort = $request->input('sort', 'recommended');
        $priceFilter = collect($request->input('price', []))->filter(fn ($v) => in_array((int) $v, [1, 2, 3, 4], true))->map(fn ($v) => (int) $v)->values()->all();
        $ratingFilter = $request->integer('rating') ?: null;
        $openNow = $request->boolean('open_now');
        $featureIds = collect($request->input('features', []))->filter()->map(fn ($v) => (int) $v)->values()->all();

        $baseQuery = Business::where('status', 'approved')
            ->where('normalized_city', $city?->normalized_name)
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term, $matchingCategories): void {
                $query->where('normalized_name', 'like', '%'.$term.'%')
                    ->orWhere('address', 'like', '%'.$term.'%')
                    ->orWhereIn('category_id', $matchingCategories);
            }))
            ->when($request->integer('category'), fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->when($priceFilter !== [], fn (Builder $query) => $query->whereIn('price_range', $priceFilter))
            ->when($featureIds !== [], function (Builder $query) use ($featureIds): void {
                foreach ($featureIds as $fid) {
                    $query->whereHas('specifications', fn (Builder $q) => $q->where('business_specifications.id', $fid));
                }
            })
            ->with(['featuredPhotos', 'photos' => fn ($query) => $query->limit(1), 'specifications' => fn ($q) => $q->where('is_active', true)->orderBy('position'), 'category', 'reviews' => fn ($q) => $q->published()->with('author:id,name')->latest()->limit(1)])
            ->withCount('reviews')->withAvg('reviews', 'rating');

        // Sorting: Yelp-inspired "Recommended" = reviews_count + rating + featured boost
        $baseQuery->when($sort === 'rating', fn (Builder $q) => $q->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'))
            ->when($sort === 'reviews', fn (Builder $q) => $q->orderByDesc('reviews_count')->orderByDesc('reviews_avg_rating'))
            ->when($sort === 'newest', fn (Builder $q) => $q->latest('businesses.id'))
            ->when($sort === 'recommended' || $sort === null, fn (Builder $q) => $q->orderByDesc('reviews_count')->orderByDesc('reviews_avg_rating')->orderBy('businesses.id'));

        if ($ratingFilter) {
            $baseQuery->whereRaw('(SELECT AVG(rating) FROM reviews WHERE reviews.business_id = businesses.id AND reviews.deleted_at IS NULL AND reviews.status = ? ) >= ?', ['published', $ratingFilter]);
        }

        // Open-now filtering: JSON weekly_hours not easily queryable; filter in-memory to keep Yelp-like "Open Now"
        if ($openNow) {
            // Fetch a larger window then filter in PHP to keep pagination accurate
            $all = $baseQuery->get();
            $filtered = $all->filter(fn (Business $b) => ($status = $hours->status($b->weekly_hours)) && $status['is_open']);
            $page = max(1, $request->integer('page', 1));
            $perPage = 12;
            $total = $filtered->count();
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
            $businesses = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
            $businesses->appends([...$request->only('query', 'category', 'sort', 'rating', 'open_now'), 'city' => $city?->name, 'price' => $priceFilter, 'features' => $featureIds]);
        } else {
            $businesses = $baseQuery->paginate(12)->appends([...$request->only('query', 'category', 'sort', 'rating', 'open_now'), 'city' => $city?->name, 'price' => $priceFilter, 'features' => $featureIds]);
        }
        $mapBusinesses = $businesses->getCollection()->map(fn (Business $business) => [
            'id' => $business->id, 'name' => $business->name, 'address' => $business->address,
            'latitude' => $business->latitude, 'longitude' => $business->longitude,
            'url' => route('businesses.show', $business->slug),
            'rating' => $business->reviews_count ? round($business->reviews_avg_rating, 1) : null,
            'reviews' => $business->reviews_count, 'price' => $business->price_range,
        ])->values();

        // For Yelp-like filter counts & active chips helper
        $activeFilters = [
            'price' => $priceFilter,
            'rating' => $ratingFilter,
            'open_now' => $openNow,
            'features' => $featureIds,
            'sort' => $sort !== 'recommended' ? $sort : null,
        ];

        return view('discovery', compact('businesses', 'categories', 'cities', 'city', 'mapBusinesses', 'specifications', 'activeFilters', 'sort'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'city' => ['nullable', 'string', 'exists:cities,name'], 'latitude' => ['nullable', 'numeric', 'between:24,41'], 'longitude' => ['nullable', 'numeric', 'between:43,64']]);
        $query = $this->searchQuery($request)->with(['featuredPhotos', 'photos' => fn ($q) => $q->limit(1)]);
        if ($request->filled(['latitude', 'longitude'])) {
            $query->orderByRaw('(COALESCE(latitude, 0) - ?) * (COALESCE(latitude, 0) - ?) + (COALESCE(longitude, 0) - ?) * (COALESCE(longitude, 0) - ?)', [$request->input('latitude'), $request->input('latitude'), $request->input('longitude'), $request->input('longitude')]);
        }
        $categories = Category::where('is_active', true)->pluck('name', 'id');

        return response()->json($query->orderBy('businesses.id')->paginate(12)->through(fn ($business) => [
            'id' => $business->id, 'name' => $business->name, 'city' => $business->city, 'address' => $business->address, 'category' => $categories[$business->category_id] ?? '',
            'thumbnail' => ($business->featuredPhotos->first() ?? $business->photos->first()) ? route('media.show', [$business->featuredPhotos->first() ?? $business->photos->first(), 'thumbnail' => 1]) : null,
        ]));
    }

    public function show(Request $request, string $slug, BusinessHours $hours): View|JsonResponse
    {
        $business = Business::where('slug', $slug)->where('status', 'approved')
            ->with(['category', 'location', 'featuredPhotos', 'specifications' => fn ($query) => $query->where('is_active', true)->orderBy('position')])
            ->withCount('reviews')->withAvg('reviews', 'rating')->firstOrFail();
        $request->validate(['rating' => ['nullable', 'integer', 'between:1,5'], 'sort' => ['nullable', 'in:newest,highest,lowest,helpful'], 'photos' => ['nullable', 'integer', 'min:1'], 'photo_category' => ['nullable', Rule::enum(MediaCategory::class)]]);
        $selectedPhotoCategory = $request->enum('photo_category', MediaCategory::class);
        $photos = $business->photos()
            ->when($selectedPhotoCategory === MediaCategory::Other, fn ($query) => $query->where(fn ($query) => $query->where('category', MediaCategory::Other->value)->orWhereNull('category')))
            ->when($selectedPhotoCategory && $selectedPhotoCategory !== MediaCategory::Other, fn ($query) => $query->where('category', $selectedPhotoCategory->value))
            ->latest()->orderBy('id')->paginate(12, ['*'], 'photos')->withQueryString();
        $photoCounts = $business->photos()->selectRaw("COALESCE(category, 'other') as photo_category, count(*) as aggregate")->groupBy('photo_category')->pluck('aggregate', 'photo_category');
        if ($request->expectsJson()) {
            return response()->json([
                'photos' => $photos->map(fn ($photo) => [
                    'id' => $photo->id, 'url' => route('media.show', $photo),
                    'thumbnail' => route('media.show', [$photo, 'thumbnail' => 1]), 'alt' => 'عکس '.$business->name,
                ])->values(),
                'next' => $photos->nextPageUrl(), 'total' => $photos->total(),
                'category' => $selectedPhotoCategory?->value,
                'counts' => ['all' => $photoCounts->sum()] + $photoCounts->all(),
            ]);
        }
        $heroPhotos = $business->featuredPhotos->take(4);
        if ($heroPhotos->count() < 4) {
            $heroPhotos = $heroPhotos->concat($business->photos()->whereNotIn('media.id', $heroPhotos->pluck('id'))->latest()->orderBy('id')->limit(4 - $heroPhotos->count())->get());
        }
        $query = $business->reviews()->with(['author:id,name,slug', 'photos' => fn ($q) => $q->published()])
            ->addSelect(['helpful_count' => DB::table('helpful_votes')->selectRaw('count(*)')->whereColumn('review_id', 'reviews.id')]);
        $query->when($request->integer('rating'), fn ($q, $rating) => $q->where('rating', $rating));
        match ($request->input('sort')) {
            'highest' => $query->orderByDesc('rating'), 'lowest' => $query->orderBy('rating'), 'helpful' => $query->orderByDesc('helpful_count'), default => null,
        };
        $reviews = $query->latest('reviews.id')->paginate(10)->withQueryString();

        return view('businesses.show', [
            'business' => $business, 'reviews' => $reviews,
            'photos' => $photos, 'heroPhotos' => $heroPhotos,
            'photoCategories' => MediaCategory::cases(), 'photoCounts' => $photoCounts,
            'selectedPhotoCategory' => $selectedPhotoCategory,
            'category' => $business->category?->name,
            'myReview' => $request->user() ? Review::withTrashed()->where('business_id', $business->id)->where('user_id', $request->user()->id)->first() : null,
            'saved' => DB::table('saved_businesses')->where('user_id', $request->user()?->id)->where('business_id', $business->id)->exists(),
            'votes' => DB::table('helpful_votes')->where('user_id', $request->user()?->id)->whereIn('review_id', $reviews->pluck('id'))->pluck('review_id')->all(),
            'ownerReplies' => DB::table('owner_replies')->where('status', 'published')->whereIn('review_id', $reviews->pluck('id'))->get()->keyBy('review_id'),
            'isOwner' => $request->user() && $business->owners()->whereKey($request->user()->id)->exists(),
            'hoursStatus' => $hours->status($business->weekly_hours),
            'businessSchema' => SchemaOrg::business($business, $reviews->getCollection(), $heroPhotos),
            'breadcrumbSchema' => SchemaOrg::breadcrumbs(array_values(array_filter([
                $business->category ? ['name' => $business->category->name, 'url' => route('categories.show', $business->category->slug)] : null,
                $business->location ? ['name' => $business->location->name, 'url' => route('cities.show', $business->location->slug)] : null,
                ['name' => $business->name, 'url' => route('businesses.show', $business->slug)],
            ]))),
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
