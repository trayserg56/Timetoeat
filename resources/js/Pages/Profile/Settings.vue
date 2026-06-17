<script setup>
import ProfileLayout from '../../Components/ProfileLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    navigation: {
        type: Array,
        default: () => [],
    },
    profile: {
        type: Object,
        required: true,
    },
});

const profileForm = useForm({
    name: props.profile.name ?? '',
    email: props.profile.email ?? '',
    phone: formatRussianPhone(props.profile.phone ?? ''),
    telegram_username: props.profile.telegram_username ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const preferencesForm = useForm({
    saved_delivery_addresses: normalizePresets(props.profile.saved_delivery_addresses),
    saved_delivery_comments: normalizePresets(props.profile.saved_delivery_comments),
});

const hasPreferenceErrors = computed(() => Object.keys(preferencesForm.errors).length > 0);

function formatRussianPhone(value) {
    const digits = String(value ?? '').replace(/\D/g, '');
    let normalizedDigits = digits;

    if (normalizedDigits.startsWith('8')) {
        normalizedDigits = `7${normalizedDigits.slice(1)}`;
    }

    if (!normalizedDigits.startsWith('7')) {
        normalizedDigits = `7${normalizedDigits}`;
    }

    const limitedDigits = normalizedDigits.slice(0, 11);
    const phone = limitedDigits.slice(1);

    if (!phone.length) {
        return '+7';
    }

    const parts = [
        phone.slice(0, 3),
        phone.slice(3, 6),
        phone.slice(6, 8),
        phone.slice(8, 10),
    ].filter(Boolean);

    let formatted = `+7 (${parts[0] ?? ''}`;

    if (phone.length >= 3) {
        formatted += ')';
    }

    if (parts[1]) {
        formatted += ` ${parts[1]}`;
    }

    if (parts[2]) {
        formatted += `-${parts[2]}`;
    }

    if (parts[3]) {
        formatted += `-${parts[3]}`;
    }

    return formatted;
}

function handlePhoneInput(event) {
    profileForm.phone = formatRussianPhone(event.target.value);
}

function normalizePresets(presets) {
    if (!Array.isArray(presets)) {
        return [];
    }

    return presets.map((preset, index) => ({
        id: preset?.id || `preset-${index}-${Math.random().toString(36).slice(2, 8)}`,
        label: preset?.label || '',
        value: preset?.value || '',
    }));
}

function createPreset() {
    return {
        id: `preset-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        label: '',
        value: '',
    };
}

function addAddressPreset() {
    preferencesForm.saved_delivery_addresses.push(createPreset());
}

function removeAddressPreset(index) {
    preferencesForm.saved_delivery_addresses.splice(index, 1);
}

function addCommentPreset() {
    preferencesForm.saved_delivery_comments.push(createPreset());
}

function removeCommentPreset(index) {
    preferencesForm.saved_delivery_comments.splice(index, 1);
}

function submitProfile() {
    profileForm.patch('/profile');
}

function submitPassword() {
    passwordForm.put('/profile/password', {
        errorBag: 'passwordUpdate',
        onSuccess: () => passwordForm.reset(),
    });
}

function submitPreferences() {
    preferencesForm.patch('/profile/order-preferences');
}
</script>

<template>
    <ProfileLayout
        title="Настройки профиля"
        subtitle="Обновите контактные данные и пароль. Эти данные будут подставляться в новые заказы."
        :navigation="navigation"
        :breadcrumbs="[
            { label: 'Главная', href: '/' },
            { label: 'Личный кабинет', href: '/profile' },
            { label: 'Настройки профиля' },
        ]"
    >
        <section class="grid min-w-0 gap-6 xl:grid-cols-2">
            <div class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Контакты</div>
                <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">Профиль</h2>

                <form class="mt-6 space-y-4" @submit.prevent="submitProfile">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Имя</span>
                        <input v-model="profileForm.name" type="text" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input v-model="profileForm.email" type="email" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Телефон</span>
                        <input :value="profileForm.phone" type="text" inputmode="tel" maxlength="18" placeholder="+7 (999) 123-45-67" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" @input="handlePhoneInput" />
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Telegram</span>
                        <input v-model="profileForm.telegram_username" type="text" placeholder="@username" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>

                    <div v-if="Object.keys(profileForm.errors).length" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div v-for="(error, key) in profileForm.errors" :key="key">{{ error }}</div>
                    </div>

                    <button type="submit" :disabled="profileForm.processing" class="w-full rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300">
                        {{ profileForm.processing ? 'Сохраняем...' : 'Сохранить профиль' }}
                    </button>
                </form>
            </div>

            <div class="min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Безопасность</div>
                <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">Пароль</h2>

                <form class="mt-6 space-y-4" @submit.prevent="submitPassword">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Текущий пароль</span>
                        <input v-model="passwordForm.current_password" type="password" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Новый пароль</span>
                        <input v-model="passwordForm.password" type="password" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Подтверждение</span>
                        <input v-model="passwordForm.password_confirmation" type="password" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>

                    <div v-if="Object.keys(passwordForm.errors).length" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div v-for="(error, key) in passwordForm.errors" :key="key">{{ error }}</div>
                    </div>

                    <button type="submit" :disabled="passwordForm.processing" class="w-full rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300">
                        {{ passwordForm.processing ? 'Обновляем...' : 'Обновить пароль' }}
                    </button>
                </form>
            </div>
        </section>

        <section class="mt-6 min-w-0 rounded-[2rem] bg-white p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-700">Шаблоны</div>
            <h2 class="mt-3 text-2xl font-black tracking-[-0.04em] text-stone-950 sm:text-3xl lg:text-4xl">Сохранённые адреса и комментарии</h2>
            <p class="mt-3 max-w-3xl text-base leading-7 text-stone-600">
                Эти данные можно быстро подставлять в новые заказы прямо из корзины.
            </p>

            <form class="mt-8 space-y-8" @submit.prevent="submitPreferences">
                <div class="grid min-w-0 gap-8 xl:grid-cols-2">
                    <section>
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-2xl font-black tracking-[-0.03em] text-stone-950">Адреса</h3>
                            <button type="button" class="rounded-full bg-stone-100 px-4 py-2 text-sm font-semibold transition hover:bg-stone-200" @click="addAddressPreset">
                                + Добавить адрес
                            </button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <article
                                v-for="(preset, index) in preferencesForm.saved_delivery_addresses"
                                :key="preset.id"
                                class="rounded-[1.6rem] bg-stone-50 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Адрес {{ index + 1 }}</div>
                                    <button type="button" class="rounded-full bg-white px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" @click="removeAddressPreset(index)">
                                        Удалить
                                    </button>
                                </div>
                                <div class="mt-4 space-y-4">
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-stone-700">Короткое название</span>
                                        <input v-model="preset.label" type="text" placeholder="Например, Дом или Офис" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                                    </label>
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-stone-700">Адрес</span>
                                        <textarea v-model="preset.value" rows="3" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500"></textarea>
                                    </label>
                                </div>
                            </article>
                            <div v-if="!preferencesForm.saved_delivery_addresses.length" class="rounded-[1.6rem] bg-stone-50 px-5 py-8 text-sm text-stone-500">
                                Пока нет сохранённых адресов.
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-2xl font-black tracking-[-0.03em] text-stone-950">Комментарии</h3>
                            <button type="button" class="rounded-full bg-stone-100 px-4 py-2 text-sm font-semibold transition hover:bg-stone-200" @click="addCommentPreset">
                                + Добавить комментарий
                            </button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <article
                                v-for="(preset, index) in preferencesForm.saved_delivery_comments"
                                :key="preset.id"
                                class="rounded-[1.6rem] bg-stone-50 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Комментарий {{ index + 1 }}</div>
                                    <button type="button" class="rounded-full bg-white px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-100" @click="removeCommentPreset(index)">
                                        Удалить
                                    </button>
                                </div>
                                <div class="mt-4 space-y-4">
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-stone-700">Короткое название</span>
                                        <input v-model="preset.label" type="text" placeholder="Например, Для курьера" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                                    </label>
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-medium text-stone-700">Комментарий</span>
                                        <textarea v-model="preset.value" rows="3" class="w-full rounded-2xl border border-[#ead9c3] bg-white px-4 py-3 outline-none transition focus:border-orange-500"></textarea>
                                    </label>
                                </div>
                            </article>
                            <div v-if="!preferencesForm.saved_delivery_comments.length" class="rounded-[1.6rem] bg-stone-50 px-5 py-8 text-sm text-stone-500">
                                Пока нет сохранённых комментариев.
                            </div>
                        </div>
                    </section>
                </div>

                <div v-if="hasPreferenceErrors" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div v-for="(error, key) in preferencesForm.errors" :key="key">{{ error }}</div>
                </div>

                <button type="submit" :disabled="preferencesForm.processing" class="rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300">
                    {{ preferencesForm.processing ? 'Сохраняем...' : 'Сохранить шаблоны' }}
                </button>
            </form>
        </section>

        <section class="rounded-[2rem] bg-stone-50 p-5 shadow-[0_20px_60px_rgba(28,25,23,0.06)] ring-1 ring-stone-100 sm:p-8">
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Аккаунт</div>
            <div class="mt-3 text-lg font-semibold text-stone-900">{{ profile.name }}</div>
            <div class="mt-1 text-sm text-stone-500">{{ profile.email }}</div>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-4 rounded-full bg-white px-6 py-3 text-sm font-semibold shadow-sm transition hover:bg-stone-100"
            >
                Выйти
            </Link>
        </section>
    </ProfileLayout>
</template>
