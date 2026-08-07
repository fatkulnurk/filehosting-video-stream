import { reactive } from 'vue';

const state = reactive({
    visible: false,
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    danger: true,
    resolver: null,
});

export function useConfirm() {
    function confirm(options = {}) {
        Object.assign(
            state,
            {
                title: 'Are you sure?',
                message: '',
                confirmLabel: 'Confirm',
                cancelLabel: 'Cancel',
                danger: true,
            },
            options,
            { visible: true },
        );

        return new Promise((resolve) => {
            state.resolver = resolve;
        });
    }

    function resolve(value) {
        state.visible = false;
        state.resolver?.(value);
        state.resolver = null;
    }

    return { state, confirm, resolve };
}