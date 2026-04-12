<template>
    <div
        :class="{
            'min-h-screen bg-gradient-to-br transition-colors duration-300': true,
            'dark from-slate-900 to-slate-800 text-white': isDark,
            'from-slate-100 to-slate-200 text-gray-900': !isDark,
        }"
    >
        <!-- Header -->
        <div class="sticky top-0 z-50 backdrop-blur-md">
            <NeuCard class="!rounded-b-3xl">
                <div
                    class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
                >
                    <h1 class="text-3xl font-bold">Track Research Paper</h1>
                    <button
                        @click="isDark = !isDark"
                        class="rounded-lg p-2 transition hover:bg-white/10"
                    >
                        {{ isDark ? '☀️' : '🌙' }}
                    </button>
                </div>
            </NeuCard>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-6xl px-6 py-8">
            <!-- Hero Card -->
            <NeuCard class="mb-8">
                <div class="flex flex-col gap-8 md:flex-row">
                    <div class="flex-1">
                        <h2 class="mb-4 text-4xl font-bold">
                            {{ paper.title }}
                        </h2>
                        <p
                            class="mb-6 line-clamp-3 text-lg text-gray-600 dark:text-gray-400"
                        >
                            {{ paper.description }}
                        </p>

                        <div class="flex flex-wrap gap-4">
                            <div>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Status
                                </p>
                                <StatusBadge :status="paper.status" />
                            </div>

                            <div>
                                <p
                                    class="mb-2 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Category
                                </p>
                                <span
                                    class="rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-900 dark:bg-purple-900 dark:text-purple-100"
                                >
                                    {{ paper.category?.name }}
                                </span>
                            </div>

                            <div>
                                <p
                                    class="mb-2 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Submitted
                                </p>
                                <span class="text-sm font-semibold">{{
                                    formatDate(paper.created_at)
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center md:w-48">
                        <div class="inline-flex flex-col items-center">
                            <div
                                class="mb-2 text-6xl font-bold text-purple-600 dark:text-purple-400"
                            >
                                {{ paper.progress || 0 }}%
                            </div>
                            <p class="mb-4 text-gray-600 dark:text-gray-400">
                                Complete
                            </p>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-white/30 dark:bg-white/10"
                            >
                                <div
                                    class="h-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500"
                                    :style="{
                                        width: (paper.progress || 0) + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </NeuCard>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Timeline -->
                <div class="lg:col-span-2">
                    <NeuCard>
                        <h3 class="mb-6 text-2xl font-bold">Timeline</h3>
                        <TrackingTimeline
                            :current-status="paper.status"
                            :tracking="paper.tracking_records || []"
                        />
                    </NeuCard>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Tracking ID -->
                    <NeuCard>
                        <h4
                            class="mb-3 text-sm font-bold tracking-wider text-gray-600 uppercase dark:text-gray-400"
                        >
                            Tracking ID
                        </h4>
                        <div
                            class="rounded-lg bg-white/20 p-3 font-mono text-lg font-bold break-all text-purple-600 dark:bg-black/20 dark:text-purple-400"
                        >
                            {{ paper.tracking_id }}
                        </div>
                    </NeuCard>

                    <!-- Authors -->
                    <NeuCard v-if="paper.authors && paper.authors.length > 0">
                        <h4
                            class="mb-3 text-sm font-bold tracking-wider text-gray-600 uppercase dark:text-gray-400"
                        >
                            Authors
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(author, index) in paper.authors"
                                :key="author.id"
                                class="rounded bg-white/10 p-2 dark:bg-black/10"
                            >
                                <p class="font-medium">{{ author.name }}</p>
                                <p
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    {{
                                        author.pivot?.author_order || index + 1
                                    }}. Author
                                </p>
                            </div>
                        </div>
                    </NeuCard>

                    <!-- Keywords -->
                    <NeuCard v-if="paper.keywords">
                        <h4
                            class="mb-3 text-sm font-bold tracking-wider text-gray-600 uppercase dark:text-gray-400"
                        >
                            Keywords
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="keyword in paper.keywords.split(',')"
                                :key="keyword.trim()"
                                class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-900 dark:bg-blue-900 dark:text-blue-100"
                            >
                                {{ keyword.trim() }}
                            </span>
                        </div>
                    </NeuCard>

                    <!-- Statistics -->
                    <NeuCard>
                        <h4
                            class="mb-3 text-sm font-bold tracking-wider text-gray-600 uppercase dark:text-gray-400"
                        >
                            Statistics
                        </h4>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Files</span
                                >
                                <span class="font-semibold">{{
                                    (paper.files || []).length
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Publications</span
                                >
                                <span class="font-semibold">{{
                                    (paper.publication || []).length
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Citations</span
                                >
                                <span class="font-semibold">{{
                                    (paper.citations || []).length
                                }}</span>
                            </div>
                        </div>
                    </NeuCard>
                </div>
            </div>

            <!-- Documents Section -->
            <NeuCard v-if="paper.files && paper.files.length > 0" class="mt-8">
                <h3 class="mb-6 text-2xl font-bold">Documents</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        v-for="file in paper.files"
                        :key="file.id"
                        class="flex items-center justify-between rounded-lg bg-white/10 p-4 transition hover:bg-white/20 dark:bg-black/10 dark:hover:bg-black/20"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📄</span>
                            <div>
                                <p class="font-semibold">
                                    {{ file.file_name }}
                                </p>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ formatFileSize(file.file_size) }}
                                </p>
                            </div>
                        </div>
                        <a
                            :href="`/storage/${file.file_path}`"
                            class="font-medium text-purple-600 hover:underline dark:text-purple-400"
                        >
                            View
                        </a>
                    </div>
                </div>
            </NeuCard>

            <!-- Publications & Citations -->
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Publications -->
                <NeuCard
                    v-if="paper.publication && paper.publication.length > 0"
                >
                    <h3 class="mb-6 text-xl font-bold">Publications</h3>
                    <div class="space-y-4">
                        <div
                            v-for="pub in paper.publication"
                            :key="pub.id"
                            class="rounded-lg bg-white/10 p-4 dark:bg-black/10"
                        >
                            <h4 class="mb-2 font-semibold">
                                {{ pub.journal_name }}
                            </h4>
                            <div
                                class="space-y-1 text-xs text-gray-600 dark:text-gray-400"
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
                    <h3 class="mb-6 text-xl font-bold">Citations</h3>
                    <div class="space-y-4">
                        <div
                            v-for="citation in paper.citations"
                            :key="citation.id"
                            class="rounded-lg bg-white/10 p-4 dark:bg-black/10"
                        >
                            <p class="mb-2 font-mono text-xs">
                                {{ citation.citation_text }}
                            </p>
                            <p
                                v-if="citation.format"
                                class="text-xs text-gray-600 dark:text-gray-400"
                            >
                                {{ citation.format }}
                            </p>
                        </div>
                    </div>
                </NeuCard>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import NeuCard from '@/components/NeuCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TrackingTimeline from '@/components/TrackingTimeline.vue';

interface Author {
    id: number;
    name: string;
    pivot?: { author_order?: number };
}

interface Category {
    id: number;
    name: string;
}

interface File {
    id: number;
    file_name: string;
    file_size: number;
    file_path: string;
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
    files?: File[];
    publication?: Publication[];
    citations?: Citation[];
    tracking_records?: TrackingRecord[];
}

interface Props {
    paper: Paper;
}

const props = defineProps<Props>();
const isDark = ref(false);

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};
</script>
