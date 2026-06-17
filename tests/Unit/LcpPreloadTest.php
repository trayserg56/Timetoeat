<?php

namespace Tests\Unit;

use App\Support\LcpPreload;
use Tests\TestCase;

class LcpPreloadTest extends TestCase
{
    public function test_it_extracts_image_origin(): void
    {
        $this->assertSame(
            'https://images.pexels.com',
            LcpPreload::origin('https://images.pexels.com/photos/123/photo.jpeg?w=1200'),
        );
    }

    public function test_it_returns_null_for_invalid_url(): void
    {
        $this->assertNull(LcpPreload::origin('not-a-url'));
    }
}
