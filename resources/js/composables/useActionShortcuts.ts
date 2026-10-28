import type {MaybeRefOrGetter} from 'vue';
import {onBeforeUnmount, onMounted, toValue} from 'vue';

export type ActionShortcutName =
    | 'add'
    | 'bulkDelete'
    | 'resetPreferences'
    | 'import'
    | 'sync'
    | 'search'
    | 'clearSearch'
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
 * Alt + Shift combinations are used to reduce conflicts with common
 * browser and operating-system shortcuts.
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
    search: {
        key: 'e',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + E',
        symbol: 'Alt + Shift + E',
    },
    clearSearch: {
        key: 'x',
        alt: true,
        shift: true,
        ctrl: false,
        meta: false,
        label: 'Alt + Shift + X',
        symbol: 'Alt + Shift + X',
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

    if (
        target.isContentEditable ||
        target.closest('[contenteditable="true"]')
    ) {
        return true;
    }

    return Boolean(
        target.closest(
            [
                'input',
                'textarea',
                'select',
                '[role="textbox"]',
                '[role="combobox"]',
                '[role="spinbutton"]',
            ].join(','),
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
        const isEditing = isEditableTarget(event.target);

        for (const [name, action] of Object.entries(
            registeredActions,
        ) as [ActionShortcutName, ShortcutAction][]) {
            const shortcut = ACTION_SHORTCUTS[name];

            if (!matchesShortcut(event, shortcut)) {
                continue;
            }

            // A disabled action must not execute or consume the shortcut.
            if (
                action.enabled !== undefined &&
                !toValue(action.enabled)
            ) {
                continue;
            }

            // Search shortcuts are allowed while editing form controls.
            if (
                isEditing &&
                name !== 'search' &&
                name !== 'clearSearch'
            ) {
                continue;
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
