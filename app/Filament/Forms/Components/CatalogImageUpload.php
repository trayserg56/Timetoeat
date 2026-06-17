<?php

namespace App\Filament\Forms\Components;

use App\Services\CatalogImageProcessor;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CatalogImageUpload
{
    public static function make(string $name = 'image_path'): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->maxSize((int) config('catalog.images.upload_max_kilobytes', 2048))
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->helperText('JPG, PNG, WEBP или AVIF до 2 МБ. При сохранении файл автоматически сжимается и конвертируется в AVIF или WebP.')
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                return app(CatalogImageProcessor::class)->storeUploadedFile(
                    $file,
                    (string) $component->getDirectory(),
                    $component->getDiskName(),
                );
            });
    }
}
