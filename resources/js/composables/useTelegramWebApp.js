import { router } from '@inertiajs/vue3';

let initialized = false;

function getTelegramWebApp() {
    return window.Telegram?.WebApp ?? null;
}

export function isTelegramWebApp() {
    return Boolean(getTelegramWebApp()?.initData);
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
