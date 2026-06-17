import { describe, expect, it } from 'vitest';
import {
    isCatalogItemOrderable,
    normalizeCatalogItem,
    resolveCatalogImageLayout,
    unavailableCatalogItemLabel,
} from './catalogItem';

describe('isCatalogItemOrderable', () => {
    it('treats missing flag as orderable', () => {
        expect(isCatalogItemOrderable({ id: 1 })).toBe(true);
    });

    it('respects explicit false', () => {
        expect(isCatalogItemOrderable({ is_orderable: false })).toBe(false);
    });
});

describe('resolveCatalogImageLayout', () => {
    it('detects portrait images', () => {
        expect(resolveCatalogImageLayout(800, 1200)).toBe('portrait');
    });

    it('detects landscape images', () => {
        expect(resolveCatalogImageLayout(1200, 800)).toBe('landscape');
    });

    it('returns null for invalid dimensions', () => {
        expect(resolveCatalogImageLayout(0, 800)).toBeNull();
    });
});

describe('unavailableCatalogItemLabel', () => {
    it('uses meal set wording', () => {
        expect(unavailableCatalogItemLabel({ type: 'meal_set' }))
            .toBe('Недоступен к заказу на завтра');
    });

    it('uses product wording', () => {
        expect(unavailableCatalogItemLabel({ type: 'product' }))
            .toBe('Недоступно к заказу');
    });
});

describe('normalizeCatalogItem', () => {
    it('maps legacy type field to entityType', () => {
        expect(normalizeCatalogItem({ id: 3, type: 'product' })).toEqual({
            id: 3,
            type: 'product',
            entityType: 'product',
        });
    });
});
