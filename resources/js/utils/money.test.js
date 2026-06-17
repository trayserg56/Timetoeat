import { describe, expect, it } from 'vitest';
import { formatPrice } from './money';

describe('formatPrice', () => {
    it('formats kopecks as whole rubles', () => {
        expect(formatPrice(46000)).toMatch(/460\s*₽/);
    });

    it('formats zero', () => {
        expect(formatPrice(0)).toMatch(/0\s*₽/);
    });
});
