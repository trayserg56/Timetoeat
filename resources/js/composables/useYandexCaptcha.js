let scriptPromise = null;
let widgetId = null;
let activeSiteKey = null;

function loadScript() {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Captcha is unavailable.'));
    }

    if (window.smartCaptcha) {
        return Promise.resolve();
    }

    if (!scriptPromise) {
        scriptPromise = new Promise((resolve, reject) => {
            window.__yandexCaptchaOnload = () => resolve();

            const script = document.createElement('script');
            script.src = 'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=__yandexCaptchaOnload';
            script.defer = true;
            script.onerror = () => reject(new Error('Не удалось загрузить Yandex SmartCaptcha.'));
            document.head.appendChild(script);
        });
    }

    return scriptPromise;
}

function ensureWidget(containerId, siteKey) {
    if (!window.smartCaptcha || !siteKey) {
        return null;
    }

    if (widgetId !== null && activeSiteKey === siteKey) {
        return widgetId;
    }

    try {
        widgetId = window.smartCaptcha.render(containerId, {
            sitekey: siteKey,
            invisible: true,
            hideShield: true,
        });
    } catch (error) {
        throw mapCaptchaError(error);
    }

    activeSiteKey = siteKey;

    return widgetId;
}

function mapCaptchaError(error) {
    const message = String(error?.message ?? error ?? '');

    if (message.includes('cannot be used in the host')) {
        return new Error('Проверка безопасности не настроена для этого домена. Оформите заказ через Telegram-бот или напишите нам.');
    }

    return error instanceof Error ? error : new Error(message || 'Не удалось пройти проверку безопасности.');
}

export function resetYandexCaptchaWidget() {
    if (typeof window !== 'undefined' && widgetId !== null && window.smartCaptcha?.reset) {
        window.smartCaptcha.reset(widgetId);
    }
}

export async function requestYandexCaptchaToken({ containerId, siteKey }) {
    if (!siteKey) {
        return null;
    }

    await loadScript();

    let id;

    try {
        id = ensureWidget(containerId, siteKey);
    } catch (error) {
        throw mapCaptchaError(error);
    }

    if (id === null) {
        throw new Error('Не удалось инициализировать Yandex SmartCaptcha.');
    }

    return new Promise((resolve, reject) => {
        const timeoutId = window.setTimeout(() => {
            reject(new Error('Истекло время ожидания проверки безопасности.'));
        }, 30000);

        const finish = (callback) => {
            window.clearTimeout(timeoutId);
            callback();
        };

        window.smartCaptcha.subscribe(id, 'success', (token) => {
            if (typeof token === 'string' && token.length > 0) {
                finish(() => resolve(token));

                return;
            }

            finish(() => reject(new Error('Не удалось получить токен проверки безопасности.')));
        });

        window.smartCaptcha.subscribe(id, 'network-error', () => {
            finish(() => reject(new Error('Ошибка сети при проверке безопасности.')));
        });

        window.smartCaptcha.execute(id);
    });
}
