---
name: tailwind-mobile-responsive
description: >-
    Writes mobile-first, touch-friendly Tailwind CSS (v3/v4) in Vue, Blade, JSX.
    Covers default-to-narrow layouts, breakpoint order (sm/md/lg/xl), overflow and
    horizontal scroll, min touch targets, safe-area insets, sticky headers with
    scroll-margin, and avoiding desktop-only assumptions. Invoke when the user asks
    for mobile-friendly, responsive, small-screen, touch, viewport, or adaptive
    layouts; when fixing horizontal overflow or clipped content on phones; or when
    adding sm:/md:/lg: utilities. Complements tailwindcss-development; use both
    when styling UI for multiple breakpoints.
---

# Mobile-compatible Tailwind CSS

## Mindset: mobile first

- **Default classes = smallest screens.** Add `sm:`, `md:`, `lg:` for larger viewports only.
- **Progressive enhancement:** start with one column, stacked flow, full-width blocks; then add side-by-side or grids at `sm`/`md`+.

```html
<!-- Good: column on phone, row on tablet+ -->
<div
    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
></div>
```

```html
<!-- Good: one column, then 2 / 3 cols -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
```

## Breakpoints (use consistently with the project)

Tailwind defaults: `sm` 40rem, `md` 48rem, `lg` 64rem, `xl` 80rem, `2xl` 96rem. **Min-width** queries: `md:flex` applies at `md` and up.

- Prefer **`max-*` only when** you need an exception on small screens only, e.g. `max-sm:hidden` (less common than mobile-first base + `sm:`+).

## Touch and interaction

- **Minimum tap target ~44×44px:** use `min-h-11 min-w-11` (2.75rem) or `p-3` on icon-only buttons; avoid `h-6 w-6` click areas without padding.
- **`active:` states** for press feedback on touch: `active:opacity-90`, `active:scale-[0.98]`.
- **Don’t rely on hover-only** for critical actions; ensure the same action is obvious on touch (hover can enhance on `md:hover:`).

## Overflow and horizontal scroll

- **Long content:** `min-w-0` on flex/grid children that should shrink; `truncate` or `break-words` for text; `overflow-x-auto` for tables/code with `-mx-*` + `px-*` pattern if full-bleed scroll is intended.
- **Pills / tabs:** `flex flex-nowrap gap-2 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none]` with optional `[&::-webkit-scrollbar]:hidden` for horizontal nav (match project patterns).
- **Never** force wide fixed widths on inner content without `max-w-full` or `w-full` where appropriate.

## Spacing and typography on small screens

- Tighter padding by default, relax at `md:`: `p-4 md:p-6`, `gap-4 md:gap-6`.
- Slightly smaller type on mobile if needed: `text-sm md:text-base`, `text-lg md:text-xl` for headings.

## Media and embeds

- **Images/video:** `max-w-full h-auto`, containers with `aspect-video` + `object-cover` when cropping is OK.
- **Full-bleed on small only:** combine with `mx-auto max-w-*` on the page shell so content doesn’t span unusably wide on desktop.

## Sticky headers and anchor links

- Sticky bars: `sticky top-0 z-10` (or `top-*` below a global header if the layout has one).
- Sections linked with hashes: add **`scroll-mt-*`** on targets (e.g. `scroll-mt-20` or `scroll-mt-24`) so content isn’t hidden under the sticky nav.
- Respect **`prefers-reduced-motion`** for animations (project may set this in global CSS; don’t add motion-only essential affordances).

## Safe areas (notched devices)

- For fixed bottom UI or full-width bars on iOS: add `pb-safe` / `pt-safe` **if** the project includes safe-area utilities in `@theme` or plugins; otherwise `pb-[env(safe-area-inset-bottom)]` as needed.

## Forms

- **Inputs:** `w-full` on narrow viewports; `text-base` on inputs on mobile to avoid iOS zoom-on-focus (16px effective). If design uses `text-sm`, add `md:text-sm` and keep `text-base` at default or use 16px min on the input.
- **Labels and errors:** stack with `space-y-2`; don’t require horizontal label+field pairs on `xs` unless using `sm:grid` for two columns at `sm`+.

## What to avoid

- Desktop-first: `flex-row` then `max-md:flex-col` everywhere — prefer the inverse (`flex-col sm:flex-row`).
- Tiny interactive zones without padding.
- `w-[600px]` (or any fixed width) on inner content without `max-w-full` / responsive overrides.
- Relying on hover for tooltips as the only explanation (poor on touch).

## Checklist before finishing a responsive change

1. Default layout works at **~320px** width (stacked, no horizontal scroll).
2. Touch targets for primary actions are **at least ~44px** in one dimension.
3. **Breakpoints** go from small to large; no missing `flex-wrap` where chips/tags could overflow.
4. Sticky/anchor patterns use **`scroll-mt-*`** when in-page nav or hash links exist.
5. **Dark mode** variants match the rest of the file if the project uses `dark:`.

## Relation to other skills

- For general Tailwind v4 rules, tokens, and dark mode: **`tailwindcss-development`**.
- This skill is **only** the mobile/responsive and touch-focused layer on top of that.
