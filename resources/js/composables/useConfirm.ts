import { ref } from 'vue';

interface ConfirmState {
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    cancelLabel: string;
    destructive: boolean;
    resolve: ((confirmed: boolean) => void) | null;
}

const state = ref<ConfirmState>({
    open: false,
    title: 'Are you sure?',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    destructive: false,
    resolve: null,
});

function confirm(
    message: string,
    options: {
        title?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
    } = {},
): Promise<boolean> {
    return new Promise((resolve) => {
        state.value = {
            open: true,
            message,
            title: options.title ?? 'Are you sure?',
            confirmLabel: options.confirmLabel ?? 'Confirm',
            cancelLabel: options.cancelLabel ?? 'Cancel',
            destructive: options.destructive ?? true,
            resolve,
        };
    });
}

function choose(confirmed: boolean) {
    const resolve = state.value.resolve;
    state.value.open = false;
    state.value.resolve = null;
    resolve?.(confirmed);
}

export function useConfirm() {
    return { state, confirm, choose };
}
