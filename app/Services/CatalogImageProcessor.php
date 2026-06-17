<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogImageProcessor
{
    public function storeUploadedFile(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
    ): ?string {
        if (! $file->isValid()) {
            return null;
        }

        $sourcePath = $file->getRealPath();

        if (! is_string($sourcePath) || $sourcePath === '') {
            return $file->storeAs($directory, $this->fallbackFilename($file), $disk);
        }

        $image = $this->loadImage($sourcePath, $file->getMimeType());

        if (! $image instanceof GdImage) {
            return $file->storeAs($directory, $this->fallbackFilename($file), $disk);
        }

        $image = $this->resizeIfNeeded($image, (int) config('catalog.images.max_width', 1600));

        [$extension, $writeCallback] = $this->resolveWriter();

        $tempPath = tempnam(sys_get_temp_dir(), 'catalog-image-');

        if ($tempPath === false) {
            imagedestroy($image);

            return $file->storeAs($directory, $this->fallbackFilename($file), $disk);
        }

        try {
            $writeCallback($image, $tempPath);
            imagedestroy($image);

            $path = trim($directory.'/'.Str::ulid().'.'.$extension, '/');
            Storage::disk($disk)->put($path, (string) file_get_contents($tempPath), 'public');

            return $path;
        } finally {
            @unlink($tempPath);
        }
    }

    protected function loadImage(string $path, ?string $mimeType): ?GdImage
    {
        $mimeType ??= mime_content_type($path) ?: '';

        return match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => @imagecreatefromjpeg($path) ?: null,
            str_contains($mimeType, 'png') => @imagecreatefrompng($path) ?: null,
            str_contains($mimeType, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path) ?: null,
            str_contains($mimeType, 'avif') && function_exists('imagecreatefromavif') => @imagecreatefromavif($path) ?: null,
            str_contains($mimeType, 'gif') => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    protected function resizeIfNeeded(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $targetWidth = $maxWidth;
        $targetHeight = (int) round($height * ($targetWidth / $width));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($resized === false) {
            return $image;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * @return array{0: string, 1: callable(GdImage, string): bool}
     */
    protected function resolveWriter(): array
    {
        if (function_exists('imageavif') && defined('IMG_AVIF') && (imagetypes() & IMG_AVIF)) {
            $quality = (int) config('catalog.images.avif_quality', 65);

            return ['avif', fn (GdImage $image, string $path): bool => imageavif($image, $path, $quality)];
        }

        if (function_exists('imagewebp') && (imagetypes() & IMG_WEBP)) {
            $quality = (int) config('catalog.images.webp_quality', 82);

            return ['webp', fn (GdImage $image, string $path): bool => imagewebp($image, $path, $quality)];
        }

        $quality = (int) config('catalog.images.jpeg_quality', 85);

        return ['jpg', fn (GdImage $image, string $path): bool => imagejpeg($image, $path, $quality)];
    }

    protected function fallbackFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::ulid().($extension !== '' ? '.'.$extension : '');
    }
}
