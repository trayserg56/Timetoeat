import { describe, expect, it } from 'vitest';
import { mailtoHref, phoneHref, telegramHref } from './contacts';

describe('phoneHref', () => {
    it('builds tel link from formatted number', () => {
        expect(phoneHref('+7 (999) 000-00-01')).toBe('tel:+79990000001');
    });
});

describe('mailtoHref', () => {
    it('builds mailto link', () => {
        expect(mailtoHref('hello@food-delivery.local')).toBe('mailto:hello@food-delivery.local');
    });
});

describe('telegramHref', () => {
    it('uses explicit url from admin', () => {
        expect(telegramHref('https://t.me/my_channel', '@ignored')).toBe('https://t.me/my_channel');
    });

    it('falls back to username', () => {
        expect(telegramHref('', '@food_delivery')).toBe('https://t.me/food_delivery');
    });
});
