<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageStorage
{
    /** @return array{id:string,path:string,thumbnail_path:string} */
    public function store(UploadedFile $file, string $directory): array
    {
        [$width, $height] = getimagesize($file->getRealPath());
        if ($width * $height > 20000000) {
            throw ValidationException::withMessages(['photo' => 'ابعاد عکس بیش از حد بزرگ است؛ نسخه کوچک‌تری انتخاب کنید.']);
        }
        $decoder = match ($file->getMimeType()) {
            'image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp', default => null,
        };
        if (! $decoder || ! function_exists($decoder) || ! ($source = @$decoder($file->getRealPath()))) {
            throw ValidationException::withMessages(['photo' => 'محتوای عکس معتبر نیست. عکس JPEG، PNG یا WebP انتخاب کنید.']);
        }
        if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (@exif_read_data($file->getRealPath()) ?: [])['Orientation'] ?? 1;
            if (in_array($orientation, [2, 5, 7], true)) {
                imageflip($source, IMG_FLIP_HORIZONTAL);
            }
            if ($orientation === 4) {
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
        $paths = ['id' => $id, 'path' => "$directory/$id.jpg", 'thumbnail_path' => "$directory/$id-thumb.jpg"];
        try {
            foreach (['path' => 2000, 'thumbnail_path' => 400] as $key => $size) {
                $ratio = min(1, $size / max(imagesx($source), imagesy($source)));
                $image = imagecreatetruecolor(max(1, (int) (imagesx($source) * $ratio)), max(1, (int) (imagesy($source) * $ratio)));
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), imagesx($source), imagesy($source));
                ob_start();
                imagejpeg($image, null, 85);
                $bytes = ob_get_clean();
                imagedestroy($image);
                if (! Storage::disk('local')->put($paths[$key], $bytes)) {
                    throw new \RuntimeException('Private image storage failed.');
                }
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete([$paths['path'], $paths['thumbnail_path']]);
            throw $exception;
        } finally {
            imagedestroy($source);
        }

        return $paths;
    }
}
