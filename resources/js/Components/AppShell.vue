<script setup>
import AuthModal from './AuthModal.vue';
import { cartApi } from '../composables/useCart';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const siteNavigation = computed(() => page.props.siteNavigation ?? []);
const siteContacts = computed(() => page.props.siteContacts ?? {});
const flashSuccess = computed(() => page.props.flash?.success);
const flashOrder = computed(() => page.props.flash?.order);
const flashRepeatOrder = computed(() => page.props.flash?.repeat_order);
const pageUrl = computed(() => page.url ?? '');
const checkoutSettings = computed(() => page.props.checkoutSettings ?? {});
const cartCatalogItems = computed(() => page.props.cartCatalogItems ?? []);
const cartMenuDate = computed(() => page.props.cartMenuDate ?? '');
const savedDeliveryAddressPresets = computed(() => authUser.value?.saved_delivery_addresses ?? []);
const savedDeliveryCommentPresets = computed(() => authUser.value?.saved_delivery_comments ?? []);

const toast = ref({ visible: false, message: '' });
const isAuthModalOpen = ref(false);
const authModalMode = ref('login');
const isMobileMenuOpen = ref(false);
const isCartOpen = ref(false);
const cartGroups = ref([]);
const activeCartGroupId = ref(null);
const isSelectedAddressCompositionOpen = ref(false);
const showOrderModal = ref(false);

let toastTimeout = null;
const storageKey = 'food-delivery-cart';

const deliveryWindowLabel = computed(() => {
    if (!cartMenuDate.value) {
        return '';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        timeZone: 'Europe/Moscow',
    }).format(new Date(`${cartMenuDate.value}T12:00:00+03:00`));
});

const checkoutForm = useForm({
    customer_name: '',
    customer_phone: '',
    customer_telegram_username: '',
    customer_email: '',
    receipt: null,
    order_groups: [],
});

const orderableItems = computed(() =>
    cartCatalogItems.value.filter((item) => item.is_orderable !== false),
);

function createCartGroup(overrides = {}) {
    return {
        id: `group-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`,
        delivery_address: '',
        customer_comment: '',
        items: [],
        ...overrides,
    };
}

function sanitizeCartItems(items) {
    if (!Array.isArray(items)) {
        return [];
    }

    return items
        .map((item) => ({
            type: item?.type,
            id: Number(item?.id),
            quantity: Math.min(Math.max(Number(item?.quantity) || 0, 0), 99),
        }))
        .filter((item) => ['product', 'meal_set'].includes(item.type) && item.id > 0 && item.quantity > 0);
}

function pruneUnavailableItemsFromGroups(groups) {
    if (!Array.isArray(groups)) {
        return [createCartGroup()];
    }

    const allowedItems = new Set(
        orderableItems.value.map((item) => `${item.entityType}:${item.id}`),
    );

    const nextGroups = groups.map((group) => createCartGroup({
        id: typeof group?.id === 'string' && group.id !== '' ? group.id : undefined,
        delivery_address: typeof group?.delivery_address === 'string' ? group.delivery_address : '',
        customer_comment: typeof group?.customer_comment === 'string' ? group.customer_comment : '',
        items: sanitizeCartItems(group?.items).filter((item) => allowedItems.has(`${item.type}:${item.id}`)),
    }));

    return nextGroups.length ? nextGroups : [createCartGroup()];
}

function normalizeStoredCart(payload) {
    if (Array.isArray(payload)) {
        return pruneUnavailableItemsFromGroups([createCartGroup({ items: sanitizeCartItems(payload) })]);
    }

    if (!payload || typeof payload !== 'object' || !Array.isArray(payload.groups)) {
        return [createCartGroup()];
    }

    const groups = pruneUnavailableItemsFromGroups(payload.groups);

    return groups.length ? groups : [createCartGroup()];
}

function getCartGroupLabelByIndex(index) {
    return `Адрес ${index + 1}`;
}

function getCartGroupIndex(groupId) {
    return cartGroups.value.findIndex((group) => group.id === groupId);
}

function getCartGroupLabel(groupId) {
    const index = getCartGroupIndex(groupId);

    return getCartGroupLabelByIndex(index >= 0 ? index : 0);
}

function resolveDetailedItems(items) {
    return items
        .map((cartItem) => {
            const item = orderableItems.value.find(
                (candidate) => candidate.entityType === cartItem.type && candidate.id === cartItem.id,
            ) ?? cartCatalogItems.value.find(
                (candidate) => candidate.entityType === cartItem.type && candidate.id === cartItem.id,
            );

            if (!item) {
                return null;
            }

            return {
                ...item,
                quantity: cartItem.quantity,
                lineTotal: item.price * cartItem.quantity,
            };
        })
        .filter(Boolean);
}

const cartGroupsDetailed = computed(() =>
    cartGroups.value.map((group, index) => {
        const itemsDetailed = resolveDetailedItems(group.items);
        const subtotal = itemsDetailed.reduce((total, item) => total + item.lineTotal, 0);
        const mealSetCount = itemsDetailed
            .filter((item) => item.entityType === 'meal_set')
            .reduce((total, item) => total + item.quantity, 0);
        const deliveryPrice = itemsDetailed.length
            ? (mealSetCount >= (checkoutSettings.value.free_delivery_meal_set_quantity ?? 5)
                ? 0
                : (checkoutSettings.value.delivery_price ?? 0))
            : 0;

        return {
            ...group,
            index,
            label: getCartGroupLabelByIndex(index),
            itemsDetailed,
            itemCount: itemsDetailed.reduce((total, item) => total + item.quantity, 0),
            subtotal,
            mealSetCount,
            deliveryPrice,
            total: subtotal + deliveryPrice,
        };
    }),
);

const cartItemsDetailed = computed(() =>
    cartCatalogItems.value
        .map((item) => {
            const distributions = cartGroupsDetailed.value
                .map((group) => {
                    const quantity = getGroupItemQuantity(group.id, item.entityType, item.id);

                    if (!quantity) {
                        return null;
                    }

                    return {
                        id: group.id,
                        label: group.label,
                        quantity,
                        lineTotal: item.price * quantity,
                    };
                })
                .filter(Boolean);

            if (!distributions.length) {
                return null;
            }

            return {
                ...item,
                quantity: distributions.reduce((total, group) => total + group.quantity, 0),
                lineTotal: distributions.reduce((total, group) => total + group.lineTotal, 0),
                distributions,
            };
        })
        .filter(Boolean),
);

const activeCartGroup = computed(() =>
    cartGroups.value.find((group) => group.id === activeCartGroupId.value) ?? cartGroups.value[0] ?? null,
);

const activeCartGroupIndex = computed(() =>
    activeCartGroup.value ? getCartGroupIndex(activeCartGroup.value.id) : 0,
);

const activeCartGroupDetailed = computed(() =>
    cartGroupsDetailed.value.find((group) => group.id === activeCartGroupId.value) ?? cartGroupsDetailed.value[0] ?? null,
);

const primaryCartGroup = computed(() => cartGroups.value[0] ?? null);

const cartCount = computed(() =>
    cartGroupsDetailed.value.reduce((total, group) => total + group.itemCount, 0),
);

const cartSubtotal = computed(() =>
    cartGroupsDetailed.value.reduce((total, group) => total + group.subtotal, 0),
);

const cartMealSetCount = computed(() =>
    cartGroupsDetailed.value.reduce((total, group) => total + group.mealSetCount, 0),
);

const deliveryPrice = computed(() =>
    cartGroupsDetailed.value.reduce((total, group) => total + group.deliveryPrice, 0),
);

const cartTotal = computed(() => cartSubtotal.value + deliveryPrice.value);

const canSubmit = computed(() =>
    cartGroupsDetailed.value.some((group) => group.itemCount > 0) && !checkoutForm.processing,
);

function getGroupItemQuantity(groupId, type, id) {
    const group = cartGroups.value.find((entry) => entry.id === groupId);

    return group?.items.find((item) => item.type === type && item.id === id)?.quantity ?? 0;
}

function getCartQuantity(type, id) {
    return cartGroups.value.reduce(
        (total, group) => total + (group.items.find((item) => item.type === type && item.id === id)?.quantity ?? 0),
        0,
    );
}

function getPrimaryGroupCartQuantity(type, id) {
    return primaryCartGroup.value ? getGroupItemQuantity(primaryCartGroup.value.id, type, id) : 0;
}

function ensureCartGroups() {
    if (cartGroups.value.length) {
        if (!activeCartGroupId.value) {
            activeCartGroupId.value = cartGroups.value[0].id;
        }

        return;
    }

    const initialGroup = createCartGroup();
    cartGroups.value = [initialGroup];
    activeCartGroupId.value = initialGroup.id;
}

function addToCart(type, id, groupId = primaryCartGroup.value?.id ?? activeCartGroup.value?.id) {
    ensureCartGroups();

    const targetGroup = cartGroups.value.find((group) => group.id === groupId)
        ?? primaryCartGroup.value
        ?? activeCartGroup.value
        ?? cartGroups.value[0];

    const existingItem = targetGroup.items.find((item) => item.type === type && item.id === id);
    const catalogItem = orderableItems.value.find((item) => item.entityType === type && item.id === id)
        ?? cartCatalogItems.value.find((item) => item.entityType === type && item.id === id);

    if (!catalogItem) {
        showAppToast('Эта позиция пока недоступна к заказу на завтра.');

        return;
    }

    const itemName = catalogItem?.name ?? 'Позиция';
    const groupLabel = getCartGroupLabel(targetGroup.id);

    if (existingItem) {
        existingItem.quantity += 1;
        showAppToast(`${itemName} добавлен в ${groupLabel}.`);

        return;
    }

    targetGroup.items.push({ type, id, quantity: 1 });
    showAppToast(`${itemName} добавлен в ${groupLabel}.`);
}

function addExistingItemToGroup(type, id, targetGroupId) {
    const catalogItem = orderableItems.value.find((item) => item.entityType === type && item.id === id)
        ?? cartCatalogItems.value.find((item) => item.entityType === type && item.id === id);

    if (!catalogItem) {
        showAppToast('Эта позиция пока недоступна к заказу на завтра.');

        return;
    }

    addToCart(type, id, targetGroupId);
}

function updateQuantity(type, id, quantity, groupId = activeCartGroup.value?.id) {
    const nextQuantity = Math.max(0, Number(quantity) || 0);
    const group = cartGroups.value.find((entry) => entry.id === groupId);
    const item = group?.items.find((entry) => entry.type === type && entry.id === id);

    if (!item) {
        return;
    }

    if (nextQuantity === 0) {
        removeFromCart(type, id, groupId);

        return;
    }

    item.quantity = Math.min(nextQuantity, 99);
}

function removeFromCart(type, id, groupId = activeCartGroup.value?.id) {
    const group = cartGroups.value.find((entry) => entry.id === groupId);
    const catalogItem = cartCatalogItems.value.find((item) => item.entityType === type && item.id === id);

    if (!group) {
        return;
    }

    group.items = group.items.filter((item) => !(item.type === type && item.id === id));

    if (catalogItem) {
        showAppToast(`${catalogItem.name} убран из ${getCartGroupLabel(groupId)}.`);
    }
}

function removeFromAllGroups(type, id) {
    cartGroups.value.forEach((group) => {
        group.items = group.items.filter((item) => !(item.type === type && item.id === id));
    });

    const catalogItem = cartCatalogItems.value.find((item) => item.entityType === type && item.id === id);

    if (catalogItem) {
        showAppToast(`${catalogItem.name} убран из заказа.`);
    }
}

function mergeItemsIntoGroup(targetGroupId, items) {
    const targetGroup = cartGroups.value.find((group) => group.id === targetGroupId);

    if (!targetGroup) {
        return;
    }

    sanitizeCartItems(items).forEach((item) => {
        const existingItem = targetGroup.items.find((entry) => entry.type === item.type && entry.id === item.id);

        if (existingItem) {
            existingItem.quantity = Math.min(existingItem.quantity + item.quantity, 99);

            return;
        }

        targetGroup.items.push({ ...item });
    });
}

function removeCartGroup(groupId) {
    ensureCartGroups();

    if (cartGroups.value.length === 1) {
        cartGroups.value = [createCartGroup()];
        activeCartGroupId.value = cartGroups.value[0].id;
        showAppToast('Корзина очищена.');

        return;
    }

    const groupIndex = getCartGroupIndex(groupId);

    if (groupIndex === -1) {
        return;
    }

    const removedGroupLabel = getCartGroupLabelByIndex(groupIndex);
    const [removedGroup] = cartGroups.value.splice(groupIndex, 1);
    const nextActiveGroup = cartGroups.value[Math.max(0, groupIndex - 1)] ?? cartGroups.value[0] ?? null;

    if (removedGroup?.items?.length) {
        mergeItemsIntoGroup(nextActiveGroup?.id, removedGroup.items);
        showAppToast(`${removedGroupLabel} объединён с другим адресом.`);
    }

    activeCartGroupId.value = nextActiveGroup?.id ?? null;
}

function addCartGroup() {
    ensureCartGroups();

    const group = createCartGroup();
    cartGroups.value.push(group);
    activeCartGroupId.value = group.id;
    showAppToast(`${getCartGroupLabel(group.id)} добавлен в заказ как отдельный адрес.`);
}

function setActiveCartGroup(groupId) {
    activeCartGroupId.value = groupId;
}

function toggleSelectedAddressComposition() {
    isSelectedAddressCompositionOpen.value = !isSelectedAddressCompositionOpen.value;
}

function mergeCartItems(items) {
    ensureCartGroups();

    items.forEach((repeatItem) => {
        const catalogItem = cartCatalogItems.value.find(
            (item) => item.entityType === repeatItem.type && item.id === repeatItem.id,
        );

        if (!catalogItem || catalogItem.is_orderable === false) {
            return;
        }

        const existingItem = cartGroups.value[0].items.find((item) => item.type === repeatItem.type && item.id === repeatItem.id);

        if (existingItem) {
            existingItem.quantity = Math.min(existingItem.quantity + repeatItem.quantity, 99);

            return;
        }

        cartGroups.value[0].items.push({
            type: repeatItem.type,
            id: repeatItem.id,
            quantity: Math.min(repeatItem.quantity, 99),
        });
    });

    activeCartGroupId.value = cartGroups.value[0].id;
}

function mergeCartGroups(groups) {
    ensureCartGroups();

    if (!Array.isArray(groups) || !groups.length) {
        return;
    }

    const normalizedGroups = groups
        .map((group) => ({
            delivery_address: typeof group?.delivery_address === 'string' ? group.delivery_address : '',
            customer_comment: typeof group?.customer_comment === 'string' ? group.customer_comment : '',
            items: sanitizeCartItems(group?.items),
        }))
        .filter((group) => group.items.length > 0);

    if (!normalizedGroups.length) {
        return;
    }

    const firstGroup = cartGroups.value[0];

    if (firstGroup && firstGroup.items.length === 0 && firstGroup.delivery_address === '' && firstGroup.customer_comment === '') {
        firstGroup.delivery_address = normalizedGroups[0].delivery_address;
        firstGroup.customer_comment = normalizedGroups[0].customer_comment;
        mergeItemsIntoGroup(firstGroup.id, normalizedGroups[0].items);
        normalizedGroups.shift();
    }

    normalizedGroups.forEach((group) => {
        const nextGroup = createCartGroup({
            delivery_address: group.delivery_address,
            customer_comment: group.customer_comment,
            items: [...group.items],
        });

        cartGroups.value.push(nextGroup);
    });

    activeCartGroupId.value = cartGroups.value[0]?.id ?? null;
}

function removeUnavailableItemsFromCart() {
    if (!orderableItems.value.length) {
        return;
    }

    const previousCount = cartGroups.value.reduce(
        (total, group) => total + group.items.reduce((itemsTotal, item) => itemsTotal + item.quantity, 0),
        0,
    );

    const nextGroups = pruneUnavailableItemsFromGroups(cartGroups.value);
    const nextCount = nextGroups.reduce(
        (total, group) => total + group.items.reduce((itemsTotal, item) => itemsTotal + item.quantity, 0),
        0,
    );

    cartGroups.value = nextGroups;

    if (!cartGroups.value.some((group) => group.id === activeCartGroupId.value)) {
        activeCartGroupId.value = cartGroups.value[0]?.id ?? null;
    }

    if (nextCount < previousCount) {
        showAppToast('Недоступные на завтра позиции убраны из корзины.');
    }
}

function hasSavedAddressPreset(value) {
    const normalizedValue = String(value ?? '').trim().replace(/\s+/g, ' ');

    if (!normalizedValue) {
        return false;
    }

    return savedDeliveryAddressPresets.value.some(
        (preset) => String(preset?.value ?? '').trim().replace(/\s+/g, ' ') === normalizedValue,
    );
}

function hasSavedCommentPreset(value) {
    const normalizedValue = String(value ?? '').trim().replace(/\s+/g, ' ');

    if (!normalizedValue) {
        return false;
    }

    return savedDeliveryCommentPresets.value.some(
        (preset) => String(preset?.value ?? '').trim().replace(/\s+/g, ' ') === normalizedValue,
    );
}

const canSaveActiveAddressPreset = computed(() =>
    Boolean(authUser.value && activeCartGroup.value?.delivery_address?.trim() && !hasSavedAddressPreset(activeCartGroup.value.delivery_address)),
);

const canSaveActiveCommentPreset = computed(() =>
    Boolean(authUser.value && activeCartGroup.value?.customer_comment?.trim() && !hasSavedCommentPreset(activeCartGroup.value.customer_comment)),
);

function applySavedAddressPreset(preset) {
    if (!activeCartGroup.value) {
        return;
    }

    activeCartGroup.value.delivery_address = preset?.value ?? '';
}

function applySavedCommentPreset(preset) {
    if (!activeCartGroup.value) {
        return;
    }

    activeCartGroup.value.customer_comment = preset?.value ?? '';
}

function saveActiveAddressPreset() {
    if (!canSaveActiveAddressPreset.value) {
        return;
    }

    router.post('/profile/order-preferences/preset', {
        kind: 'delivery_address',
        value: activeCartGroup.value.delivery_address,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isCartOpen.value = true;
        },
    });
}

function saveActiveCommentPreset() {
    if (!canSaveActiveCommentPreset.value) {
        return;
    }

    router.post('/profile/order-preferences/preset', {
        kind: 'delivery_comment',
        value: activeCartGroup.value.customer_comment,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isCartOpen.value = true;
        },
    });
}

function formatIsItemSplitAcrossGroups(type, id) {
    const distributions = cartGroupsDetailed.value
        .map((group) => ({ id: group.id, label: group.label, quantity: getGroupItemQuantity(group.id, type, id) }))
        .filter((group) => group.quantity > 0);

    return distributions.length > 1;
}

function formatItemDistribution(type, id) {
    return cartGroupsDetailed.value
        .map((group) => ({ label: group.label, quantity: getGroupItemQuantity(group.id, type, id) }))
        .filter((group) => group.quantity > 0)
        .map((group) => `${group.label}: ${group.quantity}`)
        .join(' · ');
}

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0,
    }).format(value / 100);
}

function formatRussianPhone(value) {
    const digits = String(value ?? '').replace(/\D/g, '');
    let normalizedDigits = digits;

    if (normalizedDigits.startsWith('8')) {
        normalizedDigits = `7${normalizedDigits.slice(1)}`;
    }

    if (!normalizedDigits.startsWith('7')) {
        normalizedDigits = `7${normalizedDigits}`;
    }

    const limitedDigits = normalizedDigits.slice(0, 11);
    const phone = limitedDigits.slice(1);

    if (!phone.length) {
        return '+7';
    }

    const parts = [
        phone.slice(0, 3),
        phone.slice(3, 6),
        phone.slice(6, 8),
        phone.slice(8, 10),
    ].filter(Boolean);

    let formatted = `+7 (${parts[0] ?? ''}`;

    if (phone.length >= 3) {
        formatted += ')';
    }

    if (parts[1]) {
        formatted += ` ${parts[1]}`;
    }

    if (parts[2]) {
        formatted += `-${parts[2]}`;
    }

    if (parts[3]) {
        formatted += `-${parts[3]}`;
    }

    return formatted;
}

function handlePhoneInput(event) {
    checkoutForm.customer_phone = formatRussianPhone(event.target.value);
}

function formatTelegramUsername(value) {
    const normalized = String(value ?? '').replace(/\s+/g, '').replace(/[^A-Za-z0-9_@]/g, '');

    if (normalized === '') {
        return '';
    }

    if (normalized.startsWith('@')) {
        return `@${normalized.slice(1).replace(/@/g, '').slice(0, 32)}`;
    }

    return `@${normalized.replace(/@/g, '').slice(0, 32)}`;
}

function handleTelegramInput(event) {
    checkoutForm.customer_telegram_username = formatTelegramUsername(event.target.value);
}

function openCart() {
    isMobileMenuOpen.value = false;
    isCartOpen.value = true;
}

function closeCart() {
    isCartOpen.value = false;
}

function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}

function setDocumentScrollLock(locked) {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = locked ? 'hidden' : '';
}

function submitOrder() {
    checkoutForm.order_groups = cartGroupsDetailed.value
        .filter((group) => group.itemsDetailed.length > 0)
        .map((group) => ({
            delivery_address: group.delivery_address,
            customer_comment: group.customer_comment,
            items: group.itemsDetailed.map((item) => ({
                type: item.entityType,
                id: item.id,
                quantity: item.quantity,
            })),
        }));

    checkoutForm.post('/orders', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            const nextGroup = createCartGroup();
            cartGroups.value = [nextGroup];
            activeCartGroupId.value = nextGroup.id;
            checkoutForm.reset('receipt', 'order_groups');
            isCartOpen.value = false;
        },
    });
}

// Auth modal
function clearAuthQuery() {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);

    if (!url.searchParams.has('auth')) {
        return;
    }

    url.searchParams.delete('auth');
    window.history.replaceState({}, '', url);
}

function closeAuthModal() {
    isAuthModalOpen.value = false;
    authModalMode.value = 'login';
    clearAuthQuery();
}

function openAuthModal(mode = 'login') {
    authModalMode.value = mode;
    isAuthModalOpen.value = true;
}

// Toast
function hideToast() {
    toast.value.visible = false;

    if (toastTimeout) {
        window.clearTimeout(toastTimeout);
        toastTimeout = null;
    }
}

function showToast(message) {
    if (!message) {
        return;
    }

    toast.value = { visible: true, message };

    if (toastTimeout) {
        window.clearTimeout(toastTimeout);
    }

    toastTimeout = window.setTimeout(() => {
        toast.value.visible = false;
        toastTimeout = null;
    }, 2800);
}

function showAppToast(message) {
    if (typeof window === 'undefined' || !message) {
        return;
    }

    window.dispatchEvent(new CustomEvent('app:toast', { detail: { message } }));
}

function handleToastEvent(event) {
    showToast(event.detail?.message);
}

function handleAuthModalEvent(event) {
    openAuthModal(event.detail?.mode ?? 'login');
}

function syncAuthModalWithUrl(url) {
    if (!url || authUser.value) {
        return;
    }

    const query = url.includes('?') ? url.split('?')[1] : '';
    const params = new URLSearchParams(query);
    const mode = params.get('auth');

    if (mode === 'login' || mode === 'register' || mode === 'forgot-password' || mode === 'reset-password') {
        openAuthModal(mode);
    }
}

function syncCartOpenWithUrl() {
    if (typeof window === 'undefined') {
        return;
    }

    const url = new URL(window.location.href);

    if (url.searchParams.get('cart') !== 'open') {
        return;
    }

    isCartOpen.value = true;
    url.searchParams.delete('cart');
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
}

function getStoredCartCount() {
    if (typeof window === 'undefined') {
        return 0;
    }

    try {
        const raw = window.localStorage.getItem(storageKey);

        if (!raw) {
            return 0;
        }

        const parsed = JSON.parse(raw);

        if (Array.isArray(parsed)) {
            return parsed.reduce((total, item) => total + (Number(item?.quantity) || 0), 0);
        }

        if (!parsed || typeof parsed !== 'object' || !Array.isArray(parsed.groups)) {
            return 0;
        }

        return parsed.groups.reduce(
            (groupsTotal, group) => groupsTotal + (Array.isArray(group?.items)
                ? group.items.reduce((itemsTotal, item) => itemsTotal + (Number(item?.quantity) || 0), 0)
                : 0),
            0,
        );
    } catch {
        return 0;
    }
}

function handleStorageEvent(event) {
    if (event.key && event.key !== storageKey) {
        return;
    }

    const saved = window.localStorage.getItem(storageKey);

    if (!saved) {
        return;
    }

    try {
        const parsed = JSON.parse(saved);
        cartGroups.value = normalizeStoredCart(parsed);
        activeCartGroupId.value = parsed?.active_group_id ?? cartGroups.value[0]?.id ?? null;
    } catch {
        // ignore malformed storage from other tabs
    }
}

Object.assign(cartApi, {
    addToCart,
    updateQuantity,
    removeFromCart,
    removeFromAllGroups,
    addExistingItemToGroup,
    getGroupItemQuantity,
    getPrimaryGroupCartQuantity,
    openCart,
});

watchEffect(() => {
    cartApi.primaryCartGroup = primaryCartGroup.value;
    cartApi.cartGroupsDetailed = cartGroupsDetailed.value;
    cartApi.cartCount = cartCount.value;
});

watch(flashSuccess, (message) => {
    if (message) {
        showToast(message);
    }
}, { immediate: true });

watch(flashOrder, (value) => {
    showOrderModal.value = Boolean(value);
}, { immediate: true });

watch([isCartOpen, isMobileMenuOpen], ([cartOpen, menuOpen]) => {
    setDocumentScrollLock(cartOpen || menuOpen);
}, { immediate: true });

watch(activeCartGroupId, () => {
    isSelectedAddressCompositionOpen.value = false;
});

watch([cartGroups, activeCartGroupId], ([groups, groupId]) => {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey, JSON.stringify({
            version: 2,
            active_group_id: groupId,
            groups,
        }));
    }
}, { deep: true });

watch(cartGroups, (groups) => {
    if (!groups.length) {
        const fallbackGroup = createCartGroup();
        cartGroups.value = [fallbackGroup];
        activeCartGroupId.value = fallbackGroup.id;

        return;
    }

    if (!groups.some((group) => group.id === activeCartGroupId.value)) {
        activeCartGroupId.value = groups[0]?.id ?? null;
    }
}, { deep: true });

watch(orderableItems, () => {
    if (cartGroups.value.length && orderableItems.value.length) {
        removeUnavailableItemsFromCart();
    }
}, { deep: true });

watch(pageUrl, (url) => {
    closeMobileMenu();
    syncAuthModalWithUrl(url);
}, { immediate: true });

watch(authUser, (user) => {
    if (user) {
        isAuthModalOpen.value = false;
        clearAuthQuery();
        checkoutForm.customer_name ||= user.name ?? '';
        checkoutForm.customer_email ||= user.email ?? '';
        checkoutForm.customer_phone ||= formatRussianPhone(user.phone ?? '');
        checkoutForm.customer_telegram_username ||= user.telegram_username ?? '';
    }
});

if (typeof window !== 'undefined') {
    window.addEventListener('app:toast', handleToastEvent);
    window.addEventListener('app:auth-modal', handleAuthModalEvent);
}

onMounted(() => {
    const savedCart = window.localStorage.getItem(storageKey);

    if (savedCart) {
        try {
            const parsedCart = JSON.parse(savedCart);
            cartGroups.value = normalizeStoredCart(parsedCart);
            activeCartGroupId.value = parsedCart?.active_group_id ?? cartGroups.value[0]?.id ?? null;
        } catch {
            const initialGroup = createCartGroup();
            cartGroups.value = [initialGroup];
            activeCartGroupId.value = initialGroup.id;
        }
    } else {
        const initialGroup = createCartGroup();
        cartGroups.value = [initialGroup];
        activeCartGroupId.value = initialGroup.id;
    }

    if (authUser.value) {
        checkoutForm.customer_name ||= authUser.value.name ?? '';
        checkoutForm.customer_email ||= authUser.value.email ?? '';
        checkoutForm.customer_phone ||= formatRussianPhone(authUser.value.phone ?? '');
        checkoutForm.customer_telegram_username ||= authUser.value.telegram_username ?? '';
    }

    if (flashRepeatOrder.value?.groups?.length) {
        mergeCartGroups(flashRepeatOrder.value.groups);
        isCartOpen.value = true;
    } else if (flashRepeatOrder.value?.items?.length) {
        mergeCartItems(flashRepeatOrder.value.items);
        isCartOpen.value = true;
    }

    syncCartOpenWithUrl();

    window.addEventListener('storage', handleStorageEvent);
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('app:toast', handleToastEvent);
        window.removeEventListener('app:auth-modal', handleAuthModalEvent);
        window.removeEventListener('storage', handleStorageEvent);
    }

    if (toastTimeout) {
        window.clearTimeout(toastTimeout);
        toastTimeout = null;
    }

    setDocumentScrollLock(false);
});
</script>

<template>
    <div class="min-h-screen bg-white text-stone-900">

        <header class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between gap-3">
                <Link href="/" class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-stone-950 text-sm font-black text-white sm:size-11">
                        FD
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold uppercase tracking-[0.16em] text-orange-700 sm:text-sm sm:tracking-[0.2em]">Food Delivery</div>
                        <div class="hidden text-sm text-stone-500 sm:block">Домашняя еда без переписок</div>
                    </div>
                </Link>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <button
                        v-if="siteNavigation.length"
                        type="button"
                        class="inline-flex size-10 items-center justify-center rounded-2xl bg-white text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50 lg:hidden"
                        :aria-expanded="isMobileMenuOpen"
                        aria-label="Меню"
                        @click="toggleMobileMenu"
                    >
                        <svg v-if="!isMobileMenuOpen" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg v-else class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="relative inline-flex size-10 items-center justify-center rounded-2xl bg-white text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50 sm:size-12 sm:hover:-translate-y-0.5 sm:hover:shadow-[0_16px_30px_rgba(120,87,43,0.12)]"
                        aria-label="Открыть корзину"
                        title="Корзина"
                        @click="openCart"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h1.386c.51 0 .955.343 1.085.835l.383 1.447m0 0L8.25 13.5h8.818c.54 0 1.01-.367 1.13-.893l1.514-6.675H6.604Zm1.896 10.968a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm9.75 0a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Z" />
                        </svg>
                        <span
                            class="absolute -right-1.5 -top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-orange-600 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </button>

                    <template v-if="authUser">
                        <Link
                            href="/profile"
                            class="relative inline-flex size-10 items-center justify-center rounded-2xl bg-white text-stone-500 shadow-sm ring-1 ring-stone-200 transition hover:text-stone-900 sm:size-12 sm:hover:-translate-y-0.5 sm:hover:shadow-[0_16px_30px_rgba(120,87,43,0.12)]"
                            aria-label="Открыть профиль"
                            :title="authUser.name"
                        >
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 19.5a8.25 8.25 0 0 1 14.998 0" />
                            </svg>
                            <span
                                class="absolute -bottom-1 -right-1 inline-flex size-5 items-center justify-center rounded-full bg-stone-950 text-[10px] font-bold uppercase text-white"
                            >
                                {{ authUser.name.slice(0, 1) }}
                            </span>
                        </Link>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="inline-flex size-10 items-center justify-center rounded-2xl bg-white text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50 sm:size-12 sm:hover:-translate-y-0.5 sm:hover:shadow-[0_16px_30px_rgba(120,87,43,0.12)]"
                            aria-label="Войти в профиль"
                            title="Профиль"
                            @click="openAuthModal('login')"
                        >
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 19.5a8.25 8.25 0 0 1 14.998 0" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>

            <nav v-if="siteNavigation.length" class="mt-4 hidden flex-wrap items-center justify-center gap-3 lg:flex">
                <Link
                    v-for="item in siteNavigation"
                    :key="item.id"
                    :href="item.href"
                    class="rounded-full bg-white px-4 py-2 text-sm font-semibold shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-1 opacity-0"
            >
                <nav
                    v-if="isMobileMenuOpen && siteNavigation.length"
                    class="mt-3 overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-stone-200 lg:hidden"
                >
                    <Link
                        v-for="item in siteNavigation"
                        :key="item.id"
                        :href="item.href"
                        class="block border-b border-stone-100 px-4 py-3.5 text-base font-semibold text-stone-900 transition last:border-b-0 hover:bg-stone-50"
                        @click="closeMobileMenu"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </transition>
        </header>

        <!-- Toast -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="toast.visible"
                class="pointer-events-none fixed inset-x-0 top-5 z-[70] flex justify-center px-4"
            >
                <div class="pointer-events-auto flex max-w-md items-center gap-3 rounded-full bg-white px-5 py-3 text-sm font-semibold text-stone-900 shadow-[0_20px_60px_rgba(28,25,23,0.16)]">
                    <span class="inline-flex size-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.261a1 1 0 0 1-1.42.003l-3.3-3.3a1 1 0 1 1 1.414-1.414l2.59 2.59 6.493-6.547a1 1 0 0 1 1.417-.007Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span class="leading-5">{{ toast.message }}</span>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                        aria-label="Закрыть уведомление"
                        @click="hideToast"
                    >
                        <span class="text-lg leading-none">×</span>
                    </button>
                </div>
            </div>
        </transition>

        <main :class="compact ? 'mx-auto max-w-7xl px-6 pb-16' : ''">
            <slot />
        </main>

        <footer class="bg-white border-t border-stone-200/80">
            <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-[minmax(0,1.1fr)_repeat(2,minmax(0,0.7fr))]">
                <div class="space-y-4">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-2xl bg-stone-950 text-sm font-black text-white">
                            FD
                        </div>
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-700">Food Delivery</div>
                            <div class="text-sm text-stone-500">Домашняя еда без переписок</div>
                        </div>
                    </Link>
                    <p class="max-w-md text-sm leading-7 text-stone-600">
                        {{ siteContacts.footer_description || 'Готовые наборы и блюда на следующий день, понятное оформление заказа и быстрый доступ к новостям сервиса.' }}
                    </p>
                </div>

                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Навигация</div>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-stone-700">
                        <Link
                            v-for="item in siteNavigation"
                            :key="`footer-${item.id}`"
                            :href="item.href"
                            class="transition hover:text-stone-950"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Контакты</div>
                    <div class="mt-4 space-y-3 text-sm text-stone-700">
                        <p>{{ siteContacts.email }}</p>
                        <p>{{ siteContacts.phone }}</p>
                        <p>{{ siteContacts.telegram }}</p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Cart backdrop -->
        <div
            v-if="isCartOpen"
            class="fixed inset-0 z-40 bg-stone-950/40 backdrop-blur-[2px]"
            @click="closeCart"
        ></div>

        <!-- Cart panel -->
        <aside
            class="fixed right-0 top-0 z-50 flex h-full w-full max-w-xl flex-col bg-white shadow-[-20px_0_60px_rgba(28,25,23,0.18)] transition-transform duration-300"
            :class="isCartOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="flex items-center justify-between px-6 py-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Корзина</p>
                    <h2 class="mt-1 text-2xl font-bold">Ваш заказ</h2>
                </div>
                <button
                    type="button"
                    class="rounded-full bg-stone-100 px-4 py-2 text-sm font-semibold transition hover:bg-stone-200"
                    @click="closeCart"
                >
                    Закрыть
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6">
                <div class="rounded-[1.5rem] bg-stone-950 px-5 py-4 text-white">
                    <div class="text-sm text-stone-300">Итого к оплате</div>
                    <div class="mt-2 text-3xl font-black">{{ formatPrice(cartTotal) }}</div>
                    <div class="mt-4 space-y-2 pt-4 text-sm text-stone-300">
                        <div class="flex items-center justify-between gap-4">
                            <span>Позиции</span>
                            <span>{{ formatPrice(cartSubtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span>Доставка</span>
                            <span>{{ deliveryPrice === 0 && cartCount ? 'Бесплатно' : formatPrice(deliveryPrice) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-[1.5rem] bg-orange-50 px-5 py-4 text-sm leading-6 text-orange-950">
                    Можно разделить один заказ на несколько адресов. Для каждого адреса доставка
                    <span class="font-semibold">считается отдельно</span>:
                    <span class="font-semibold">{{ formatPrice(checkoutSettings.delivery_price ?? 0) }}</span> за адрес, либо бесплатно от
                    <span class="font-semibold">{{ checkoutSettings.free_delivery_meal_set_quantity ?? 5 }} наборов</span> в одной доставке.
                </div>

                <div class="mt-6">
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="group in cartGroupsDetailed"
                            :key="group.id"
                            class="group inline-flex items-center overflow-hidden rounded-full transition"
                            :class="group.id === activeCartGroupDetailed?.id
                                ? 'bg-stone-950 text-white'
                                : 'bg-stone-100 text-stone-700'"
                        >
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-semibold transition"
                                :class="group.id === activeCartGroupDetailed?.id ? 'hover:bg-stone-900' : 'hover:bg-stone-200'"
                                @click="setActiveCartGroup(group.id)"
                            >
                                {{ group.label }}
                                <span class="ml-1 text-xs opacity-80">{{ group.itemCount }} шт.</span>
                            </button>
                            <button
                                v-if="group.index > 0"
                                type="button"
                                class="border-l border-white/10 px-3 py-2 text-sm font-bold transition"
                                :class="group.id === activeCartGroupDetailed?.id
                                    ? 'text-white/80 hover:bg-stone-900 hover:text-white'
                                    : 'text-stone-400 hover:bg-stone-200 hover:text-stone-700'"
                                aria-label="Удалить адрес"
                                title="Удалить адрес"
                                @click.stop="removeCartGroup(group.id)"
                            >
                                ×
                            </button>
                        </div>
                        <button
                            type="button"
                            class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-900 shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-50"
                            @click="addCartGroup"
                        >
                            + Ещё адрес
                        </button>
                    </div>
                </div>

                <div v-if="activeCartGroupDetailed" class="mt-4 rounded-[1.5rem] bg-white px-5 py-4 text-sm leading-6 text-stone-700 ring-1 ring-stone-100">
                    Новые товары из каталога попадают в <span class="font-semibold text-stone-950">Адрес 1</span>.
                    Если часть заказа нужна на другой адрес, просто нажмите у позиции кнопку
                    <span class="font-semibold text-stone-950">«В адрес 2»</span>, <span class="font-semibold text-stone-950">«В адрес 3»</span> и так далее.
                </div>

                <div v-if="cartGroupsDetailed.length > 1 && activeCartGroupDetailed?.itemsDetailed.length" class="mt-6 rounded-[1.75rem] bg-white px-5 py-5 ring-1 ring-stone-100">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 text-left"
                        @click="toggleSelectedAddressComposition"
                    >
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Состав заказа этого адреса</div>
                            <div class="mt-2 text-sm text-stone-600">{{ activeCartGroupDetailed.itemsDetailed.length }} поз.</div>
                        </div>
                        <span
                            class="inline-flex size-10 items-center justify-center rounded-full bg-white text-stone-700 shadow-sm ring-1 ring-stone-200 transition"
                            :class="isSelectedAddressCompositionOpen ? 'rotate-180' : ''"
                            aria-hidden="true"
                        >
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                    <div v-if="isSelectedAddressCompositionOpen" class="mt-4 space-y-3">
                        <article
                            v-for="item in activeCartGroupDetailed.itemsDetailed"
                            :key="`selected-group-preview-${activeCartGroupDetailed.id}-${item.entityType}-${item.id}`"
                            class="rounded-[1.4rem] bg-white px-4 py-4 shadow-sm ring-1 ring-stone-100"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-lg font-bold text-stone-950">{{ item.name }}</div>
                                    <div class="mt-1 text-sm text-stone-500">{{ item.quantity }} шт. · {{ formatPrice(item.lineTotal) }}</div>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-200"
                                    @click="removeFromCart(item.entityType, item.id, activeCartGroupDetailed.id)"
                                >
                                    Убрать
                                </button>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="activeCartGroupDetailed" class="mt-6 rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-stone-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-black text-stone-950">{{ activeCartGroupDetailed.label }}</h3>
                            <p class="mt-1 text-sm text-stone-500">
                                Позиции: {{ activeCartGroupDetailed.itemCount }},
                                наборов: {{ activeCartGroupDetailed.mealSetCount }},
                                сумма: {{ formatPrice(activeCartGroupDetailed.total) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <label class="block">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <span class="block text-sm font-medium text-stone-700">Адрес доставки</span>
                                <button
                                    v-if="canSaveActiveAddressPreset"
                                    type="button"
                                    class="rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-200"
                                    @click="saveActiveAddressPreset"
                                >
                                    Сохранить адрес
                                </button>
                            </div>
                            <textarea
                                v-model="activeCartGroup.delivery_address"
                                rows="1"
                                class="min-h-[52px] w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            ></textarea>
                            <div v-if="savedDeliveryAddressPresets.length" class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="preset in savedDeliveryAddressPresets"
                                    :key="preset.id"
                                    type="button"
                                    class="rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-200"
                                    @click="applySavedAddressPreset(preset)"
                                >
                                    {{ preset.label || preset.value }}
                                </button>
                            </div>
                        </label>

                        <label class="block">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <span class="block text-sm font-medium text-stone-700">Комментарий для этого адреса</span>
                                <button
                                    v-if="canSaveActiveCommentPreset"
                                    type="button"
                                    class="rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-200"
                                    @click="saveActiveCommentPreset"
                                >
                                    Сохранить комментарий
                                </button>
                            </div>
                            <textarea
                                v-model="activeCartGroup.customer_comment"
                                rows="1"
                                class="min-h-[52px] w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500"
                            ></textarea>
                            <div v-if="savedDeliveryCommentPresets.length" class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="preset in savedDeliveryCommentPresets"
                                    :key="preset.id"
                                    type="button"
                                    class="rounded-full bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:bg-stone-200"
                                    @click="applySavedCommentPreset(preset)"
                                >
                                    {{ preset.label || preset.value }}
                                </button>
                            </div>
                        </label>

                        <div class="rounded-[1.25rem] bg-orange-50 px-4 py-4 text-sm leading-6 text-orange-950">
                            Для <span class="font-semibold">{{ activeCartGroupDetailed.label }}</span>:
                            позиции на <span class="font-semibold">{{ formatPrice(activeCartGroupDetailed.subtotal) }}</span>,
                            доставка
                            <span class="font-semibold">
                                {{ activeCartGroupDetailed.deliveryPrice === 0 && activeCartGroupDetailed.itemCount ? 'бесплатно' : formatPrice(activeCartGroupDetailed.deliveryPrice) }}
                            </span>.
                        </div>
                    </div>
                </div>

                <div v-if="cartItemsDetailed.length" class="mt-6 space-y-3">
                    <div
                        v-for="item in cartItemsDetailed"
                        :key="`${item.entityType}-${item.id}`"
                        class="rounded-2xl bg-white p-4 ring-1 ring-stone-100"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                                    {{ item.entityType === 'meal_set' ? 'Набор' : item.category_name || 'Блюдо' }}
                                </div>
                                <div class="mt-1 text-lg font-semibold">{{ item.name }}</div>
                                <div class="mt-1 text-sm text-stone-500">{{ formatPrice(item.price) }} за шт.</div>
                                <div
                                    v-if="formatIsItemSplitAcrossGroups(item.entityType, item.id)"
                                    class="mt-3 inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800"
                                >
                                    Разделено: {{ formatItemDistribution(item.entityType, item.id) }}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="text-sm font-medium text-stone-400 transition hover:text-stone-900"
                                @click="removeFromAllGroups(item.entityType, item.id)"
                            >
                                Убрать
                            </button>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="distribution in item.distributions"
                                :key="`distribution-${item.entityType}-${item.id}-${distribution.id}`"
                                class="rounded-[1.25rem] bg-white px-4 py-3 shadow-sm ring-1 ring-stone-100"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold text-stone-900">{{ distribution.label }}</div>
                                        <div class="mt-1 text-xs text-stone-500">{{ formatPrice(distribution.lineTotal) }}</div>
                                    </div>
                                    <div class="inline-flex items-center gap-2 rounded-full bg-stone-50 px-2 py-1">
                                        <button
                                            type="button"
                                            class="size-8 rounded-full text-lg"
                                            @click="updateQuantity(item.entityType, item.id, distribution.quantity - 1, distribution.id)"
                                        >
                                            -
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold">{{ distribution.quantity }}</span>
                                        <button
                                            type="button"
                                            class="size-8 rounded-full text-lg"
                                            @click="updateQuantity(item.entityType, item.id, distribution.quantity + 1, distribution.id)"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="cartGroupsDetailed.length > 1" class="mt-4">
                            <div class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                                Добавить ещё в другой адрес
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="group in cartGroupsDetailed"
                                    :key="`clone-${group.id}-${item.entityType}-${item.id}`"
                                    type="button"
                                    class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm ring-1 ring-stone-200 transition hover:bg-orange-50 hover:text-orange-700"
                                    @click="addExistingItemToGroup(item.entityType, item.id, group.id)"
                                >
                                    В {{ group.label.toLowerCase() }}
                                    <span
                                        v-if="getGroupItemQuantity(group.id, item.entityType, item.id)"
                                        class="ml-1 text-xs text-stone-500"
                                    >
                                        ({{ getGroupItemQuantity(group.id, item.entityType, item.id) }})
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-6 rounded-[1.75rem] bg-white px-5 py-10 text-center text-stone-500 ring-1 ring-stone-100">
                    Добавьте набор или отдельные блюда, и они появятся здесь.
                </div>

                <section class="mt-6 rounded-[1.75rem] bg-amber-50 px-5 py-5 text-sm leading-6 text-stone-800">
                    <h3 class="text-base font-black text-stone-950">📌 Номер для оплаты 📌</h3>
                    <p class="mt-2 text-lg font-bold text-stone-950">
                        {{ checkoutSettings.payment_phone }}, {{ checkoutSettings.payment_recipient }}
                    </p>
                    <p class="mt-1 font-semibold">🏦 {{ checkoutSettings.payment_banks }} 🏦</p>
                    <p class="mt-3 font-semibold">⚡ {{ checkoutSettings.payment_instruction }} 📩</p>

                    <div class="mt-4 pt-4">
                        <p class="font-bold text-stone-950">При заказе обязательно указываем:</p>
                        <p class="mt-2">🌍 {{ checkoutSettings.address_instruction }}</p>
                        <p class="mt-1">📞 {{ checkoutSettings.phone_instruction }}</p>
                        <p class="mt-3 font-bold text-orange-800">
                            Заказы принимаем до {{ checkoutSettings.order_cutoff_time }}.
                        </p>
                    </div>
                </section>

                <form class="mt-6 space-y-4" @submit.prevent="submitOrder">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-stone-700">Имя</span>
                            <input v-model="checkoutForm.customer_name" type="text" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-stone-700">Телефон</span>
                            <input :value="checkoutForm.customer_phone" type="text" inputmode="tel" maxlength="18" placeholder="+7 (999) 123-45-67" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500" @input="handlePhoneInput" />
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Telegram</span>
                        <input :value="checkoutForm.customer_telegram_username" type="text" maxlength="33" placeholder="@username" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500" @input="handleTelegramInput" />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Email</span>
                        <input v-model="checkoutForm.customer_email" type="email" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-orange-500" />
                    </label>

                    <div class="rounded-[1.5rem] bg-orange-50 px-4 py-4 text-sm leading-6 text-orange-950">
                        Доставим <span class="font-semibold">{{ deliveryWindowLabel }}</span>,
                        интервал: <span class="font-semibold">{{ checkoutSettings.delivery_interval }}</span>.
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-stone-700">Чек перевода</span>
                        <input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" class="block w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm" @input="checkoutForm.receipt = $event.target.files[0]" />
                    </label>

                    <div v-if="Object.keys(checkoutForm.errors).length" class="rounded-[1.5rem] bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
                        <div class="font-bold text-red-950">Проверьте данные заказа</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li v-for="(error, key) in checkoutForm.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>

                    <button type="submit" :disabled="!canSubmit" class="w-full rounded-full bg-stone-950 px-6 py-4 text-base font-semibold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-stone-300">
                        {{ checkoutForm.processing ? 'Отправляем заказ...' : `Отправить заказ на ${formatPrice(cartTotal)}` }}
                    </button>
                </form>
            </div>
        </aside>

        <!-- Order success modal -->
        <div v-if="showOrderModal && flashOrder" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/55 px-4">
            <div class="w-full max-w-lg rounded-[2rem] bg-white p-8 shadow-[0_24px_80px_rgba(28,25,23,0.3)]">
                <div class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800">
                    Заказ принят
                </div>
                <h2 class="mt-4 text-4xl font-black tracking-[-0.04em]">Спасибо за заказ</h2>
                <p class="mt-4 text-base leading-7 text-stone-600">
                    <template v-if="flashOrder.delivery_groups_count > 1">
                        Номер вашего заказа:
                        <span class="font-bold text-stone-950">{{ flashOrder.number }}</span>.
                        Внутри него <span class="font-bold text-stone-950">{{ flashOrder.delivery_groups_count }} адреса доставки</span>.
                        Мы проверим чек и свяжемся с вами для подтверждения.
                    </template>
                    <template v-else>
                        Номер вашего заказа:
                        <span class="font-bold text-stone-950">{{ flashOrder.number }}</span>.
                        Мы проверим чек и свяжемся с вами для подтверждения.
                    </template>
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" class="rounded-full bg-stone-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600" @click="showOrderModal = false">
                        Понятно
                    </button>
                    <Link v-if="authUser" href="/profile" class="rounded-full bg-stone-100 px-5 py-3 text-sm font-semibold transition hover:bg-stone-200">
                        Открыть мои заказы
                    </Link>
                </div>
            </div>
        </div>

        <AuthModal
            :visible="isAuthModalOpen"
            :initial-mode="authModalMode"
            @close="closeAuthModal"
        />
    </div>
</template>
