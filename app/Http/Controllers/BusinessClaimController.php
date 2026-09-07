<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessClaimRequest;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\BusinessClaimProof;
use App\Services\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessClaimController extends Controller
{
    public function index(Request $request): View
    {
        return view('business-claims.index', ['claims' => BusinessClaim::with('business')->where('user_id', $request->user()->id)->latest()->paginate(10)]);
    }

    public function create(Request $request): View
    {
        $business = $request->integer('business') ? Business::where('status', 'approved')->whereNull('merged_into_id')->findOrFail($request->integer('business')) : null;

        return view('business-claims.create', ['business' => $business]);
    }

    public function store(StoreBusinessClaimRequest $request, ImageStorage $images): RedirectResponse
    {
        $business = Business::where('status', 'approved')->whereNull('merged_into_id')->findOrFail($request->integer('business_id'));
        abort_if($business->owners()->whereKey($request->user()->id)->exists(), 422, 'شما هم‌اکنون مالک تأییدشده این کسب‌وکار هستید.');
        $claim = DB::transaction(function () use ($business, $request): BusinessClaim {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $openKey = $business->id.':'.$request->user()->id;
            if (BusinessClaim::where('open_key', $openKey)->exists()) {
                throw ValidationException::withMessages(['business_id' => 'یک درخواست باز برای این کسب‌وکار دارید.']);
            }

            return BusinessClaim::create(['business_id' => $business->id, 'user_id' => $request->user()->id, 'note' => $request->string('note')->toString(), 'status' => 'pending', 'open_key' => $openKey]);
        });
        $storedPaths = [];
        try {
            foreach ($request->file('proofs') as $proof) {
                $stored = $images->store($proof, 'business-claims/'.$claim->id);
                $storedPaths[] = $stored;
                BusinessClaimProof::create($stored + ['business_claim_id' => $claim->id]);
            }
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $stored) {
                Storage::disk('local')->delete([$stored['path'], $stored['thumbnail_path']]);
            }
            $claim->delete();
            throw $exception;
        }

        return redirect()->route('business.claims.index')->with('status', 'درخواست مالکیت برای بررسی مدیریت ثبت شد.');
    }

    public function proof(Request $request, BusinessClaim $claim, BusinessClaimProof $proof): StreamedResponse
    {
        abort_unless($proof->business_claim_id === $claim->id, 404);
        $staff = $request->user()->hasStaffAccess() && $request->session()->get('staff_auth.user_id') === $request->user()->id && $request->session()->get('staff_auth.verified_at', 0) > now()->timestamp - config('otp.staff_session_seconds');
        abort_unless($claim->user_id === $request->user()->id || $staff, 404);

        return Storage::disk('local')->response($request->boolean('thumbnail') ? $proof->thumbnail_path : $proof->path, 'proof.jpg', ['Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
