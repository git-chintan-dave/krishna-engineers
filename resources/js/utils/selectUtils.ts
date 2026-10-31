export type AppSelectValue =
    | string
    | number
    | null
    | (string | number)[];

export type NumericSelectValue = number | number[] | null;

export const normalizeSelectValue = (
    value: AppSelectValue,
): NumericSelectValue => {
    if (Array.isArray(value)) {
        return value
            .map(Number)
            .filter(Number.isFinite);
    }

    if (value === null || value === '') {
        return null;
    }

    const normalized = Number(value);

    return Number.isFinite(normalized) ? normalized : null;
};

export const isValidSelectionMode = (
    value: AppSelectValue,
    multiple: boolean,
): boolean => {
    return multiple
        ? value === null || Array.isArray(value)
        : !Array.isArray(value);
};
