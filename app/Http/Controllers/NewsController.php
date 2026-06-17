<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        $news = News::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (News $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => $item->excerpt,
                'image' => $this->resolveImageUrl($item->image_url ?: $item->image_path),
                'published_at' => $item->published_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('News/Index', [
            'news' => $news,
        ]);
    }

    public function show(string $slug): Response
    {
        $news = News::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $latestNews = News::query()
            ->where('id', '!=', $news->id)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (News $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'image' => $this->resolveImageUrl($item->image_url ?: $item->image_path),
                'published_at' => $item->published_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('News/Show', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'excerpt' => $news->excerpt,
                'content' => $news->content,
                'image' => $this->resolveImageUrl($news->image_url ?: $news->image_path),
                'published_at' => $news->published_at?->toIso8601String(),
            ],
            'latestNews' => $latestNews,
        ]);
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
