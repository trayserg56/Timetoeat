<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

class LcpPreload
{
    public static function share(?string $imageUrl): void
    {
        if (! filled($imageUrl)) {
            return;
        }

        View::share('lcpPreloadImage', $imageUrl);

        $origin = self::origin($imageUrl);

        if ($origin) {
            View::share('lcpPreloadOrigin', $origin);
        }
    }

    public static function origin(?string $imageUrl): ?string
    {
        if (! filled($imageUrl)) {
            return null;
        }

        $host = parse_url($imageUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $scheme = parse_url($imageUrl, PHP_URL_SCHEME);

        return ($scheme ?: 'https').'://'.$host;
    }
}
