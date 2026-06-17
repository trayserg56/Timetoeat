<?php

namespace Tests\Unit;

use App\Services\CatalogImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImageProcessorTest extends TestCase
{
    public function test_it_converts_uploaded_jpeg_to_modern_format(): void
    {
        if (! function_exists('imagejpeg')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        Storage::fake('public');

        $source = imagecreatetruecolor(2200, 1200);
        $tempPath = tempnam(sys_get_temp_dir(), 'catalog-source-').'.jpg';
        imagejpeg($source, $tempPath, 90);
        imagedestroy($source);

        $uploadedFile = new UploadedFile($tempPath, 'dish.jpg', 'image/jpeg', null, true);

        $storedPath = app(CatalogImageProcessor::class)->storeUploadedFile(
            $uploadedFile,
            'products',
            'public',
        );

        $this->assertNotNull($storedPath);
        $this->assertStringStartsWith('products/', $storedPath);
        $this->assertMatchesRegularExpression('/\.(avif|webp|jpg)$/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertGreaterThan(0, Storage::disk('public')->size($storedPath));

        @unlink($tempPath);
    }
}
