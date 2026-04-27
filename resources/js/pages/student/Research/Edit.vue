<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    Loader2,
    Plus,
    Tag,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import FilePreview from '@/components/FilePreview.vue';
import TagsInput from '@/components/TagsInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { search as searchProponents } from '@/routes/papers/proponents';
import student from '@/routes/student';

interface Props {
    paper: {
        id: string;
        title: string;
        abstract?: string | null;
        proponents?: Array<{ id: string; name: string }> | null;
        keywords?: string | null;
        files?: Array<{
            id: string;
            file_name: string;
            file_size: number;
            file_path: string;
        }> | null;
    };
    auth_user: { id: string; name: string };
}

const props = defineProps<Props>();
const paper = props.paper;
const uploadMaxSizeMb = Number(import.meta.env.VITE_UPLOAD_MAX_SIZE_MB ?? 25);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Research', href: student.research.index() },
            { title: 'Edit Title Proposal' },
        ],
    },
});

const form = useForm({
    title: paper.title,
    abstract: paper.abstract ?? '',
    proponents: paper.proponents?.length
        ? paper.proponents
        : ([{ id: props.auth_user.id, name: props.auth_user.name }] as Array<{
              id: string;
              name: string;
          }>),
    keywords: paper.keywords ?? '',
    file: null as File | null,
});

interface SearchResult {
    id: string;
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

    form.proponents.push({ id: '', name: '' });
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
                .filter((id) => id !== '');
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
    form.transform((data) => ({
        ...data,
        _method: 'put',
        proponents: data.proponents.filter((p) => p.id !== ''),
    })).post(student.research.update.url(paper.id));
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col bg-background">
        <!-- Page header -->
        <div class="border-b border-border bg-card px-4 py-4 md:px-6">
            <div class="mx-auto flex max-w-3xl items-center gap-3">
                <Link :href="student.research.index()">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Back
                    </Button>
                </Link>
                <div class="h-4 w-px bg-border" />
                <div>
                    <h1 class="text-lg font-bold text-foreground">
                        Edit Title Proposal
                    </h1>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ paper.title }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Form body -->
        <div class="flex-1 overflow-y-auto px-4 py-6 md:px-6">
            <form
                id="edit-form"
                @submit.prevent="submit"
                class="mx-auto max-w-3xl space-y-5"
            >
                <!-- Basic Information -->
                <section
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center gap-2.5 border-b border-border bg-muted/40 px-5 py-3.5"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-500/20"
                        >
                            <FileText
                                class="h-3.5 w-3.5 text-orange-600 dark:text-orange-400"
                            />
                        </div>
                        <h2 class="text-sm font-semibold text-foreground">
                            Basic Information
                        </h2>
                        <span class="ml-auto text-xs text-red-500"
                            >* Required</span
                        >
                    </div>
                    <div class="space-y-4 p-5">
                        <!-- Title -->
                        <div>
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <Label
                                    class="text-sm font-medium text-foreground"
                                >
                                    Title <span class="text-red-500">*</span>
                                </Label>
                                <span
                                    :class="[
                                        'text-xs tabular-nums transition',
                                        form.title.length > 230
                                            ? form.title.length > 255
                                                ? 'text-red-500'
                                                : 'text-amber-500'
                                            : 'text-muted-foreground',
                                    ]"
                                >
                                    {{ form.title.length }} / 255
                                </span>
                            </div>
                            <Input
                                v-model="form.title"
                                placeholder="Enter your research title"
                                maxlength="255"
                                :class="[
                                    'h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm transition',
                                    form.errors.title
                                        ? 'border-red-400 focus-visible:border-red-500 focus-visible:ring-2 focus-visible:ring-red-100 dark:focus-visible:ring-red-500/20'
                                        : 'border-input focus-visible:border-orange-500 focus-visible:ring-2 focus-visible:ring-orange-100 dark:focus-visible:ring-orange-500/20',
                                ]"
                            />
                            <p
                                v-if="form.errors.title"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <X class="h-3 w-3" /> {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Rationale (stored as abstract) -->
                        <div>
                            <Label
                                class="mb-1.5 block text-sm font-medium text-foreground"
                                >Rationale</Label
                            >
                            <textarea
                                v-model="form.abstract"
                                rows="6"
                                placeholder="Explain the rationale for your study: the problem, significance, scope, and expected contribution…"
                                :class="[
                                    'w-full resize-y rounded-lg border bg-background px-3 py-2.5 text-sm text-foreground transition outline-none',
                                    form.errors.abstract
                                        ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 dark:focus:ring-red-500/20'
                                        : 'border-input focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20',
                                ]"
                            />
                            <p
                                v-if="form.errors.abstract"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <X class="h-3 w-3" /> {{ form.errors.abstract }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Proponents -->
                <section
                    class="rounded-xl border border-border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center gap-2.5 border-b border-border bg-muted/40 px-5 py-3.5"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/20"
                        >
                            <Users
                                class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <h2 class="text-sm font-semibold text-foreground">
                            Proponents
                        </h2>
                        <span
                            class="ml-auto rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                        >
                            {{ form.proponents.filter((p) => p.id).length }} / 3
                        </span>
                    </div>
                    <div class="space-y-2 p-5">
                        <!-- Slot 0: always you -->
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                >1</span
                            >
                            <div
                                class="flex h-10 flex-1 items-center gap-2 rounded-lg border border-input bg-muted px-3 py-2 text-sm text-foreground"
                            >
                                <span
                                    class="rounded bg-orange-100 px-1.5 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                    >You</span
                                >
                                {{ form.proponents[0]?.name }}
                            </div>
                        </div>

                        <!-- Additional proponent slots -->
                        <div
                            v-for="(proponent, idx) in form.proponents.slice(1)"
                            :key="idx + 1"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground"
                                >{{ idx + 2 }}</span
                            >

                            <template v-if="activeSearchSlot !== idx + 1">
                                <div
                                    :class="[
                                        'flex h-10 flex-1 cursor-pointer items-center rounded-lg border px-3 py-2 text-sm transition',
                                        proponent.id
                                            ? 'border-input bg-background text-foreground hover:border-orange-400'
                                            : 'border-dashed border-orange-300 bg-orange-50/50 text-muted-foreground hover:border-orange-400 hover:bg-orange-50 dark:border-orange-800 dark:bg-orange-950/10 dark:hover:bg-orange-950/20',
                                    ]"
                                    @click="openSearch(idx + 1)"
                                >
                                    <span
                                        v-if="proponent.id"
                                        class="text-foreground"
                                        >{{ proponent.name }}</span
                                    >
                                    <span
                                        v-else
                                        class="flex items-center gap-1.5 text-orange-600 dark:text-orange-400"
                                    >
                                        <Plus class="h-3.5 w-3.5" /> Click to
                                        search for a co-researcher
                                    </span>
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
                                            class="cursor-pointer px-3 py-2.5 text-sm text-foreground hover:bg-muted"
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
                                        class="absolute z-20 mt-1 w-full rounded-lg border border-border bg-card px-3 py-2.5 text-xs text-muted-foreground shadow-lg"
                                    >
                                        No students found.
                                    </div>
                                </div>
                            </template>

                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/20"
                                title="Remove proponent"
                                @click="removeProponent(idx + 1)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p
                            v-if="form.errors.proponents"
                            class="mt-1 flex items-center gap-1 text-xs text-red-500"
                        >
                            <X class="h-3 w-3" /> {{ form.errors.proponents }}
                        </p>

                        <button
                            v-if="canAddProponent"
                            type="button"
                            class="mt-1 flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-orange-600 transition hover:bg-orange-50 hover:text-orange-700 dark:hover:bg-orange-950/20"
                            @click="addProponentSlot"
                        >
                            <Plus class="h-4 w-4" />
                            Add Co-Researcher
                        </button>
                    </div>
                </section>

                <!-- Keywords + File Upload -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <section
                        class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                    >
                        <div
                            class="flex items-center gap-2.5 border-b border-border bg-muted/40 px-5 py-3.5"
                        >
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-500/20"
                            >
                                <Tag
                                    class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400"
                                />
                            </div>
                            <h2 class="text-sm font-semibold text-foreground">
                                Keywords
                            </h2>
                        </div>
                        <div class="p-5">
                            <TagsInput
                                v-model="form.keywords"
                                placeholder="Add keyword…"
                                wrapper-focus-class="focus-within:border-orange-400 focus-within:ring-2 focus-within:ring-orange-400/30"
                            />
                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Press Enter or comma to add a keyword.
                            </p>
                            <p
                                v-if="form.errors.keywords"
                                class="mt-1 flex items-center gap-1 text-xs text-red-500"
                            >
                                <X class="h-3 w-3" /> {{ form.errors.keywords }}
                            </p>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                    >
                        <div
                            class="flex items-center gap-2.5 border-b border-border bg-muted/40 px-5 py-3.5"
                        >
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-500/20"
                            >
                                <FileText
                                    class="h-3.5 w-3.5 text-orange-600 dark:text-orange-400"
                                />
                            </div>
                            <h2 class="text-sm font-semibold text-foreground">
                                File Upload
                            </h2>
                            <span class="ml-auto text-xs text-muted-foreground"
                                >PDF only · max {{ uploadMaxSizeMb }} MB</span
                            >
                        </div>
                        <div class="p-5">
                            <!-- Existing file indicator -->
                            <div
                                v-if="existingFile && !form.file"
                                class="mb-3 flex items-start gap-3 rounded-lg border border-border bg-muted/50 px-4 py-3"
                            >
                                <svg
                                    class="mt-0.5 h-8 w-8 shrink-0 text-red-500"
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
                                        class="min-w-0 text-sm leading-snug font-medium [overflow-wrap:anywhere] break-words text-foreground"
                                    >
                                        {{ existingFile.file_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            formatFileSize(
                                                existingFile.file_size,
                                            )
                                        }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 self-center rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    >Current</span
                                >
                            </div>
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
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <X class="h-3 w-3" /> {{ form.errors.file }}
                            </p>
                        </div>
                    </section>
                </div>
            </form>
        </div>

        <!-- Sticky submit bar -->
        <div
            class="border-t border-border bg-card/80 px-4 py-3 backdrop-blur-sm md:px-6"
        >
            <div
                class="mx-auto flex max-w-3xl items-center justify-between gap-3"
            >
                <p
                    v-if="form.hasErrors"
                    class="flex items-center gap-1.5 text-xs text-red-500"
                >
                    <X class="h-3.5 w-3.5" />
                    Please fix the errors above before saving.
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Changes will be saved to your existing proposal.
                </p>
                <div class="flex items-center gap-2">
                    <Link :href="student.research.index()">
                        <Button type="button" variant="outline" size="sm"
                            >Cancel</Button
                        >
                    </Link>
                    <Button
                        type="submit"
                        form="edit-form"
                        size="sm"
                        :disabled="form.processing"
                        class="min-w-[130px] gap-2 bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-70"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
