<script setup>
import ProfileLayout from '../../Components/ProfileLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    navigation: {
        type: Array,
        default: () => [],
    },
    profile: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    latestOrders: {
        type: Array,
        default: () => [],
    },
});

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0,
    }).format(value / 100);
}

function formatDate(value) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <ProfileLayout
        title="Личный кабинет"
        subtitle="Настройки профиля, заказы и быстрый доступ к вашей истории покупок."
        :navigation="navigation"
        :breadcrumbs="[
            { label: 'Главная', href: '/' },
            { label: 'Личный кабинет' },
        ]"
    >
        <section class="grid min-w-0 gap-6 md:grid-cols-2">
            <div class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Профиль</div>
                <div class="mt-4 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl">{{ profile.name }}</div>
                <div class="mt-3 text-base text-stone-500">{{ profile.email }}</div>
                <div class="mt-1 text-base text-stone-500">{{ profile.phone || 'Телефон не указан' }}</div>
                <div class="mt-1 text-base text-stone-500">{{ profile.telegram_username || 'Telegram не указан' }}</div>
            </div>

            <div class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Активность</div>
                <div class="mt-4 text-4xl font-black tracking-[-0.05em] text-stone-950 sm:text-5xl">{{ stats.orders_count }}</div>
                <div class="mt-2 text-base text-stone-500">заказов сохранено в вашем профиле</div>
                <div v-if="stats.latest_order_total" class="mt-6 rounded-[1.4rem] bg-stone-50 px-5 py-4 text-sm text-stone-600">
                    Последний заказ на сумму <span class="font-bold text-stone-900">{{ formatPrice(stats.latest_order_total) }}</span>
                </div>
            </div>
        </section>

        <section class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Последние заказы</div>
                    <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">Обзор</h2>
                </div>
            </div>

            <div v-if="latestOrders.length" class="mt-6 space-y-4">
                <article
                    v-for="order in latestOrders"
                    :key="order.id"
                    class="rounded-[1.8rem] bg-stone-50 px-4 py-5 sm:px-6"
                >
                    <div class="flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <Link
                                :href="`/profile/orders/${order.id}`"
                                class="min-w-0 break-all text-lg font-black tracking-[-0.03em] text-stone-950 transition hover:text-orange-700 sm:text-2xl"
                            >
                                {{ order.number }}
                            </Link>
                            <div class="shrink-0 text-xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl">{{ formatPrice(order.total) }}</div>
                        </div>
                        <div class="text-sm text-stone-500 sm:text-base">{{ formatDate(order.created_at) }}</div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="inline-flex rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 sm:px-4 sm:py-2 sm:text-sm">
                                {{ order.status_label }}
                            </div>
                            <Link
                                :href="`/profile/orders/${order.id}/repeat`"
                                method="post"
                                as="button"
                                class="shrink-0 rounded-full bg-stone-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600 sm:px-5 sm:py-3"
                            >
                                Повторить
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="mt-6 rounded-[1.8rem] bg-stone-50 px-6 py-10 text-center text-stone-500">
                Пока нет заказов. Первый можно оформить на главной странице.
            </div>
        </section>
    </ProfileLayout>
</template>
