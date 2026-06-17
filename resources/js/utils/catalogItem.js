export function isCatalogItemOrderable(item) {
    return item?.is_orderable !== false;
}

export function resolveCatalogImageLayout(width, height) {
    if (!width || !height) {
        return null;
    }

    return width / height < 1 ? 'portrait' : 'landscape';
}

export function unavailableCatalogItemLabel(item) {
    const type = item?.entityType ?? item?.type;

    return type === 'meal_set'
        ? 'Недоступен к заказу на завтра'
        : 'Недоступно к заказу';
}

export function normalizeCatalogItem(item) {
    return {
        ...item,
        entityType: item.entityType ?? item.type,
    };
}
