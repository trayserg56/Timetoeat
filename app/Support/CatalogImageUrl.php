<?php

namespace App\Support;

class CatalogImageUrl
{
    public static function resolve(?string $path, ?int $width = null): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::optimizeExternalUrl($path, $width ?? (int) config('catalog.images.card_width', 800));
        }

        return asset('storage/'.$path);
    }

    protected static function optimizeExternalUrl(string $url, int $width): string
    {
        if (! str_contains($url, 'images.pexels.com')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return $url;
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        $query['auto'] = 'compress';
        $query['cs'] = 'tinysrgb';
        $query['w'] = (string) max(320, min($width, 1600));

        unset($query['dl'], $query['fm']);

        $optimizedQuery = http_build_query($query);

        return $parts['scheme'].'://'.$parts['host'].$parts['path'].($optimizedQuery !== '' ? '?'.$optimizedQuery : '');
    }
}
