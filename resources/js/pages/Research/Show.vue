<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ClipboardCopy, Link2, Maximize2, X } from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref } from 'vue';
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
    step?: string;
    action?: string;
    status?: string;
    created_at: string;
    notes?: string;
}

interface Paper {
    id: number;
    title: string;
    abstract: string;
    status: string;
    current_step: string;
    tracking_id: string;
    created_at: string;
    progress?: number;
    keywords?: string;
    category?: Category;
    sdg_ids?: number[] | null;
    agenda_ids?: number[] | null;
    authors?: Author[];
    proponents?: Array<{ id: number; name: string }> | null;
    publication?: Publication[];
    citations?: Citation[];
    files?: File[];
    tracking_records?: TrackingRecord[];
}

interface Props {
    paper: Paper;
    steps: string[];
    stepLabels: Record<string, string>;
    sdgs: Array<{ id: number; name: string; number?: number }>;
    agendas: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();
const sdgMap = computed(() =>
    Object.fromEntries(props.sdgs.map((s) => [s.id, s])),
);
const agendaMap = computed(() =>
    Object.fromEntries(props.agendas.map((a) => [a.id, a])),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Research Papers', href: index() },
            { title: 'View Paper' },
        ],
    },
});

const copied = ref(false);
const qrModalOpen = ref(false);

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

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- ── Hero Header ─────────────────────────────────────── -->
        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0 flex-1">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :status="paper.status" />
                        <span
                            v-if="paper.category"
                            class="rounded-full border border-border bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"
                        >
                            {{ paper.category.name }}
                        </span>
                    </div>
                    <h1
                        class="text-xl leading-snug font-bold text-foreground md:text-2xl"
                    >
                        {{ paper.title }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Submitted {{ formatDate(paper.created_at) }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Link :href="edit(paper.id)">
                        <Button
                            size="sm"
                            class="bg-orange-500 text-white hover:bg-orange-600"
                            >Edit</Button
                        >
                    </Link>
                    <Link :href="index()">
                        <Button size="sm" variant="outline">Back</Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Body Grid ───────────────────────────────────────── -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Main Content (2/3) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Abstract + Keywords merged -->
                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2
                        class="mb-3 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Abstract
                    </h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ paper.abstract }}
                    </p>

                    <template v-if="paper.keywords">
                        <div class="my-4 border-t border-border" />
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Keywords
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="keyword in paper.keywords.split(',')"
                                :key="keyword.trim()"
                                class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300"
                            >
                                {{ keyword.trim() }}
                            </span>
                        </div>
                    </template>
                </section>

                <!-- Tracking Timeline -->
                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2
                        class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Submission Progress
                    </h2>
                    <TrackingTimeline
                        :current-step="paper.current_step"
                        :steps="steps"
                        :step-labels="stepLabels"
                        :tracking="paper.tracking_records || []"
                    />
                </section>

                <!-- Documents -->
                <section
                    v-if="paper.files && paper.files.length > 0"
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2
                        class="mb-3 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Documents
                    </h2>
                    <div class="space-y-2">
                        <div
                            v-for="file in paper.files"
                            :key="file.id"
                            class="flex items-center gap-3 rounded-lg bg-muted/50 px-4 py-3 transition hover:bg-muted"
                        >
                            <svg
                                class="h-8 w-8 shrink-0 text-red-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                                />
                            </svg>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ file.file_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatFileSize(file.file_size) }}
                                </p>
                            </div>
                            <a
                                :href="`/storage/${file.file_path}`"
                                download
                                class="shrink-0 rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                            >
                                Download
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Publications + Citations merged -->
                <section
                    v-if="
                        (paper.publication && paper.publication.length > 0) ||
                        (paper.citations && paper.citations.length > 0)
                    "
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <!-- Publications -->
                    <template
                        v-if="paper.publication && paper.publication.length > 0"
                    >
                        <h2
                            class="mb-3 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Publications
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="pub in paper.publication"
                                :key="pub.id"
                                class="rounded-lg border border-border bg-muted/50 p-4"
                            >
                                <h3
                                    class="mb-1 text-sm font-semibold text-foreground"
                                >
                                    {{ pub.journal_name }}
                                </h3>
                                <div
                                    class="space-y-0.5 text-xs text-muted-foreground"
                                >
                                    <p v-if="pub.doi">
                                        DOI:
                                        <code class="font-mono">{{
                                            pub.doi
                                        }}</code>
                                    </p>
                                    <p v-if="pub.publisher">
                                        Publisher: {{ pub.publisher }}
                                    </p>
                                    <p v-if="pub.volume || pub.issue">
                                        Vol. {{ pub.volume }}, Issue
                                        {{ pub.issue }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="paper.citations && paper.citations.length > 0"
                            class="my-4 border-t border-border"
                        />
                    </template>

                    <!-- Citations -->
                    <template
                        v-if="paper.citations && paper.citations.length > 0"
                    >
                        <h2
                            class="mb-3 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Citations
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="citation in paper.citations"
                                :key="citation.id"
                                class="rounded-lg border border-border bg-muted/50 p-4"
                            >
                                <p class="font-mono text-xs text-foreground">
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
                    </template>
                </section>
            </div>

            <!-- Right: Sidebar (1/3) -->
            <div class="space-y-5">
                <!-- Overview card: proponents + authors + SDGs + agendas -->
                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h3
                        class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Overview
                    </h3>
                    <div class="space-y-4 text-sm">
                        <!-- Proponents -->
                        <div v-if="paper.proponents?.length">
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Proponents
                            </p>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(proponent, idx) in paper.proponents"
                                    :key="proponent.id"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                    >
                                        {{
                                            proponent.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-medium text-foreground"
                                        >
                                            {{ proponent.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                idx === 0
                                                    ? 'Lead Proponent'
                                                    : 'Proponent'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Authors -->
                        <div v-if="paper.authors && paper.authors.length > 0">
                            <div class="mb-1.5 border-t border-border pt-3" />
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Authors
                            </p>
                            <div class="space-y-1">
                                <div
                                    v-for="(author, index) in paper.authors"
                                    :key="author.id"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-sm text-foreground">{{
                                        author.name
                                    }}</span>
                                    <span class="text-xs text-muted-foreground"
                                        >#{{
                                            author.pivot?.author_order ||
                                            index + 1
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- SDGs -->
                        <div v-if="paper.sdg_ids?.length">
                            <div class="mb-1.5 border-t border-border pt-3" />
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                SDGs
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="id in paper.sdg_ids"
                                    :key="id"
                                    class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400"
                                >
                                    {{
                                        sdgMap[id]
                                            ? sdgMap[id].number
                                                ? `SDG ${sdgMap[id].number}`
                                                : sdgMap[id].name
                                            : `SDG ${id}`
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Agendas -->
                        <div v-if="paper.agenda_ids?.length">
                            <div class="mb-1.5 border-t border-border pt-3" />
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Agendas
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="id in paper.agenda_ids"
                                    :key="id"
                                    class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-500/10 dark:text-violet-400"
                                >
                                    {{ agendaMap[id]?.name ?? `Agenda ${id}` }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Share & Track card: tracking ID + public link -->
                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h3
                        class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Share & Track
                    </h3>
                    <!-- QR Code -->
                    <div class="mb-4 flex flex-col items-center gap-3">
                        <div
                            class="relative cursor-pointer rounded-xl border-2 border-border bg-white p-2 transition hover:border-orange-400"
                            @click="qrModalOpen = true"
                            title="Click to enlarge"
                        >
                            <QrcodeVue :value="publicTrackingUrl" :size="120" level="M" />
                            <span class="absolute right-1 bottom-1 rounded bg-black/30 p-0.5">
                                <Maximize2 class="h-2.5 w-2.5 text-white" />
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">Scan to track publicly</p>
                    </div>

                    <div class="my-3 border-t border-border" />
                    <p
                        class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Tracking ID
                    </p>
                    <div class="flex items-center gap-2">
                        <code
                            class="flex-1 truncate rounded-lg bg-muted px-3 py-2 font-mono text-sm font-bold text-orange-600 dark:text-orange-400"
                        >
                            {{ paper.tracking_id }}
                        </code>
                        <button
                            @click="copyToClipboard(paper.tracking_id)"
                            class="shrink-0 rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-muted"
                        >
                            {{ copied ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>

                    <div class="my-4 border-t border-border" />

                    <!-- Public tracking link -->
                    <p
                        class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Public Link
                    </p>
                    <p class="mb-2 text-xs text-muted-foreground">
                        Share without authentication.
                    </p>
                    <div class="flex items-center gap-2">
                        <code
                            class="min-w-0 flex-1 truncate overflow-hidden rounded-lg bg-muted px-3 py-2 font-mono text-xs text-muted-foreground"
                        >
                            {{ publicTrackingUrl }}
                        </code>
                        <button
                            @click="copyToClipboard(publicTrackingUrl)"
                            class="shrink-0 rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-muted"
                        >
                            Copy
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="qrModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                @click.self="qrModalOpen = false"
            >
                <div class="relative flex w-full max-w-sm flex-col items-center gap-6 rounded-2xl border border-border bg-card p-8 shadow-2xl">
                    <button
                        type="button"
                        class="absolute top-3 right-3 rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="qrModalOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <div class="text-center">
                        <h2 class="text-base font-bold text-foreground">Tracking QR Code</h2>
                        <p class="mt-0.5 font-mono text-xs text-muted-foreground">{{ paper.tracking_id }}</p>
                    </div>

                    <div class="rounded-2xl border-4 border-orange-400 bg-white p-4">
                        <QrcodeVue :value="publicTrackingUrl" :size="220" level="H" />
                    </div>

                    <div class="flex w-full items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded-lg bg-muted px-3 py-2 font-mono text-xs text-muted-foreground">
                            {{ publicTrackingUrl }}
                        </code>
                        <button
                            type="button"
                            @click="copyToClipboard(publicTrackingUrl)"
                            class="shrink-0 rounded-lg border border-border bg-background p-2 text-foreground transition hover:bg-muted"
                        >
                            <ClipboardCopy class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-xs text-green-600">Copied!</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
