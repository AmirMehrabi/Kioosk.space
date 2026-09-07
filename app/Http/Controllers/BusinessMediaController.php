<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Media;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessMediaController extends Controller
{
    public function store(Request $request, Business $business, ImageStorage $images): JsonResponse
    {
        $this->authorizeBusiness($request, $business);
        $request->validate(['photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000']]);
        abort_if($business->photos()->where('source', 'management')->where('status', 'published')->count() >= 50, 422, 'حداکثر پنجاه تصویر مدیریتی برای هر کسب‌وکار مجاز است.');
        $stored = $images->store($request->file('photo'), 'businesses/'.$business->id);
        $photo = Media::create($stored + ['user_id' => $request->user()->id, 'client_id' => (string) Str::uuid(), 'business_id' => $business->id, 'status' => 'published', 'source' => 'management']);

        return response()->json(['id' => $photo->id, 'thumbnail' => route('media.show', [$photo, 'thumbnail' => 1])], 201);
    }

    public function destroy(Request $request, Business $business, Media $media): JsonResponse
    {
        $this->authorizeBusiness($request, $business);
        abort_unless($media->business_id === $business->id && $media->source === 'management', 404);
        $media->featuredByBusinesses()->detach();
        $media->update(['status' => 'removed']);
        Storage::disk('local')->delete([$media->path, $media->thumbnail_path]);

        return response()->json(['removed' => true]);
    }

    private function authorizeBusiness(Request $request, Business $business): void
    {
        if ($request->routeIs('admin.*')) {
            abort_unless($request->user()->hasStaffAccess(), 403);
        } else {
            abort_unless($business->owners()->whereKey($request->user()->id)->exists(), 404);
        }
    }
}
