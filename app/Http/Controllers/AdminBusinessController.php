<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\BusinessSpecification;
use App\Models\City;
use App\Services\UpdateBusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBusinessController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['query' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', Rule::in(['approved', 'pending', 'corrections', 'incomplete', 'rejected', 'merged'])], 'city' => ['nullable', 'string'], 'category' => ['nullable', 'integer']]);
        $businesses = Business::query()->with('featuredPhotos')->when($request->filled('query'), fn ($query) => $query->where('name', 'like', '%'.$request->string('query').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')))
            ->when($request->integer('category'), fn ($query) => $query->where('category_id', $request->integer('category')))->latest('id')->paginate(20)->withQueryString();

        return view('business-management.index', ['businesses' => $businesses, 'categories' => DB::table('categories')->get(), 'cities' => City::orderBy('name')->get()]);
    }

    public function edit(Business $business): View
    {
        abort_unless($business->status === 'approved', 409, 'این کسب‌وکار باید از مسیر بررسی مشارکت مدیریت شود.');
        $business->load(['photos' => fn ($query) => $query->where('status', 'published')->latest(), 'featuredPhotos', 'specifications']);

        return view('business-management.edit', ['business' => $business, 'admin' => true, 'categories' => DB::table('categories')->get(), 'cities' => City::orderBy('name')->get(), 'specifications' => BusinessSpecification::where('is_active', true)->orderBy('position')->get()]);
    }

    public function update(UpdateBusinessRequest $request, Business $business, UpdateBusinessProfile $update): RedirectResponse
    {
        abort_unless($business->status === 'approved', 409);
        $update->handle($business, $request->user(), $request->validated());

        return back()->with('status', 'اطلاعات کسب‌وکار ذخیره شد.');
    }
}
