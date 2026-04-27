import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicInfoLayout from '@/layouts/PublicInfoLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { applyBrandColorVariables } from '@/lib/applyBrandColorVariables';
import { initializeFlashToast } from '@/lib/flashToast';

applyBrandColorVariables();

function readAppNameFromDocument(): string {
    if (typeof document === 'undefined') {
        return import.meta.env.VITE_APP_NAME || 'Laravel';
    }

    return (
        document.querySelector<HTMLMetaElement>('meta[name="application-name"]')
            ?.content ||
        import.meta.env.VITE_APP_NAME ||
        'Laravel'
    );
}

let appName = readAppNameFromDocument();

if (typeof window !== 'undefined') {
    router.on('navigate', (event) => {
        const name = event.detail.page.props.name;

        if (typeof name === 'string' && name.length > 0) {
            appName = name;
        }
    });
}

const inertiaProgressColor =
    import.meta.env.VITE_BRAND_RING ||
    import.meta.env.VITE_BRAND_PRIMARY ||
    '#4B5563';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Research/PublicTracking':
                return null;
            case name === 'Documentation':
            case name === 'Faq':
            case name === 'Terms':
            case name === 'PrivacyPolicy':
                return PublicInfoLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: inertiaProgressColor,
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
