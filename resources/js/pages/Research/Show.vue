<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="line-clamp-1 text-2xl font-black text-slate-900 dark:text-white"
                >
                    {{ paper.title }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ formatDate(paper.created_at) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="edit(paper.id)">
                    <Button
                        size="sm"
                        class="border-0 bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700"
                        >Edit</Button
                    >
                </Link>
                <Link :href="index()">
                    <Button size="sm" variant="outline">Back</Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Tracking Status -->
                <NeuCard>
                    <h2
                        class="mb-4 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Tracking Progress
                    </h2>
                    <TrackingTimeline
                        :current-status="paper.status"
                        :tracking="paper.tracking_records || []"
                    />
                </NeuCard>

                <!-- Abstract -->
                <NeuCard>
                    <h2
                        class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Abstract
                    </h2>
                    <p
                        class="text-sm leading-relaxed text-slate-700 dark:text-slate-300"
                    >
                        {{ paper.description }}
                    </p>
                </NeuCard>

                <!-- Keywords -->
                <NeuCard v-if="paper.keywords">
                    <h2
                        class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Keywords
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="keyword in paper.keywords.split(',')"
                            :key="keyword.trim()"
                            class="rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300"
                        >
                            {{ keyword.trim() }}
                        </span>
                    </div>
                </NeuCard>

                <!-- Publications -->
                <NeuCard
                    v-if="paper.publication && paper.publication.length > 0"
                >
                    <h2
                        class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Publications
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="pub in paper.publication"
                            :key="pub.id"
                            class="rounded-xl bg-white/40 p-4 dark:bg-black/10"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-200"
                            >
                                {{ pub.journal_name }}
                            </h3>
                            <div
                                class="space-y-1 text-xs text-slate-600 dark:text-slate-400"
                            >
                                <p v-if="pub.doi">
                                    DOI:
                                    <code class="font-mono">{{ pub.doi }}</code>
                                </p>
                                <p v-if="pub.publisher">
                                    Publisher: {{ pub.publisher }}
                                </p>
                                <p v-if="pub.volume || pub.issue">
                                    Vol. {{ pub.volume }}, Issue {{ pub.issue }}
                                </p>
                            </div>
                        </div>
                    </div>
                </NeuCard>

                <!-- Citations -->
                <NeuCard v-if="paper.citations && paper.citations.length > 0">
                    <h2
                        class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Citations
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="citation in paper.citations"
                            :key="citation.id"
                            class="rounded-xl bg-white/40 p-4 dark:bg-black/10"
                        >
                            <p
                                class="font-mono text-xs text-slate-700 dark:text-slate-300"
                            >
                                {{ citation.citation_text }}
                            </p>
                            <p
                                v-if="citation.format"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ citation.format }}
                            </p>
                        </div>
                    </div>
                </NeuCard>

                <!-- Files -->
                <NeuCard v-if="paper.files && paper.files.length > 0">
                    <h2
                        class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-100"
                    >
                        Documents
                    </h2>
                    <div class="space-y-2">
                        <div
                            v-for="file in paper.files"
                            :key="file.id"
                            class="flex items-center justify-between rounded-xl bg-white/40 p-3 transition hover:bg-white/60 dark:bg-black/10 dark:hover:bg-black/20"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">📄</span>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-800 dark:text-slate-200"
                                    >
                                        {{ file.file_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatFileSize(file.file_size) }}
                                    </p>
                                </div>
                            </div>
                            <a
                                :href="`/storage/${file.file_path}`"
                                download
                                class="text-xs font-semibold text-orange-600 hover:underline dark:text-orange-400"
                            >
                                Download
                            </a>
                        </div>
                    </div>
                </NeuCard>
            </div>

            <!-- Sidebar -->
            <div class="space-y-5">
                <!-- Status -->
                <NeuCard>
                    <h3
                        class="mb-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Status
                    </h3>
                    <StatusBadge :status="paper.status" />
                    <div
                        class="mt-4 rounded-xl bg-white/20 p-3 text-center dark:bg-black/20"
                    >
                        <div
                            class="text-2xl font-bold text-orange-600 dark:text-orange-400"
                        >
                            {{ paper.progress || 0 }}%
                        </div>
                        <p class="text-xs text-muted-foreground">Complete</p>
                    </div>
                </NeuCard>

                <!-- Category -->
                <NeuCard>
                    <h3
                        class="mb-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Category
                    </h3>
                    <div
                        class="rounded-lg bg-orange-100 px-3 py-2 text-sm font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300"
                    >
                        {{ paper.category?.name || 'Uncategorized' }}
                    </div>
                </NeuCard>

                <!-- Tracking ID -->
                <NeuCard>
                    <h3
                        class="mb-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Tracking ID
                    </h3>
                    <code
                        class="block rounded-xl bg-white/20 p-3 font-mono text-sm font-bold break-all text-orange-600 dark:bg-black/20 dark:text-orange-400"
                    >
                        {{ paper.tracking_id }}
                    </code>
                    <button
                        @click="copyToClipboard(paper.tracking_id)"
                        class="mt-2 w-full rounded-lg py-2 text-xs font-semibold text-orange-600 transition hover:bg-white/20 dark:text-orange-400 dark:hover:bg-black/20"
                    >
                        📋 Copy
                    </button>
                </NeuCard>

                <!-- Authors -->
                <NeuCard v-if="paper.authors && paper.authors.length > 0">
                    <h3
                        class="mb-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Authors
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="(author, index) in paper.authors"
                            :key="author.id"
                            class="rounded-lg bg-white/10 p-2 dark:bg-black/10"
                        >
                            <p
                                class="text-sm font-medium text-slate-800 dark:text-slate-200"
                            >
                                {{ author.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ author.pivot?.author_order || index + 1 }}.
                                Author
                            </p>
                        </div>
                    </div>
                </NeuCard>

                <!-- Public Tracking Link -->
                <NeuCard>
                    <h3
                        class="mb-3 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Public Tracking
                    </h3>
                    <p class="mb-3 text-xs text-muted-foreground">
                        Share this link for public tracking without
                        authentication.
                    </p>
                    <code
                        class="block rounded-lg bg-white/20 p-2 font-mono text-xs break-all text-slate-700 dark:bg-black/20 dark:text-slate-300"
                    >
                        {{ publicTrackingUrl }}
                    </code>
                    <button
                        @click="copyToClipboard(publicTrackingUrl)"
                        class="mt-2 w-full rounded-lg py-2 text-xs font-semibold text-orange-600 transition hover:bg-white/20 dark:text-orange-400 dark:hover:bg-black/20"
                    >
                        📋 Copy Link
                    </button>
                </NeuCard>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import NeuCard from '@/components/NeuCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TrackingTimeline from '@/components/TrackingTimeline.vue';
import { Button } from '@/components/ui/button';
import { edit, index, publicTracking } from '@/routes/papers';

interface Author {
    id: number;
    name: string;
    pivot?: { author_order?: number };
}

interface Publication {
    id: number;
    journal_name: string;
    doi?: string;
    publisher?: string;
    volume?: number;
    issue?: number;
}

interface Citation {
    id: number;
    citation_text: string;
    format?: string;
}

interface File {
    id: number;
    file_name: string;
    file_size: number;
    file_path: string;
}

interface Category {
    id: number;
    name: string;
}

interface TrackingRecord {
    id: number;
    status: string;
    created_at: string;
    notes?: string;
}

interface Paper {
    id: number;
    title: string;
    description: string;
    status: string;
    tracking_id: string;
    created_at: string;
    progress?: number;
    keywords?: string;
    category?: Category;
    authors?: Author[];
    publication?: Publication[];
    citations?: Citation[];
    files?: File[];
    tracking_records?: TrackingRecord[];
}

interface Props {
    paper: Paper;
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Research Papers', href: index() },
            { title: 'View Paper' },
        ],
    },
});

const copied = ref(false);

const publicTrackingUrl = computed(
    () =>
        `${window.location.origin}${publicTracking.url(props.paper.tracking_id)}`,
);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) {
        return '0 Bytes';
    }

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};
</script>
