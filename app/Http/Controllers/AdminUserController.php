<?php

namespace App\Http\Controllers;

use App\Enums\PlatformRole;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->platform_role === PlatformRole::Superadmin, 403);
        $request->validate(['query' => ['nullable', 'string', 'max:120'], 'role' => ['nullable', 'in:user,admin,superadmin'], 'status' => ['nullable', 'in:active,suspended']]);

        $users = User::query()
            ->when($request->filled('query'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->string('query').'%')->orWhere('mobile', 'like', '%'.$request->string('query').'%')))
            ->when($request->filled('role'), fn ($query) => $query->where('platform_role', $request->string('role')))
            ->when($request->input('status') === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->when($request->input('status') === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $mobile = IranianMobile::normalize($request->validated('mobile'));
        $user = User::firstOrNew(['mobile' => $mobile]);
        $user->name = $request->validated('name');
        $user->mobile = $mobile;
        $user->platform_role = PlatformRole::from($request->validated('platform_role'));
        $user->save();
        $this->audit($request, $user, $user->wasRecentlyCreated ? 'staff_provisioned' : 'staff_access_updated');

        return redirect()->route('admin.users.index')->with('status', 'دسترسی همکار ذخیره شد. ورود با کد یک‌بارمصرف مدیریت انجام می‌شود.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'امکان تغییر سطح دسترسی یا تعلیق حساب خودتان از این صفحه وجود ندارد.');
        $data = $request->validated();
        $role = PlatformRole::from($data['platform_role']);
        if ($user->platform_role === PlatformRole::Superadmin && $role !== PlatformRole::Superadmin && User::staff()->where('platform_role', PlatformRole::Superadmin)->count() === 1) {
            throw ValidationException::withMessages(['platform_role' => 'آخرین مدیر ارشد را نمی‌توان به نقش دیگری تغییر داد.']);
        }

        DB::transaction(function () use ($request, $user, $role, $data): void {
            $user->platform_role = $role;
            $user->suspended_at = $data['status'] === 'suspended' ? now() : null;
            $user->save();
            $this->audit($request, $user, $data['status'] === 'suspended' ? 'user_suspended' : 'user_updated');
        });

        return back()->with('status', 'وضعیت حساب به‌روزرسانی شد.');
    }

    private function audit(Request $request, User $user, string $action): void
    {
        DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'user', 'content_id' => (string) $user->id, 'action' => $action, 'reason' => 'مدیریت دسترسی و وضعیت حساب', 'snapshot' => json_encode($user->only(['name', 'mobile', 'platform_role', 'suspended_at'])), 'created_at' => now()]);
    }
}
