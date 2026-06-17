<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\BannerTag;
use App\Models\CatalogTag;
use App\Models\Category;
use App\Models\MealSet;
use App\Models\News;
use App\Models\Product;
use App\Models\SiteMenuItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tomorrow = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();
        $dayAfterTomorrow = CarbonImmutable::now('Europe/Moscow')->addDays(2)->toDateString();

        $imageLibrary = [
            'chicken_soup' => 'https://images.pexels.com/photos/24334858/pexels-photo-24334858.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'pumpkin_soup' => 'https://images.pexels.com/photos/5662180/pexels-photo-5662180.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'borscht' => 'https://images.pexels.com/photos/24334858/pexels-photo-24334858.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'greek_salad' => 'https://images.pexels.com/photos/15580730/pexels-photo-15580730.jpeg?cs=srgb&dl=pexels-veersajid-15580730.jpg&fm=jpg',
            'caesar_salad' => 'https://images.pexels.com/photos/34886620/pexels-photo-34886620.jpeg?cs=srgb&dl=pexels-simeon-maryska-2003803697-34886620.jpg&fm=jpg',
            'beet_salad' => 'https://images.pexels.com/photos/3774599/pexels-photo-3774599.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'cutlet' => 'https://images.pexels.com/photos/16696798/pexels-photo-16696798.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'stroganoff' => 'https://images.pexels.com/photos/20234576/pexels-photo-20234576.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'teriyaki' => 'https://images.pexels.com/photos/11787138/pexels-photo-11787138.jpeg?cs=srgb&dl=pexels-dyon-siregar-200026696-11787138.jpg&fm=jpg',
            'carbonara' => 'https://images.pexels.com/photos/20234576/pexels-photo-20234576.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'berry_mors' => 'https://images.pexels.com/photos/17585015/pexels-photo-17585015.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'lemonade' => 'https://images.pexels.com/photos/33107433/pexels-photo-33107433.jpeg?cs=srgb&dl=pexels-beyza-sukran-demi-rbas-730252876-33107433.jpg&fm=jpg',
            'home_lunch_set' => 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?cs=srgb&dl=pexels-ella-olsson-572949-1640771.jpg&fm=jpg',
            'light_lunch_set' => 'https://images.pexels.com/photos/19917471/pexels-photo-19917471.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'hearty_set' => 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?cs=srgb&dl=pexels-ella-olsson-572949-1640771.jpg&fm=jpg',
            'comfort_set' => 'https://images.pexels.com/photos/5852323/pexels-photo-5852323.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'family_set' => 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?cs=srgb&dl=pexels-ella-olsson-572949-1640771.jpg&fm=jpg',
        ];

        $bannerTag = BannerTag::query()->updateOrCreate(
            ['slug' => 'menu-na-zavtra'],
            [
                'name' => 'Меню на завтра',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );

        collect([
            ['label' => 'Новости', 'href' => '/news', 'sort_order' => 0, 'is_active' => true],
            ['label' => 'Контакты', 'href' => '/contacts', 'sort_order' => 10, 'is_active' => true],
        ])->each(function (array $item): void {
            SiteMenuItem::query()->updateOrCreate(
                ['href' => $item['href']],
                $item,
            );
        });

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Администратор',
                'phone' => '+79990000001',
                'telegram_username' => '@food_admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        $categories = collect([
            ['name' => 'Супы', 'slug' => 'soups', 'sort_order' => 0],
            ['name' => 'Салаты', 'slug' => 'salads', 'sort_order' => 10],
            ['name' => 'Горячее', 'slug' => 'hot-meals', 'sort_order' => 20],
            ['name' => 'Напитки', 'slug' => 'drinks', 'sort_order' => 30],
        ])->mapWithKeys(
            fn (array $category): array => [
                $category['slug'] => Category::query()->updateOrCreate(
                    ['slug' => $category['slug']],
                    $category,
                ),
            ],
        );

        $products = collect([
            [
                'category' => 'soups',
                'name' => 'Куриный суп',
                'slug' => 'chicken-soup',
                'description' => 'Лёгкий домашний суп с курицей, лапшой и свежей зеленью.',
                'ingredients' => 'Курица, домашняя лапша, картофель, морковь, лук, зелень.',
                'price' => 25000,
                'weight_grams' => 350,
                'image_path' => $imageLibrary['chicken_soup'],
                'sort_order' => 0,
            ],
            [
                'category' => 'soups',
                'name' => 'Крем-суп из тыквы',
                'slug' => 'pumpkin-cream-soup',
                'description' => 'Нежный крем-суп с тыквой, семечками и сливочной текстурой.',
                'ingredients' => 'Тыква, сливки, овощной бульон, тыквенные семечки, специи.',
                'price' => 27000,
                'weight_grams' => 330,
                'image_path' => $imageLibrary['pumpkin_soup'],
                'sort_order' => 10,
            ],
            [
                'category' => 'soups',
                'name' => 'Борщ со сметаной',
                'slug' => 'borscht-with-sour-cream',
                'description' => 'Насыщенный борщ со свёклой, мясом и ложкой сметаны.',
                'ingredients' => 'Говядина, свёкла, капуста, картофель, морковь, томаты, сметана.',
                'price' => 29000,
                'weight_grams' => 380,
                'image_path' => $imageLibrary['borscht'],
                'sort_order' => 20,
            ],
            [
                'category' => 'salads',
                'name' => 'Греческий салат',
                'slug' => 'greek-salad',
                'description' => 'Свежие овощи, фета, оливки и лёгкая заправка.',
                'ingredients' => 'Огурцы, томаты, болгарский перец, фета, оливки, красный лук.',
                'price' => 23000,
                'weight_grams' => 210,
                'image_path' => $imageLibrary['greek_salad'],
                'sort_order' => 0,
            ],
            [
                'category' => 'salads',
                'name' => 'Цезарь с курицей',
                'slug' => 'caesar-with-chicken',
                'description' => 'Хрустящий романо, курица, сухарики и соус цезарь.',
                'ingredients' => 'Куриное филе, салат романо, сухарики, пармезан, соус цезарь.',
                'price' => 26000,
                'weight_grams' => 220,
                'image_path' => $imageLibrary['caesar_salad'],
                'sort_order' => 10,
            ],
            [
                'category' => 'salads',
                'name' => 'Свекольный с фетой',
                'slug' => 'beetroot-with-feta',
                'description' => 'Запечённая свёкла, зелень и фета с мягкой кислинкой.',
                'ingredients' => 'Запечённая свёкла, фета, микс салата, орехи, лимонная заправка.',
                'price' => 22000,
                'weight_grams' => 190,
                'image_path' => $imageLibrary['beet_salad'],
                'sort_order' => 20,
            ],
            [
                'category' => 'hot-meals',
                'name' => 'Котлета с пюре',
                'slug' => 'cutlet-with-mashed-potatoes',
                'description' => 'Домашняя куриная котлета с воздушным картофельным пюре.',
                'ingredients' => 'Куриная котлета, картофельное пюре, сливочное масло, зелень.',
                'price' => 39000,
                'weight_grams' => 320,
                'image_path' => $imageLibrary['cutlet'],
                'sort_order' => 0,
            ],
            [
                'category' => 'hot-meals',
                'name' => 'Бефстроганов с пастой',
                'slug' => 'beef-stroganoff-with-pasta',
                'description' => 'Томлёная говядина в сливочном соусе с мягкой пастой.',
                'ingredients' => 'Говядина, паста, сливочный соус, шампиньоны, лук, специи.',
                'price' => 46000,
                'weight_grams' => 340,
                'image_path' => $imageLibrary['stroganoff'],
                'sort_order' => 10,
            ],
            [
                'category' => 'hot-meals',
                'name' => 'Терияки с рисом',
                'slug' => 'teriyaki-chicken-with-rice',
                'description' => 'Курица терияки, рис и овощи в насыщенном соусе.',
                'ingredients' => 'Курица, рис, брокколи, морковь, соус терияки, кунжут.',
                'price' => 42000,
                'weight_grams' => 330,
                'image_path' => $imageLibrary['teriyaki'],
                'sort_order' => 20,
            ],
            [
                'category' => 'hot-meals',
                'name' => 'Паста карбонара',
                'slug' => 'pasta-carbonara',
                'description' => 'Кремовая паста с сыром, беконом и чёрным перцем.',
                'ingredients' => 'Паста, бекон, сливки, пармезан, яйцо, чёрный перец.',
                'price' => 41000,
                'weight_grams' => 300,
                'image_path' => $imageLibrary['carbonara'],
                'sort_order' => 30,
            ],
            [
                'category' => 'drinks',
                'name' => 'Ягодный морс',
                'slug' => 'berry-mors',
                'description' => 'Освежающий домашний морс из ягод без лишней сладости.',
                'ingredients' => 'Клюква, смородина, вода, сахар.',
                'price' => 12000,
                'weight_grams' => 300,
                'image_path' => $imageLibrary['berry_mors'],
                'sort_order' => 0,
            ],
            [
                'category' => 'drinks',
                'name' => 'Домашний лимонад',
                'slug' => 'homemade-lemonade',
                'description' => 'Лимонад с лимоном и мятой для лёгкого финала к обеду.',
                'ingredients' => 'Лимон, мята, вода, сахарный сироп.',
                'price' => 14000,
                'weight_grams' => 320,
                'image_path' => $imageLibrary['lemonade'],
                'sort_order' => 10,
            ],
        ])->mapWithKeys(function (array $product) use ($categories, $tomorrow): array {
            $productRecord = Product::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    'category_id' => $categories[$product['category']]->id,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'ingredients' => $product['ingredients'] ?? null,
                    'price' => $product['price'],
                    'weight_grams' => $product['weight_grams'],
                    'image_path' => $product['image_path'],
                    'menu_dates' => $product['menu_dates'] ?? [['date' => $tomorrow]],
                    'sort_order' => $product['sort_order'],
                ],
            );

            return [$product['slug'] => $productRecord];
        });

        $mealSets = [
            [
                'name' => 'Домашний обед',
                'slug' => 'home-lunch',
                'description' => 'Классика на каждый день: суп, салат и сытное горячее.',
                'price' => 79000,
                'image_path' => $imageLibrary['home_lunch_set'],
                'menu_dates' => [['date' => $tomorrow]],
                'sort_order' => 0,
                'items' => [
                    ['slug' => 'chicken-soup', 'quantity' => 1],
                    ['slug' => 'greek-salad', 'quantity' => 1],
                    ['slug' => 'cutlet-with-mashed-potatoes', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Лёгкий ланч',
                'slug' => 'light-lunch',
                'description' => 'Сбалансированный вариант с крем-супом, салатом и терияки.',
                'price' => 83000,
                'image_path' => $imageLibrary['light_lunch_set'],
                'menu_dates' => [['date' => $tomorrow]],
                'sort_order' => 10,
                'items' => [
                    ['slug' => 'pumpkin-cream-soup', 'quantity' => 1],
                    ['slug' => 'caesar-with-chicken', 'quantity' => 1],
                    ['slug' => 'teriyaki-chicken-with-rice', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Сытная классика',
                'slug' => 'hearty-classic',
                'description' => 'Борщ, свекольный салат и бефстроганов для тех, кто любит погуще.',
                'price' => 91000,
                'image_path' => $imageLibrary['hearty_set'],
                'menu_dates' => [['date' => $tomorrow]],
                'sort_order' => 20,
                'items' => [
                    ['slug' => 'borscht-with-sour-cream', 'quantity' => 1],
                    ['slug' => 'beetroot-with-feta', 'quantity' => 1],
                    ['slug' => 'beef-stroganoff-with-pasta', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Комфортный ужин',
                'slug' => 'comfort-dinner',
                'description' => 'Паста, салат и морс — для спокойного вкусного вечера.',
                'price' => 76000,
                'image_path' => $imageLibrary['comfort_set'],
                'menu_dates' => [['date' => $tomorrow]],
                'sort_order' => 30,
                'items' => [
                    ['slug' => 'pasta-carbonara', 'quantity' => 1],
                    ['slug' => 'greek-salad', 'quantity' => 1],
                    ['slug' => 'berry-mors', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Семейный выбор',
                'slug' => 'family-choice',
                'description' => 'Собран как универсальный хит: суп, горячее, напиток и салат.',
                'price' => 97000,
                'image_path' => $imageLibrary['family_set'],
                'menu_dates' => [['date' => $tomorrow]],
                'sort_order' => 40,
                'items' => [
                    ['slug' => 'chicken-soup', 'quantity' => 1],
                    ['slug' => 'caesar-with-chicken', 'quantity' => 1],
                    ['slug' => 'teriyaki-chicken-with-rice', 'quantity' => 1],
                    ['slug' => 'homemade-lemonade', 'quantity' => 1],
                ],
            ],
        ];

        foreach ($mealSets as $mealSetData) {
            $items = $mealSetData['items'];
            unset($mealSetData['items']);

            $mealSet = MealSet::query()->updateOrCreate(
                ['slug' => $mealSetData['slug']],
                $mealSetData,
            );

            $mealSet->items()->delete();

            $mealSet->items()->createMany(
                collect($items)->map(fn (array $item, int $index): array => [
                    'product_id' => $products[$item['slug']]->id,
                    'quantity' => $item['quantity'],
                    'sort_order' => $index * 10,
                ])->all(),
            );
        }

        $newTag = CatalogTag::query()->updateOrCreate(
            ['slug' => 'novinka'],
            [
                'name' => 'Новинка',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );

        $products['chicken-soup']->tags()->syncWithoutDetaching([$newTag->id]);
        MealSet::query()
            ->where('slug', 'home-lunch')
            ->firstOrFail()
            ->tags()
            ->syncWithoutDetaching([$newTag->id]);

        collect([
            [
                'banner_tag_id' => $bannerTag->id,
                'meal_set_id' => MealSet::query()->where('slug', 'home-lunch')->value('id'),
                'title' => 'Домашние наборы на завтра',
                'description' => 'Классические сочетания для спокойного обеда без лишнего выбора.',
                'image_path' => $imageLibrary['home_lunch_set'],
                'image_url' => null,
                'link_url' => '/news/menu-na-zavtra-launch',
                'menu_date' => $tomorrow,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'banner_tag_id' => $bannerTag->id,
                'meal_set_id' => MealSet::query()->where('slug', 'light-lunch')->value('id'),
                'title' => 'Лёгкий вариант на день',
                'description' => 'Собрали сбалансированные наборы для тех, кто хочет поесть легко и вкусно.',
                'image_path' => $imageLibrary['light_lunch_set'],
                'image_url' => null,
                'link_url' => '/news/hero-banners-live',
                'menu_date' => $tomorrow,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'banner_tag_id' => $bannerTag->id,
                'meal_set_id' => MealSet::query()->where('slug', 'family-choice')->value('id'),
                'title' => 'Сытные решения для семьи',
                'description' => 'Большие и понятные наборы на завтра, которые удобно заказать одним кликом.',
                'image_path' => $imageLibrary['family_set'],
                'image_url' => null,
                'link_url' => '/news/profile-and-orders-ready',
                'menu_date' => $tomorrow,
                'is_active' => true,
                'sort_order' => 20,
            ],
        ])->each(function (array $banner): void {
            Banner::query()->updateOrCreate(
                ['title' => $banner['title'], 'menu_date' => $banner['menu_date']],
                $banner,
            );
        });

        collect([
            [
                'title' => 'Мы запускаем меню на завтра',
                'slug' => 'menu-na-zavtra-launch',
                'excerpt' => 'Теперь заказы собираются по отдельному меню на следующий день — так понятнее и для клиента, и для кухни.',
                'content' => 'Теперь на сайте каждый день публикуется новое меню на следующий день. Это помогает заранее собрать наборы, дополнительные блюда и не путать актуальные позиции между днями.',
                'image_path' => null,
                'image_url' => $imageLibrary['home_lunch_set'],
                'published_at' => now()->subDays(2),
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'На главной появились баннеры',
                'slug' => 'hero-banners-live',
                'excerpt' => 'Главный экран стал визуальнее: теперь важные предложения и анонсы можно показывать через баннеры.',
                'content' => 'Мы добавили управляемые баннеры на главный экран. Через админку можно задать заголовок, описание, картинку и ссылку, чтобы использовать этот блок и для акций, и для будущих новостей.',
                'image_path' => null,
                'image_url' => $imageLibrary['light_lunch_set'],
                'published_at' => now()->subDay(),
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'title' => 'История заказов и профиль уже работают',
                'slug' => 'profile-and-orders-ready',
                'excerpt' => 'Пользователь уже может зарегистрироваться, редактировать профиль и смотреть историю своих заказов.',
                'content' => 'В проекте уже доступны регистрация, авторизация, личный кабинет и история заказов. Это важная основа для следующего этапа: новостей, повторных заказов и персональных предложений.',
                'image_path' => null,
                'image_url' => $imageLibrary['family_set'],
                'published_at' => now(),
                'is_active' => true,
                'sort_order' => 20,
            ],
        ])->each(function (array $news): void {
            News::query()->updateOrCreate(
                ['slug' => $news['slug']],
                $news,
            );
        });

        Product::query()->updateOrCreate(
            ['slug' => 'iced-tea-next-day'],
            [
                'category_id' => $categories['drinks']->id,
                'name' => 'Холодный чай с лимоном',
                'description' => 'Позиция для следующего меню, чтобы проверить фильтрацию по дате.',
                'ingredients' => 'Чёрный чай, лимон, мята, сахарный сироп.',
                'price' => 15000,
                'weight_grams' => 300,
                'image_path' => $imageLibrary['lemonade'],
                'menu_dates' => [['date' => $dayAfterTomorrow]],
                'sort_order' => 999,
            ],
        );

        $futureSet = MealSet::query()->updateOrCreate(
            ['slug' => 'menu-day-after'],
            [
                'name' => 'Меню послезавтра',
                'description' => 'Тестовый набор для следующей даты меню.',
                'price' => 78000,
                'image_path' => $imageLibrary['family_set'],
                'menu_dates' => [['date' => $dayAfterTomorrow]],
                'sort_order' => 999,
            ],
        );

        $futureSet->items()->delete();

        $futureSet->items()->createMany([
            [
                'product_id' => $products['pumpkin-cream-soup']->id,
                'quantity' => 1,
                'sort_order' => 0,
            ],
            [
                'product_id' => $products['homemade-lemonade']->id,
                'quantity' => 1,
                'sort_order' => 10,
            ],
        ]);

        Banner::query()->updateOrCreate(
            ['title' => 'Баннер на послезавтра', 'menu_date' => $dayAfterTomorrow],
            [
                'banner_tag_id' => $bannerTag->id,
                'meal_set_id' => $futureSet->id,
                'description' => 'Нужен для проверки фильтрации баннеров по дате.',
                'image_path' => $imageLibrary['family_set'],
                'image_url' => null,
                'link_url' => '/news/menu-na-zavtra-launch',
                'is_active' => true,
                'sort_order' => 999,
            ],
        );
    }
}
