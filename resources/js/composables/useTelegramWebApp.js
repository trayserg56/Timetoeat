import { router } from '@inertiajs/vue3';

let initialized = false;
let scriptLoadPromise = null;

const TELEGRAM_SCRIPT_SRC = 'https://telegram.org/js/telegram-web-app.js';

function getTelegramWebApp() {
    return window.Telegram?.WebApp ?? null;
}

export function isLikelyTelegramWebAppContext() {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return /Telegram/i.test(navigator.userAgent);
}

export function loadTelegramWebAppScript() {
    if (typeof window === 'undefined') {
        return Promise.resolve(null);
    }

    const existing = getTelegramWebApp();

    if (existing) {
        return Promise.resolve(existing);
    }

    if (! isLikelyTelegramWebAppContext()) {
        return Promise.resolve(null);
    }

    if (scriptLoadPromise) {
        return scriptLoadPromise;
    }

    scriptLoadPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = TELEGRAM_SCRIPT_SRC;
        script.async = true;
        script.onload = () => resolve(getTelegramWebApp());
        script.onerror = () => reject(new Error('Failed to load Telegram WebApp SDK'));
        document.head.appendChild(script);
    });

    return scriptLoadPromise;
}

export function isTelegramWebApp() {
    const tg = getTelegramWebApp();

    if (! tg) {
        return isLikelyTelegramWebAppContext();
    }

    if (tg.initData) {
        return true;
    }

    return typeof tg.platform === 'string' && tg.platform !== 'unknown';
}

export function shouldSkipCheckoutCaptcha(authUser = null) {
    if (isTelegramWebApp()) {
        return true;
    }

    return Boolean(authUser?.telegram_id);
}

export function getTelegramInitData() {
    return getTelegramWebApp()?.initData ?? '';
}

export function getTelegramUser() {
    const user = getTelegramWebApp()?.initDataUnsafe?.user;

    if (! user) {
        return null;
    }

    return {
        id: user.id,
        firstName: user.first_name ?? '',
        lastName: user.last_name ?? '',
        username: user.username ? `@${user.username}` : `@tg${user.id}`,
    };
}

export async function ensureTelegramWebAppReady() {
    await loadTelegramWebAppScript();

    return initTelegramWebApp();
}

export function initTelegramWebApp() {
    if (initialized || typeof window === 'undefined') {
        return getTelegramWebApp();
    }

    const tg = getTelegramWebApp();

    if (! tg?.initData) {
        return null;
    }

    initialized = true;

    tg.ready();
    tg.expand();
    tg.enableClosingConfirmation();

    if (typeof tg.setHeaderColor === 'function') {
        tg.setHeaderColor('#fff8f1');
    }

    if (typeof tg.setBackgroundColor === 'function') {
        tg.setBackgroundColor('#fff8f1');
    }

    document.documentElement.classList.add('telegram-webapp');
    document.body.classList.add('telegram-webapp');

    return tg;
}

export async function authenticateTelegramWebApp() {
    await loadTelegramWebAppScript();

    const initData = getTelegramInitData();

    if (! initData || typeof window === 'undefined') {
        return false;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (! csrfToken) {
        return false;
    }

    const response = await fetch('/auth/telegram/webapp', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ init_data: initData }),
    });

    if (! response.ok) {
        return false;
    }

    await router.reload({ only: ['auth'] });

    return true;
}
