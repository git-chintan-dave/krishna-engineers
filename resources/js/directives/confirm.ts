import {type Directive} from 'vue';

import {confirm, type ConfirmOptions} from '@/composables/useConfirm';

type ConfirmAction = () => void | Promise<void>;

type ConfirmBinding =
    | ConfirmAction
    | {
    action: ConfirmAction;
    options?: ConfirmOptions;
};

type ConfirmElement = HTMLElement & {
    __confirmClick?: EventListener;
    __confirmBinding?: ConfirmBinding;
};

const getBinding = (
    value: ConfirmBinding,
): {
    action: ConfirmAction;
    options: ConfirmOptions;
} => {
    if (typeof value === 'function') {
        return {
            action: value,
            options: {},
        };
    }

    return {
        action: value.action,
        options: value.options ?? {},
    };
};

export const executeConfirm = async (
    binding: ConfirmBinding,
): Promise<void> => {
    const { action, options } = getBinding(binding);

    const confirmed = await confirm(options);

    if (!confirmed) {
        return;
    }

    await action();
};

const vConfirm: Directive<HTMLElement, ConfirmBinding> = {
    mounted(el, binding) {
        const element = el as ConfirmElement;

        element.__confirmBinding = binding.value;

        const handleClick = async (event: Event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!element.__confirmBinding) {
                return;
            }

            await executeConfirm(element.__confirmBinding);
        };

        element.__confirmClick = handleClick;

        el.addEventListener('click', handleClick);
    },

    updated(el, binding) {
        const element = el as ConfirmElement;

        element.__confirmBinding = binding.value;
    },

    unmounted(el) {
        const element = el as ConfirmElement;

        if (element.__confirmClick) {
            el.removeEventListener('click', element.__confirmClick);

            delete element.__confirmClick;
        }

        delete element.__confirmBinding;
    },
};

export default vConfirm;
