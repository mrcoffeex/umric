<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    FileSearch,
    Search,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { getStepBadgeClass } from '@/lib/step-colors';
import admin from '@/routes/admin';

interface PaperRow {
    id: number;
    tracking_id: string;
    title: string;
    current_step: string;
    step_label?: string;
    created_at: string;
    user?: { name: string } | null;
    school_class?: { name: string } | null;
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

interface Props {
    papers: Paginator<PaperRow>;
    stepCounts: Record<string, number>;
    filters: { step: string | null; search: string | null };
    stepLabels: Record<string, string>;
    facultyUsers: Array<{ id: number; name: string }>;
    staffUsers: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const selectedStep = ref(props.filters.step ?? '');
const mounted = ref(false);
let debounce: ReturnType<typeof setTimeout>;

onMounted(() => {
    mounted.value = true;
});

watch([search, selectedStep], () => {
    if (!mounted.value) {
        return;
    }

    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            admin.research.index(),
            {
                search: search.value || undefined,
                step: selectedStep.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 350);
});

const stepTabs = computed(() => [
    { key: '', label: 'All', count: props.papers.total },
    ...Object.entries(props.stepLabels).map(([key, label]) => ({
        key,
        label,
        count: Number(props.stepCounts[key] ?? 0),
    })),
]);

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Research', href: admin.research.index() },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    Research Workflow
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Review and monitor all papers in the workflow pipeline.
                </p>
            </div>
            <div class="text-sm text-muted-foreground">
                {{ papers.total }} papers
            </div>
        </div>

        <div class="relative">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <input
                v-model="search"
                type="text"
                placeholder="Search by tracking ID, title, or student..."
                class="w-full rounded-xl border border-input bg-background py-2 pl-9 pr-3 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
            />
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                v-for="tab in stepTabs"
                :key="tab.key || 'all'"
                type="button"
                @click="selectedStep = tab.key"
                :class="[
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                    selectedStep === tab.key
                        ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-950/30 dark:text-orange-300'
                        : 'border-border bg-card text-muted-foreground hover:border-orange-300 hover:text-orange-600',
                ]"
            >
                {{ tab.label }}
                <span class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] dark:bg-white/10">{{ tab.count }}</span>
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            <div v-if="papers.data.length === 0" class="p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30">
                    <FileSearch class="h-5 w-5 text-orange-400" />
                </div>
                <p class="font-semibold text-foreground">No papers found</p>
                <p class="mt-1 text-sm text-muted-foreground">Adjust search or step filters.</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tracking ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Title</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell">Student</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Current Step</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell">Date</th>
                        <th class="w-24 px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="paper in papers.data"
                        :key="paper.id"
                        class="hover:bg-muted/50"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-foreground">
                            {{ paper.tracking_id }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="line-clamp-1 font-semibold text-foreground">
                                {{ paper.title }}
                            </p>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell">
                            {{ paper.user?.name ?? '-' }}
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground xl:table-cell">
                            {{ paper.school_class?.name ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                    getStepBadgeClass(paper.current_step),
                                ]"
                            >
                                {{ paper.step_label ?? stepLabel(paper.current_step) }}
                            </span>
                        </td>
                        <td class="hidden px-4 py-3 text-xs text-muted-foreground md:table-cell">
                            {{ formatDate(paper.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="admin.research.show.url({ paper: paper.id })"
                                class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted"
                            >
                                <Eye class="h-4 w-4" /> View
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="papers.last_page > 1" class="flex items-center justify-between text-sm">
            <p class="text-muted-foreground">
                Showing {{ papers.from }}-{{ papers.to }} of {{ papers.total }}
            </p>
            <div class="flex items-center gap-1">
                <template v-for="link in papers.links" :key="link.label">
                    <button
                        v-if="link.label === '&laquo; Previous'"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-else-if="link.label === 'Next &raquo;'"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        :class="[
                            'min-w-[2rem] rounded-lg border px-2 py-1.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                            link.active
                                ? 'border-orange-500 bg-orange-500 text-white'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
