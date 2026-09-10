<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $slug): View
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $businesses = $category->businesses()->where('status', 'approved')->with(['location', 'featuredPhotos'])
            ->withCount('reviews')->withAvg('reviews', 'rating')->orderByDesc('reviews_count')->paginate(20);

        return view('entities.category', compact('category', 'businesses'));
    }
}
