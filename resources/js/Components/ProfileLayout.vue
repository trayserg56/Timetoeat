<script setup>
import AppShell from './AppShell.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    navigation: {
        type: Array,
        default: () => [],
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <Head :title="props.title" />

    <AppShell compact>
        <section class="py-4">
            <div class="max-w-5xl">
                <Breadcrumbs :items="props.breadcrumbs" />
                <h1 class="text-3xl font-black tracking-[-0.04em] text-stone-950 sm:text-4xl lg:text-5xl">{{ props.title }}</h1>
                <p class="mt-3 text-base leading-7 text-stone-500 sm:mt-4 sm:text-lg sm:leading-8">{{ props.subtitle }}</p>
            </div>

            <div class="mt-8 grid items-start gap-8 xl:grid-cols-[320px_1fr]">
                <aside class="self-start rounded-[2rem] bg-white p-4 shadow-[0_24px_80px_rgba(28,25,23,0.08)] sm:p-5 xl:sticky xl:top-28">
                    <div class="flex gap-2 overflow-x-auto pb-1 xl:flex-col xl:gap-0 xl:space-y-4 xl:overflow-visible xl:pb-0">
                        <Link
                            v-for="item in props.navigation"
                            :key="item.key"
                            :href="item.href"
                            class="block shrink-0 rounded-[1.6rem] px-4 py-3.5 text-base font-medium transition xl:shrink xl:px-6 xl:py-5 xl:text-xl"
                            :class="item.active
                                ? 'bg-orange-50 text-orange-700 shadow-[0_10px_30px_rgba(251,146,60,0.12)]'
                                : 'bg-white text-stone-800 shadow-sm hover:bg-orange-50 hover:text-orange-700'"
                        >
                            {{ item.label }}
                        </Link>
                    </div>

                    <div class="mt-8 rounded-[1.6rem] bg-stone-50 px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Аккаунт</div>
                        <div class="mt-3 text-lg font-semibold text-stone-900">{{ authUser?.name }}</div>
                        <div class="mt-1 text-sm text-stone-500">{{ authUser?.email }}</div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="mt-4 w-full rounded-full bg-white px-4 py-3 text-sm font-semibold shadow-sm transition hover:bg-stone-100"
                        >
                            Выйти
                        </Link>
                    </div>
                </aside>

                <div class="space-y-6">
                    <div v-if="flashSuccess" class="rounded-[1.8rem] bg-emerald-50 px-6 py-5 text-emerald-900 shadow-sm">
                        {{ flashSuccess }}
                    </div>
                    <slot />
                </div>
            </div>
        </section>
    </AppShell>
</template>
