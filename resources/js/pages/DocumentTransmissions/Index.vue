<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Inbox,
    Loader2,
    Plus,
    Search,
    Send,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dashboard } from '@/routes';
import documentTransmissions from '@/routes/document-transmissions';

interface Party {
    id: string;
    name: string;
    email: string;
}

interface Row {
    id: string;
    purpose: string;
    status: string;
    completed_at: string | null;
    created_at: string;
    checklist: { checked: number; total: number };
    other_party: Party | null;
}

interface TabCounts {
    total: number;
    pending: number;
    completed: number;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    direction: 'incoming' | 'outgoing';
    filters: {
        q: string;
        status: 'all' | 'pending' | 'completed';
        per_page: number;
    };
    counts: {
        incoming: TabCounts;
        outgoing: TabCounts;
    };
    handoffs: Paginator<Row>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Document handoffs' },
        ],
    },
});

const searchInput = ref(props.filters.q ?? '');
const statusFilter = ref<'all' | 'pending' | 'completed'>(props.filters.status);
const perPage = ref(props.filters.per_page);
const mounted = ref(false);
const loading = ref(false);

watch(
    () => props.filters.q,
    (q) => {
        searchInput.value = q ?? '';
    },
);

watch(
    () => props.filters.status,
    (s) => {
        statusFilter.value = s;
    },
);

watch(
    () => props.filters.per_page,
    (n) => {
        perPage.value = n;
    },
);

const stopStart = router.on('start', () => {
    loading.value = true;
});
const stopFinish = router.on('finish', () => {
    loading.value = false;
});

onUnmounted(() => {
    stopStart();
    stopFinish();
});

onMounted(() => {
    mounted.value = true;
});

function buildParams(
    overrides: {
        direction?: string;
        page?: number;
        status?: string;
        per_page?: number;
    } = {},
): {
    direction: string;
    q: string | undefined;
    status: string;
    per_page: number;
    page: number;
} {
    return {
        direction: overrides.direction ?? props.direction,
        q: searchInput.value.trim() || undefined,
        status: overrides.status ?? statusFilter.value,
        per_page: overrides.per_page ?? perPage.value,
        page: overrides.page ?? 1,
    };
}

function visitIndex(params: ReturnType<typeof buildParams>) {
    router.get(documentTransmissions.index.url(), params, {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => {
    if (!mounted.value) {
        return;
    }

    visitIndex(buildParams({ page: 1 }));
}, 350);

watch(searchInput, () => {
    debouncedSearch();
});

watch(statusFilter, (s) => {
    if (!mounted.value) {
        return;
    }

    visitIndex(buildParams({ page: 1, status: s }));
});

watch(perPage, (n) => {
    if (!mounted.value) {
        return;
    }

    visitIndex(buildParams({ page: 1, per_page: n }));
});

function onDirectionChange(dir: string | number) {
    const d = String(dir);

    if (d !== 'incoming' && d !== 'outgoing') {
        return;
    }

    visitIndex(buildParams({ direction: d, page: 1 }));
}

function clearSearch() {
    searchInput.value = '';
}

function resetFilters() {
    searchInput.value = '';
    statusFilter.value = 'all';
    perPage.value = 15;
    visitIndex(buildParams({ page: 1, status: 'all', per_page: 15 }));
}

const hasActiveFilters = computed(
    () =>
        (props.filters.q ?? '').length > 0 ||
        props.filters.status !== 'all' ||
        props.filters.per_page !== 15,
);

const activeCounts = computed(() =>
    props.direction === 'incoming'
        ? props.counts.incoming
        : props.counts.outgoing,
);

const partyLabel = computed(() =>
    props.direction === 'incoming' ? 'From' : 'To',
);

const emptyTitle = computed(() => {
    if ((props.filters.q ?? '').length > 0 || props.filters.status !== 'all') {
        return 'No handoffs match your filters';
    }

    return props.direction === 'incoming'
        ? 'No incoming handoffs yet'
        : 'No outgoing handoffs yet';
});

const emptyHint = computed(() => {
    if ((props.filters.q ?? '').length > 0 || props.filters.status !== 'all') {
        return 'Try a different search, switch status, or reset filters.';
    }

    return props.direction === 'incoming'
        ? 'When someone sends you a handoff, it will appear here.'
        : 'Create a handoff to send a document checklist to someone else.';
});

function formatListDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

const statusPills = computed(() => [
    { key: 'all' as const, label: 'All', count: activeCounts.value.total },
    {
        key: 'pending' as const,
        label: 'Open',
        count: activeCounts.value.pending,
    },
    {
        key: 'completed' as const,
        label: 'Complete',
        count: activeCounts.value.completed,
    },
]);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Document handoffs" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-foreground md:text-2xl">
                    Document handoffs
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Search and filter your incoming and outgoing checklists.
                    Open a row for share links, QR codes, and receipt history.
                </p>
            </div>
            <Button
                as-child
                class="shrink-0 bg-orange-500 text-white hover:bg-orange-600"
            >
                <Link
                    :href="documentTransmissions.create.url()"
                    class="inline-flex items-center gap-2"
                >
                    <Plus class="h-4 w-4" />
                    New handoff
                </Link>
            </Button>
        </div>

        <Tabs
            class="w-full"
            :model-value="direction"
            @update:model-value="onDirectionChange"
        >
            <TabsList
                class="grid h-auto w-full grid-cols-2 gap-1 p-1 sm:inline-flex sm:w-auto sm:grid-cols-none"
            >
                <TabsTrigger
                    value="incoming"
                    class="gap-1.5 px-3 py-2 text-sm data-[state=active]:ring-1 data-[state=active]:ring-orange-500/30"
                >
                    <Inbox class="h-4 w-4 shrink-0 text-teal-600" />
                    <span>Incoming</span>
                    <Badge
                        v-if="counts.incoming.pending > 0"
                        variant="secondary"
                        class="border-teal-200 bg-teal-100 text-[10px] text-teal-900 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-200"
                    >
                        {{ counts.incoming.pending }} open
                    </Badge>
                    <span
                        class="hidden text-[11px] text-muted-foreground sm:inline"
                    >
                        ({{ counts.incoming.total }})
                    </span>
                </TabsTrigger>
                <TabsTrigger
                    value="outgoing"
                    class="gap-1.5 px-3 py-2 text-sm data-[state=active]:ring-1 data-[state=active]:ring-orange-500/30"
                >
                    <Send class="h-4 w-4 shrink-0 text-orange-600" />
                    <span>Outgoing</span>
                    <span
                        class="hidden text-[11px] text-muted-foreground sm:inline"
                    >
                        ({{ counts.outgoing.total }})
                    </span>
                </TabsTrigger>
            </TabsList>
        </Tabs>

        <Card class="border-border shadow-sm">
            <CardContent class="space-y-4 p-4 md:p-5">
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="searchInput"
                            type="search"
                            placeholder="Search purpose or person (name, email)…"
                            class="h-10 border-border pr-10 pl-9"
                            autocomplete="off"
                            :aria-busy="loading"
                        />
                        <button
                            v-if="searchInput.length > 0"
                            type="button"
                            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            title="Clear search"
                            @click="clearSearch"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2 lg:justify-end"
                    >
                        <Loader2
                            v-if="loading"
                            class="h-4 w-4 shrink-0 animate-spin text-muted-foreground"
                            aria-hidden="true"
                        />
                        <label
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="whitespace-nowrap">Per page</span>
                            <select
                                v-model.number="perPage"
                                class="h-9 rounded-md border border-input bg-background px-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                        </label>
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="shrink-0"
                            @click="resetFilters"
                        >
                            Reset filters
                        </Button>
                    </div>
                </div>

                <div
                    class="flex flex-wrap gap-2 border-b border-border pb-3"
                    role="group"
                    aria-label="Status filter"
                >
                    <button
                        v-for="pill in statusPills"
                        :key="pill.key"
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition',
                            statusFilter === pill.key
                                ? 'border-orange-500 bg-orange-500 text-white shadow-sm'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        @click="statusFilter = pill.key"
                    >
                        {{ pill.label }}
                        <span
                            :class="[
                                'rounded-full px-1.5 py-0 text-[10px] tabular-nums',
                                statusFilter === pill.key
                                    ? 'bg-white/20 text-white'
                                    : 'bg-muted text-foreground',
                            ]"
                        >
                            {{ pill.count }}
                        </span>
                    </button>
                </div>

                <p
                    v-if="handoffs.total > 0"
                    class="text-xs text-muted-foreground"
                >
                    Showing
                    <span class="font-medium text-foreground">{{
                        handoffs.from ?? 0
                    }}</span>
                    –
                    <span class="font-medium text-foreground">{{
                        handoffs.to ?? 0
                    }}</span>
                    of
                    <span class="font-medium text-foreground">{{
                        handoffs.total
                    }}</span>
                    {{ direction === 'incoming' ? 'incoming' : 'outgoing' }}
                    handoffs
                </p>

                <div v-if="!handoffs.data.length" class="py-10 text-center">
                    <p class="text-sm font-medium text-foreground">
                        {{ emptyTitle }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ emptyHint }}
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <Link
                        v-for="row in handoffs.data"
                        :key="row.id"
                        :href="documentTransmissions.show.url(row.id)"
                        class="flex items-start gap-3 rounded-xl border border-border bg-card p-3 transition hover:border-orange-300 hover:bg-orange-50/40 dark:hover:bg-orange-950/20"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="line-clamp-2 text-sm font-medium text-foreground"
                            >
                                {{ row.purpose }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ partyLabel }}
                                {{ row.other_party?.name ?? 'Unknown' }}
                                <span
                                    v-if="row.other_party?.email"
                                    class="hidden sm:inline"
                                >
                                    · {{ row.other_party.email }}
                                </span>
                                · {{ row.checklist.checked }}/{{
                                    row.checklist.total
                                }}
                                checked · {{ formatListDate(row.created_at) }}
                            </p>
                            <p class="mt-1">
                                <span
                                    v-if="row.status === 'completed'"
                                    class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[10px] font-semibold text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-300"
                                >
                                    Complete
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
                                >
                                    Open
                                </span>
                            </p>
                        </div>
                        <ArrowRight
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                    </Link>
                </div>

                <div
                    v-if="handoffs.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-border pt-4 text-sm"
                >
                    <p class="text-xs text-muted-foreground">
                        Page {{ handoffs.current_page }} /
                        {{ handoffs.last_page }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template
                            v-for="(link, index) in handoffs.links"
                            :key="`${index}-${link.label}`"
                        >
                            <button
                                v-if="index === 0"
                                type="button"
                                :disabled="!link.url"
                                class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                @click="link.url && router.get(link.url)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>
                            <button
                                v-else-if="index === handoffs.links.length - 1"
                                type="button"
                                :disabled="!link.url"
                                class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                @click="link.url && router.get(link.url)"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                            <button
                                v-else
                                type="button"
                                :disabled="!link.url"
                                :class="[
                                    'min-w-[2rem] rounded-lg border px-2 py-1.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                                    link.active
                                        ? 'border-orange-500 bg-orange-500 text-white'
                                        : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                                ]"
                                @click="link.url && router.get(link.url)"
                            >
                                <span v-html="link.label" />
                            </button>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            class="flex items-start gap-3 rounded-xl border border-dashed border-border bg-muted/30 p-4 text-sm text-muted-foreground"
        >
            <ClipboardList class="mt-0.5 h-4 w-4 shrink-0 text-orange-500" />
            <p>
                A handoff is a checklist of document titles plus an optional PDF
                per line. The receiver ticks the rows they accept and confirms
                receipt once on the handoff detail page. History is kept for
                every confirmation.
            </p>
        </div>
    </div>
</template>
