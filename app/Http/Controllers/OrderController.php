<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Models\MealSet;
use App\Models\Order;
use App\Models\OrderDeliveryGroup;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MaxOrderNotifier;
use App\Services\TelegramOrderNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, TelegramOrderNotifier $telegramOrderNotifier, MaxOrderNotifier $maxOrderNotifier): RedirectResponse
    {
        $validated = $request->validated();
        $createdOrderNumber = null;
        $createdOrderId = null;
        $deliveryAt = CarbonImmutable::now('Europe/Moscow')->addDay();
        $siteSettings = SiteSetting::current();
        $requestMetadata = $this->captureRequestMetadata($request);

        if ($this->isPastOrderCutoff($siteSettings)) {
            return back()->withErrors([
                'items' => "Заказы на завтра принимаем до {$siteSettings->order_cutoff_time}. Попробуйте оформить заказ в следующий день.",
            ]);
        }

        $normalizedGroups = $this->normalizeOrderGroups($validated);
        $normalizedItems = $normalizedGroups
            ->flatMap(fn (array $group): array => $group['items'])
            ->values();

        $products = Product::query()
            ->whereIn('id', $normalizedItems->where('type', 'product')->pluck('id'))
            ->where('is_active', true)
            ->where('is_available', true)
            ->availableOn($deliveryAt->toDateString())
            ->get()
            ->keyBy('id');

        $mealSets = MealSet::query()
            ->whereIn('id', $normalizedItems->where('type', 'meal_set')->pluck('id'))
            ->where('is_active', true)
            ->where('is_available', true)
            ->availableOn($deliveryAt->toDateString())
            ->with('items.product')
            ->get()
            ->keyBy('id');

        $missingItems = $normalizedItems->contains(function (array $item) use ($products, $mealSets): bool {
            return match ($item['type']) {
                'product' => ! $products->has($item['id']),
                'meal_set' => ! $mealSets->has($item['id']),
                default => true,
            };
        });

        if ($missingItems) {
            return back()->withErrors([
                'items' => 'Часть позиций больше недоступна. Обновите страницу и попробуйте снова.',
            ]);
        }

        $receiptPath = $request->file('receipt')->store('receipts', 'local');

        DB::transaction(function () use ($validated, $normalizedGroups, $products, $mealSets, $receiptPath, $deliveryAt, $siteSettings, $requestMetadata, &$createdOrderNumber, &$createdOrderId): void {
            $preparedGroups = $normalizedGroups->map(function (array $group) use ($products, $mealSets, $siteSettings): array {
                $items = collect($group['items']);
                $subtotal = $this->calculateSubtotal($items, $products, $mealSets);
                $deliveryPrice = $this->calculateDeliveryPrice($items, $siteSettings);

                return [
                    ...$group,
                    'subtotal' => $subtotal,
                    'delivery_price' => $deliveryPrice,
                    'total' => $subtotal + $deliveryPrice,
                ];
            });

            $firstGroup = $preparedGroups->first();
            $groupsCount = $preparedGroups->count();

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'number' => $this->generateOrderNumber(),
                'user_id' => request()->user()?->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_telegram_username' => $validated['customer_telegram_username'],
                'source_ip' => $requestMetadata['source_ip'],
                'source_forwarded_for' => $requestMetadata['source_forwarded_for'],
                'source_user_agent' => $requestMetadata['source_user_agent'],
                'customer_email' => $validated['customer_email'] ?? null,
                'delivery_address' => $groupsCount === 1
                    ? $firstGroup['delivery_address']
                    : "Несколько адресов: {$groupsCount}",
                'delivery_date' => $deliveryAt->toDateString(),
                'delivery_interval' => $siteSettings->delivery_interval,
                'customer_comment' => $groupsCount === 1 ? $firstGroup['customer_comment'] : null,
                'status' => OrderStatus::New,
                'payment_status' => PaymentStatus::ReceiptUploaded,
                'payment_method' => 'bank_transfer',
                'subtotal' => $preparedGroups->sum('subtotal'),
                'delivery_price' => $preparedGroups->sum('delivery_price'),
                'total' => $preparedGroups->sum('total'),
                'receipt_path' => $receiptPath,
                'receipt_uploaded_at' => now(),
            ]);

            $createdOrderNumber = $order->number;
            $createdOrderId = $order->id;

            $preparedGroups->values()->each(function (array $group, int $index) use ($order, $products, $mealSets): void {
                $deliveryGroup = $order->deliveryGroups()->create([
                    'delivery_address' => $group['delivery_address'],
                    'customer_comment' => $group['customer_comment'],
                    'subtotal' => $group['subtotal'],
                    'delivery_price' => $group['delivery_price'],
                    'total' => $group['total'],
                    'sort_order' => $index,
                ]);

                $this->createOrderItems($order, $deliveryGroup, collect($group['items']), $products, $mealSets);
            });

            if (request()->user()) {
                request()->user()->forceFill([
                    'telegram_username' => $validated['customer_telegram_username'],
                ])->save();
            }
        });

        if ($createdOrderId) {
            $order = Order::query()
                ->with('deliveryGroups.items.components')
                ->findOrFail($createdOrderId);

            Log::channel('security')->info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'user_id' => $order->user_id,
                'source_ip' => $order->source_ip,
                'source_forwarded_for' => $order->source_forwarded_for,
                'source_user_agent' => $order->source_user_agent,
            ]);

            $telegramOrderNotifier->sendNewOrder($order);
            $maxOrderNotifier->sendNewOrder($order);
        }

        return to_route('home')
            ->with('success', 'Заказ принят. Мы свяжемся с вами после проверки чека.')
            ->with('order', [
                'number' => $createdOrderNumber,
                'delivery_groups_count' => $normalizedGroups->count(),
            ]);
    }

    protected function normalizeOrderGroups(array $validated): Collection
    {
        if (! empty($validated['order_groups']) && is_array($validated['order_groups'])) {
            return collect($validated['order_groups'])
                ->map(fn (array $group): array => [
                    'delivery_address' => $group['delivery_address'],
                    'customer_comment' => $group['customer_comment'] ?? null,
                    'items' => collect($group['items'])
                        ->map(fn (array $item): array => [
                            'type' => $item['type'],
                            'id' => (int) $item['id'],
                            'quantity' => (int) $item['quantity'],
                        ])
                        ->values()
                        ->all(),
                ])
                ->values();
        }

        return collect([
            [
                'delivery_address' => $validated['delivery_address'],
                'customer_comment' => $validated['customer_comment'] ?? null,
                'items' => collect($validated['items'] ?? [])
                    ->map(fn (array $item): array => [
                        'type' => $item['type'],
                        'id' => (int) $item['id'],
                        'quantity' => (int) $item['quantity'],
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    protected function calculateSubtotal(
        Collection $items,
        Collection $products,
        Collection $mealSets,
    ): int {
        return $items->sum(function (array $item) use ($products, $mealSets): int {
            return match ($item['type']) {
                'product' => $products->get($item['id'])->price * $item['quantity'],
                'meal_set' => $mealSets->get($item['id'])->price * $item['quantity'],
            };
        });
    }

    protected function calculateDeliveryPrice(Collection $items, SiteSetting $siteSettings): int
    {
        $mealSetQuantity = $items
            ->where('type', 'meal_set')
            ->sum('quantity');

        return $mealSetQuantity >= $siteSettings->free_delivery_meal_set_quantity
            ? 0
            : $siteSettings->delivery_price;
    }

    protected function createOrderItems(
        Order $order,
        OrderDeliveryGroup $deliveryGroup,
        Collection $items,
        Collection $products,
        Collection $mealSets,
    ): void {
        $items->each(function (array $item) use ($order, $deliveryGroup, $products, $mealSets): void {
            if ($item['type'] === 'product') {
                $product = $products->get($item['id']);

                $order->items()->create([
                    'order_delivery_group_id' => $deliveryGroup->id,
                    'purchasable_type' => Product::class,
                    'purchasable_id' => $product->id,
                    'type' => 'product',
                    'name' => $product->name,
                    'product_ingredients' => $product->ingredients,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'total_price' => $product->price * $item['quantity'],
                ]);

                return;
            }

            $mealSet = $mealSets->get($item['id']);

            $orderItem = $order->items()->create([
                'order_delivery_group_id' => $deliveryGroup->id,
                'purchasable_type' => MealSet::class,
                'purchasable_id' => $mealSet->id,
                'type' => 'meal_set',
                'name' => $mealSet->name,
                'unit_price' => $mealSet->price,
                'quantity' => $item['quantity'],
                'total_price' => $mealSet->price * $item['quantity'],
            ]);

            $orderItem->components()->createMany(
                $mealSet->items->map(fn ($setItem): array => [
                    'product_id' => $setItem->product_id,
                    'name' => $setItem->product->name,
                    'quantity' => $setItem->quantity * $item['quantity'],
                ])->all(),
            );
        });
    }

    protected function generateOrderNumber(): string
    {
        return 'FD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function isPastOrderCutoff(SiteSetting $siteSettings): bool
    {
        $now = CarbonImmutable::now('Europe/Moscow');
        $time = trim($siteSettings->order_cutoff_time);

        if ($time === '' || $time === '00:00') {
            $cutoff = $now->endOfDay();
        } else {
            $cutoff = CarbonImmutable::createFromFormat('Y-m-d H:i', $now->toDateString().' '.$time, 'Europe/Moscow');
        }

        return $now->greaterThan($cutoff);
    }

    protected function captureRequestMetadata(Request $request): array
    {
        $forwardedFor = $this->normalizeHeaderValue($request->header('CF-Connecting-IP'))
            ?? $this->extractForwardedForFirstIp($request->header('X-Forwarded-For'))
            ?? $request->ip();

        return [
            'source_ip' => $forwardedFor,
            'source_forwarded_for' => $this->normalizeHeaderValue($request->header('X-Forwarded-For')),
            'source_user_agent' => $this->normalizeHeaderValue($request->userAgent()),
        ];
    }

    protected function extractForwardedForFirstIp(mixed $value): ?string
    {
        $normalized = $this->normalizeHeaderValue($value);

        if (! $normalized) {
            return null;
        }

        $firstIp = trim(Str::before($normalized, ','));

        return $firstIp !== '' ? $firstIp : null;
    }

    protected function normalizeHeaderValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value, fn ($item): bool => is_string($item) && trim($item) !== ''));
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
