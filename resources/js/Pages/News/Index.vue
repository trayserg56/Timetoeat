<script setup>
import AppShell from '../../Components/AppShell.vue';
import Breadcrumbs from '../../Components/Breadcrumbs.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    news: {
        type: Array,
        default: () => [],
    },
});

function formatDate(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Новости" />

    <AppShell compact>
        <section class="space-y-8 py-6">
            <div class="max-w-3xl space-y-4">
                <Breadcrumbs
                    :items="[
                        { label: 'Главная', href: '/' },
                        { label: 'Новости' },
                    ]"
                />
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Новости</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-stone-950 sm:text-4xl lg:text-5xl">Обновления сервиса и меню</h1>
                <p class="text-lg leading-8 text-stone-700">
                    Здесь будем публиковать важные новости, изменения меню и полезные анонсы для клиентов.
                </p>
            </div>

            <div v-if="news.length" class="grid gap-6 lg:grid-cols-3">
                <article
                    v-for="item in news"
                    :key="item.id"
                    class="overflow-hidden rounded-[2rem] bg-white shadow-[0_20px_70px_rgba(120,87,43,0.08)] transition hover:-translate-y-1 hover:shadow-[0_24px_80px_rgba(120,87,43,0.14)]"
                >
                    <Link :href="`/news/${item.slug}`" class="block">
                    <div class="relative h-56 overflow-hidden">
                        <img v-if="item.image" :src="item.image" :alt="item.title" class="size-full object-cover" loading="lazy" decoding="async" />
                        <div v-else class="size-full bg-[linear-gradient(135deg,#fdba74,#fb923c,#7c2d12)]"></div>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="text-sm text-stone-500">{{ formatDate(item.published_at) }}</div>
                        <h2 class="text-xl font-black leading-tight text-stone-950 sm:text-2xl">{{ item.title }}</h2>
                        <p class="text-sm leading-6 text-stone-600">{{ item.excerpt }}</p>
                    </div>
                    </Link>
                </article>
            </div>

            <div v-else class="rounded-[2rem] bg-white px-6 py-12 text-center text-stone-500 shadow-sm">
                Новостей пока нет.
            </div>
        </section>
    </AppShell>
</template>
