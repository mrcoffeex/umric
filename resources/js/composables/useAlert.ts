import { ref } from 'vue';

type AlertVariant = 'info' | 'success' | 'warning' | 'danger';

interface AlertState {
    open: boolean;
    title: string;
    message: string;
    variant: AlertVariant;
    confirmLabel: string;
    resolve: (() => void) | null;
}

const state = ref<AlertState>({
    open: false,
    title: '',
    message: '',
    variant: 'info',
    confirmLabel: 'OK',
    resolve: null,
});

function alert(
    message: string,
    options: {
        title?: string;
        variant?: AlertVariant;
        confirmLabel?: string;
    } = {},
): Promise<void> {
    return new Promise((resolve) => {
        state.value = {
            open: true,
            message,
            title: options.title ?? variantTitle(options.variant ?? 'info'),
            variant: options.variant ?? 'info',
            confirmLabel: options.confirmLabel ?? 'OK',
            resolve,
        };
    });
}

function close() {
    const resolve = state.value.resolve;
    state.value.open = false;
    state.value.resolve = null;
    resolve?.();
}

function variantTitle(variant: AlertVariant): string {
    const titles: Record<AlertVariant, string> = {
        info: 'Information',
        success: 'Success',
        warning: 'Warning',
        danger: 'Error',
    };

    return titles[variant];
}

export function useAlert() {
    return { state, alert, close };
}
