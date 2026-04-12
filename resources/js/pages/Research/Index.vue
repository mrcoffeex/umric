<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                    Research Papers
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Browse and manage research submissions.
                </p>
            </div>
            <Link :href="create()">
                <Button
                    class="flex items-center gap-2 border-0 bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow shadow-orange-500/25 hover:from-orange-600 hover:to-orange-700"
                >
                    <Plus class="h-4 w-4" />
                    New Paper
                </Button>
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by title or description…"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-4 pl-10 text-sm text-slate-800 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-sidebar-border dark:bg-sidebar dark:text-slate-200"
                />
            </div>
            <select
                v-model="selectedStatus"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-sidebar-border dark:bg-sidebar dark:text-slate-300"
            >
                <option value="">All statuses</option>
                <option value="submitted">Submitted</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="presented">Presented</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
            <select
                v-model="selectedCategory"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-sidebar-border dark:bg-sidebar dark:text-slate-300"
            >
                <option value="">All categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <!-- Papers Grid -->
        <div
            v-if="filteredPapers.length > 0"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="paper in filteredPapers"
                :key="paper.id"
                :href="show(paper.id)"
                class="group"
            >
                <NeuCard
                    class="h-full cursor-pointer transition-all group-hover:scale-[1.01]"
                >
                    <div class="flex h-full flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <h3
                                class="line-clamp-2 text-base font-bold text-slate-800 transition-colors group-hover:text-orange-600 dark:text-slate-100 dark:group-hover:text-orange-400"
                            >
                                {{ paper.title }}
                            </h3>
                        </div>
                        <StatusBadge :status="paper.status" />
                        <p
                            class="line-clamp-2 flex-1 text-sm text-slate-600 dark:text-slate-400"
                        >
                            {{ paper.description }}
                        </p>
                        <div
                            class="flex items-center justify-between border-t border-white/30 pt-3 dark:border-white/10"
                        >
                            <code
                                class="rounded bg-orange-50 px-2 py-0.5 font-mono text-[11px] text-orange-600 dark:bg-orange-950/40 dark:text-orange-400"
                            >
                                {{ paper.tracking_id }}
                            </code>
                            <span class="text-xs text-slate-500">{{
                                formatDate(paper.created_at)
                            }}</span>
                        </div>
                    </div>
                </NeuCard>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-16">
            <div
                class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
            >
                <FileText class="h-8 w-8 text-orange-400" />
            </div>
            <h3
                class="mb-1 text-lg font-bold text-slate-700 dark:text-slate-300"
            >
                No papers found
            </h3>
            <p class="mb-5 text-sm text-muted-foreground">
                {{
                    search || selectedStatus || selectedCategory
                        ? 'Try adjusting your filters.'
                        : 'Submit your first research paper.'
                }}
            </p>
            <Link :href="create()">
                <Button
                    class="border-0 bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700"
                >
                    Submit First Paper
                </Button>
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import NeuCard from '@/components/NeuCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/papers';

interface Paper {
    id: number;
    title: string;
    description: string;
    status: string;
    tracking_id: string;
    created_at: string;
    authors_count?: number;
    category?: { id: number; name: string };
}

interface Category {
    id: number;
    name: string;
}

interface Props {
    papers: Paper[];
    categories: Category[];
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Research Papers', href: index() }],
    },
});

const search = ref('');
const selectedStatus = ref('');
const selectedCategory = ref<number | ''>('');

const filteredPapers = computed(() => {
    return props.papers.filter((paper) => {
        const matchesSearch =
            search.value === '' ||
            paper.title.toLowerCase().includes(search.value.toLowerCase()) ||
            paper.description
                ?.toLowerCase()
                .includes(search.value.toLowerCase());

        const matchesStatus =
            selectedStatus.value === '' ||
            paper.status === selectedStatus.value;

        const matchesCategory =
            selectedCategory.value === '' ||
            paper.category?.id === selectedCategory.value;

        return matchesSearch && matchesStatus && matchesCategory;
    });
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>
