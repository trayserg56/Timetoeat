<?php

namespace Tests\Unit;

use App\Support\CatalogImageUrl;
use Tests\TestCase;

class CatalogImageUrlTest extends TestCase
{
    public function test_it_optimizes_pexels_urls_to_compressed_width(): void
    {
        $url = 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?cs=srgb&dl=pexels-ella-olsson-572949-1640771.jpg&fm=jpg';

        $optimized = CatalogImageUrl::resolve($url, 800);

        $this->assertStringStartsWith('https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?', $optimized);
        $this->assertStringContainsString('auto=compress', $optimized);
        $this->assertStringContainsString('w=800', $optimized);
        $this->assertStringNotContainsString('dl=', $optimized);
    }

    public function test_it_returns_storage_url_for_local_paths(): void
    {
        $this->assertSame(
            asset('storage/products/example.webp'),
            CatalogImageUrl::resolve('products/example.webp'),
        );
    }

    public function test_it_leaves_non_pexels_external_urls_unchanged(): void
    {
        $url = 'https://cdn.example.com/menu.jpg';

        $this->assertSame($url, CatalogImageUrl::resolve($url));
    }
}
