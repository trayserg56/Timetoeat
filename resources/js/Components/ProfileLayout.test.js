import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import ProfileLayout from './ProfileLayout.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    usePage: () => ({
        props: {
            auth: { user: { name: 'Тест', email: 'test@example.com' } },
            flash: {},
        },
    }),
}));

vi.mock('./AppShell.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

vi.mock('./Breadcrumbs.vue', () => ({
    default: { template: '<nav />' },
}));

describe('ProfileLayout', () => {
    it('renders title and navigation links', () => {
        const wrapper = mount(ProfileLayout, {
            props: {
                title: 'Заказы',
                subtitle: 'История покупок',
                navigation: [
                    { key: 'overview', label: 'Обзор', href: '/profile', active: false },
                    { key: 'orders', label: 'Заказы', href: '/profile/orders', active: true },
                ],
            },
            slots: {
                default: '<p class="slot-content">Контент</p>',
            },
        });

        expect(wrapper.text()).toContain('Заказы');
        expect(wrapper.text()).toContain('История покупок');
        expect(wrapper.text()).toContain('Обзор');
        expect(wrapper.find('.slot-content').exists()).toBe(true);
    });
});
