<?php

namespace App\Console\Commands;

use App\Models\MealSet;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AssignMenuDatesCommand extends Command
{
    protected $signature = 'menu:assign-dates
                            {--products-days=5 : Сколько дней вперёд назначить всем блюдам, начиная с завтра}
                            {--dry-run : Показать изменения без сохранения}';

    protected $description = 'Назначить даты меню: всем блюдам — несколько дней вперёд, у наборов — смешанная доступность на завтра';

    public function handle(): int
    {
        $timezone = 'Europe/Moscow';
        $tomorrow = CarbonImmutable::now($timezone)->addDay()->toDateString();
        $productDays = max(1, (int) $this->option('products-days'));
        $dryRun = (bool) $this->option('dry-run');

        $productDates = $this->datesForward($productDays, startOffset: 1, timezone: $timezone);

        $mealSetSchedule = [
            'home-lunch' => $this->datesForward(1, 1, $timezone),
            'light-lunch' => $this->datesForward(1, 3, $timezone),
            'hearty-classic' => $this->datesForward(1, 4, $timezone),
        ];

        $this->components->info("Меню на завтра: {$tomorrow}");

        $productCount = 0;

        Product::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->each(function (Product $product) use ($productDates, $dryRun, &$productCount): void {
                if ($dryRun) {
                    $this->line("  [product] {$product->name}: ".$this->formatDates($productDates));

                    return;
                }

                $product->update(['menu_dates' => $productDates]);
                $productCount++;
            });

        $this->components->info("Блюда: {$productCount} поз. → {$productDays} дн. вперёд (включая завтра).");

        $mealSetCount = 0;

        foreach ($mealSetSchedule as $slug => $dates) {
            $mealSet = MealSet::query()->where('slug', $slug)->first();

            if (! $mealSet) {
                $this->components->warn("Набор «{$slug}» не найден, пропуск.");

                continue;
            }

            $availableTomorrow = collect($dates)
                ->pluck('date')
                ->contains($tomorrow);

            $status = $availableTomorrow ? 'доступен завтра' : 'не доступен завтра';

            if ($dryRun) {
                $this->line("  [meal set] {$mealSet->name}: ".$this->formatDates($dates)." ({$status})");

                continue;
            }

            $mealSet->update(['menu_dates' => $dates]);
            $mealSetCount++;
            $this->line("  {$mealSet->name}: {$status}");
        }

        $this->components->info("Наборы: обновлено {$mealSetCount} шт. (1 доступен завтра, 2 — нет).");

        if ($dryRun) {
            $this->components->warn('Dry run: изменения не сохранены.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{date: string}>
     */
    private function datesForward(int $count, int $startOffset, string $timezone): array
    {
        return collect(range($startOffset, $startOffset + $count - 1))
            ->map(fn (int $days): array => [
                'date' => CarbonImmutable::now($timezone)->addDays($days)->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{date: string}>  $dates
     */
    private function formatDates(array $dates): string
    {
        return collect($dates)->pluck('date')->join(', ');
    }
}
