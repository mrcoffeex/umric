<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, FileText, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { getStepBadgeClass } from '@/lib/step-colors';
import student from '@/routes/student';

interface Paper {
    id: number;
    title: string;
    abstract?: string | null;
    tracking_id: string;
    status: string;
    current_step: string;
    created_at: string;
    user_id: number;
    proponents?: Array<{ id: string; name: string }> | null;
    school_class?: { id: number; name: string; section?: string | null } | null;
    adviser?: { id: number; name: string } | null;
}

interface Props {
    papers: Paper[];
    stepLabels: Record<string, string>;
    steps: string[];
}

const props = defineProps<Props>();
const page = usePage();
const authUserId = computed(() => (page.props.auth as { user: { id: number } }).user.id);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Research', href: student.research.index() },
        ],
    },
});

const searchQuery = ref('');

const filteredPapers = computed(() => {
    if (!searchQuery.value) {
        return props.papers;
    }

    const q = searchQuery.value.toLowerCase();

    return props.papers.filter(
        (p) => p.title.toLowerCase().includes(q) || p.tracking_id.toLowerCase().includes(q),
    );
});

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function progressPercent(paper: Paper): number {
    const idx = props.steps.indexOf(paper.current_step);

    if (idx < 0 || props.steps.length <= 1) {
        return 0;
    }

    return Math.round((idx / (props.steps.length - 1)) * 100);
}

function isOwner(paper: Paper): boolean {
    return paper.user_id === authUserId.value;
}

function canEdit(paper: Paper): boolean {
    return isOwner(paper) && paper.current_step === 'title_proposal';
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ── Delete Confirmation ─────────────────────────────
const showDeleteModal = ref(false);
const deletePaper = ref<Paper | null>(null);
const deleteConfirmText = ref('');
const deleteProcessing = ref(false);

function openDelete(paper: Paper): void {
    deletePaper.value = paper;
    deleteConfirmText.value = '';
    showDeleteModal.value = true;
}

function submitDelete(): void {
    if (!deletePaper.value) {
        return;
    }

    deleteProcessing.value = true;
    router.delete(student.research.destroy.url(deletePaper.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
        },
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
}
</script>

<template>
    <Head title="My Research" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">My Research</h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">Track and manage your research papers.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                    @click="router.visit(student.research.create.url())"
                >
                    <Plus class="h-4 w-4" />
                    New Title Proposal
                </button>
            </div>
        </section>

        <!-- Search -->
        <div class="relative">
            <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by title or tracking ID…"
                class="w-full rounded-xl border border-border bg-card py-2.5 pr-4 pl-10 text-sm text-foreground outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/30"
            />
        </div>

        <!-- Papers List -->
        <div v-if="filteredPapers.length > 0" class="space-y-3">
            <a
                v-for="paper in filteredPapers"
                :key="paper.id"
                :href="student.research.show.url(paper.id)"
                class="group block rounded-2xl border border-border bg-card transition hover:border-orange-300 hover:shadow-sm dark:hover:border-orange-800"
                @click.prevent="router.visit(student.research.show.url(paper.id))"
            >
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:gap-5">
                    <!-- Main info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-bold text-foreground group-hover:text-orange-600 dark:group-hover:text-orange-400">
                                {{ paper.title }}
                            </h3>
                            <span v-if="!isOwner(paper)" class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300">
                                Co-proponent
                            </span>
                        </div>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <code class="rounded bg-orange-50 px-2 py-0.5 font-mono text-[11px] text-orange-600 dark:bg-orange-950/40 dark:text-orange-400">
                                {{ paper.tracking_id }}
                            </code>
                            <span v-if="paper.school_class">{{ paper.school_class.name }}</span>
                            <span v-if="paper.adviser">Adviser: {{ paper.adviser.name }}</span>
                            <span>{{ formatDate(paper.created_at) }}</span>
                        </div>

                        <!-- Mini progress bar -->
                        <div class="mt-2.5 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-orange-500 transition-all"
                                    :style="{ width: progressPercent(paper) + '%' }"
                                />
                            </div>
                            <span class="text-[10px] font-semibold text-muted-foreground">{{ progressPercent(paper) }}%</span>
                        </div>
                    </div>

                    <!-- Right side: step badge + actions -->
                    <div class="flex shrink-0 items-center gap-2">
                        <span :class="['rounded-full px-2.5 py-1 text-[11px] font-semibold', getStepBadgeClass(paper.current_step)]">
                            {{ stepLabel(paper.current_step) }}
                        </span>

                        <template v-if="canEdit(paper)">
                            <button
                                type="button"
                                class="rounded-lg border border-border p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                title="Edit"
                                @click.stop.prevent="router.visit(student.research.edit.url(paper.id))"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-red-200 p-2 text-red-400 transition hover:bg-red-50 hover:text-red-600 dark:border-red-900 dark:hover:bg-red-950/30"
                                title="Delete"
                                @click.stop.prevent="openDelete(paper)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </template>
                    </div>
                </div>
            </a>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-16">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30">
                <FileText class="h-8 w-8 text-orange-400" />
            </div>
            <h3 class="mb-1 text-lg font-bold text-foreground">No research papers yet</h3>
            <p class="mb-5 text-sm text-muted-foreground">
                {{ searchQuery ? 'No papers match your search.' : 'Submit your first title proposal to get started.' }}
            </p>
            <button
                v-if="!searchQuery"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                @click="router.visit(student.research.create.url())"
            >
                <Plus class="h-4 w-4" />
                New Title Proposal
            </button>
        </div>
    </div>

    <!-- ── Delete Confirmation Modal ──────────────────── -->
    <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showDeleteModal = false">
            <div class="w-full max-w-md rounded-2xl border border-red-200 bg-card shadow-xl dark:border-red-900">
                <div class="border-b border-red-200 p-5 dark:border-red-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50 dark:bg-red-950/30">
                            <AlertTriangle class="h-5 w-5 text-red-500" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-red-600 dark:text-red-400">Delete Research Paper</h2>
                            <p class="text-sm text-muted-foreground">This action cannot be undone.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <p class="text-sm text-foreground">
                        You are about to permanently delete <strong>"{{ deletePaper?.title }}"</strong> and all its tracking records.
                    </p>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Type <strong class="text-red-500">DELETE</strong> to confirm
                        </label>
                        <input
                            v-model="deleteConfirmText"
                            type="text"
                            class="w-full rounded-xl border border-red-200 bg-background px-3 py-2 text-sm outline-none focus:border-red-400 focus:ring-2 focus:ring-red-400/30 dark:border-red-900"
                            placeholder="DELETE"
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted" @click="showDeleteModal = false">Cancel</button>
                        <button
                            type="button"
                            :disabled="deleteConfirmText !== 'DELETE' || deleteProcessing"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                            @click="submitDelete"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Permanently
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
