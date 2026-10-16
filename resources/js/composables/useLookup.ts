import {ref} from 'vue';

import type {SelectOption} from '@/types/common';
import type {EnumLookupKey} from '@/types/lookup';

type LookupResponse<TValue extends string | number = string> = {
    data: SelectOption<TValue>[];
};

const enumCache = new Map<EnumLookupKey, SelectOption<string | number>[]>();

const lookupCache = new Map<string, SelectOption<string | number>[]>();

export function useLookup() {
    const loading = ref(false);
    const error = ref<string | null>(null);

    /*
    |--------------------------------------------------------------------------
    | Enum Lookups
    |--------------------------------------------------------------------------
    */

    async function enumOptions<TValue extends string | number = string>(
        key: EnumLookupKey,
    ): Promise<SelectOption<TValue>[]> {
        const cached = enumCache.get(key);

        if (cached) {
            return cached as SelectOption<TValue>[];
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/lookups/enums/${key}`);

            if (!response.ok) {
                throw new Error(`Failed to load lookup: ${key}`);
            }

            const result = (await response.json()) as LookupResponse<TValue>;

            enumCache.set(key, result.data as SelectOption<string | number>[]);

            return result.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : 'Failed to load lookup options.';

            throw exception;
        } finally {
            loading.value = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database Lookups
    |--------------------------------------------------------------------------
    */

    async function databaseOptions<TValue extends string | number = number>(
        key: string,
        url: string,
    ): Promise<SelectOption<TValue>[]> {
        const cached = lookupCache.get(key);

        if (cached) {
            return cached as SelectOption<TValue>[];
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`Failed to load lookup: ${key}`);
            }

            const result = (await response.json()) as LookupResponse<TValue>;

            lookupCache.set(
                key,
                result.data as SelectOption<string | number>[],
            );

            return result.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : 'Failed to load lookup options.';

            throw exception;
        } finally {
            loading.value = false;
        }
    }

    async function parts(): Promise<SelectOption<number>[]> {
        return databaseOptions<number>('parts', '/lookups/parts');
    }

    async function setups(): Promise<SelectOption<number>[]> {
        return databaseOptions<number>('setups', '/lookups/setups');
    }

    async function machines(): Promise<SelectOption<number>[]> {
        return databaseOptions<number>('machines', '/lookups/machines');
    }

    async function sections(): Promise<SelectOption<number>[]> {
        return databaseOptions<number>('sections', '/lookups/sections');
    }

    async function sectionSizes(
        sectionId: number,
    ): Promise<SelectOption<number>[]> {
        return databaseOptions<number>(
            `section-sizes:${sectionId}`,
            `/lookups/sections/${sectionId}/sizes`,
        );
    }

    async function employees(
        roles?: string[],
    ): Promise<SelectOption<number>[]> {
        const params = new URLSearchParams();

        roles?.forEach((role) => {
            params.append('roles[]', role);
        });

        const queryString = params.toString();

        return databaseOptions<number>(
            `employees:${queryString}`,
            `/lookups/employees${queryString ? `?${queryString}` : ''}`,
        );
    }

    async function tools(
        type?: string | null,
    ): Promise<SelectOption<number>[]> {
        const queryString = type
            ? new URLSearchParams({
                type,
            }).toString()
            : '';

        return databaseOptions<number>(
            `tools:${queryString}`,
            `/lookups/tools${queryString ? `?${queryString}` : ''}`,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    function clearEnumCache(key?: EnumLookupKey): void {
        if (key) {
            enumCache.delete(key);

            return;
        }

        enumCache.clear();
    }

    function clearLookupCache(key?: string): void {
        if (key) {
            lookupCache.delete(key);

            return;
        }

        lookupCache.clear();
    }

    function clearCache(): void {
        enumCache.clear();
        lookupCache.clear();
    }

    return {
        loading,
        error,

        // Enum lookups
        enumOptions,
        clearEnumCache,

        // Database lookups
        parts,
        setups,
        machines,
        sections,
        sectionSizes,
        employees,
        tools,
        databaseOptions,
        clearLookupCache,

        // All
        clearCache,
    };
}
