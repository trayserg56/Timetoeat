<script setup>
import ProfileLayout from '../../Components/ProfileLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    navigation: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        required: true,
    },
    orders: {
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
        title="Заказы"
        subtitle="Полная история покупок с быстрым фильтром по статусам."
        :navigation="navigation"
        :breadcrumbs="[
            { label: 'Главная', href: '/' },
            { label: 'Личный кабинет', href: '/profile' },
            { label: 'Заказы' },
        ]"
    >
        <section class="rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 sm:mx-0 sm:flex-wrap sm:gap-3 sm:overflow-visible sm:px-0 sm:pb-0">
                <Link
                    v-for="option in filters.options"
                    :key="option.value"
                    :href="option.value === 'all' ? '/profile/orders' : `/profile/orders?status=${option.value}`"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition sm:px-6 sm:py-3 sm:text-base"
                    :class="filters.status === option.value
                        ? 'bg-orange-50 text-orange-700 shadow-sm'
                        : 'bg-white text-stone-800 shadow-sm ring-1 ring-stone-100 hover:bg-orange-50 hover:text-orange-700'"
                >
                    {{ option.label }}
                </Link>
            </div>

            <div v-if="orders.length" class="mt-6 space-y-4 sm:mt-8">
                <article
                    v-for="order in orders"
                    :key="order.id"
                    class="rounded-[1.8rem] bg-white px-4 py-5 shadow-[0_10px_35px_rgba(120,87,43,0.05)] ring-1 ring-stone-100 sm:px-7 sm:py-6"
                >
                    <div class="flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <Link
                                :href="`/profile/orders/${order.id}`"
                                class="min-w-0 break-all text-lg font-black tracking-[-0.03em] text-stone-950 transition hover:text-orange-700 sm:text-2xl"
                            >
                                {{ order.number }}
                            </Link>
                            <div class="shrink-0 text-xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl">
                                {{ formatPrice(order.total) }}
                            </div>
                        </div>

                        <div class="text-sm text-stone-500 sm:text-base">
                            <div>{{ formatDate(order.created_at) }}</div>
                            <div class="mt-1">
                                {{ order.delivery_date ? `Доставка ${order.delivery_date}` : 'Дата доставки уточняется' }}
                                <span v-if="order.delivery_interval"> · {{ order.delivery_interval }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <div class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 sm:px-4 sm:py-2 sm:text-sm">
                                    {{ order.status_label }}
                                </div>
                                <div class="rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 sm:px-4 sm:py-2 sm:text-sm">
                                    {{ order.payment_status_label }}
                                </div>
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

                        <ul class="space-y-1.5 border-t border-stone-100 pt-4 text-sm text-stone-600 sm:text-base">
                            <li v-for="item in order.items" :key="item.id">
                                {{ item.name }} x{{ item.quantity }}
                            </li>
                        </ul>
                    </div>
                </article>
            </div>

            <div v-else class="mt-6 rounded-[1.8rem] bg-stone-50 px-6 py-12 text-center text-stone-500 sm:mt-8">
                По выбранному фильтру заказов пока нет.
            </div>
        </section>
    </ProfileLayout>
</template>
