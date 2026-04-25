import type { Auth } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        /** Main brand; default Button uses `primary` → maps here */
        readonly VITE_BRAND_PRIMARY?: string;
        readonly VITE_BRAND_ON_PRIMARY?: string;
        /** Secondary CTA, secondary button, chart accents */
        readonly VITE_BRAND_SECONDARY?: string;
        readonly VITE_BRAND_ON_SECONDARY?: string;
        readonly VITE_BRAND_RING?: string;
        readonly VITE_BRAND_MUTED?: string;
        readonly VITE_BRAND_MUTED_FOREGROUND?: string;
        readonly VITE_BRAND_ON_MUTED?: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            documentHandoffs: { incomingPending: number };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
