<?php

namespace App\Http\Middleware;

use App\Models\MealSet;
use App\Models\Product;
use App\Models\SiteMenuItem;
use App\Models\SiteSetting;
use App\Support\ContactLinks;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $siteSettings = SiteSetting::current();
        $menuDate = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'phone' => $request->user()->phone,
                        'telegram_username' => $request->user()->telegram_username,
                        'saved_delivery_addresses' => $request->user()->saved_delivery_addresses ?? [],
                        'saved_delivery_comments' => $request->user()->saved_delivery_comments ?? [],
                    ]
                    : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'order' => $request->session()->get('order'),
                'repeat_order' => $request->session()->get('repeat_order'),
            ],
            'siteContacts' => ContactLinks::fromSiteSetting($siteSettings),
            'siteNavigation' => SiteMenuItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (SiteMenuItem $item): array => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'href' => $item->href,
                ])
                ->values()
                ->all(),
            'checkoutSettings' => [
                'delivery_price' => $siteSettings->delivery_price,
                'free_delivery_meal_set_quantity' => $siteSettings->free_delivery_meal_set_quantity,
                'delivery_interval' => $siteSettings->delivery_interval,
                'order_cutoff_time' => $siteSettings->order_cutoff_time,
                'payment_phone' => $siteSettings->payment_phone,
                'payment_recipient' => $siteSettings->payment_recipient,
                'payment_banks' => $siteSettings->payment_banks,
                'payment_instruction' => $siteSettings->payment_instruction,
                'address_instruction' => $siteSettings->address_instruction,
                'phone_instruction' => $siteSettings->phone_instruction,
            ],
            'cartMenuDate' => $menuDate,
            'cartCatalogItems' => $this->buildCartCatalogItems($menuDate),
            'yandexCaptcha' => [
                'clientKey' => config('services.yandex_captcha.client_key'),
                'enabled' => filled(config('services.yandex_captcha.server_key')),
            ],
        ];
    }

    protected function buildCartCatalogItems(string $menuDate): array
    {
        $mealSets = MealSet::query()
            ->where('is_active', true)
            ->get()
            ->map(fn (MealSet $mealSet): array => [
                'id' => $mealSet->id,
                'type' => 'meal_set',
                'entityType' => 'meal_set',
                'name' => $mealSet->name,
                'price' => $mealSet->price,
                'is_orderable' => $mealSet->is_available && collect($mealSet->menu_dates ?? [])
                    ->map(fn ($v) => is_array($v) ? ($v['date'] ?? null) : $v)
                    ->filter()
                    ->contains($menuDate),
            ])
            ->values()
            ->all();

        $products = Product::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->availableOn($menuDate)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'type' => 'product',
                'entityType' => 'product',
                'name' => $product->name,
                'price' => $product->price,
                'is_orderable' => true,
            ])
            ->values()
            ->all();

        return array_merge($mealSets, $products);
    }
}
