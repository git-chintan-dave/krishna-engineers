export function getQuantityRowHighlight(row: {
    quantity: number | string;
}) {
    const quantity = Number(row.quantity);

    if (quantity === 0) {
        return 'danger';
    }

    if (quantity < 10) {
        return 'warning';
    }

    return undefined;
}

export function getAttendanceRowHighlight(row: {
    is_open: boolean;
    needs_attention: boolean;
}) {
    if (row.is_open) {
        return 'warning';
    } else  if (row.needs_attention) {
        return 'danger';
    }

    return undefined;
}
