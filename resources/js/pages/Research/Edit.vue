<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import FilePreview from '@/components/FilePreview.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import TagsInput from '@/components/TagsInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/papers';
import { search as searchProponents } from '@/routes/papers/proponents';

interface Props {
    paper: {
        id: number;
        title: string;
        abstract?: string | null;
        sdg_ids?: number[] | null;
        agenda_ids?: number[] | null;
        proponents?: Array<{ id: number; name: string }> | null;
        keywords?: string | null;
        files?: Array<{
            id: number;
            file_name: string;
            file_size: number;
            file_path: string;
        }> | null;
    };
    sdgs: Array<{ id: number; name: string; number?: number; color?: string }>;
    agendas: Array<{ id: number; name: string }>;
    auth_user: { id: number; name: string };
}

const props = defineProps<Props>();
const paper = props.paper;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Research Papers', href: index() },
            { title: 'Edit Title Proposal' },
        ],
    },
});

const form = useForm({
    title: paper.title,
    abstract: paper.abstract ?? '',
    sdg_ids: (paper.sdg_ids ?? []).map(Number),
    agenda_ids: (paper.agenda_ids ?? []).map(Number),
    proponents: paper.proponents?.length
        ? paper.proponents
        : ([{ id: props.auth_user.id, name: props.auth_user.name }] as Array<{
              id: number;
              name: string;
          }>),
    keywords: paper.keywords ?? '',
    file: null as File | null,
});

interface SearchResult {
    id: number;
    name: string;
}

const activeSearchSlot = ref<number | null>(null);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchInput = ref<HTMLInputElement[]>([]);
let searchDebounce: ReturnType<typeof setTimeout>;

const canAddProponent = computed(() => form.proponents.length < 3);

const existingFile = computed(() => props.paper.files?.[0] ?? null);

function formatFileSize(bytes: number): string {
    if (bytes === 0) {
return '0 Bytes';
}

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`;
}

const sdgOptions = computed(() =>
    props.sdgs.map((s) => ({
        value: s.id,
        label: s.number ? `SDG ${s.number}: ${s.name}` : s.name,
    })),
);

const agendaOptions = computed(() =>
    props.agendas.map((a) => ({ value: a.id, label: a.name })),
);

function openSearch(slotIndex: number) {
    activeSearchSlot.value = slotIndex;
    searchQuery.value = '';
    searchResults.value = [];
    nextTick(() => searchInput.value[0]?.focus());
}

function closeSearch() {
    activeSearchSlot.value = null;
    searchQuery.value = '';
    searchResults.value = [];
}

function addProponentSlot() {
    if (form.proponents.length >= 3) {
return;
}

    form.proponents.push({ id: 0, name: '' });
    openSearch(form.proponents.length - 1);
}

function removeProponent(index: number) {
    form.proponents.splice(index, 1);
    closeSearch();
}

async function onSearchInput(value: string) {
    searchQuery.value = value;
    clearTimeout(searchDebounce);

    if (value.trim().length < 2) {
        searchResults.value = [];

        return;
    }

    searchDebounce = setTimeout(async () => {
        try {
            const res = await fetch(
                searchProponents.url({ query: { q: value.trim() } }),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!res.ok) {
                searchResults.value = [];

                return;
            }

            const data: SearchResult[] = await res.json();
            const selectedIds = form.proponents
                .map((p) => p.id)
                .filter((id) => id !== 0);
            searchResults.value = data.filter(
                (u) => !selectedIds.includes(u.id),
            );
        } catch {
            searchResults.value = [];
        }
    }, 300);
}

function selectProponent(slot: number, result: SearchResult) {
    form.proponents[slot] = { id: result.id, name: result.name };
    closeSearch();
}

function submit() {
    form.put(update.url(paper.id));
}
</script>

<template>
    <div class="flex h-full flex-1 justify-center bg-background p-4 md:p-6">
        <div class="w-full max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="index()">
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <ArrowLeft class="h-3.5 w-3.5" />
                            Back
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold text-foreground">
                        Edit Title Proposal
                    </h1>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2
                        class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Basic Information
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <Label
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Title
                            </Label>
                            <Input
                                v-model="form.title"
                                required
                                placeholder="Enter title proposal"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition focus-visible:border-orange-500 focus-visible:ring-2 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20"
                            />
                            <p
                                v-if="form.errors.title"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <Label
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Abstract
                            </Label>
                            <textarea
                                v-model="form.abstract"
                                rows="5"
                                required
                                placeholder="Write a concise abstract for this proposal"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground transition outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                            />
                            <p
                                v-if="form.errors.abstract"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.abstract }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <section
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            SDG
                        </h2>
                        <Label
                            class="mb-1.5 block text-sm font-medium text-foreground"
                            >Sustainable Development Goals</Label
                        >
                        <MultiSelect
                            v-model="form.sdg_ids"
                            :options="sdgOptions"
                            search-placeholder="Search SDGs…"
                            checkbox-accent-class="accent-orange-500"
                        />
                        <p
                            v-if="form.errors.sdg_ids"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.sdg_ids }}
                        </p>
                    </section>

                    <section
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Agenda
                        </h2>
                        <Label
                            class="mb-1.5 block text-sm font-medium text-foreground"
                            >Research Agenda</Label
                        >
                        <MultiSelect
                            v-model="form.agenda_ids"
                            :options="agendaOptions"
                            search-placeholder="Search agendas…"
                            checkbox-accent-class="accent-orange-500"
                        />
                        <p
                            v-if="form.errors.agenda_ids"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.agenda_ids }}
                        </p>
                    </section>
                </div>

                <section
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2
                        class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Proponents / Researchers
                    </h2>

                    <div class="space-y-2">
                        <!-- Slot 0: lead proponent — always locked -->
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-10 flex-1 items-center gap-2 rounded-lg border border-input bg-muted px-3 py-2 text-sm text-foreground"
                            >
                                <span
                                    v-if="form.proponents[0]?.id === auth_user.id"
                                    class="rounded bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                    >You</span
                                >
                                <span
                                    class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                    >Lead</span
                                >
                                {{ form.proponents[0]?.name }}
                            </div>
                        </div>
                        <div
                            v-for="(proponent, idx) in form.proponents.slice(1)"
                            :key="idx + 1"
                            class="relative flex items-center gap-2"
                        >
                            <!-- Co-proponent locked when it's the logged-in user -->
                            <template v-if="proponent.id === auth_user.id">
                                <div
                                    class="flex h-10 flex-1 items-center gap-2 rounded-lg border border-input bg-muted px-3 py-2 text-sm text-foreground"
                                >
                                    <span
                                        class="rounded bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                        >You</span
                                    >
                                    {{ proponent.name }}
                                </div>
                            </template>
                            <template v-else-if="activeSearchSlot !== idx + 1">
                                <div
                                    class="flex h-10 flex-1 cursor-pointer items-center rounded-lg border border-input bg-background px-3 py-2 text-sm transition hover:border-orange-400"
                                    @click="openSearch(idx + 1)"
                                >
                                    <span
                                        v-if="proponent.id"
                                        class="text-foreground"
                                        >{{ proponent.name }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >Click to search for a student...</span
                                    >
                                </div>
                            </template>
                            <template v-else>
                                <div class="relative flex-1">
                                    <input
                                        ref="searchInput"
                                        :value="searchQuery"
                                        type="text"
                                        placeholder="Type name or email..."
                                        class="h-10 w-full rounded-lg border border-orange-400 bg-background px-3 py-2 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                                        @input="
                                            onSearchInput(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                        "
                                        @keydown.escape="closeSearch"
                                    />
                                    <ul
                                        v-if="searchResults.length"
                                        class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-border bg-card shadow-lg"
                                    >
                                        <li
                                            v-for="result in searchResults"
                                            :key="result.id"
                                            class="cursor-pointer px-3 py-2 text-sm text-foreground hover:bg-muted"
                                            @mousedown.prevent="
                                                selectProponent(idx + 1, result)
                                            "
                                        >
                                            {{ result.name }}
                                        </li>
                                    </ul>
                                    <div
                                        v-else-if="
                                            searchQuery.trim().length >= 2
                                        "
                                        class="absolute z-20 mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-lg"
                                    >
                                        No students found.
                                    </div>
                                </div>
                            </template>
                            <button
                                v-if="proponent.id !== auth_user.id"
                                type="button"
                                class="h-10 w-10 text-muted-foreground transition hover:text-red-500"
                                @click="removeProponent(idx + 1)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.proponents"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.proponents }}
                    </p>
                    <button
                        v-if="canAddProponent"
                        type="button"
                        class="mt-3 flex items-center gap-1 text-sm font-medium text-orange-500 hover:text-orange-600"
                        @click="addProponentSlot"
                    >
                        <Plus class="h-4 w-4" />
                        Add Proponent
                    </button>
                </section>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <section
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Keywords (comma-separated)
                        </h2>
                        <Label
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Keywords
                        </Label>
                        <TagsInput
                            v-model="form.keywords"
                            placeholder="Add keyword…"
                            wrapper-focus-class="focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-400/30"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Press Enter or comma to add a keyword.
                        </p>
                        <p
                            v-if="form.errors.keywords"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.keywords }}
                        </p>
                    </section>

                    <section
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            File Upload
                        </h2>
                        <Label
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Research File
                        </Label>
                        <!-- Existing file -->
                        <div
                            v-if="existingFile && !form.file"
                            class="mb-3 flex items-center gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3"
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
                                    {{ existingFile.file_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatFileSize(existingFile.file_size) }}
                                </p>
                            </div>
                            <span class="text-xs text-muted-foreground"
                                >Current file</span
                            >
                        </div>
                        <!-- Replace file -->
                        <p class="mb-2 text-xs text-muted-foreground">
                            {{
                                existingFile
                                    ? 'Upload a new file to replace the current one (optional).'
                                    : 'Upload a research file.'
                            }}
                        </p>
                        <FilePreview
                            :file="form.file"
                            hover-border-class="hover:border-orange-400 hover:bg-orange-50/30 dark:hover:bg-orange-500/10"
                            @update:file="form.file = $event"
                        />
                        <p
                            v-if="form.errors.file"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.file }}
                        </p>
                    </section>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="index()">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-orange-500 px-6 py-2 font-medium text-white transition hover:bg-orange-600"
                    >
                        {{ form.processing ? 'Updating…' : 'Update Proposal' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
