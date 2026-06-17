import { describe, expect, it } from 'vitest';
import { cartApi } from './useCart';

describe('cartApi defaults', () => {
    it('starts empty', () => {
        expect(cartApi.cartCount).toBe(0);
        expect(cartApi.cartGroupsDetailed).toEqual([]);
        expect(cartApi.primaryCartGroup).toBeNull();
    });

    it('returns zero quantity for unknown items', () => {
        expect(cartApi.getPrimaryGroupCartQuantity('product', 999)).toBe(0);
    });
});
