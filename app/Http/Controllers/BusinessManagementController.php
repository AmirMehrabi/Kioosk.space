<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\BusinessSpecification;
use App\Models\City;
use App\Services\UpdateBusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessManagementController extends Controller
{
    public function edit(Business $business): View
    {
        abort_unless($business->owners()->whereKey(auth()->id())->exists(), 404);
        abort_unless($business->status === 'approved', 409);
        $business->load(['photos' => fn ($query) => $query->where('status', 'published')->latest(), 'featuredPhotos', 'specifications']);

        return view('business-management.edit', ['business' => $business, 'admin' => false, 'categories' => DB::table('categories')->get(), 'cities' => City::orderBy('name')->get(), 'specifications' => BusinessSpecification::where('is_active', true)->orderBy('position')->get()]);
    }

    public function update(UpdateBusinessRequest $request, Business $business, UpdateBusinessProfile $update): RedirectResponse
    {
        abort_unless($business->status === 'approved', 409);
        $update->handle($business, $request->user(), $request->validated());

        return back()->with('status', 'اطلاعات کسب‌وکار ذخیره شد.');
    }
}
