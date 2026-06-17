<script setup>
import AppShell from '../Components/AppShell.vue';
import { useCart } from '../composables/useCart';
import {
    isCatalogItemOrderable,
    normalizeCatalogItem,
    resolveCatalogImageLayout,
    unavailableCatalogItemLabel,
} from '../utils/catalogItem';
import { formatPrice } from '../utils/money';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    latestNews: {
        type: Array,
        default: () => [],
    },
    extraProducts: {
        type: Array,
        default: () => [],
    },
    mealSets: {
        type: Array,
        default: () => [],
    },
    menuDate: {
        type: String,
        default: '',
    },
});

const cartApi = useCart();

const selectedCatalogItem = ref(null);
const selectedItemImageLayout = ref(null);
const selectedItemImageLoaded = ref(false);
const currentHeroSlide = ref(0);

const menuDateLabel = computed(() =>
    props.menuDate
        ? new Intl.DateTimeFormat('ru-RU', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            timeZone: 'Europe/Moscow',
        }).format(new Date(`${props.menuDate}T12:00:00+03:00`))
        : '',
);

const heroSlides = computed(() => {
    if (props.banners.length) {
        return props.banners.map((banner) => ({
            id: banner.id,
            eyebrow: banner.tag,
            title: banner.title,
            description: banner.description || 'Свежий баннер из завтрашнего меню.',
            image: banner.image,
            link_url: banner.link_url,
            price: banner.price,
            item: banner.meal_set,
        }));
    }

    if (props.mealSets.length) {
        return props.mealSets.slice(0, 4).map((mealSet) => ({
            id: `meal-set-${mealSet.id}`,
            eyebrow: 'Набор на завтра',
            title: mealSet.name,
            description: mealSet.description || 'Готовая комбинация для быстрого заказа на следующий день.',
            image: mealSet.image,
            price: mealSet.price,
            item: mealSet,
        }));
    }

    return [
        {
            id: 'fallback',
            eyebrow: 'Меню на завтра',
            title: 'Свежие блюда каждый день',
            description: 'Каждый день здесь публикуется новое меню на завтра: наборы и дополнительные позиции в одном заказе.',
            image: null,
            price: null,
            item: null,
        },
    ];
});

const activeHeroSlide = computed(() => heroSlides.value[currentHeroSlide.value] ?? heroSlides.value[0] ?? null);

let heroSliderInterval = null;

function formatNewsDate(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}

function openItemModal(item) {
    selectedItemImageLayout.value = null;
    selectedItemImageLoaded.value = false;
    selectedCatalogItem.value = normalizeCatalogItem(item);
}

function closeItemModal() {
    selectedCatalogItem.value = null;
    selectedItemImageLayout.value = null;
    selectedItemImageLoaded.value = false;
}

function handleSelectedItemImageLoad(event) {
    const img = event.target;

    selectedItemImageLoaded.value = true;

    selectedItemImageLayout.value = resolveCatalogImageLayout(img?.naturalWidth, img?.naturalHeight);
}

function handleSelectedItemImageError() {
    selectedItemImageLoaded.value = true;
    selectedItemImageLayout.value = null;
}

function isSelectedItemOrderable() {
    return isCatalogItemOrderable(selectedCatalogItem.value);
}

const selectedItemCartQuantity = computed(() => {
    cartApi.cartCount;

    const item = selectedCatalogItem.value;

    if (!item) {
        return 0;
    }

    return cartApi.getPrimaryGroupCartQuantity(item.entityType, item.id);
});

function updateSelectedItemQuantity(nextQuantity) {
    const item = selectedCatalogItem.value;

    if (!item || !isSelectedItemOrderable()) {
        return;
    }

    cartApi.updateQuantity(
        item.entityType,
        item.id,
        nextQuantity,
        cartApi.primaryCartGroup?.id,
    );
}

function addSelectedItemToCart() {
    if (!selectedCatalogItem.value || !isSelectedItemOrderable()) {
        return;
    }

    cartApi.addToCart(selectedCatalogItem.value.entityType, selectedCatalogItem.value.id);
}

function openHeroSlide(slide) {
    if (slide?.link_url) {
        window.location.href = slide.link_url;

        return;
    }

    if (!slide?.item) {
        return;
    }

    openItemModal(slide.item);
}

function goToHeroSlide(index) {
    if (!heroSlides.value.length) {
        return;
    }

    currentHeroSlide.value = (index + heroSlides.value.length) % heroSlides.value.length;
}

function nextHeroSlide() {
    goToHeroSlide(currentHeroSlide.value + 1);
}

function previousHeroSlide() {
    goToHeroSlide(currentHeroSlide.value - 1);
}

function startHeroSlider() {
    if (heroSlides.value.length <= 1) {
        return;
    }

    heroSliderInterval = window.setInterval(() => {
        nextHeroSlide();
    }, 5000);
}

function stopHeroSlider() {
    if (!heroSliderInterval) {
        return;
    }

    window.clearInterval(heroSliderInterval);
    heroSliderInterval = null;
}

watch(heroSlides, (slides) => {
    if (currentHeroSlide.value >= slides.length) {
        currentHeroSlide.value = 0;
    }

    stopHeroSlider();
    startHeroSlider();
}, { deep: true });

onMounted(() => {
    startHeroSlider();
});

onBeforeUnmount(() => {
    stopHeroSlider();
});
</script>

<template>
    <Head title="Доставка домашней еды" />

    <AppShell>
        <section class="mx-auto max-w-7xl px-4 pb-12 pt-2 sm:px-6">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(320px,540px)] lg:items-center">
                <div class="space-y-6">
                    <div class="max-w-4xl space-y-5">
                        <h1 class="text-3xl font-black leading-tight tracking-[-0.04em] text-stone-950 sm:text-5xl sm:leading-none lg:text-7xl">
                            Закажите еду
                            <span class="text-orange-600">на {{ menuDateLabel }}</span>
                            за пару минут.
                        </h1>
                        <p class="max-w-2xl text-lg leading-6 text-stone-700">
                            Каждый день здесь публикуется новое меню на завтра: готовые наборы и дополнительные блюда, которые можно быстро собрать в один заказ.
                        </p>
                    </div>
                </div>

                <div
                    class="group relative overflow-hidden rounded-[2rem] bg-white shadow-[0_24px_80px_rgba(15,23,42,0.08)] ring-1 ring-stone-100"
                    @mouseenter="stopHeroSlider"
                    @mouseleave="startHeroSlider"
                >
                    <button
                        v-if="activeHeroSlide?.item"
                        type="button"
                        class="absolute inset-0 z-10"
                        @click="openHeroSlide(activeHeroSlide)"
                    >
                        <span class="sr-only">Открыть баннер</span>
                    </button>

                    <div class="relative h-[300px] sm:h-[360px]">
                        <img
                            v-if="activeHeroSlide?.image"
                            :src="activeHeroSlide.image"
                            :alt="activeHeroSlide.title"
                            class="size-full object-cover transition duration-700"
                            fetchpriority="high"
                            decoding="async"
                        />
                        <div
                            v-else
                            class="size-full bg-[linear-gradient(135deg,#fdba74,#fb923c,#7c2d12)]"
                        ></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/35 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 z-20 p-6 text-white sm:p-7">
                            <div
                                v-if="activeHeroSlide?.eyebrow"
                                class="inline-flex rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/90 backdrop-blur"
                            >
                                {{ activeHeroSlide.eyebrow }}
                            </div>
                            <h2 class="mt-4 max-w-md text-2xl font-black leading-tight tracking-[-0.03em] sm:text-3xl lg:text-4xl">
                                {{ activeHeroSlide?.title }}
                            </h2>
                            <p class="mt-3 max-w-md text-sm leading-6 text-white/85 sm:text-base">
                                {{ activeHeroSlide?.description }}
                            </p>
                            <div class="mt-5 flex items-center gap-3">
                                <div
                                    v-if="activeHeroSlide?.price"
                                    class="rounded-full bg-white/12 px-4 py-2 text-sm font-semibold backdrop-blur"
                                >
                                    {{ formatPrice(activeHeroSlide.price) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="heroSlides.length > 1"
                        class="relative z-20 flex items-center justify-center gap-2 bg-white px-5 py-4"
                    >
                        <button
                            v-for="(slide, index) in heroSlides"
                            :key="slide.id"
                            type="button"
                            class="h-2.5 rounded-full transition"
                            :class="index === currentHeroSlide ? 'w-8 bg-orange-500' : 'w-2.5 bg-stone-300 hover:bg-stone-400'"
                            @click="goToHeroSlide(index)"
                        >
                            <span class="sr-only">Перейти к баннеру {{ index + 1 }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 pb-20">
            <div class="flex items-end justify-between gap-6">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Меню на завтра</p>
                    <h2 class="mt-3 text-2xl font-black tracking-[-0.03em] sm:text-3xl">Наборы на {{ menuDateLabel }}</h2>
                </div>
            </div>

            <div v-if="mealSets.length" class="mt-8 grid gap-5 lg:grid-cols-3">
                <article
                    v-for="mealSet in mealSets"
                    :key="mealSet.id"
                    class="group cursor-pointer overflow-hidden rounded-[2rem] bg-white shadow-[0_20px_70px_rgba(15,23,42,0.06)] ring-1 ring-stone-100 transition hover:-translate-y-1 hover:shadow-[0_24px_90px_rgba(15,23,42,0.1)]"
                    role="button"
                    tabindex="0"
                    @click="openItemModal(mealSet)"
                    @keydown.enter.prevent="openItemModal(mealSet)"
                    @keydown.space.prevent="openItemModal(mealSet)"
                >
                    <div class="relative h-56 overflow-hidden">
                        <img
                            v-if="mealSet.image"
                            :src="mealSet.image"
                            :alt="mealSet.name"
                            class="size-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                        />
                        <div v-else class="size-full bg-[linear-gradient(135deg,#fed7aa,#fb923c,#7c2d12)]"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-stone-950/80 via-stone-950/35 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                            <div class="flex flex-wrap gap-2">
                                <div class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                                    Набор
                                </div>
                                <div
                                    v-for="tag in mealSet.tags"
                                    :key="tag.id"
                                    class="inline-flex rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white"
                                >
                                    {{ tag.name }}
                                </div>
                            </div>
                            <h3 class="mt-4 max-w-xs text-2xl font-black leading-tight sm:text-3xl">{{ mealSet.name }}</h3>
                            <p class="mt-3 max-w-sm text-sm leading-6 text-white/85">
                                {{ mealSet.description || 'Комбинация для тех, кто хочет закрыть весь приём пищи сразу.' }}
                            </p>
                        </div>
                    </div>
                    <div class="space-y-5 p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div class="text-2xl font-black text-stone-950">{{ formatPrice(mealSet.price) }}</div>
                            <div
                                v-if="cartApi.getPrimaryGroupCartQuantity('meal_set', mealSet.id)"
                                class="inline-flex items-center gap-2 rounded-full bg-white px-2 py-1 shadow-sm ring-1 ring-stone-200"
                                @click.stop
                            >
                                <button
                                    type="button"
                                    class="inline-flex size-9 items-center justify-center rounded-full text-lg font-medium transition hover:bg-stone-100"
                                    @click="cartApi.updateQuantity('meal_set', mealSet.id, cartApi.getPrimaryGroupCartQuantity('meal_set', mealSet.id) - 1, cartApi.primaryCartGroup?.id)"
                                >
                                    -
                                </button>
                                <span class="min-w-7 text-center text-sm font-semibold text-stone-900">
                                    {{ cartApi.getPrimaryGroupCartQuantity('meal_set', mealSet.id) }}
                                </span>
                                <button
                                    type="button"
                                    class="inline-flex size-9 items-center justify-center rounded-full text-lg font-medium transition hover:bg-stone-100"
                                    @click="cartApi.updateQuantity('meal_set', mealSet.id, cartApi.getPrimaryGroupCartQuantity('meal_set', mealSet.id) + 1, cartApi.primaryCartGroup?.id)"
                                >
                                    +
                                </button>
                            </div>
                            <button
                                v-else
                                type="button"
                                class="rounded-full px-5 py-3 text-sm font-semibold shadow-sm ring-1 transition"
                                :class="mealSet.is_orderable
                                    ? 'bg-white ring-stone-200 hover:bg-orange-50 hover:text-orange-600'
                                    : 'cursor-not-allowed bg-stone-100 text-stone-500 ring-stone-200'"
                                :disabled="!mealSet.is_orderable"
                                @click.stop="mealSet.is_orderable && cartApi.addToCart('meal_set', mealSet.id)"
                            >
                                {{ mealSet.is_orderable ? 'В корзину' : 'Недоступен к заказу' }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <div v-else class="mt-8 rounded-[2rem] bg-white px-6 py-12 text-center text-stone-500 shadow-sm ring-1 ring-stone-100">
                Наборы на {{ menuDateLabel }} ещё не опубликованы.
            </div>

            <section class="mt-16">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Дополнительно</p>
                        <h3 class="mt-2 text-2xl font-black tracking-[-0.03em]">Что можно добавить к заказу</h3>
                    </div>
                </div>

                <div v-if="extraProducts.length" class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="product in extraProducts"
                        :key="product.id"
                        class="group flex h-full cursor-pointer flex-col overflow-hidden rounded-[2rem] bg-white shadow-[0_16px_50px_rgba(15,23,42,0.06)] ring-1 ring-stone-100 transition hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(15,23,42,0.1)]"
                        role="button"
                        tabindex="0"
                        @click="openItemModal(product)"
                        @keydown.enter.prevent="openItemModal(product)"
                        @keydown.space.prevent="openItemModal(product)"
                    >
                        <div class="relative h-56 overflow-hidden">
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.name"
                                class="size-full object-cover transition duration-500 group-hover:scale-105"
                                loading="lazy"
                                decoding="async"
                            />
                            <div v-else class="size-full bg-[linear-gradient(135deg,#fde68a,#fb923c,#7c2d12)]"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-stone-950/80 via-stone-950/25 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 p-6 text-white">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-200">
                                            {{ product.category_name || 'Отдельно' }}
                                        </div>
                                        <div
                                            v-for="tag in product.tags"
                                            :key="tag.id"
                                            class="rounded-full bg-orange-500 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.1em] text-white"
                                        >
                                            {{ tag.name }}
                                        </div>
                                    </div>
                                    <h4 class="mt-3 text-2xl font-bold">{{ product.name }}</h4>
                                </div>
                                <div v-if="product.weight_grams" class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                                    {{ product.weight_grams }} г
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <p class="min-h-12 text-sm leading-6 text-stone-600 line-clamp-2">
                                {{ product.description || 'Можно добавить к любому набору или заказать отдельно.' }}
                            </p>
                            <p class="mt-5 min-h-10 text-xs leading-5 text-stone-500 line-clamp-2">
                                <template v-if="product.ingredients">
                                    <span class="font-semibold text-stone-700">Состав:</span>
                                    {{ ' ' }}{{ product.ingredients }}
                                </template>
                            </p>
                            <div class="mt-auto flex items-center justify-between gap-4 pt-5">
                                <div class="text-2xl font-black text-stone-950">{{ formatPrice(product.price) }}</div>
                                <div
                                    v-if="cartApi.getPrimaryGroupCartQuantity('product', product.id)"
                                    class="inline-flex items-center gap-2 rounded-full bg-white px-2 py-1 shadow-sm ring-1 ring-stone-200"
                                    @click.stop
                                >
                                    <button
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-full text-lg font-medium transition hover:bg-stone-100"
                                        @click="cartApi.updateQuantity('product', product.id, cartApi.getPrimaryGroupCartQuantity('product', product.id) - 1, cartApi.primaryCartGroup?.id)"
                                    >
                                        -
                                    </button>
                                    <span class="min-w-7 text-center text-sm font-semibold text-stone-900">
                                        {{ cartApi.getPrimaryGroupCartQuantity('product', product.id) }}
                                    </span>
                                    <button
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-full text-lg font-medium transition hover:bg-stone-100"
                                        @click="cartApi.updateQuantity('product', product.id, cartApi.getPrimaryGroupCartQuantity('product', product.id) + 1, cartApi.primaryCartGroup?.id)"
                                    >
                                        +
                                    </button>
                                </div>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-full bg-white px-5 py-3 text-sm font-semibold shadow-sm ring-1 ring-stone-200 transition hover:bg-orange-50 hover:text-orange-600"
                                    @click.stop="cartApi.addToCart('product', product.id)"
                                >
                                    В корзину
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="mt-6 rounded-[2rem] bg-white px-6 py-12 text-center text-stone-500 shadow-sm ring-1 ring-stone-100">
                    Дополнительные блюда на {{ menuDateLabel }} пока не опубликованы.
                </div>
            </section>

            <section class="mt-20">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Новости</p>
                        <h2 class="mt-3 text-2xl font-black tracking-[-0.03em] sm:text-3xl">Что нового в сервисе</h2>
                    </div>
                    <Link href="/news" class="rounded-full bg-white px-5 py-3 text-sm font-semibold shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50">
                        Все новости
                    </Link>
                </div>

                <div v-if="latestNews.length" class="mt-8 grid gap-5 lg:grid-cols-3">
                    <article
                        v-for="item in latestNews"
                        :key="item.id"
                        class="overflow-hidden rounded-[2rem] bg-white shadow-[0_16px_50px_rgba(15,23,42,0.06)] ring-1 ring-stone-100 transition hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(15,23,42,0.09)]"
                    >
                        <Link :href="`/news/${item.slug}`" class="block">
                            <div class="relative h-52 overflow-hidden">
                                <img v-if="item.image" :src="item.image" :alt="item.title" class="size-full object-cover" loading="lazy" decoding="async" />
                                <div v-else class="size-full bg-[linear-gradient(135deg,#fdba74,#fb923c,#7c2d12)]"></div>
                            </div>
                            <div class="space-y-4 p-6">
                                <div class="text-sm text-stone-500">{{ formatNewsDate(item.published_at) }}</div>
                                <h3 class="text-2xl font-black leading-tight text-stone-950">{{ item.title }}</h3>
                                <p class="text-sm leading-6 text-stone-600">{{ item.excerpt }}</p>
                            </div>
                        </Link>
                    </article>
                </div>
            </section>
        </section>

        <!-- Item quick-view modal -->
        <div
            v-if="selectedCatalogItem"
            class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-stone-950/55 [-webkit-overflow-scrolling:touch]"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-full items-end justify-center sm:items-center sm:px-6 sm:py-8">
                <div class="absolute inset-0" @click="closeItemModal"></div>
                <div class="relative w-full max-w-3xl bg-white shadow-[0_24px_80px_rgba(28,25,23,0.3)] sm:my-auto sm:rounded-[2rem]">
                <div
                    class="sticky z-30 h-0 overflow-visible"
                    style="top: max(0px, env(safe-area-inset-top));"
                >
                    <button
                        type="button"
                        class="absolute right-3 top-3 inline-flex size-10 items-center justify-center rounded-full bg-white/95 text-stone-900 shadow-md ring-1 ring-black/5 transition hover:bg-white sm:right-5 sm:top-5 sm:size-11"
                        @click="closeItemModal"
                    >
                        <span class="text-xl leading-none">×</span>
                    </button>
                </div>
                <div class="relative isolate min-h-60 overflow-hidden bg-stone-200 sm:min-h-72 sm:rounded-t-[2rem]">
                    <div
                        v-if="selectedCatalogItem.image && !selectedItemImageLoaded"
                        class="absolute inset-0 animate-pulse bg-gradient-to-br from-stone-100 via-stone-200 to-orange-50"
                        aria-hidden="true"
                    ></div>
                    <template v-if="selectedCatalogItem.image">
                        <template v-if="selectedItemImageLoaded && selectedItemImageLayout === 'portrait'">
                            <img
                                :src="selectedCatalogItem.image"
                                alt=""
                                aria-hidden="true"
                                class="absolute inset-0 size-full scale-110 object-cover blur-2xl saturate-125"
                            />
                            <div class="absolute inset-0 bg-white/40"></div>
                        </template>
                        <img
                            :src="selectedCatalogItem.image"
                            :alt="selectedCatalogItem.name"
                            class="relative z-10 mx-auto block max-h-60 w-full object-contain transition-opacity duration-300 sm:max-h-72"
                            :class="[
                                selectedItemImageLayout === 'portrait' ? 'w-auto max-w-full' : '',
                                selectedItemImageLoaded ? 'opacity-100' : 'opacity-0',
                            ]"
                            decoding="async"
                            @load="handleSelectedItemImageLoad"
                            @error="handleSelectedItemImageError"
                        />
                    </template>
                    <div
                        v-else
                        class="h-60 w-full sm:h-72"
                        :class="selectedCatalogItem.entityType === 'meal_set'
                            ? 'bg-[linear-gradient(135deg,#fed7aa,#fb923c,#7c2d12)]'
                            : 'bg-stone-950'"
                    ></div>
                </div>

                <div class="px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4 sm:rounded-b-[2rem] sm:px-8 sm:pb-8 sm:pt-6">
                    <div class="flex flex-wrap gap-2">
                        <div class="inline-flex rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-stone-700">
                            {{ selectedCatalogItem.entityType === 'meal_set' ? 'Набор' : 'Блюдо' }}
                        </div>
                        <div
                            v-for="tag in selectedCatalogItem.tags"
                            :key="tag.id"
                            class="inline-flex rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white"
                        >
                            {{ tag.name }}
                        </div>
                    </div>
                    <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:mt-4 sm:text-3xl lg:text-4xl">{{ selectedCatalogItem.name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600 sm:mt-3 sm:text-base">
                        {{
                            selectedCatalogItem.description
                                || (selectedCatalogItem.entityType === 'meal_set'
                                    ? 'Готовая комбинация для быстрого и понятного заказа.'
                                    : 'Можно заказать отдельно или добавить к любому набору.')
                        }}
                    </p>

                <div class="mt-5 grid gap-5 sm:mt-6 sm:gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                    <div>
                        <template v-if="selectedCatalogItem.entityType === 'meal_set'">
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Что входит в набор</div>
                            <div class="mt-5 space-y-3">
                                <div
                                    v-for="item in selectedCatalogItem.items"
                                    :key="item.id"
                                    class="group/component relative flex items-center justify-between gap-4 rounded-[1.25rem] bg-white px-4 py-4 outline-none ring-1 ring-stone-100 transition hover:bg-orange-50 focus:bg-orange-50"
                                    tabindex="0"
                                >
                                    <div>
                                        <div class="font-medium text-stone-900">{{ item.product?.name }}</div>
                                        <div v-if="item.product?.category_name" class="mt-1 text-sm text-stone-500">
                                            {{ item.product.category_name }}
                                        </div>
                                        <div
                                            v-if="item.product?.ingredients"
                                            class="pointer-events-none absolute left-4 right-4 top-[calc(100%+0.5rem)] z-20 rounded-2xl bg-stone-950 px-4 py-3 text-sm leading-6 text-white opacity-0 shadow-[0_20px_50px_rgba(28,25,23,0.25)] transition group-hover/component:opacity-100 group-focus/component:opacity-100"
                                        >
                                            <span class="font-semibold text-orange-200">Состав:</span>
                                            {{ item.product.ingredients }}
                                        </div>
                                    </div>
                                    <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-stone-500">
                                        x{{ item.quantity }}
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">О позиции</div>
                            <div class="mt-5 space-y-3">
                                <div class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-stone-100">
                                    <div class="text-sm text-stone-500">Категория</div>
                                    <div class="mt-1 font-medium text-stone-900">{{ selectedCatalogItem.category_name || 'Блюдо' }}</div>
                                </div>
                                <div v-if="selectedCatalogItem.weight_grams" class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-stone-100">
                                    <div class="text-sm text-stone-500">Вес</div>
                                    <div class="mt-1 font-medium text-stone-900">{{ selectedCatalogItem.weight_grams }} г</div>
                                </div>
                                <div v-if="selectedCatalogItem.ingredients" class="rounded-[1.25rem] bg-white px-4 py-3 ring-1 ring-stone-100">
                                    <div class="text-sm text-stone-500">Состав</div>
                                    <div class="mt-1 font-medium leading-7 text-stone-900">{{ selectedCatalogItem.ingredients }}</div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="self-start rounded-[1.5rem] bg-white p-5 ring-1 ring-stone-100">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Стоимость</div>
                        <div class="mt-3 text-3xl font-black tracking-[-0.04em] text-stone-950">
                            {{ formatPrice(selectedCatalogItem.price) }}
                        </div>
                        <p v-if="!isSelectedItemOrderable()" class="mt-3 text-sm leading-6 text-stone-600">
                            Эта позиция показывается в каталоге, но пока недоступна к заказу на завтра.
                        </p>
                        <div
                            v-if="isSelectedItemOrderable() && selectedItemCartQuantity > 0"
                            class="mt-4 inline-flex h-11 w-full items-center justify-center gap-3 rounded-full bg-stone-950 px-4 text-white"
                        >
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-full text-lg font-medium text-white transition hover:bg-white/15"
                                @click="updateSelectedItemQuantity(selectedItemCartQuantity - 1)"
                            >
                                -
                            </button>
                            <span class="min-w-8 text-center text-sm font-semibold text-white">
                                {{ selectedItemCartQuantity }}
                            </span>
                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-full text-lg font-medium text-white transition hover:bg-white/15"
                                @click="updateSelectedItemQuantity(selectedItemCartQuantity + 1)"
                            >
                                +
                            </button>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-full px-5 text-sm font-semibold transition"
                            :class="isSelectedItemOrderable()
                                ? 'bg-stone-950 text-white hover:bg-orange-600'
                                : 'cursor-not-allowed bg-stone-200 text-stone-500'"
                            :disabled="!isSelectedItemOrderable()"
                            @click="addSelectedItemToCart"
                        >
                            {{
                                isSelectedItemOrderable()
                                    ? (selectedCatalogItem.entityType === 'meal_set' ? 'Добавить набор в корзину' : 'Добавить в корзину')
                                    : unavailableCatalogItemLabel(selectedCatalogItem)
                            }}
                        </button>
                    </div>
                </div>
                </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
