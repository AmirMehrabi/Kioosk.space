<?php

namespace App\Http\Controllers;

use App\Models\ContributionDraft;
use App\Models\Media;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function store(Request $request, ContributionDraft $draft, ImageStorage $images): JsonResponse
    {
        abort_unless($draft->user_id === $request->user()->id, 404);
        $request->validate(['client_id' => ['required', 'uuid'], 'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000']]);

        return Cache::lock('contributions:write', 60)->block(10, function () use ($request, $draft, $images) {
            $draft->refresh();
            abort_unless($draft->status === 'draft' && $draft->expires_at->isFuture(), 409);
            $existing = $draft->photos()->where('client_id', $request->input('client_id'))->first();
            if ($existing) {
                return response()->json($existing);
            }
            if ($draft->photos()->count() >= 6) {
                throw ValidationException::withMessages(['photo' => 'حداکثر شش عکس مجاز است.']);
            }
            $stored = $images->store($request->file('photo'), 'contributions');
            try {
                $photo = Media::create($stored + ['user_id' => $request->user()->id, 'contribution_draft_id' => $draft->id, 'client_id' => $request->input('client_id')]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete([$stored['path'], $stored['thumbnail_path']]);
                logger()->error('contribution.upload_failed', ['draft_id' => $draft->id, 'exception' => $exception::class]);
                throw $exception;
            }

            return response()->json($photo, 201);
        });
    }

    public function show(Request $request, Media $media): StreamedResponse
    {
        $public = Media::published()->whereKey($media->id)->exists();
        $user = $request->user();
        $private = $user && ! $user->suspended_at && ($media->user_id === $user->id || $user->hasStaffAccess());
        abort_unless($public || $private, 404);

        return Storage::disk('local')->response($request->boolean('thumbnail') ? $media->thumbnail_path : $media->path, 'photo.jpg', ['Content-Type' => 'image/jpeg', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function destroy(Request $request, ContributionDraft $draft, Media $media): JsonResponse
    {
        abort_unless($draft->user_id === $request->user()->id && $media->contribution_draft_id === $draft->id, 404);

        return Cache::lock('contributions:write', 60)->block(10, function () use ($draft, $media) {
            abort_unless($draft->fresh()->status === 'draft', 409);
            $media->delete();
            Storage::disk('local')->delete([$media->path, $media->thumbnail_path]);

            return response()->json(['removed' => true]);
        });
    }

    public function removePublished(Request $request, Media $media): RedirectResponse
    {
        abort_unless($media->user_id === $request->user()->id, 404);
        $media->featuredByBusinesses()->detach();
        $media->update(['status' => 'removed']);

        return back()->with('status', 'عکس حذف شد.');
    }
}
