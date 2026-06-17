<script setup>
import { requestYandexCaptchaToken, resetYandexCaptchaWidget } from '../composables/useYandexCaptcha';
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    initialMode: {
        type: String,
        default: 'login',
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const captchaConfig = computed(() => page.props.yandexCaptcha ?? { enabled: false, clientKey: null });
const captchaError = ref('');

const currentMode = ref(props.initialMode);

const isLoginMode = computed(() => currentMode.value === 'login');
const isRegisterMode = computed(() => currentMode.value === 'register');
const isForgotPasswordMode = computed(() => currentMode.value === 'forgot-password');
const isResetPasswordMode = computed(() => currentMode.value === 'reset-password');

const loginForm = useForm({
    email: '',
    password: '',
    'smart-token': '',
});

const forgotPasswordForm = useForm({
    email: '',
    'smart-token': '',
});

const resetPasswordForm = useForm({
    email: '',
    code: '',
    password: '',
    password_confirmation: '',
    'smart-token': '',
});

const registerForm = useForm({
    name: '',
    email: '',
    phone: '',
    telegram_username: '',
    password: '',
    password_confirmation: '',
    'smart-token': '',
});

watch(
    () => props.initialMode,
    (value) => {
        currentMode.value = value || 'login';
    },
    { immediate: true },
);

function switchMode(mode) {
    currentMode.value = mode;

    if (mode === 'forgot-password') {
        forgotPasswordForm.email = loginForm.email;
    }

    if (mode === 'login') {
        loginForm.password = '';
    }
}

function closeModal() {
    currentMode.value = 'login';
    emit('close');
}

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

function formatTelegramUsername(value) {
    const normalized = String(value ?? '').replace(/\s+/g, '').replace(/[^A-Za-z0-9_@]/g, '');

    if (normalized === '') {
        return '';
    }

    if (normalized.startsWith('@')) {
        return `@${normalized.slice(1).replace(/@/g, '').slice(0, 32)}`;
    }

    return `@${normalized.replace(/@/g, '').slice(0, 32)}`;
}

function handlePhoneInput(event) {
    registerForm.phone = formatRussianPhone(event.target.value);
}

function handleTelegramInput(event) {
    registerForm.telegram_username = formatTelegramUsername(event.target.value);
}

function handleResetCodeInput(event) {
    resetPasswordForm.code = String(event.target.value ?? '').replace(/\D/g, '').slice(0, 6);
}

async function attachCaptchaToken(form) {
    if (!captchaConfig.value.enabled) {
        return true;
    }

    captchaError.value = '';

    try {
        form['smart-token'] = await requestYandexCaptchaToken({
            containerId: 'auth-captcha-container',
            siteKey: captchaConfig.value.clientKey,
        });

        return true;
    } catch (error) {
        captchaError.value = error?.message || 'Не удалось пройти проверку безопасности. Попробуйте ещё раз.';

        return false;
    }
}

async function submitLogin() {
    if (!(await attachCaptchaToken(loginForm))) {
        return;
    }

    loginForm.post('/login', {
        onError: () => resetYandexCaptchaWidget(),
    });
}

async function submitForgotPassword() {
    if (!(await attachCaptchaToken(forgotPasswordForm))) {
        return;
    }

    forgotPasswordForm.post('/forgot-password', {
        errorBag: 'forgotPassword',
        preserveScroll: true,
        onSuccess: () => {
            resetPasswordForm.email = forgotPasswordForm.email;
            resetPasswordForm.code = '';
            resetPasswordForm.password = '';
            resetPasswordForm.password_confirmation = '';
            currentMode.value = 'reset-password';
        },
        onError: () => resetYandexCaptchaWidget(),
    });
}

async function submitResetPassword() {
    if (!(await attachCaptchaToken(resetPasswordForm))) {
        return;
    }

    resetPasswordForm.post('/reset-password', {
        errorBag: 'resetPassword',
        preserveScroll: true,
        onError: () => resetYandexCaptchaWidget(),
    });
}

async function submitRegister() {
    if (!(await attachCaptchaToken(registerForm))) {
        return;
    }

    registerForm.post('/register', {
        onError: () => resetYandexCaptchaWidget(),
    });
}
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-stone-950/55 px-4 py-8"
        >
            <div class="absolute inset-0" @click="emit('close')"></div>

            <div class="relative z-10 w-full max-w-2xl rounded-[2rem] bg-white/95 p-8 shadow-[0_30px_120px_rgba(28,25,23,0.28)] backdrop-blur">
                <button
                    type="button"
                    class="absolute right-5 top-5 inline-flex size-11 items-center justify-center rounded-full bg-white text-stone-500 shadow-sm transition hover:text-stone-900"
                    aria-label="Закрыть авторизацию"
                    @click="closeModal"
                >
                    <span class="text-xl leading-none">×</span>
                </button>

                <div class="max-w-xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-700">Аккаунт</p>
                    <h2 class="mt-3 text-4xl font-black tracking-[-0.04em]">
                        {{ isLoginMode ? 'Вход в профиль' : isRegisterMode ? 'Регистрация профиля' : isForgotPasswordMode ? 'Сброс пароля' : 'Новый пароль' }}
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-stone-600">
                        {{
                            isForgotPasswordMode
                                ? 'Введите email от аккаунта. Мы отправим шестизначный код, который нужно будет ввести здесь же.'
                                : isResetPasswordMode
                                    ? 'Проверьте почту, введите код из письма и сразу задайте новый пароль.'
                                    : 'Всё открывается поверх текущей страницы: можно быстро войти или создать аккаунт и продолжить заказ без лишнего перехода.'
                        }}
                    </p>
                </div>

                <div v-if="isLoginMode || isRegisterMode" class="mt-8 inline-flex rounded-full bg-stone-50 p-1 shadow-inner">
                    <button
                        type="button"
                        class="rounded-full px-5 py-3 text-sm font-semibold transition"
                        :class="isLoginMode ? 'bg-stone-950 text-white shadow-sm' : 'text-stone-600 hover:text-stone-950'"
                        @click="switchMode('login')"
                    >
                        Войти
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-5 py-3 text-sm font-semibold transition"
                        :class="!isLoginMode ? 'bg-stone-950 text-white shadow-sm' : 'text-stone-600 hover:text-stone-950'"
                        @click="switchMode('register')"
                    >
                        Зарегистрироваться
                    </button>
                </div>

                <div id="auth-captcha-container" class="sr-only" aria-hidden="true"></div>

                <p v-if="captchaConfig.enabled" class="mt-4 text-xs leading-5 text-stone-400">
                    Форма защищена сервисом
                    <a href="https://yandex.ru/legal/smartcaptcha_notice/" class="underline hover:text-stone-600" target="_blank" rel="noopener noreferrer">Yandex SmartCaptcha</a>.
                </p>

                <p v-if="captchaError" class="mt-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ captchaError }}
                </p>

                <form
                    v-if="isLoginMode"
                    class="mt-8 space-y-4"
                    @submit.prevent="submitLogin"
                >
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input
                            v-model="loginForm.email"
                            type="email"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Пароль</span>
                        <input
                            v-model="loginForm.password"
                            type="password"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <div v-if="loginForm.errors.email" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ loginForm.errors.email }}
                    </div>

                    <div class="flex items-center justify-between gap-4 text-sm">
                        <button
                            type="button"
                            class="font-semibold text-orange-700 transition hover:text-orange-600"
                            @click="switchMode('forgot-password')"
                        >
                            Забыли пароль?
                        </button>
                        <span class="text-stone-400">Восстановим по email</span>
                    </div>

                    <button
                        type="submit"
                        :disabled="loginForm.processing"
                        class="w-full rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300"
                    >
                        {{ loginForm.processing ? 'Входим...' : 'Войти' }}
                    </button>
                </form>

                <form
                    v-else-if="isRegisterMode"
                    class="mt-8 grid gap-4 sm:grid-cols-2"
                    @submit.prevent="submitRegister"
                >
                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Имя</span>
                        <input
                            v-model="registerForm.name"
                            type="text"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input
                            v-model="registerForm.email"
                            type="email"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Телефон</span>
                        <input
                            :value="registerForm.phone"
                            type="text"
                            inputmode="tel"
                            maxlength="18"
                            placeholder="+7 (999) 123-45-67"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            @input="handlePhoneInput"
                        />
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Telegram</span>
                        <input
                            :value="registerForm.telegram_username"
                            type="text"
                            maxlength="33"
                            placeholder="@username"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            @input="handleTelegramInput"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Пароль</span>
                        <input
                            v-model="registerForm.password"
                            type="password"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Подтверждение пароля</span>
                        <input
                            v-model="registerForm.password_confirmation"
                            type="password"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <div
                        v-if="Object.keys(registerForm.errors).length"
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:col-span-2"
                    >
                        <div v-for="(error, key) in registerForm.errors" :key="key">
                            {{ error }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="registerForm.processing"
                        class="w-full rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300 sm:col-span-2"
                    >
                        {{ registerForm.processing ? 'Создаём профиль...' : 'Создать аккаунт' }}
                    </button>
                </form>

                <form
                    v-else-if="isForgotPasswordMode"
                    class="mt-8 space-y-4"
                    @submit.prevent="submitForgotPassword"
                >
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input
                            v-model="forgotPasswordForm.email"
                            type="email"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <div v-if="forgotPasswordForm.errors.email" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ forgotPasswordForm.errors.email }}
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-full border border-stone-200 px-5 py-3 text-sm font-semibold text-stone-600 transition hover:border-stone-300 hover:text-stone-900"
                            @click="switchMode('login')"
                        >
                            Назад ко входу
                        </button>
                        <button
                            type="submit"
                            :disabled="forgotPasswordForm.processing"
                            class="rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300"
                        >
                            {{ forgotPasswordForm.processing ? 'Отправляем код...' : 'Получить код' }}
                        </button>
                    </div>
                </form>

                <form
                    v-else
                    class="mt-8 space-y-4"
                    @submit.prevent="submitResetPassword"
                >
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input
                            v-model="resetPasswordForm.email"
                            type="email"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Код из письма</span>
                        <input
                            :value="resetPasswordForm.code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="123456"
                            class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 tracking-[0.3em] outline-none transition focus:border-orange-500"
                            @input="handleResetCodeInput"
                        />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-stone-700">Новый пароль</span>
                            <input
                                v-model="resetPasswordForm.password"
                                type="password"
                                class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-stone-700">Подтверждение пароля</span>
                            <input
                                v-model="resetPasswordForm.password_confirmation"
                                type="password"
                                class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            />
                        </label>
                    </div>

                    <div
                        v-if="Object.keys(resetPasswordForm.errors).length"
                        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        <div v-for="(error, key) in resetPasswordForm.errors" :key="key">
                            {{ error }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-full border border-stone-200 px-5 py-3 text-sm font-semibold text-stone-600 transition hover:border-stone-300 hover:text-stone-900"
                            @click="switchMode('login')"
                        >
                            Назад ко входу
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-stone-200 px-5 py-3 text-sm font-semibold text-stone-600 transition hover:border-stone-300 hover:text-stone-900"
                            @click="switchMode('forgot-password')"
                        >
                            Запросить новый код
                        </button>
                        <button
                            type="submit"
                            :disabled="resetPasswordForm.processing"
                            class="rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:bg-stone-300"
                        >
                            {{ resetPasswordForm.processing ? 'Меняем пароль...' : 'Сменить пароль' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </transition>
</template>
