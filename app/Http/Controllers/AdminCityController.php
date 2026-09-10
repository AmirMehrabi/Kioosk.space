<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\Business;
use App\Models\City;
use App\Support\BusinessIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCityController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.cities.index', ['cities' => City::orderBy('position')->orderBy('name')->paginate(30)]);
    }

    public function create(Request $request): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.cities.form', ['city' => new City, 'nextPosition' => ((int) City::max('position')) + 10]);
    }

    public function store(StoreCityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $normalizedName = BusinessIdentity::normalize($data['name']);
        if (City::where('normalized_name', $normalizedName)->exists()) {
            throw ValidationException::withMessages(['name' => 'این شهر از قبل در فهرست وجود دارد.']);
        }
        $city = City::create($data + ['normalized_name' => $normalizedName, 'is_active' => true, 'position' => $data['position'] ?? ((int) City::max('position')) + 10]);
        $this->audit($request, $city, 'created');

        return redirect()->route('admin.cities.edit', $city)->with('status', 'شهر جدید اضافه شد.');
    }

    public function edit(Request $request, City $city): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.cities.form', ['city' => $city, 'nextPosition' => null]);
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $data = $request->validated();
        $normalizedName = BusinessIdentity::normalize($data['name']);
        if (City::where('normalized_name', $normalizedName)->whereKeyNot($city->id)->exists()) {
            throw ValidationException::withMessages(['name' => 'این شهر از قبل در فهرست وجود دارد.']);
        }
        $city->update($data + ['normalized_name' => $normalizedName]);
        $this->audit($request, $city, 'updated');

        return back()->with('status', 'شهر به‌روزرسانی شد.');
    }

    public function destroy(Request $request, City $city): RedirectResponse
    {
        $this->ensureSuperadmin($request);
        abort_if(Business::where('city', $city->name)->exists(), 422, 'این شهر در کسب‌وکارها استفاده شده است؛ ابتدا آن را غیرفعال کنید.');
        $snapshot = $city->getAttributes();
        $city->delete();
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'city', 'content_id' => (string) $city->id, 'action' => 'deleted', 'reason' => 'حذف شهر بدون استفاده', 'snapshot' => json_encode($snapshot), 'created_at' => now()]);

        return redirect()->route('admin.cities.index')->with('status', 'شهر حذف شد.');
    }

    private function ensureSuperadmin(Request $request): void
    {
        abort_unless($request->user()->platform_role === PlatformRole::Superadmin, 403);
    }

    private function audit(Request $request, City $city, string $action): void
    {
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'city', 'content_id' => (string) $city->id, 'action' => $action, 'reason' => 'مدیریت شهرها', 'snapshot' => json_encode($city->getAttributes()), 'created_at' => now()]);
    }
}
