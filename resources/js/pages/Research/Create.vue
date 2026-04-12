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
                        Create Title Proposal
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
                            <Label class="mb-1.5 block text-sm font-medium text-foreground">
                                Title
                            </Label>
                            <Input
                                v-model="form.title"
                                required
                                placeholder="Enter title proposal"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition focus-visible:border-orange-500 focus-visible:ring-2 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <Label class="mb-1.5 block text-sm font-medium text-foreground">
                                Abstract
                            </Label>
                            <textarea
                                v-model="form.abstract"
                                rows="5"
                                required
                                placeholder="Write a concise abstract for this proposal"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                            />
                            <p v-if="form.errors.abstract" class="mt-1 text-xs text-red-500">
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
                        <Label class="mb-1.5 block text-sm font-medium text-foreground">
                            Sustainable Development Goal (SDG)
                        </Label>
                        <select
                            v-model="form.sdg_id"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                        >
                            <option value="">Select SDG (optional)</option>
                            <option v-for="sdg in sdgs" :key="sdg.id" :value="sdg.id">
                                {{ formatSdg(sdg) }}
                            </option>
                        </select>
                        <p v-if="form.errors.sdg_id" class="mt-1 text-xs text-red-500">
                            {{ form.errors.sdg_id }}
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
                        <Label class="mb-1.5 block text-sm font-medium text-foreground">
                            Research Agenda
                        </Label>
                        <select
                            v-model="form.agenda_id"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                        >
                            <option value="">Select agenda (optional)</option>
                            <option
                                v-for="agenda in agendas"
                                :key="agenda.id"
                                :value="agenda.id"
                            >
                                {{ agenda.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.agenda_id" class="mt-1 text-xs text-red-500">
                            {{ form.errors.agenda_id }}
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
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 flex-1 items-center gap-2 rounded-lg border border-input bg-muted px-3 py-2 text-sm text-foreground">
                                <span
                                    class="rounded bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                >You</span>
                                {{ form.proponents[0]?.name }}
                            </div>
                        </div>

                        <div
                            v-for="(proponent, idx) in form.proponents.slice(1)"
                            :key="idx + 1"
                            class="relative flex items-center gap-2"
                        >
                            <template v-if="activeSearchSlot !== idx + 1">
                                <div
                                    class="flex h-10 flex-1 cursor-pointer items-center rounded-lg border border-input bg-background px-3 py-2 text-sm transition hover:border-orange-400"
                                    @click="openSearch(idx + 1)"
                                >
                                    <span v-if="proponent.id" class="text-foreground">{{ proponent.name }}</span>
                                    <span v-else class="text-muted-foreground">Click to search for a student...</span>
                                </div>
                            </template>

                            <template v-else>
                                <div class="relative flex-1">
                                    <Input
                                        :value="searchQuery"
                                        autofocus
                                        placeholder="Type name or email..."
                                        class="h-10 w-full border-orange-400 bg-background focus-visible:border-orange-500 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20"
                                        @input="onSearchInput(($event.target as HTMLInputElement).value)"
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
                                            @mousedown.prevent="selectProponent(idx + 1, result)"
                                        >
                                            {{ result.name }}
                                        </li>
                                    </ul>
                                    <div
                                        v-else-if="searchQuery.trim().length >= 2"
                                        class="absolute z-20 mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-lg"
                                    >
                                        No students found.
                                    </div>
                                </div>
                            </template>

                            <button
                                type="button"
                                class="h-10 w-10 text-muted-foreground transition hover:text-red-500"
                                @click="removeProponent(idx + 1)"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <p v-if="form.errors.proponents" class="mt-1 text-xs text-red-500">
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
                            Keywords
                        </h2>
                        <Label class="mb-1.5 block text-sm font-medium text-foreground">
                            Keywords
                        </Label>
                        <Input
                            v-model="form.keywords"
                            placeholder="e.g. machine learning, AI, neural networks"
                            class="h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition focus-visible:border-orange-500 focus-visible:ring-2 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Separate each keyword with commas.
                        </p>
                        <p v-if="form.errors.keywords" class="mt-1 text-xs text-red-500">
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
                        <Label class="mb-1.5 block text-sm font-medium text-foreground">
                            Research File
                        </Label>
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept=".pdf,.doc,.docx"
                            @change="handleFileChange"
                        />
                        <div
                            class="cursor-pointer rounded-xl border-2 border-dashed border-gray-200 p-8 text-center transition-colors hover:border-orange-400 hover:bg-orange-50/30 dark:hover:bg-orange-500/10"
                            @click="openFilePicker"
                            @dragover.prevent
                            @drop.prevent="handleFileDrop"
                        >
                            <p class="text-sm font-medium text-foreground">
                                {{ fileName || 'Drag and drop your file here, or click to browse' }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">PDF, DOC, DOCX</p>
                        </div>
                        <p v-if="form.errors.file" class="mt-1 text-xs text-red-500">
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
                        {{ form.processing ? 'Submitting…' : 'Submit Proposal' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index, store } from '@/routes/papers';
import { search as searchProponents } from '@/routes/papers/proponents';

interface Props {
    sdgs: Array<{ id: number; name: string; number?: number; color?: string }>;
    agendas: Array<{ id: number; name: string }>;
    auth_user: { id: number; name: string };
}

const props = defineProps<Props>();
const { sdgs, agendas } = props;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Research Papers', href: index() },
            { title: 'Create Title Proposal', href: create() },
        ],
    },
});

const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref('');

const form = useForm({
    title: '',
    abstract: '',
    sdg_id: '' as string | number,
    agenda_id: '' as string | number,
    proponents: [{ id: props.auth_user.id, name: props.auth_user.name }] as Array<{ id: number; name: string }>,
    keywords: '',
    file: null as File | null,
});

interface SearchResult {
    id: number;
    name: string;
}

const activeSearchSlot = ref<number | null>(null);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
let searchDebounce: ReturnType<typeof setTimeout>;

const canAddProponent = computed(() => form.proponents.length < 3);

function formatSdg(sdg: { id: number; name: string; number?: number }) {
    return sdg.number ? `SDG ${sdg.number}: ${sdg.name}` : sdg.name;
}

function openSearch(slotIndex: number) {
    activeSearchSlot.value = slotIndex;
    searchQuery.value = '';
    searchResults.value = [];
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
            const res = await fetch(searchProponents.url({ query: { q: value.trim() } }), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data: SearchResult[] = await res.json();
            const selectedIds = form.proponents.map((proponent) => proponent.id).filter((id) => id !== 0);
            searchResults.value = data.filter((user) => !selectedIds.includes(user.id));
        } catch {
            searchResults.value = [];
        }
    }, 300);
}

function selectProponent(slot: number, result: SearchResult) {
    form.proponents[slot] = { id: result.id, name: result.name };
    closeSearch();
}

function assignFile(file?: File) {
    if (!file) {
        return;
    }

    form.file = file;
    fileName.value = file.name;
}

function handleFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    assignFile(file);
}

function handleFileDrop(event: DragEvent) {
    const file = event.dataTransfer?.files?.[0];
    assignFile(file);
}

function openFilePicker() {
    fileInput.value?.click();
}

function submit() {
    form.post(store.url(), {
        forceFormData: true,
    });
}
</script>
