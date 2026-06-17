<script setup>
import AppShell from '../Components/AppShell.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    contacts: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Контакты" />

    <AppShell compact>
        <section class="space-y-8 py-6">
            <div class="max-w-4xl space-y-4">
                <Breadcrumbs
                    :items="[
                        { label: 'Главная', href: '/' },
                        { label: 'Контакты' },
                    ]"
                />
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Контакты</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-stone-950 sm:text-4xl lg:text-5xl">Как с нами связаться</h1>
                <p class="text-lg leading-8 text-stone-700">
                    Если нужно уточнить детали заказа, доставку или меню, здесь собраны основные каналы связи.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-[2rem] bg-white p-8 shadow-[0_20px_70px_rgba(120,87,43,0.08)]">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Связь</div>
                    <div class="mt-6 space-y-5">
                        <div>
                            <div class="text-sm text-stone-500">Телефон</div>
                            <div class="mt-1 text-2xl font-bold text-stone-950">{{ contacts.phone }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-stone-500">Email</div>
                            <div class="mt-1 text-2xl font-bold text-stone-950">{{ contacts.email }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-stone-500">Telegram</div>
                            <div class="mt-1 text-2xl font-bold text-stone-950">{{ contacts.telegram }}</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-8 shadow-[0_20px_70px_rgba(120,87,43,0.08)]">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Доставка</div>
                    <div class="mt-6 space-y-5 text-stone-700">
                        <div>
                            <div class="text-sm text-stone-500">Зона доставки</div>
                            <div class="mt-1 text-lg font-medium text-stone-950">{{ contacts.address }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-stone-500">График</div>
                            <div class="mt-1 text-lg font-medium text-stone-950">{{ contacts.schedule }}</div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </AppShell>
</template>
