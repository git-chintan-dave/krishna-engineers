type QuantityRow = {
    quantity: number | string;
};

export function getQuantityRowHighlight(row: QuantityRow) {
    const quantity = Number(row.quantity);

    if (quantity === 0) {
        return 'danger';
    }

    if (quantity < 10) {
        return 'warning';
    }

    return undefined;
}
