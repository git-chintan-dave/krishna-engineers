import {reactive, readonly} from 'vue';

export type ConfirmOptions = {
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
};

type ConfirmState = {
    isOpen: boolean;
    title: string;
    description: string;
    confirmText: string;
    cancelText: string;
    resolvePromise: ((value: boolean) => void) | null;
};

const defaults: Required<ConfirmOptions> = {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
};

const state = reactive<ConfirmState>({
    isOpen: false,
    ...defaults,
    resolvePromise: null,
});

const close = (result: boolean) => {
    const resolve = state.resolvePromise;

    state.resolvePromise = null;
    state.isOpen = false;

    resolve?.(result);
};

export function useConfirmState() {
    return {
        state: readonly(state),

        onConfirm: () => {
            close(true);
        },

        onCancel: () => {
            close(false);
        },
    };
}

export function confirm(options: ConfirmOptions = {}): Promise<boolean> {
    return new Promise((resolve) => {
        /*
         * Prevent replacing an active confirmation.
         */
        if (state.isOpen) {
            return resolve(false);
        }

        state.title = options.title ?? defaults.title;

        state.description = options.description ?? defaults.description;

        state.confirmText = options.confirmText ?? defaults.confirmText;

        state.cancelText = options.cancelText ?? defaults.cancelText;

        state.resolvePromise = resolve;
        state.isOpen = true;
    });
}
