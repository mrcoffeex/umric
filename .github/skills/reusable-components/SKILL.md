---
name: reusable-components
description: "Use when building or modifying Vue components. Guides extracting reusable components from UI code. Trigger when: creating new pages, seeing repeated template blocks, building forms, refactoring or componentizing UI code, asked to 'extract', 'refactor a component', or 'make this reusable'. Covers: check-before-create checklist, extraction criteria (3-strikes rule), component file structure, TypeScript props and slot patterns, shadcn-vue primitives, semantic token guidelines. Skips extraction for one-off page sections with no reuse potential."
---

# Reusable Component Development

## When to Extract a Component

Extract a Vue component when **any one** of these is true:

| Criterion | Threshold |
|-----------|-----------|
| Already used in 2+ places | 2 current uses (not just "might be used") |
| Will be needed in a third place now | Upcoming duplication |
| Template block exceeds ~40 lines | Complexity justifies isolation |
| Domain widget with internal state | Status badge, timeline, file dropzone, modal |
| Repeated compound pattern | label + input + error message trio |

**Do NOT extract** one-off page sections, large layout wrappers with no abstraction benefit, or anything only used once with no known reuse case.

---

## Step 1 — Check Before Creating

### 1a. Check shadcn-vue primitives first (`resources/js/components/ui/`)
Available: `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `card`, `checkbox`, `collapsible`, `dialog`, `dropdown-menu`, `input`, `input-otp`, `label`, `navigation-menu`, `select`, `separator`, `sheet`, `sidebar`, `skeleton`, `sonner`, `spinner`, `tooltip`

Use these for all core UI — never hand-roll buttons, inputs, labels, or cards.

### 1b. Check project-level components (`resources/js/components/`)
Notable existing components to reuse:
- `InputError.vue` — field error message display
- `StatusBadge.vue` — colored status pill with dot
- `PasswordInput.vue` — password toggle input
- `Heading.vue` — page/section heading
- `TrackingTimeline.vue` — timeline list
- `AlertError.vue` — error alert block
- `TextLink.vue` — styled anchor link

---

## Step 2 — Extract the Component

### File location
- General UI component: `resources/js/components/<ComponentName>.vue`
- Feature-specific component: `resources/js/components/<feature>/<ComponentName>.vue` (e.g., `research/`, `admin/`)
- shadcn primitive wrapper: `resources/js/components/ui/<name>/index.ts`

### Component file structure
```vue
<script setup lang="ts">
// 1. Framework imports first
import { computed, ref } from 'vue';
// 2. Child component imports
import { Button } from '@/components/ui/button';
// 3. Props interface (use defineProps generic syntax)
interface Props {
    title: string;
    description?: string;
    variant?: 'default' | 'muted';
}
const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});
// 4. Emits (if any)
const emit = defineEmits<{ change: [value: string] }>();
// 5. Slots: prefer default + named slots over prop-drilling content
</script>

<template>
    <!-- Single root element required -->
    <div>
        <slot />
    </div>
</template>
```

### Naming rules
- PascalCase filename: `SectionCard.vue`, `FormField.vue`
- Descriptive prefix for domain components: `ResearchStatusBadge.vue`, `AdminUserRow.vue`
- No generic names: `Card2.vue`, `NewComponent.vue` are forbidden

---

## Step 3 — Design Props and Slots

### Props: keep them specific, not config-objects
```ts
// Good
interface Props { label: string; error?: string; required?: boolean; }

// Bad — passes too much config as one object
interface Props { config: { label: string; error?: string; required?: boolean; } }
```

### Slots: prefer slots over `content` props for rich markup
```vue
<!-- Good: slot for content -->
<template>
    <div class="rounded-xl border border-border bg-card p-6">
        <h2 v-if="title" class="mb-4 text-sm font-semibold text-muted-foreground uppercase">{{ title }}</h2>
        <slot />
    </div>
</template>

<!-- Good: named slots for structured layouts -->
<slot name="header" />
<slot />
<slot name="footer" />
```

---

## Step 4 — Theming Rules

Always use **semantic Tailwind tokens** — never hardcode light/dark color values.

| Use | Token |
|-----|-------|
| Page/app background | `bg-background` |
| Card/panel surface | `bg-card` |
| Default text | `text-foreground` |
| Subtle/secondary text | `text-muted-foreground` |
| Default border | `border-border` |
| Input border | `border-input` |
| Input background | `bg-background` |
| Subdued fill | `bg-muted` |
| Error text | `text-destructive` |

**Brand accent colors** (use for CTAs, active states, focus rings):
- Primary CTA: `bg-orange-500 hover:bg-orange-600 text-white`
- Secondary/teal accent: `text-teal-500 border-teal-500`
- Focus rings: `focus-visible:border-orange-500 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20`
- Section heading accent border: `border-l-4 border-orange-500`

**Anti-patterns to reject:**
- `bg-white`, `bg-gray-50`, `text-gray-900`, `border-gray-200` — these break in dark mode
- `dark:` variants on `bg-muted`/`bg-background`/`border-border` — already handle dark mode internally
- Raw `MutationObserver` for dark mode detection — use `dark:` Tailwind classes instead

---

## Step 5 — Apply and Replace

1. Create the component file.
2. Import it in the consuming file.
3. Replace the original template block with the component.
4. Confirm the replacing file is shorter/simpler.
5. Run `vendor/bin/sail artisan test --compact` to verify nothing broke.

---

## Composite Patterns Seen in This Project

### Form field (label + input + error)
Candidate for `FormField.vue` if used 3+ times:
```vue
<div>
    <Label class="mb-1.5 block text-sm font-medium text-foreground">{{ label }}</Label>
    <Input v-model="modelValue" :placeholder="placeholder" class="border-input bg-background" />
    <InputError v-if="error" :message="error" />
</div>
```

### Section card with branded heading
Candidate for `SectionCard.vue` — already appears in Create.vue and profile pages:
```vue
<section class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <h2 class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
        {{ title }}
    </h2>
    <slot />
</section>
```
