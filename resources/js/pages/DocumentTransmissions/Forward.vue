<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    Loader2,
    PackageOpen,
    Users,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import documentTransmissions from '@/routes/document-transmissions';
import { search as searchRecipients } from '@/routes/document-transmissions/recipients';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'Document handoffs',
                href: documentTransmissions.index.url(),
            },
            { title: 'Forward handoff' },
        ],
    },
});

interface UserRef {
    id: string;
    name: string;
    email: string;
}

interface SourceItem {
    id: string;
    label: string;
    has_attachment: boolean;
    file_name: string | null;
}

const props = defineProps<{
    source: {
        id: string;
        purpose: string;
        status: string;
        created_at: string;
        sender: UserRef | null;
        receiver: UserRef | null;
        items: SourceItem[];
    };
}>();

const form = useForm({
    receiver_id: '',
    purpose: `Forward: ${props.source.purpose}`.slice(0, 5000),
    item_ids: props.source.items.map((i) => i.id) as string[],
});

const nDocuments = computed(() => props.source.items.length);

const allDocumentsSelected = computed(
    () =>
        nDocuments.value > 0 &&
        form.item_ids.length === nDocuments.value &&
        props.source.items.every((row) => form.item_ids.includes(row.id)),
);

const noDocumentsSelected = computed(() => form.item_ids.length === 0);

function documentSelected(id: string): boolean {
    return form.item_ids.includes(id);
}

function setDocumentSelected(id: string, checked: boolean) {
    const next = new Set(form.item_ids);

    if (checked) {
        next.add(id);
    } else {
        next.delete(id);
    }

    form.item_ids = [...next];
}

function checkAllDocuments() {
    form.item_ids = props.source.items.map((i) => i.id);
}

function uncheckAllDocuments() {
    form.item_ids = [];
}

interface SearchResult {
    id: string;
    name: string;
    email: string;
}

const recipientLabel = ref('');
const recipientSearchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchInput = ref<HTMLInputElement | null>(null);
const RECIPIENT_SEARCH_DEBOUNCE_MS = 400;

let recipientSearchAbort: AbortController | null = null;
let recipientSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

function openRecipientSearch() {
    recipientSearchOpen.value = true;
    searchQuery.value = '';
    searchResults.value = [];
    nextTick(() => searchInput.value?.focus());
}

function closeRecipientSearch() {
    recipientSearchOpen.value = false;
    searchQuery.value = '';
    searchResults.value = [];
}

function selectRecipient(r: SearchResult) {
    form.receiver_id = r.id;
    recipientLabel.value = `${r.name} (${r.email})`;
    closeRecipientSearch();
}

function clearRecipient() {
    form.receiver_id = '';
    recipientLabel.value = '';
}

async function fetchRecipients(q: string) {
    const trimmed = q.trim();

    if (trimmed.length < 2) {
        recipientSearchAbort?.abort();
        recipientSearchAbort = null;
        searchResults.value = [];

        return;
    }

    recipientSearchAbort?.abort();
    recipientSearchAbort = new AbortController();
    const { signal } = recipientSearchAbort;

    try {
        const res = await fetch(
            searchRecipients.url({
                query: { q: trimmed },
            }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal,
            },
        );

        if (!res.ok) {
            searchResults.value = [];

            return;
        }

        searchResults.value = await res.json();
    } catch (err) {
        if (err instanceof DOMException && err.name === 'AbortError') {
            return;
        }

        searchResults.value = [];
    }
}

function onSearchInput(value: string | number) {
    const s = typeof value === 'number' ? String(value) : value;
    searchQuery.value = s;

    if (recipientSearchDebounceTimer !== null) {
        clearTimeout(recipientSearchDebounceTimer);
        recipientSearchDebounceTimer = null;
    }

    if (s.trim().length < 2) {
        recipientSearchAbort?.abort();
        recipientSearchAbort = null;
        searchResults.value = [];

        return;
    }

    recipientSearchDebounceTimer = setTimeout(() => {
        recipientSearchDebounceTimer = null;
        void fetchRecipients(s);
    }, RECIPIENT_SEARCH_DEBOUNCE_MS);
}

onBeforeUnmount(() => {
    if (recipientSearchDebounceTimer !== null) {
        clearTimeout(recipientSearchDebounceTimer);
    }

    recipientSearchAbort?.abort();
});

function submit() {
    if (form.item_ids.length < 1) {
        return;
    }

    form.post(
        documentTransmissions.forward.store.url({
            transmission: props.source.id,
        }),
        { preserveScroll: true },
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col bg-background">
        <Head title="Forward handoff" />

        <div class="border-b border-border bg-card px-4 py-4 md:px-6">
            <div class="mx-auto flex max-w-2xl items-center gap-3">
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="
                            documentTransmissions.show.url({
                                transmission: source.id,
                            })
                        "
                        class="gap-1.5 text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Back
                    </Link>
                </Button>
                <div class="h-4 w-px bg-border" />
                <div>
                    <h1 class="text-lg font-bold text-foreground">
                        Forward handoff
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        The same document list and file copies are sent to a new
                        recipient. The original handoff’s activity log is
                        updated on each line. Duplicate pending handoffs to the
                        same person are blocked.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6 md:px-6">
            <form class="mx-auto max-w-2xl space-y-6" @submit.prevent="submit">
                <section
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center gap-2 border-b border-border bg-muted/40 px-5 py-3.5"
                    >
                        <PackageOpen class="h-4 w-4 text-teal-600" />
                        <h2 class="text-sm font-semibold text-foreground">
                            Source handoff
                        </h2>
                    </div>
                    <div class="space-y-2 p-5 text-sm">
                        <p class="whitespace-pre-wrap text-foreground">
                            {{ source.purpose }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            From
                            <span class="font-medium text-foreground">{{
                                source.sender?.name ?? '—'
                            }}</span>
                            · To
                            <span class="font-medium text-foreground">{{
                                source.receiver?.name ?? '—'
                            }}</span>
                            · Created
                            {{ new Date(source.created_at).toLocaleString() }}
                        </p>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center gap-2 border-b border-border bg-muted/40 px-5 py-3.5"
                    >
                        <Users
                            class="h-4 w-4 text-orange-600 dark:text-orange-400"
                        />
                        <h2 class="text-sm font-semibold text-foreground">
                            New recipient
                        </h2>
                    </div>
                    <div class="space-y-3 p-5">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <Label for="recipient-forward-display"
                                    >Forward to</Label
                                >
                                <Input
                                    id="recipient-forward-display"
                                    readonly
                                    :value="recipientLabel"
                                    placeholder="Search a user…"
                                    class="cursor-pointer bg-muted/50"
                                    @click="openRecipientSearch"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                                @click="openRecipientSearch"
                            >
                                Search
                            </Button>
                            <Button
                                v-if="form.receiver_id"
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="clearRecipient"
                            >
                                Clear
                            </Button>
                        </div>
                        <p
                            v-if="form.errors.receiver_id"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.receiver_id }}
                        </p>

                        <div
                            v-if="recipientSearchOpen"
                            class="rounded-lg border border-border bg-popover p-3 shadow-md"
                        >
                            <Input
                                ref="searchInput"
                                :model-value="searchQuery"
                                placeholder="Type name or email…"
                                class="mb-2"
                                @update:model-value="onSearchInput"
                            />
                            <ul
                                v-if="searchResults.length"
                                class="max-h-48 overflow-y-auto text-sm"
                            >
                                <li v-for="r in searchResults" :key="r.id">
                                    <button
                                        type="button"
                                        class="flex w-full flex-col items-start rounded-md px-2 py-2 text-left hover:bg-muted"
                                        @click="selectRecipient(r)"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >{{ r.name }}</span
                                        >
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{ r.email }}</span
                                        >
                                    </button>
                                </li>
                            </ul>
                            <p
                                v-else-if="searchQuery.trim().length >= 2"
                                class="text-xs text-muted-foreground"
                            >
                                No matches.
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Enter at least 2 characters.
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                >
                    <div class="border-b border-border bg-muted/40 px-5 py-3.5">
                        <h2 class="text-sm font-semibold text-foreground">
                            Purpose
                        </h2>
                    </div>
                    <div class="p-5">
                        <textarea
                            v-model="form.purpose"
                            rows="4"
                            class="min-h-[100px] w-full resize-y rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground transition outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                        />
                        <p
                            v-if="form.errors.purpose"
                            class="mt-2 text-xs text-destructive"
                        >
                            {{ form.errors.purpose }}
                        </p>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-xs"
                >
                    <div
                        class="flex flex-col gap-2 border-b border-border bg-muted/40 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">
                                Documents to include
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Choose which lines to copy. PDFs are copied to
                                the new handoff.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                                :disabled="allDocumentsSelected"
                                @click="checkAllDocuments"
                            >
                                Check all
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                                :disabled="noDocumentsSelected"
                                @click="uncheckAllDocuments"
                            >
                                Uncheck all
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.item_ids"
                        class="px-5 pt-3 text-xs text-destructive"
                    >
                        {{ form.errors.item_ids }}
                    </p>
                    <ul class="divide-y divide-border p-0">
                        <li
                            v-for="row in source.items"
                            :key="row.id"
                            class="flex gap-3 px-5 py-3 text-sm"
                        >
                            <div class="shrink-0 pt-0.5" @click.stop>
                                <Checkbox
                                    :id="`forward-doc-${row.id}`"
                                    :model-value="documentSelected(row.id)"
                                    :aria-label="`Include document: ${row.label}`"
                                    @update:model-value="
                                        (v) =>
                                            setDocumentSelected(
                                                row.id,
                                                v === true,
                                            )
                                    "
                                />
                            </div>
                            <FileText
                                class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0 flex-1">
                                <label
                                    :for="`forward-doc-${row.id}`"
                                    class="block cursor-pointer font-medium text-foreground"
                                >
                                    {{ row.label }}
                                </label>
                                <p
                                    v-if="row.has_attachment"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ row.file_name ?? 'PDF attached' }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    No file on this line
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p
                        class="border-t border-border px-5 py-2 text-xs text-muted-foreground"
                    >
                        {{ form.item_ids.length }} of
                        {{ source.items.length }} selected
                    </p>
                </section>

                <div class="flex justify-end gap-2 pb-8">
                    <Button
                        variant="outline"
                        type="button"
                        as-child
                        class="border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                    >
                        <Link
                            :href="
                                documentTransmissions.show.url({
                                    transmission: source.id,
                                })
                            "
                            >Cancel</Link
                        >
                    </Button>
                    <Button
                        type="submit"
                        class="bg-orange-500 text-white hover:bg-orange-600"
                        :disabled="form.processing || noDocumentsSelected"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Create forwarded handoff
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
