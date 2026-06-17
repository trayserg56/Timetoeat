<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>404 — Страница не найдена</title>

        @vite('resources/js/app.js')
    </head>
    <body>
        @php
            try {
                $siteSettings = \App\Models\SiteSetting::current();
                $siteNavigation = \App\Models\SiteMenuItem::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            } catch (\Throwable $exception) {
                $siteSettings = (object) \App\Models\SiteSetting::defaults();
                $siteNavigation = collect();
            }

            $footerDescription = $siteSettings->footer_description
                ?: 'Готовые наборы и блюда на следующий день, понятное оформление заказа и быстрый доступ к новостям сервиса.';
        @endphp

        <div class="min-h-screen bg-[linear-gradient(180deg,#fbf3e6_0%,#fffdf8_28%,#f5ead8_100%)] text-stone-900">
            <div class="absolute inset-x-0 top-0 -z-10 h-[28rem] bg-[radial-gradient(circle_at_top_left,#ffb36b_0%,transparent_38%),radial-gradient(circle_at_top_right,#ffd7a8_0%,transparent_32%)]"></div>

            <header class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-4 px-6 py-6 lg:grid-cols-[auto_1fr_auto]">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-2xl bg-stone-950 text-sm font-black text-white">
                        FD
                    </div>
                    <div>
                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-700">Food Delivery</div>
                        <div class="text-sm text-stone-500">Домашняя еда без переписок</div>
                    </div>
                </a>

                @if ($siteNavigation->isNotEmpty())
                    <nav class="flex flex-wrap items-center justify-center gap-3 lg:px-6">
                        @foreach ($siteNavigation as $item)
                            <a
                                href="{{ $item->href }}"
                                class="rounded-full bg-white/80 px-4 py-2 text-sm font-semibold shadow-sm transition hover:bg-white"
                            >
                                {{ $item->label }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <nav class="flex flex-wrap items-center justify-end gap-3">
                    @auth
                        <a
                            href="{{ route('profile.overview') }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-stone-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(120,87,43,0.12)]"
                        >
                            Личный кабинет
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-stone-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(120,87,43,0.12)]"
                        >
                            Войти
                        </a>
                    @endauth
                </nav>
            </header>

            <main class="mx-auto max-w-7xl px-6 pb-16 pt-4">
                <section class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_22rem] lg:items-center">
                    <div class="rounded-[2rem] bg-white/80 p-8 shadow-[0_30px_80px_rgba(28,25,23,0.08)] backdrop-blur sm:p-10 lg:p-14">
                        <div class="inline-flex rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700">
                            Ошибка 404
                        </div>

                        <h1 class="mt-6 text-4xl font-black tracking-tight text-stone-950 sm:text-5xl lg:text-6xl">
                            Страница потерялась по дороге.
                        </h1>

                        <p class="mt-5 max-w-2xl text-lg leading-8 text-stone-600">
                            Похоже, ссылка устарела или адрес был введён с ошибкой. Ничего страшного:
                            можно быстро вернуться на главную, открыть новости или перейти к контактам.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                href="{{ route('home') }}"
                                class="inline-flex items-center justify-center rounded-full bg-stone-950 px-6 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-stone-800"
                            >
                                На главную
                            </a>
                            <a
                                href="{{ route('news.index') }}"
                                class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:-translate-y-0.5 hover:bg-stone-50"
                            >
                                Открыть новости
                            </a>
                            <a
                                href="{{ route('contacts') }}"
                                class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:-translate-y-0.5 hover:bg-stone-50"
                            >
                                Контакты
                            </a>
                        </div>
                    </div>

                    <aside class="rounded-[2rem] bg-stone-950 p-8 text-white shadow-[0_30px_80px_rgba(28,25,23,0.18)]">
                        <div class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-300">
                            Что можно сделать
                        </div>

                        <div class="mt-6 space-y-5">
                            <div class="rounded-3xl bg-white/8 p-5">
                                <div class="text-sm font-semibold text-orange-200">Меню и наборы</div>
                                <p class="mt-2 text-sm leading-7 text-white/75">
                                    На главной всегда можно быстро посмотреть доступные блюда и собрать заказ.
                                </p>
                            </div>

                            <div class="rounded-3xl bg-white/8 p-5">
                                <div class="text-sm font-semibold text-orange-200">Связаться с нами</div>
                                <div class="mt-2 space-y-2 text-sm leading-7 text-white/75">
                                    <p>{{ $siteSettings->contact_phone }}</p>
                                    <p>{{ $siteSettings->contact_email }}</p>
                                    <p>{{ $siteSettings->contact_telegram }}</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </section>
            </main>

            <footer class="bg-white/70 backdrop-blur">
                <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-[minmax(0,1.1fr)_repeat(2,minmax(0,0.7fr))]">
                    <div class="space-y-4">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                            <div class="flex size-11 items-center justify-center rounded-2xl bg-stone-950 text-sm font-black text-white">
                                FD
                            </div>
                            <div>
                                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-700">Food Delivery</div>
                                <div class="text-sm text-stone-500">Домашняя еда без переписок</div>
                            </div>
                        </a>

                        <p class="max-w-md text-sm leading-7 text-stone-600">
                            {{ $footerDescription }}
                        </p>
                    </div>

                    <div>
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Навигация</div>

                        <div class="mt-4 flex flex-col gap-3 text-sm text-stone-700">
                            @forelse ($siteNavigation as $item)
                                <a href="{{ $item->href }}" class="transition hover:text-stone-950">
                                    {{ $item->label }}
                                </a>
                            @empty
                                <a href="{{ route('home') }}" class="transition hover:text-stone-950">Главная</a>
                                <a href="{{ route('news.index') }}" class="transition hover:text-stone-950">Новости</a>
                                <a href="{{ route('contacts') }}" class="transition hover:text-stone-950">Контакты</a>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Контакты</div>

                        <div class="mt-4 space-y-3 text-sm text-stone-700">
                            <p>{{ $siteSettings->contact_email }}</p>
                            <p>{{ $siteSettings->contact_phone }}</p>
                            <p>{{ $siteSettings->contact_telegram }}</p>
                            <p>{{ $siteSettings->contact_address }}</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
