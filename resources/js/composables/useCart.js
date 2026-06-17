import { reactive } from 'vue';

export const cartApi = reactive({
    primaryCartGroup: null,
    cartGroupsDetailed: [],
    cartCount: 0,
    addToCart() {},
    updateQuantity() {},
    removeFromCart() {},
    removeFromAllGroups() {},
    addExistingItemToGroup() {},
    getGroupItemQuantity() {
        return 0;
    },
    getPrimaryGroupCartQuantity() {
        return 0;
    },
    openCart() {},
});

export function useCart() {
    return cartApi;
}
