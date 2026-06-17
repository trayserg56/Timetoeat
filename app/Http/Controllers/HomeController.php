<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\MealSet;
use App\Models\News;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\CatalogImageUrl;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $menuDate = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();
        $siteSettings = SiteSetting::current();

        $mealSets = MealSet::query()
            ->where('is_active', true)
            ->with(['items.product', 'tags'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MealSet $mealSet): array => [
                'id' => $mealSet->id,
                'type' => 'meal_set',
                'name' => $mealSet->name,
                'slug' => $mealSet->slug,
                'description' => $mealSet->description,
                'price' => $mealSet->price,
                'image' => CatalogImageUrl::resolve($mealSet->image_path),
                'is_orderable' => $mealSet->is_available && $this->mealSetIsAvailableOn($mealSet, $menuDate),
                'tags' => $this->serializeTags($mealSet->tags),
                'items' => $mealSet->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product?->id,
                        'name' => $item->product?->name,
                        'category_name' => $item->product?->category?->name,
                        'ingredients' => $item->product?->ingredients,
                    ],
                ])->values(),
            ])
            ->values();

        $banners = Banner::query()
            ->with(['bannerTag', 'mealSet.items.product', 'mealSet.tags'])
            ->where('is_active', true)
            ->availableOn($menuDate)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'tag' => $banner->bannerTag && $banner->bannerTag->is_active ? $banner->bannerTag->name : null,
                'image' => CatalogImageUrl::resolve(
                    $banner->image_url ?: $banner->image_path ?: $banner->mealSet?->image_path,
                    (int) config('catalog.images.hero_width', 1200),
                ),
                'link_url' => $banner->link_url,
                'price' => $banner->mealSet?->price,
                'meal_set' => $banner->mealSet
                    ? [
                        'id' => $banner->mealSet->id,
                        'type' => 'meal_set',
                        'name' => $banner->mealSet->name,
                        'slug' => $banner->mealSet->slug,
                        'description' => $banner->mealSet->description,
                        'price' => $banner->mealSet->price,
                        'image' => CatalogImageUrl::resolve($banner->mealSet->image_path),
                        'is_orderable' => $banner->mealSet->is_active
                            && $banner->mealSet->is_available
                            && $this->mealSetIsAvailableOn($banner->mealSet, $menuDate),
                        'tags' => $this->serializeTags($banner->mealSet->tags),
                        'items' => $banner->mealSet->items->map(fn ($item): array => [
                            'id' => $item->id,
                            'quantity' => $item->quantity,
                            'product' => [
                                'id' => $item->product?->id,
                                'name' => $item->product?->name,
                                'category_name' => $item->product?->category?->name,
                                'ingredients' => $item->product?->ingredients,
                            ],
                        ])->values(),
                    ]
                    : null,
            ])
            ->values();

        $extraProducts = Product::query()
            ->with(['category', 'tags'])
            ->where('is_active', true)
            ->where('is_available', true)
            ->availableOn($menuDate)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'type' => 'product',
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'ingredients' => $product->ingredients,
                'price' => $product->price,
                'weight_grams' => $product->weight_grams,
                'category_name' => $product->category?->name,
                'image' => CatalogImageUrl::resolve($product->image_path),
                'tags' => $this->serializeTags($product->tags),
            ])
            ->values();

        $latestNews = News::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (News $news): array => [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'excerpt' => $news->excerpt,
                'image' => CatalogImageUrl::resolve($news->image_url ?: $news->image_path),
                'published_at' => $news->published_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Home', [
            'banners' => $banners,
            'mealSets' => $mealSets,
            'extraProducts' => $extraProducts,
            'latestNews' => $latestNews,
            'menuDate' => $menuDate,
        ]);
    }

    protected function serializeTags($tags): array
    {
        return $tags
            ->where('is_active', true)
            ->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }

    protected function mealSetIsAvailableOn(MealSet $mealSet, string $date): bool
    {
        return collect($mealSet->menu_dates ?? [])
            ->map(fn ($value) => is_array($value) ? ($value['date'] ?? null) : $value)
            ->filter()
            ->contains($date);
    }
}
