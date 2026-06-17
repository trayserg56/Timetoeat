let initialized = false;

function getTelegramWebApp() {
    return window.Telegram?.WebApp ?? null;
}

export function isTelegramWebApp() {
    return Boolean(getTelegramWebApp()?.initData);
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
        username: user.username ? `@${user.username}` : '',
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
