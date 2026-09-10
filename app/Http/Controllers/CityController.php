<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\View\View;

class CityController extends Controller
{
    public function show(string $slug): View
    {
        $city = City::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $businesses = $city->businesses()->where('status', 'approved')->with(['category', 'featuredPhotos'])
            ->withCount('reviews')->withAvg('reviews', 'rating')->orderByDesc('reviews_count')->paginate(20);

        return view('entities.city', compact('city', 'businesses'));
    }
}
