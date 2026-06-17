export function phoneHref(phone) {
    if (!phone) {
        return null;
    }

    let digits = String(phone).replace(/\D/g, '');

    if (!digits) {
        return null;
    }

    if (digits.startsWith('8') && digits.length === 11) {
        digits = `7${digits.slice(1)}`;
    }

    if (!digits.startsWith('7') && digits.length === 10) {
        digits = `7${digits}`;
    }

    return `tel:+${digits}`;
}

export function mailtoHref(email) {
    const value = String(email ?? '').trim();

    return value ? `mailto:${value}` : null;
}

export function telegramHref(url, label) {
    const explicitUrl = String(url ?? '').trim();

    if (explicitUrl) {
        return explicitUrl;
    }

    const handle = String(label ?? '').trim().replace(/^@/, '');

    return handle ? `https://t.me/${handle}` : null;
}
