<script setup>
import AppShell from '../Components/AppShell.vue';
import Breadcrumbs from '../Components/Breadcrumbs.vue';
import { mailtoHref, phoneHref, telegramHref } from '../utils/contacts';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    contacts: {
        type: Object,
        required: true,
    },
});

const resolvedContacts = computed(() => ({
    ...props.contacts,
    phone_href: props.contacts.phone_href ?? phoneHref(props.contacts.phone),
    email_href: props.contacts.email_href ?? mailtoHref(props.contacts.email),
    telegram_href: props.contacts.telegram_href ?? telegramHref(props.contacts.telegram_url, props.contacts.telegram),
}));
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
                <p class="text-lg leading-6 text-stone-700">
                    Если нужно уточнить детали заказа, доставку или меню, здесь собраны основные каналы связи.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-[2rem] bg-white p-8 shadow-[0_20px_70px_rgba(120,87,43,0.08)]">
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Связь</div>
                    <div class="mt-6 space-y-5">
                        <div>
                            <div class="text-sm text-stone-500">Телефон</div>
                            <a
                                v-if="resolvedContacts.phone_href"
                                :href="resolvedContacts.phone_href"
                                class="mt-1 block text-2xl font-bold text-stone-950 transition hover:text-orange-700"
                            >
                                {{ resolvedContacts.phone }}
                            </a>
                            <div v-else class="mt-1 text-2xl font-bold text-stone-950">{{ resolvedContacts.phone }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-stone-500">Email</div>
                            <a
                                v-if="resolvedContacts.email_href"
                                :href="resolvedContacts.email_href"
                                class="mt-1 block text-2xl font-bold text-stone-950 transition hover:text-orange-700"
                            >
                                {{ resolvedContacts.email }}
                            </a>
                            <div v-else class="mt-1 text-2xl font-bold text-stone-950">{{ resolvedContacts.email }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-stone-500">Telegram</div>
                            <a
                                v-if="resolvedContacts.telegram_href"
                                :href="resolvedContacts.telegram_href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 block text-2xl font-bold text-stone-950 transition hover:text-orange-700"
                            >
                                {{ resolvedContacts.telegram }}
                            </a>
                            <div v-else class="mt-1 text-2xl font-bold text-stone-950">{{ resolvedContacts.telegram }}</div>
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
