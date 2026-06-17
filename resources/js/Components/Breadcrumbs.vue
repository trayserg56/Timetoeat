<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav v-if="items.length" aria-label="Хлебные крошки" class="flex flex-wrap items-center gap-2 text-sm text-stone-500">
        <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
            <Link
                v-if="item.href && index !== items.length - 1"
                :href="item.href"
                class="transition hover:text-stone-900"
            >
                {{ item.label }}
            </Link>
            <span
                v-else
                class="font-medium text-stone-900"
                :aria-current="index === items.length - 1 ? 'page' : undefined"
            >
                {{ item.label }}
            </span>
            <span v-if="index !== items.length - 1" class="text-stone-300">/</span>
        </template>
    </nav>
</template>
