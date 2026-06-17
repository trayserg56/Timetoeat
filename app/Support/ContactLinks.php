<?php

namespace App\Support;

class ContactLinks
{
    public static function phoneHref(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '8') && strlen($digits) === 11) {
            $digits = '7'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '7') && strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        return 'tel:+'.$digits;
    }

    public static function mailtoHref(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email !== '' ? "mailto:{$email}" : null;
    }

    public static function telegramHref(?string $url, ?string $label): ?string
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return $url;
        }

        $handle = ltrim(trim((string) $label), '@');

        return $handle !== '' ? "https://t.me/{$handle}" : null;
    }

    /**
     * @return array{
     *     phone: ?string,
     *     phone_href: ?string,
     *     email: ?string,
     *     email_href: ?string,
     *     telegram: ?string,
     *     telegram_href: ?string,
     *     footer_description: ?string,
     * }
     */
    public static function fromSiteSetting(\App\Models\SiteSetting $settings): array
    {
        return [
            'phone' => $settings->contact_phone,
            'phone_href' => self::phoneHref($settings->contact_phone),
            'email' => $settings->contact_email,
            'email_href' => self::mailtoHref($settings->contact_email),
            'telegram' => $settings->contact_telegram,
            'telegram_href' => self::telegramHref($settings->contact_telegram_url, $settings->contact_telegram),
            'footer_description' => $settings->footer_description,
        ];
    }
}
