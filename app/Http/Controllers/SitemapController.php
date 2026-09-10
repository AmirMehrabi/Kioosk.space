<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        return $this->xml('sitemaps.index', ['sitemaps' => ['places', 'categories', 'cities', 'users']]);
    }

    public function places(): Response
    {
        $items = Business::where('status', 'approved')->whereNotNull('slug')->select(['slug', 'updated_at'])->orderBy('id')->get()
            ->map(fn (Business $business): array => ['url' => route('businesses.show', $business->slug), 'updatedAt' => $business->updated_at]);

        return $this->xml('sitemaps.urls', compact('items'));
    }

    public function categories(): Response
    {
        $items = Category::where('is_active', true)->whereNotNull('slug')->orderBy('id')->get()
            ->map(fn (Category $category): array => ['url' => route('categories.show', $category->slug), 'updatedAt' => null]);

        return $this->xml('sitemaps.urls', compact('items'));
    }

    public function cities(): Response
    {
        $items = City::where('is_active', true)->whereNotNull('slug')->orderBy('id')->get()
            ->map(fn (City $city): array => ['url' => route('cities.show', $city->slug), 'updatedAt' => $city->updated_at]);

        return $this->xml('sitemaps.urls', compact('items'));
    }

    public function users(): Response
    {
        $items = User::whereNotNull('slug')->whereHas('reviews', fn ($query) => $query->published())->select(['slug', 'updated_at'])->orderBy('id')->get()
            ->map(fn (User $user): array => ['url' => route('users.show', $user->slug), 'updatedAt' => $user->updated_at]);

        return $this->xml('sitemaps.urls', compact('items'));
    }

    private function xml(string $view, array $data): Response
    {
        return response(view($view, $data)->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
