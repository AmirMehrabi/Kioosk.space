<?php

namespace App\Http\Controllers;

use App\Models\ContributionDraft;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function store(Request $request, ContributionDraft $draft): JsonResponse
    {
        abort_unless($draft->user_id === $request->user()->id, 404);
        $request->validate(['client_id' => ['required', 'uuid'], 'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000']]);

        return Cache::lock('contributions:write', 60)->block(10, function () use ($request, $draft) {
            $draft->refresh();
            abort_unless($draft->status === 'draft' && $draft->expires_at->isFuture(), 409);
            $existing = $draft->photos()->where('client_id', $request->input('client_id'))->first();
            if ($existing) {
                return response()->json($existing);
            }
            if ($draft->photos()->count() >= 6) {
                throw ValidationException::withMessages(['photo' => 'حداکثر شش عکس مجاز است.']);
            }
            $file = $request->file('photo');
            [$width, $height] = getimagesize($file->getRealPath());
            if ($width * $height > 20000000) {
                throw ValidationException::withMessages(['photo' => 'ابعاد عکس بیش از حد بزرگ است؛ نسخه کوچک‌تری انتخاب کنید.']);
            }
            $decoder = match ($file->getMimeType()) {
                'image/jpeg' => 'imagecreatefromjpeg',
                'image/png' => 'imagecreatefrompng',
                'image/webp' => 'imagecreatefromwebp',
                default => null,
            };
            if (! $decoder || ! function_exists($decoder)) {
                throw ValidationException::withMessages(['photo' => 'پردازش این نوع عکس روی سرور فعال نیست.']);
            }
            $source = @$decoder($file->getRealPath());
            if (! $source) {
                throw ValidationException::withMessages(['photo' => 'محتوای عکس معتبر نیست. عکس JPEG، PNG یا WebP انتخاب کنید.']);
            }
            if ($file->getMimeType() === 'image/jpeg') {
                $orientation = (@exif_read_data($file->getRealPath()) ?: [])['Orientation'] ?? 1;
                if (in_array($orientation, [2, 5, 7], true)) {
                    imageflip($source, IMG_FLIP_HORIZONTAL);
                } elseif ($orientation === 4) {
                    imageflip($source, IMG_FLIP_VERTICAL);
                }
                $angle = match ($orientation) {
                    3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0
                };
                if ($angle) {
                    $source = imagerotate($source, $angle, 0);
                }
            }
            $id = (string) Str::uuid();
            $paths = ['path' => 'contributions/'.$id.'.jpg', 'thumbnail_path' => 'contributions/'.$id.'-thumb.jpg'];
            try {
                foreach (['path' => 2000, 'thumbnail_path' => 400] as $key => $size) {
                    $ratio = min(1, $size / max(imagesx($source), imagesy($source)));
                    $image = imagecreatetruecolor(max(1, (int) (imagesx($source) * $ratio)), max(1, (int) (imagesy($source) * $ratio)));
                    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                    imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), imagesx($source), imagesy($source));
                    ob_start();
                    imagejpeg($image, null, 85);
                    $bytes = ob_get_clean();
                    if (! Storage::disk('local')->put($paths[$key], $bytes)) {
                        throw new \RuntimeException('Private image storage failed.');
                    }
                }
                $photo = Media::create($paths + ['id' => $id, 'user_id' => $request->user()->id, 'contribution_draft_id' => $draft->id, 'client_id' => $request->input('client_id')]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete(array_values($paths));
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
        $private = $user && ! $user->suspended_at && ($media->user_id === $user->id || ($user->hasStaffAccess() && $request->session()->get('staff_auth.user_id') === $user->id && $request->session()->get('staff_auth.verified_at', 0) > now()->timestamp - config('otp.staff_session_seconds')));
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
        $media->update(['status' => 'removed']);

        return back()->with('status', 'عکس حذف شد.');
    }
}
