import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';

export type Branding = {
    name: string;
    tagline: string | null;
    logoUrl: string | null;
};

/**
 * Global site name, optional tagline, and optional public logo URL (shared via HandleInertiaRequests).
 */
export function useBranding(): ComputedRef<Branding> {
    const page = usePage();

    return computed(() => ({
        name: (page.props.name as string) ?? 'Laravel',
        tagline:
            (page.props.branding as { tagline?: string | null } | undefined)
                ?.tagline ?? null,
        logoUrl:
            (page.props.branding as { logoUrl?: string | null } | undefined)
                ?.logoUrl ?? null,
    }));
}
