import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicInfoLayout from '@/layouts/PublicInfoLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { applyBrandColorVariables } from '@/lib/applyBrandColorVariables';
import { initializeFlashToast } from '@/lib/flashToast';

applyBrandColorVariables();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
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
