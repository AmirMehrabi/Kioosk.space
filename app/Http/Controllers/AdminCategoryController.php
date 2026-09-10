<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Business;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.categories.index', ['categories' => Category::orderBy('position')->orderBy('name')->paginate(30)]);
    }

    public function create(Request $request): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.categories.form', ['category' => new Category, 'nextPosition' => ((int) Category::max('position')) + 10]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = Category::create($data + ['is_active' => true, 'position' => $data['position'] ?? ((int) Category::max('position')) + 10]);
        $this->audit($request, $category, 'created');

        return redirect()->route('admin.categories.edit', $category)->with('status', 'دسته‌بندی جدید اضافه شد.');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->ensureSuperadmin($request);

        return view('admin.categories.form', ['category' => $category, 'nextPosition' => null]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        $this->audit($request, $category, 'updated');

        return back()->with('status', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->ensureSuperadmin($request);
        abort_if(Business::where('category_id', $category->id)->exists(), 422, 'این دسته‌بندی در کسب‌وکارها استفاده شده است؛ ابتدا آن را غیرفعال کنید.');
        $snapshot = $category->getAttributes();
        $category->delete();
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'category', 'content_id' => (string) $category->id, 'action' => 'deleted', 'reason' => 'حذف دسته‌بندی بدون استفاده', 'snapshot' => json_encode($snapshot), 'created_at' => now()]);

        return redirect()->route('admin.categories.index')->with('status', 'دسته‌بندی حذف شد.');
    }

    private function ensureSuperadmin(Request $request): void
    {
        abort_unless($request->user()->platform_role === PlatformRole::Superadmin, 403);
    }

    private function audit(Request $request, Category $category, string $action): void
    {
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'category', 'content_id' => (string) $category->id, 'action' => $action, 'reason' => 'مدیریت دسته‌بندی‌ها', 'snapshot' => json_encode($category->getAttributes()), 'created_at' => now()]);
    }
}
