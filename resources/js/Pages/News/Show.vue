<script setup>
import AppShell from '../../Components/AppShell.vue';
import Breadcrumbs from '../../Components/Breadcrumbs.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    news: {
        type: Object,
        required: true,
    },
    latestNews: {
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
    <Head :title="news.title" />

    <AppShell compact>
        <section class="grid gap-10 py-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <article class="overflow-hidden rounded-[2rem] bg-white shadow-[0_20px_70px_rgba(120,87,43,0.08)]">
                <div class="relative h-72 overflow-hidden">
                    <img v-if="news.image" :src="news.image" :alt="news.title" class="size-full object-cover" fetchpriority="high" decoding="async" />
                    <div v-else class="size-full bg-[linear-gradient(135deg,#fdba74,#fb923c,#7c2d12)]"></div>
                </div>
                <div class="space-y-6 p-5 sm:p-8">
                    <div class="space-y-3">
                        <Breadcrumbs
                            :items="[
                                { label: 'Главная', href: '/' },
                                { label: 'Новости', href: '/news' },
                                { label: news.title },
                            ]"
                        />
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Новость</p>
                        <h1 class="text-2xl font-black leading-tight tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">{{ news.title }}</h1>
                        <div class="text-sm text-stone-500">{{ formatDate(news.published_at) }}</div>
                    </div>
                    <p v-if="news.excerpt" class="text-lg leading-6 text-stone-700">{{ news.excerpt }}</p>
                    <div class="whitespace-pre-line text-base leading-6 text-stone-700">{{ news.content }}</div>
                </div>
            </article>

            <aside class="space-y-4">
                <div v-if="latestNews.length" class="rounded-[2rem] bg-white p-6 shadow-[0_20px_70px_rgba(120,87,43,0.08)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Ещё новости</p>
                    <div class="mt-4 space-y-4">
                        <Link
                            v-for="item in latestNews"
                            :key="item.id"
                            :href="`/news/${item.slug}`"
                            class="block overflow-hidden rounded-[1.25rem] bg-stone-50 transition hover:bg-stone-100"
                        >
                            <div class="grid gap-4 p-4 sm:grid-cols-[88px_minmax(0,1fr)] sm:items-center">
                                <div class="relative h-24 overflow-hidden rounded-[1rem] bg-stone-100">
                                    <img
                                        v-if="item.image"
                                        :src="item.image"
                                        :alt="item.title"
                                        class="size-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <div
                                        v-else
                                        class="size-full bg-[linear-gradient(135deg,#fdba74,#fb923c,#7c2d12)]"
                                    ></div>
                                </div>
                                <div>
                                    <div class="font-semibold leading-6 text-stone-900">{{ item.title }}</div>
                                    <div class="mt-1 text-sm text-stone-500">{{ formatDate(item.published_at) }}</div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </aside>
        </section>
    </AppShell>
</template>
