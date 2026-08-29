<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    CheckCircle2,
    ClipboardList,
    Info,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

export type InsightTone = 'urgent' | 'waiting' | 'info' | 'done';

export interface InsightAction {
    id: string;
    tone: InsightTone | string;
    title: string;
    description: string;
    cta_label?: string | null;
    cta_href?: string | null;
    count?: number | null;
    show_join?: boolean;
}

export interface InsightHealth {
    id: string;
    label: string;
    value: string | number;
    hint?: string | null;
}

export interface InsightsPayload {
    actions: InsightAction[];
    health: InsightHealth[];
}

const props = withDefaults(
    defineProps<{
        insights: InsightsPayload;
        /** Label above the primary banner */
        primaryLabel?: string;
        /** Label above secondary action list */
        listLabel?: string;
    }>(),
    {
        primaryLabel: 'Next action',
        listLabel: 'Also needs attention',
    },
);

const primary = computed(() => props.insights.actions[0] ?? null);
const secondary = computed(() => props.insights.actions.slice(1));
const health = computed(() => props.insights.health ?? []);
const isEmpty = computed(() => !primary.value && health.value.length === 0);

function toneRailClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-amber-500';
    }

    if (tone === 'done') {
        return 'bg-teal-500';
    }

    if (tone === 'waiting') {
        return 'bg-slate-400 dark:bg-slate-500';
    }

    return 'bg-orange-500';
}

function toneWashClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-gradient-to-br from-amber-50 via-amber-50/40 to-transparent dark:from-amber-950/40 dark:via-amber-950/15 dark:to-transparent';
    }

    if (tone === 'done') {
        return 'bg-gradient-to-br from-teal-50 via-teal-50/40 to-transparent dark:from-teal-950/40 dark:via-teal-950/15 dark:to-transparent';
    }

    if (tone === 'waiting') {
        return 'bg-gradient-to-br from-muted/80 via-muted/30 to-transparent';
    }

    return 'bg-gradient-to-br from-orange-50 via-orange-50/40 to-transparent dark:from-orange-950/40 dark:via-orange-950/15 dark:to-transparent';
}

function toneIconWrapClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-amber-500 text-white shadow-sm shadow-amber-500/25';
    }

    if (tone === 'done') {
        return 'bg-teal-500 text-white shadow-sm shadow-teal-500/25';
    }

    if (tone === 'waiting') {
        return 'bg-background text-muted-foreground ring-1 ring-border';
    }

    return 'bg-orange-500 text-white shadow-sm shadow-orange-500/25';
}

function toneCtaClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-amber-500 text-white hover:bg-amber-600 focus-visible:ring-amber-400/40';
    }

    if (tone === 'done') {
        return 'bg-teal-500 text-white hover:bg-teal-600 focus-visible:ring-teal-400/40';
    }

    if (tone === 'waiting') {
        return 'bg-foreground text-background hover:bg-foreground/90 focus-visible:ring-foreground/20';
    }

    return 'bg-orange-500 text-white hover:bg-orange-600 focus-visible:ring-orange-400/40';
}

function toneBadgeClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-amber-500/15 text-amber-800 dark:text-amber-300';
    }

    if (tone === 'done') {
        return 'bg-teal-500/15 text-teal-800 dark:text-teal-300';
    }

    if (tone === 'waiting') {
        return 'bg-muted text-muted-foreground';
    }

    return 'bg-orange-500/15 text-orange-800 dark:text-orange-300';
}

function toneListIconClass(tone: string): string {
    if (tone === 'urgent') {
        return 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400';
    }

    if (tone === 'done') {
        return 'bg-teal-50 text-teal-600 dark:bg-teal-950/50 dark:text-teal-400';
    }

    if (tone === 'waiting') {
        return 'bg-muted text-muted-foreground';
    }

    return 'bg-orange-50 text-orange-600 dark:bg-orange-950/50 dark:text-orange-400';
}

function toneLabel(tone: string): string {
    if (tone === 'urgent') {
        return 'Needs action';
    }

    if (tone === 'done') {
        return 'All clear';
    }

    if (tone === 'waiting') {
        return 'In progress';
    }

    return 'FYI';
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-border bg-card motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-1"
        aria-label="Smart insights"
    >
        <!-- Empty -->
        <div
            v-if="isEmpty"
            class="flex flex-col items-center justify-center gap-2 px-5 py-10 text-center"
        >
            <span
                class="flex size-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400"
            >
                <CheckCircle2 class="h-5 w-5" />
            </span>
            <p class="text-sm font-semibold text-foreground">Nothing urgent</p>
            <p class="max-w-xs text-sm text-muted-foreground">
                New updates and next steps will show up here.
            </p>
        </div>

        <template v-else>
            <!-- Primary action -->
            <div
                v-if="primary"
                :class="[
                    'relative overflow-hidden',
                    toneWashClass(primary.tone),
                ]"
            >
                <div
                    :class="[
                        'absolute inset-y-0 left-0 w-1',
                        toneRailClass(primary.tone),
                    ]"
                    aria-hidden="true"
                />

                <div
                    class="flex flex-col gap-4 p-4 pl-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5 sm:pl-6"
                >
                    <div class="flex min-w-0 items-start gap-3.5">
                        <span
                            :class="[
                                'mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-xl',
                                toneIconWrapClass(primary.tone),
                            ]"
                        >
                            <AlertCircle
                                v-if="primary.tone === 'urgent'"
                                class="h-5 w-5"
                            />
                            <CheckCircle2
                                v-else-if="primary.tone === 'done'"
                                class="h-5 w-5"
                            />
                            <ClipboardList
                                v-else-if="primary.tone === 'waiting'"
                                class="h-5 w-5"
                            />
                            <Info v-else class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="text-[11px] font-bold tracking-[0.14em] text-muted-foreground uppercase"
                                >
                                    {{ primaryLabel }}
                                </p>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase',
                                        toneBadgeClass(primary.tone),
                                    ]"
                                >
                                    {{ toneLabel(primary.tone) }}
                                </span>
                                <span
                                    v-if="
                                        primary.count != null &&
                                        primary.count > 0
                                    "
                                    class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-md bg-foreground/10 px-1.5 text-[11px] font-bold text-foreground tabular-nums"
                                >
                                    {{ primary.count }}
                                </span>
                            </div>

                            <h2
                                class="text-base font-bold tracking-tight text-foreground sm:text-lg"
                            >
                                {{ primary.title }}
                            </h2>
                            <p
                                class="max-w-2xl text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ primary.description }}
                            </p>
                        </div>
                    </div>

                    <slot
                        v-if="primary.show_join"
                        name="primary-cta"
                        :action="primary"
                    />

                    <Link
                        v-else-if="primary.cta_href"
                        :href="primary.cta_href"
                        :class="[
                            'inline-flex h-11 min-h-11 w-full shrink-0 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold transition focus-visible:ring-2 focus-visible:outline-none active:scale-[0.98] sm:w-auto',
                            toneCtaClass(primary.tone),
                        ]"
                    >
                        {{ primary.cta_label ?? 'Open' }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>

            <!-- Pipeline health -->
            <div
                v-if="health.length > 0"
                class="border-t border-border/80 bg-muted/25"
            >
                <div class="flex items-center gap-2 px-4 pt-3 sm:px-5">
                    <Sparkles
                        class="h-3.5 w-3.5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p
                        class="text-[11px] font-bold tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        Pipeline health
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-px"
                    :class="
                        health.length >= 4
                            ? 'sm:grid-cols-4'
                            : health.length === 3
                              ? 'sm:grid-cols-3'
                              : 'sm:grid-cols-2'
                    "
                >
                    <div
                        v-for="(metric, index) in health"
                        :key="metric.id"
                        class="group relative px-4 py-3.5 sm:px-5"
                    >
                        <div
                            v-if="index > 0"
                            class="pointer-events-none absolute inset-y-3 left-0 hidden w-px bg-border sm:block"
                            aria-hidden="true"
                        />
                        <p
                            class="text-[10px] font-bold tracking-[0.1em] text-muted-foreground uppercase"
                        >
                            {{ metric.label }}
                        </p>
                        <p
                            class="mt-1 text-xl font-bold tracking-tight text-foreground tabular-nums transition-colors group-hover:text-orange-600 dark:group-hover:text-orange-400"
                        >
                            {{ metric.value }}
                        </p>
                        <p
                            v-if="metric.hint"
                            class="mt-0.5 line-clamp-1 text-[11px] text-muted-foreground"
                        >
                            {{ metric.hint }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Secondary actions -->
            <div v-if="secondary.length > 0" class="border-t border-border">
                <div
                    class="flex items-center justify-between gap-2 px-4 py-2.5 sm:px-5"
                >
                    <p
                        class="text-[11px] font-bold tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ listLabel }}
                    </p>
                    <span
                        class="rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground tabular-nums"
                    >
                        {{ secondary.length }}
                    </span>
                </div>

                <ul>
                    <li
                        v-for="item in secondary"
                        :key="item.id"
                        class="border-t border-border/70 first:border-t-0"
                    >
                        <component
                            :is="item.cta_href ? Link : 'div'"
                            v-bind="
                                item.cta_href
                                    ? { href: item.cta_href }
                                    : undefined
                            "
                            class="group flex min-h-12 items-start gap-3 px-4 py-3.5 transition-colors sm:items-center sm:px-5"
                            :class="
                                item.cta_href
                                    ? 'hover:bg-muted/50 active:bg-muted/70'
                                    : ''
                            "
                        >
                            <span
                                :class="[
                                    'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg sm:mt-0',
                                    toneListIconClass(item.tone),
                                ]"
                            >
                                <AlertCircle
                                    v-if="item.tone === 'urgent'"
                                    class="h-4 w-4"
                                />
                                <ClipboardList
                                    v-else-if="item.tone === 'waiting'"
                                    class="h-4 w-4"
                                />
                                <CheckCircle2
                                    v-else-if="item.tone === 'done'"
                                    class="h-4 w-4"
                                />
                                <Info v-else class="h-4 w-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                >
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        {{ item.title }}
                                    </p>
                                    <span
                                        v-if="
                                            item.count != null && item.count > 0
                                        "
                                        :class="[
                                            'inline-flex min-h-5 items-center rounded-md px-1.5 text-[11px] font-bold tabular-nums',
                                            toneBadgeClass(item.tone),
                                        ]"
                                    >
                                        {{ item.count }}
                                    </span>
                                </div>
                                <p
                                    class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-muted-foreground sm:line-clamp-1"
                                >
                                    {{ item.description }}
                                </p>
                            </div>

                            <span
                                v-if="item.cta_href"
                                class="mt-1 flex shrink-0 items-center gap-1 text-xs font-semibold text-muted-foreground transition group-hover:text-orange-600 sm:mt-0 dark:group-hover:text-orange-400"
                            >
                                <span class="hidden sm:inline">
                                    {{ item.cta_label ?? 'Open' }}
                                </span>
                                <ArrowRight
                                    class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                />
                            </span>
                        </component>
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
