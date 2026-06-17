<script setup>
import ProfileLayout from '../../Components/ProfileLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    navigation: {
        type: Array,
        default: () => [],
    },
    order: {
        type: Object,
        required: true,
    },
});

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0,
    }).format(value / 100);
}

function formatDateTime(value) {
    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatDate(value) {
    if (!value) {
        return 'Дата доставки уточняется';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'long',
    }).format(new Date(`${value}T12:00:00`));
}
</script>

<template>
    <ProfileLayout
        :title="`Заказ ${order.number}`"
        subtitle="Детали заказа: позиции, состав, доставка и итоговая сумма."
        :navigation="navigation"
        :breadcrumbs="[
            { label: 'Главная', href: '/' },
            { label: 'Личный кабинет', href: '/profile' },
            { label: 'Заказы', href: '/profile/orders' },
            { label: order.number },
        ]"
    >
        <section class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-5">
                <div class="min-w-0">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Детали заказа</div>
                    <h2 class="mt-3 break-all text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">{{ order.number }}</h2>
                    <p class="mt-3 text-lg text-stone-500">Создан {{ formatDateTime(order.created_at) }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-full bg-slate-100 px-4 py-2 text-base font-medium text-slate-600">
                        {{ order.status_label }}
                    </div>
                    <div class="rounded-full bg-orange-50 px-4 py-2 text-base font-medium text-orange-700">
                        {{ order.payment_status_label }}
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.6rem] bg-stone-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Доставка</div>
                    <div class="mt-3 text-xl font-bold text-stone-950">{{ formatDate(order.delivery_date) }}</div>
                    <div v-if="order.delivery_interval" class="mt-1 text-base text-stone-600">{{ order.delivery_interval }}</div>
                    <div class="mt-3 text-base leading-7 text-stone-600">
                        {{ order.delivery_groups_count > 1 ? `Адресов в заказе: ${order.delivery_groups_count}` : order.delivery_address }}
                    </div>
                </div>

                <div class="rounded-[1.6rem] bg-stone-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Оплата</div>
                    <div class="mt-3 space-y-2 text-base text-stone-600">
                        <div class="flex justify-between gap-4">
                            <span>Позиции</span>
                            <span class="font-semibold text-stone-950">{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span>Доставка</span>
                            <span class="font-semibold text-stone-950">{{ formatPrice(order.delivery_price) }}</span>
                        </div>
                        <div class="flex justify-between gap-4 pt-2 text-xl font-black text-stone-950">
                            <span>Итого</span>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="order.customer_comment && order.delivery_groups_count <= 1" class="mt-4 rounded-[1.6rem] bg-orange-50 px-5 py-4 text-base leading-7 text-orange-950">
                <span class="font-bold">Комментарий:</span> {{ order.customer_comment }}
            </div>
        </section>

        <section class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Что заказали</div>
                    <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">Позиции</h2>
                </div>

                <Link
                    :href="`/profile/orders/${order.id}/repeat`"
                    method="post"
                    as="button"
                    class="rounded-full bg-stone-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-600"
                >
                    Повторить заказ
                </Link>
            </div>

            <div v-if="order.delivery_groups?.length" class="mt-8 space-y-5">
                <article
                    v-for="(group, groupIndex) in order.delivery_groups"
                    :key="group.id"
                    class="rounded-[1.8rem] bg-stone-50 px-6 py-5"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.16em] text-orange-700">
                                Адрес {{ groupIndex + 1 }}
                            </div>
                            <div class="mt-2 text-lg font-bold leading-7 text-stone-950">{{ group.delivery_address }}</div>
                            <div v-if="group.customer_comment" class="mt-3 rounded-[1.2rem] bg-orange-50 px-4 py-3 text-base leading-7 text-orange-950">
                                <span class="font-bold">Комментарий:</span> {{ group.customer_comment }}
                            </div>
                        </div>
                        <div class="rounded-[1.2rem] bg-white px-4 py-3 text-right">
                            <div class="text-sm text-stone-500">Позиции {{ formatPrice(group.subtotal) }}</div>
                            <div class="mt-1 text-sm text-stone-500">Доставка {{ formatPrice(group.delivery_price) }}</div>
                            <div class="mt-2 text-xl font-black tracking-[-0.04em] text-stone-950">{{ formatPrice(group.total) }}</div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <article
                            v-for="item in group.items"
                            :key="item.id"
                            class="rounded-[1.4rem] bg-white px-5 py-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="text-2xl font-black tracking-[-0.03em] text-stone-950">{{ item.name }}</div>
                                    <div class="mt-2 text-base text-stone-500">
                                        {{ item.type === 'meal_set' ? 'Набор' : 'Блюдо' }} x{{ item.quantity }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-stone-500">{{ formatPrice(item.unit_price) }} за шт.</div>
                                    <div class="mt-1 text-2xl font-black tracking-[-0.04em] text-stone-950">{{ formatPrice(item.total_price) }}</div>
                                </div>
                            </div>

                            <div v-if="item.product_ingredients" class="mt-5 rounded-[1.2rem] bg-stone-50 px-4 py-3 text-base leading-7 text-stone-600">
                                <span class="font-bold text-stone-950">Состав:</span> {{ item.product_ingredients }}
                            </div>

                            <div v-if="item.components?.length" class="mt-5">
                                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Состав набора</div>
                                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <li
                                        v-for="component in item.components"
                                        :key="component.id"
                                        class="rounded-[1.2rem] bg-stone-50 px-4 py-3 text-base text-stone-700"
                                    >
                                        {{ component.name }} x{{ component.quantity }}
                                    </li>
                                </ul>
                            </div>
                        </article>
                    </div>
                </article>
            </div>
        </section>
    </ProfileLayout>
</template>
