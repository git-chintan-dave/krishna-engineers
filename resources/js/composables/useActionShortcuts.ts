import type {MaybeRefOrGetter} from 'vue';
import {onBeforeUnmount, onMounted, toValue} from 'vue';

export type ActionShortcutName =
    | 'add'
    | 'bulkDelete'
    | 'resetPreferences'
    | 'import'
    | 'sync'
    | 'generateSalary'
    | 'back'
    | 'save';

export type ShortcutDefinition = {
    key: string;
    alt: boolean;
    shift: boolean;
    ctrl: boolean;
    meta: boolean;
    label: string;
    symbol: string;
};

export type ShortcutAction = {
    handler: () => void;
    enabled?: MaybeRefOrGetter<boolean>;
};

export type ActionShortcutHandlers = Partial<
    Record<ActionShortcutName, ShortcutAction>
>;

/**
 * Shared keyboard shortcut definitions.
 *
 * Alt + Shift combinations reduce conflicts with browser shortcuts.
 */
export const ACTION_SHORTCUTS: Record<
    ActionShortcutName,
    ShortcutDefinition
> = {
    add: {
        key: 'n',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + N',
        symbol: 'Alt + Shift + N',
    },
    bulkDelete: {
        key: 'b',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + B',
        symbol: 'Alt + Shift + B',
    },
    resetPreferences: {
        key: 'r',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + R',
        symbol: 'Alt + Shift + R',
    },
    import: {
        key: 'i',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + I',
        symbol: 'Alt + Shift + I',
    },
    sync: {
        key: 's',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + S',
        symbol: 'Alt + Shift + S',
    },
    generateSalary: {
        key: 'g',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + G',
        symbol: 'Alt + Shift + G',
    },
    back: {
        key: 'arrowleft',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + ←',
        symbol: 'Alt + Shift + ←',
    },
    save: {
        key: 'enter',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + Enter',
        symbol: 'Alt + Shift + Enter',
    },
};

function isEditableTarget(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return Boolean(
        target.isContentEditable ||
        target.closest(
            'input, textarea, select, [contenteditable="true"], [role="textbox"]',
        ),
    );
}

function matchesShortcut(
    event: KeyboardEvent,
    shortcut: ShortcutDefinition,
): boolean {
    return (
        event.key.toLowerCase() === shortcut.key &&
        event.altKey === shortcut.alt &&
        event.shiftKey === shortcut.shift &&
        event.ctrlKey === shortcut.ctrl &&
        event.metaKey === shortcut.meta
    );
}

export function useActionShortcuts(
    actions: MaybeRefOrGetter<ActionShortcutHandlers>,
): void {
    const handleKeydown = (event: KeyboardEvent): void => {
        if (
            event.defaultPrevented ||
            event.repeat ||
            event.isComposing
        ) {
            return;
        }

        const registeredActions = toValue(actions);

        for (const [name, action] of Object.entries(
            registeredActions,
        ) as [ActionShortcutName, ShortcutAction][]) {
            const shortcut = ACTION_SHORTCUTS[name];

            if (!matchesShortcut(event, shortcut)) {
                continue;
            }

            // Allow registered shortcuts inside inputs and search fields.
            // Do not intercept shortcuts while typing unless one matches.
            if (
                isEditableTarget(event.target) &&
                !matchesShortcut(event, shortcut)
            ) {
                continue;
            }

            if (
                action.enabled !== undefined &&
                !toValue(action.enabled)
            ) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            action.handler();
            return;
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handleKeydown);
    });
}
