<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\StoreOrderPresetRequest;
use App\Http\Requests\Profile\UpdateOrderPreferencesRequest;
use App\Models\MealSet;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function overview(Request $request): Response
    {
        $user = $request->user()->loadCount('orders');

        $latestOrders = $this->serializeOrders(
            $user->orders()->with('items.components', 'deliveryGroups.items.components')->latest()->limit(3)->get(),
        );

        return Inertia::render('Profile/Overview', [
            'navigation' => $this->navigation('overview'),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'telegram_username' => $user->telegram_username,
            ],
            'stats' => [
                'orders_count' => $user->orders_count,
                'latest_order_total' => $latestOrders[0]['total'] ?? null,
            ],
            'latestOrders' => $latestOrders,
        ]);
    }

    public function settings(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Settings', [
            'navigation' => $this->navigation('settings'),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'telegram_username' => $user->telegram_username,
                'saved_delivery_addresses' => $user->saved_delivery_addresses ?? [],
                'saved_delivery_comments' => $user->saved_delivery_comments ?? [],
            ],
        ]);
    }

    public function orders(Request $request): Response
    {
        $user = $request->user();
        $status = $request->string('status')->toString();

        $query = $user->orders()->with('items.components', 'deliveryGroups.items.components')->latest();

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        return Inertia::render('Profile/Orders', [
            'navigation' => $this->navigation('orders'),
            'filters' => [
                'status' => $status !== '' ? $status : 'all',
                'options' => [
                    ['value' => 'all', 'label' => 'Все'],
                    ['value' => 'new', 'label' => 'Новые'],
                    ['value' => 'completed', 'label' => 'Завершённые'],
                    ['value' => 'cancelled', 'label' => 'Отменённые'],
                ],
            ],
            'orders' => $this->serializeOrders($query->limit(20)->get()),
        ]);
    }

    public function showOrder(Request $request, Order $order): Response
    {
        abort_unless($request->user()?->can('view', $order), 404);

        $order->load('items.components', 'deliveryGroups.items.components');

        return Inertia::render('Profile/OrderShow', [
            'navigation' => $this->navigation('orders'),
            'order' => $this->serializeOrder($order),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('profile.settings')->with('success', 'Профиль обновлён.');
    }

    public function updateOrderPreferences(UpdateOrderPreferencesRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('profile.settings')->with('success', 'Сохранённые адреса и комментарии обновлены.');
    }

    public function storeOrderPreset(StoreOrderPresetRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $attribute = $validated['kind'] === 'delivery_address'
            ? 'saved_delivery_addresses'
            : 'saved_delivery_comments';

        $existingPresets = collect($user->{$attribute} ?? []);
        $normalizedValue = $this->normalizePresetValue($validated['value']);

        $alreadyExists = $existingPresets->contains(
            fn (array $preset): bool => $this->normalizePresetValue($preset['value'] ?? '') === $normalizedValue,
        );

        if ($alreadyExists) {
            return back()->with('success', 'Такой шаблон уже сохранён.');
        }

        $existingPresets->push([
            'id' => (string) Str::uuid(),
            'label' => filled($validated['label'] ?? null) ? $validated['label'] : null,
            'value' => $validated['value'],
        ]);

        $user->update([
            $attribute => $existingPresets->values()->all(),
        ]);

        return back()->with('success', $validated['kind'] === 'delivery_address'
            ? 'Адрес сохранён для будущих заказов.'
            : 'Комментарий сохранён для будущих заказов.');
    }

    public function repeatOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()?->can('repeat', $order), 404);

        $menuDate = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();
        $order->load('deliveryGroups.items');

        $repeatGroups = $order->deliveryGroups
            ->map(function ($group): ?array {
                $menuDate = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();

                $items = $group->items
                    ->filter(fn ($item): bool => $this->isRepeatableOrderItem($item, $menuDate))
                    ->map(fn ($item): array => [
                        'type' => $item->type,
                        'id' => $item->purchasable_id,
                        'quantity' => $item->quantity,
                    ])
                    ->values()
                    ->all();

                if ($items === []) {
                    return null;
                }

                return [
                    'delivery_address' => $group->delivery_address,
                    'customer_comment' => $group->customer_comment,
                    'items' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($repeatGroups === []) {
            return back()->withErrors([
                'repeat' => 'В этом заказе нет позиций, доступных для повторного заказа на завтра.',
            ]);
        }

        return back()
            ->with('success', 'Позиции из заказа добавлены в корзину.')
            ->with('repeat_order', [
                'items' => collect($repeatGroups)
                    ->flatMap(fn (array $group): array => $group['items'])
                    ->values()
                    ->all(),
                'groups' => $repeatGroups,
            ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return back()->withErrors([
                'current_password' => 'Текущий пароль указан неверно.',
            ], 'passwordUpdate');
        }

        $user->update([
            'password' => $request->string('password')->toString(),
        ]);

        return to_route('profile.settings')->with('success', 'Пароль обновлён.');
    }

    protected function navigation(string $active): array
    {
        return [
            ['key' => 'overview', 'label' => 'Обзор', 'href' => route('profile.overview'), 'active' => $active === 'overview'],
            ['key' => 'settings', 'label' => 'Настройки профиля', 'href' => route('profile.settings'), 'active' => $active === 'settings'],
            ['key' => 'orders', 'label' => 'Заказы', 'href' => route('profile.orders'), 'active' => $active === 'orders'],
        ];
    }

    protected function serializeOrders($orders): array
    {
        return $orders->map(fn ($order): array => $this->serializeOrder($order))->values()->all();
    }

    protected function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->getLabel(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->getLabel(),
            'subtotal' => $order->subtotal,
            'delivery_price' => $order->delivery_price,
            'total' => $order->total,
            'delivery_date' => $order->delivery_date?->format('Y-m-d'),
            'delivery_interval' => $order->delivery_interval,
            'delivery_address' => $order->delivery_address,
            'delivery_groups_count' => $order->deliveryGroups->count(),
            'customer_comment' => $order->customer_comment,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'type' => $item->type,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'product_ingredients' => $item->product_ingredients,
                'components' => $item->components->map(fn ($component): array => [
                    'id' => $component->id,
                    'name' => $component->name,
                    'quantity' => $component->quantity,
                ])->values(),
            ])->values(),
            'delivery_groups' => $order->deliveryGroups->map(fn ($group): array => [
                'id' => $group->id,
                'delivery_address' => $group->delivery_address,
                'customer_comment' => $group->customer_comment,
                'subtotal' => $group->subtotal,
                'delivery_price' => $group->delivery_price,
                'total' => $group->total,
                'items' => $group->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'product_ingredients' => $item->product_ingredients,
                    'components' => $item->components->map(fn ($component): array => [
                        'id' => $component->id,
                        'name' => $component->name,
                        'quantity' => $component->quantity,
                    ])->values(),
                ])->values(),
            ])->values(),
        ];
    }

    protected function isRepeatableOrderItem($item, string $menuDate): bool
    {
        return match ($item->type) {
            'product' => Product::query()
                ->whereKey($item->purchasable_id)
                ->where('is_active', true)
                ->where('is_available', true)
                ->availableOn($menuDate)
                ->exists(),
            'meal_set' => MealSet::query()
                ->whereKey($item->purchasable_id)
                ->where('is_active', true)
                ->where('is_available', true)
                ->availableOn($menuDate)
                ->exists(),
            default => false,
        };
    }

    protected function normalizePresetValue(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }
}
