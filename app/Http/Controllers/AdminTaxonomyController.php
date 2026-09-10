<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\Category;
use App\Models\City;
use App\Support\BusinessIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTaxonomyController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->platform_role === PlatformRole::Superadmin, 403);

        return view('admin.taxonomy', ['cities' => City::orderBy('position')->orderBy('name')->get(), 'categories' => Category::orderBy('position')->orderBy('name')->get()]);
    }

    public function storeCity(StoreCityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $normalizedName = BusinessIdentity::normalize($data['name']);
        if (City::where('normalized_name', $normalizedName)->exists()) {
            throw ValidationException::withMessages(['name' => 'این شهر از قبل در فهرست وجود دارد.']);
        }
        $city = City::create($data + ['normalized_name' => $normalizedName, 'is_active' => true, 'position' => $data['position'] ?? $this->nextPosition(City::class)]);
        $this->audit($request, 'city', (string) $city->id, 'created', $city->getAttributes());

        return back()->with('status', 'شهر جدید اضافه شد.');
    }

    public function updateCity(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $data = $request->validated();
        $normalizedName = BusinessIdentity::normalize($data['name']);
        if (City::where('normalized_name', $normalizedName)->whereKeyNot($city->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'این شهر از قبل در فهرست وجود دارد.']);
        }
        $city->update($data + ['normalized_name' => $normalizedName]);
        $this->audit($request, 'city', (string) $city->id, 'updated', $city->getAttributes());

        return back()->with('status', 'شهر به‌روزرسانی شد.');
    }

    public function storeCategory(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = Category::create($data + ['is_active' => true, 'position' => $data['position'] ?? $this->nextPosition(Category::class)]);
        $this->audit($request, 'category', (string) $category->id, 'created', $category->getAttributes());

        return back()->with('status', 'دسته‌بندی جدید اضافه شد.');
    }

    public function updateCategory(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        $this->audit($request, 'category', (string) $category->id, 'updated', $category->getAttributes());

        return back()->with('status', 'دسته‌بندی به‌روزرسانی شد.');
    }

    private function nextPosition(string $model): int
    {
        return ((int) $model::max('position')) + 10;
    }

    private function audit(Request $request, string $type, string $id, string $action, array $snapshot): void
    {
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => $type, 'content_id' => $id, 'action' => $action, 'reason' => 'مدیریت فهرست‌های پایه', 'snapshot' => json_encode($snapshot), 'created_at' => now()]);
    }
}
