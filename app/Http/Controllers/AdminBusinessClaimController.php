<?php

namespace App\Http\Controllers;

use App\Models\BusinessClaim;
use App\Notifications\OwnershipClaimUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBusinessClaimController extends Controller
{
    public function index(): View
    {
        return view('business-claims.admin-index', ['claims' => BusinessClaim::with(['business', 'claimant', 'proofs'])->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest('id')->paginate(20)]);
    }

    public function update(Request $request, BusinessClaim $claim): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['required', 'string', 'min:3', 'max:1000']]);
        DB::transaction(function () use ($request, $claim, $data): void {
            $claim = BusinessClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_unless($claim->status === 'pending', 409, 'این درخواست قبلاً بررسی شده است.');
            $business = $claim->business()->lockForUpdate()->firstOrFail();
            abort_unless($business->status === 'approved' && $business->merged_into_id === null, 409, 'این کسب‌وکار دیگر برای واگذاری مالکیت در دسترس نیست.');
            $status = $data['action'] === 'approve' ? 'approved' : 'rejected';
            if ($status === 'approved') {
                $business->owners()->syncWithoutDetaching([$claim->user_id => ['role' => 'owner', 'approved_at' => now()]]);
            }
            $claim->update(['status' => $status, 'open_key' => null, 'decision_reason' => $data['reason'], 'decided_by' => $request->user()->id, 'decided_at' => now()]);
            DB::table('moderation_history')->insert(['actor_id' => $request->user()->id, 'content_type' => 'business_claim', 'content_id' => (string) $claim->id, 'action' => $data['action'], 'reason' => $data['reason'], 'snapshot' => json_encode(['business_id' => $claim->business_id, 'user_id' => $claim->user_id]), 'created_at' => now()]);
            $claim->claimant->notify(new OwnershipClaimUpdated($claim->id, $status, $data['reason']));
        });

        return back()->with('status', 'درخواست مالکیت بررسی شد.');
    }
}
