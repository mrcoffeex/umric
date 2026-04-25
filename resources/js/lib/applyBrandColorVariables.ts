/**
 * Maps VITE_BRAND_* env (set at build time from .env) onto CSS custom properties.
 * @see resources/css/app.css for fallbacks and --color-* / shadcn token wiring
 */
export function applyBrandColorVariables(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    const normalize = (raw: string | undefined): string | undefined => {
        if (raw === undefined || raw === '') {
            return undefined;
        }

        const t = raw.trim();

        if (
            (t.startsWith('"') && t.endsWith('"')) ||
            (t.startsWith("'") && t.endsWith("'"))
        ) {
            return t.slice(1, -1);
        }

        return t;
    };

    const set = (name: string, value: string | undefined) => {
        const v = normalize(value);

        if (v === undefined || v === '') {
            return;
        }

        root.style.setProperty(name, v);
    };

    set('--brand-primary', import.meta.env.VITE_BRAND_PRIMARY);
    set('--brand-on-primary', import.meta.env.VITE_BRAND_ON_PRIMARY);
    set('--brand-secondary', import.meta.env.VITE_BRAND_SECONDARY);
    set('--brand-on-secondary', import.meta.env.VITE_BRAND_ON_SECONDARY);
    set('--brand-ring', import.meta.env.VITE_BRAND_RING);
    set('--brand-muted', import.meta.env.VITE_BRAND_MUTED);
    set(
        '--brand-on-muted',
        import.meta.env.VITE_BRAND_ON_MUTED ??
            import.meta.env.VITE_BRAND_MUTED_FOREGROUND,
    );
}
