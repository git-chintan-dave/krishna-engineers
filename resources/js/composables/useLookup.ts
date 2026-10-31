import {ref} from 'vue';

import type {SelectOption} from '@/types/common';
import type {EnumLookupKey} from '@/types/lookup';

type CacheStrategy = 'memory' | 'localStorage' | 'sessionStorage';

type LookupResponse<TValue extends string | number = string> = {
    data: SelectOption<TValue>[];
};

type LookupCacheOptions = {
    cache?: CacheStrategy;
};

const DEFAULT_CACHE: CacheStrategy = 'memory';
const STORAGE_PREFIX = 'krishna-engineers:lookup:';

const ENUM_STORAGE_PREFIX = `${STORAGE_PREFIX}enum:`;
const LOOKUP_STORAGE_PREFIX = `${STORAGE_PREFIX}lookup:`;

const ENUM_LOOKUP_KEYS: EnumLookupKey[] = [
    'status',
    'salary_status',
    'raw_material_transaction_type',
    'tool_transaction_type',
    'tool_type',
    'user_role',
    'user_shift',
];

const enumCache = new Map<string, SelectOption<string | number>[]>();
const lookupCache = new Map<string, SelectOption<string | number>[]>();

const enumRequests = new Map<
    string,
    Promise<SelectOption<string | number>[]>
>();

const lookupRequests = new Map<
    string,
    Promise<SelectOption<string | number>[]>
>();

/*
|--------------------------------------------------------------------------
| Cache Helpers
|--------------------------------------------------------------------------
*/

function getRequestKey(key: string, cache: CacheStrategy): string {
    return `${cache}:${key}`;
}

function getStorage(cache: CacheStrategy): Storage | null {
    if (typeof window === 'undefined' || cache === 'memory') {
        return null;
    }

    try {
        return cache === 'localStorage'
            ? window.localStorage
            : window.sessionStorage;
    } catch {
        return null;
    }
}

function getCached<TValue extends string | number>(
    key: string,
    cache: CacheStrategy,
    memoryCache: Map<string, SelectOption<string | number>[]>,
): SelectOption<TValue>[] | undefined {
    if (cache === 'memory') {
        return memoryCache.get(key) as
            | SelectOption<TValue>[]
            | undefined;
    }

    const storage = getStorage(cache);

    if (!storage) {
        return undefined;
    }

    try {
        const value = storage.getItem(key);

        if (!value) {
            return undefined;
        }

        const parsed: unknown = JSON.parse(value);

        if (!Array.isArray(parsed)) {
            storage.removeItem(key);
            return undefined;
        }

        return parsed as SelectOption<TValue>[];
    } catch {
        return undefined;
    }
}

function setCached(
    key: string,
    cache: CacheStrategy,
    data: SelectOption<string | number>[],
    memoryCache: Map<string, SelectOption<string | number>[]>,
): void {
    if (cache === 'memory') {
        memoryCache.set(key, data);
        return;
    }

    const storage = getStorage(cache);

    if (!storage) {
        return;
    }

    try {
        storage.setItem(key, JSON.stringify(data));
    } catch {
        // Storage may be unavailable or full.
        // The lookup still succeeds even if persistence fails.
    }
}

function clearCached(
    key: string,
    cache: CacheStrategy,
    memoryCache: Map<string, SelectOption<string | number>[]>,
): void {
    if (cache === 'memory') {
        memoryCache.delete(key);
        return;
    }

    const storage = getStorage(cache);

    try {
        storage?.removeItem(key);
    } catch {
        // Ignore storage access errors.
    }
}

function clearStorageByPrefix(
    cache: CacheStrategy,
    prefix: string,
): void {
    const storage = getStorage(cache);

    if (!storage) {
        return;
    }

    try {
        const keys: string[] = [];

        for (let index = 0; index < storage.length; index++) {
            const key = storage.key(index);

            if (key?.startsWith(prefix)) {
                keys.push(key);
            }
        }

        keys.forEach((key) => storage.removeItem(key));
    } catch {
        // Ignore storage access errors.
    }
}

/*
|--------------------------------------------------------------------------
| Composable
|--------------------------------------------------------------------------
*/

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
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<TValue>[]> {
        const cache = options.cache ?? DEFAULT_CACHE;
        const cacheKey = `${ENUM_STORAGE_PREFIX}${key}`;
        const requestKey = getRequestKey(cacheKey, cache);

        const cached = getCached<TValue>(
            cacheKey,
            cache,
            enumCache,
        );

        if (cached) {
            return cached;
        }

        const pending = enumRequests.get(requestKey);

        if (pending) {
            return pending as Promise<SelectOption<TValue>[]>;
        }

        loading.value = true;
        error.value = null;

        const request = (async () => {
            try {
                const response = await fetch(`/lookups/enums/${key}`);

                if (!response.ok) {
                    throw new Error(`Failed to load lookup: ${key}`);
                }

                const result =
                    (await response.json()) as LookupResponse<TValue>;

                setCached(
                    cacheKey,
                    cache,
                    result.data as SelectOption<string | number>[],
                    enumCache,
                );

                return result.data;
            } catch (exception) {
                error.value =
                    exception instanceof Error
                        ? exception.message
                        : 'Failed to load lookup options.';

                throw exception;
            } finally {
                enumRequests.delete(requestKey);
                loading.value = false;
            }
        })();

        enumRequests.set(
            requestKey,
            request as Promise<SelectOption<string | number>[]>,
        );

        return request;
    }

    /**
     * Prefetch every registered enum lookup.
     *
     * Failed lookups do not prevent the remaining lookups from loading.
     */
    async function prefetchEnumOptions(): Promise<void> {
        await Promise.all(
            ENUM_LOOKUP_KEYS.map((key) =>
                enumOptions(key, { cache: 'localStorage' }).catch(() => []),
            ),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Database Lookups
    |--------------------------------------------------------------------------
    */

    async function databaseOptions<TValue extends string | number = number>(
        key: string,
        url: string,
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<TValue>[]> {
        const cache = options.cache ?? DEFAULT_CACHE;
        const cacheKey = `${LOOKUP_STORAGE_PREFIX}${key}`;
        const requestKey = getRequestKey(cacheKey, cache);

        const cached = getCached<TValue>(
            cacheKey,
            cache,
            lookupCache,
        );

        if (cached) {
            return cached;
        }

        const pending = lookupRequests.get(requestKey);

        if (pending) {
            return pending as Promise<SelectOption<TValue>[]>;
        }

        loading.value = true;
        error.value = null;

        const request = (async () => {
            try {
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`Failed to load lookup: ${key}`);
                }

                const result =
                    (await response.json()) as LookupResponse<TValue>;

                setCached(
                    cacheKey,
                    cache,
                    result.data as SelectOption<string | number>[],
                    lookupCache,
                );

                return result.data;
            } catch (exception) {
                error.value =
                    exception instanceof Error
                        ? exception.message
                        : 'Failed to load lookup options.';

                throw exception;
            } finally {
                lookupRequests.delete(requestKey);
                loading.value = false;
            }
        })();

        lookupRequests.set(
            requestKey,
            request as Promise<SelectOption<string | number>[]>,
        );

        return request;
    }

    /*
    |--------------------------------------------------------------------------
    | Specific Database Lookups
    |--------------------------------------------------------------------------
    */

    async function parts(
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        return databaseOptions<number>(
            'parts',
            '/lookups/parts',
            options,
        );
    }

    async function setups(
        partId?: number | null,
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        const params = new URLSearchParams();

        if (partId) {
            params.set('part_id', String(partId));
        }

        const queryString = params.toString();

        return databaseOptions<number>(
            `setups:${queryString}`,
            `/lookups/setups${queryString ? `?${queryString}` : ''}`,
            options,
        );
    }

    async function machines(
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        return databaseOptions<number>(
            'machines',
            '/lookups/machines',
            options,
        );
    }

    async function sections(
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        return databaseOptions<number>(
            'sections',
            '/lookups/sections',
            options,
        );
    }

    async function sectionSizes(
        sectionId: number,
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        return databaseOptions<number>(
            `section-sizes:${sectionId}`,
            `/lookups/sections/${sectionId}/sizes`,
            options,
        );
    }

    async function employees(
        role?: string | null,
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        const params = new URLSearchParams();

        if (role) {
            params.set('role', role);
        }

        const queryString = params.toString();

        return databaseOptions<number>(
            `employees:${queryString}`,
            `/lookups/employees${queryString ? `?${queryString}` : ''}`,
            options,
        );
    }

    async function tools(
        type?: string | null,
        options: LookupCacheOptions = {},
    ): Promise<SelectOption<number>[]> {
        const queryString = type
            ? new URLSearchParams({ type }).toString()
            : '';

        return databaseOptions<number>(
            `tools:${queryString}`,
            `/lookups/tools${queryString ? `?${queryString}` : ''}`,
            options,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cache Management
    |--------------------------------------------------------------------------
    */

    function clearEnumCache(
        key?: EnumLookupKey,
        cache?: CacheStrategy,
    ): void {
        const strategies: CacheStrategy[] = cache
            ? [cache]
            : ['memory', 'localStorage', 'sessionStorage'];

        for (const strategy of strategies) {
            if (key) {
                const cacheKey = `${ENUM_STORAGE_PREFIX}${key}`;

                clearCached(cacheKey, strategy, enumCache);
                enumRequests.delete(getRequestKey(cacheKey, strategy));

                continue;
            }

            enumCache.clear();

            for (const requestKey of enumRequests.keys()) {
                if (requestKey.startsWith(`${strategy}:`)) {
                    enumRequests.delete(requestKey);
                }
            }

            clearStorageByPrefix(strategy, ENUM_STORAGE_PREFIX);
        }
    }

    function clearLookupCache(
        key?: string,
        cache?: CacheStrategy,
    ): void {
        const strategies: CacheStrategy[] = cache
            ? [cache]
            : ['memory', 'localStorage', 'sessionStorage'];

        for (const strategy of strategies) {
            if (key) {
                const cacheKey = `${LOOKUP_STORAGE_PREFIX}${key}`;

                clearCached(cacheKey, strategy, lookupCache);
                lookupRequests.delete(getRequestKey(cacheKey, strategy));

                continue;
            }

            lookupCache.clear();

            for (const requestKey of lookupRequests.keys()) {
                if (requestKey.startsWith(`${strategy}:`)) {
                    lookupRequests.delete(requestKey);
                }
            }

            clearStorageByPrefix(strategy, LOOKUP_STORAGE_PREFIX);
        }
    }

    function clearCache(): void {
        clearEnumCache();
        clearLookupCache();
    }

    return {
        loading,
        error,

        // Enum lookups
        enumOptions,
        prefetchEnumOptions,
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
