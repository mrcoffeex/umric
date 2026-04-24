<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    Loader2,
    Plus,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
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
            { title: 'New handoff' },
        ],
    },
});

const uploadMaxSizeMb = Number(import.meta.env.VITE_UPLOAD_MAX_SIZE_MB ?? 25);

interface ItemRow {
    label: string;
    file: File | null;
}

const form = useForm({
    receiver_id: '',
    purpose: '',
    items: [{ label: '', file: null }] as ItemRow[],
});

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

/** Debounce delay (ms) — avoids a request on every keystroke */
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

function addItemRow() {
    if (form.items.length >= 100) {
        return;
    }

    form.items.push({ label: '', file: null });
}

function removeItemRow(index: number) {
    if (form.items.length <= 1) {
        form.items[0] = { label: '', file: null };
        nextTick(() => {
            const el = document.getElementById(
                'item-file-0',
            ) as HTMLInputElement | null;

            if (el) {
                el.value = '';
            }
        });

        return;
    }

    form.items.splice(index, 1);
}

function onItemFileChange(index: number, e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] ?? null;

    if (f && f.type !== 'application/pdf') {
        input.value = '';
        form.items[index].file = null;

        return;
    }

    form.items[index].file = f;
}

function clearItemFile(index: number) {
    form.items[index].file = null;
    nextTick(() => {
        const el = document.getElementById(
            `item-file-${index}`,
        ) as HTMLInputElement | null;

        if (el) {
            el.value = '';
        }
    });
}

function submit() {
    form.transform((data) => ({
        receiver_id: data.receiver_id,
        purpose: data.purpose,
        items: data.items
            .map((row) => ({
                label: row.label.trim(),
                file: row.file,
            }))
            .filter((row) => row.label !== ''),
    })).post(documentTransmissions.store.url(), {
        forceFormData: true,
    });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col bg-background">
        <Head title="New document handoff" />

        <div class="border-b border-border bg-card px-4 py-4 md:px-6">
            <div class="mx-auto flex max-w-2xl items-center gap-3">
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="documentTransmissions.index.url()"
                        class="gap-1.5 text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Back
                    </Link>
                </Button>
                <div class="h-4 w-px bg-border" />
                <div>
                    <h1 class="text-lg font-bold text-foreground">
                        New document handoff
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Choose who receives the bundle, describe why you are
                        sending it, list each document, and optionally attach a
                        PDF per line.
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
                        <Users
                            class="h-4 w-4 text-orange-600 dark:text-orange-400"
                        />
                        <h2 class="text-sm font-semibold text-foreground">
                            Recipient
                        </h2>
                    </div>
                    <div class="space-y-3 p-5">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <Label for="recipient-display">Send to</Label>
                                <Input
                                    id="recipient-display"
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
                        <p class="text-xs text-muted-foreground">
                            One or two sentences help the receiver prioritize.
                        </p>
                    </div>
                    <div class="p-5">
                        <textarea
                            v-model="form.purpose"
                            rows="4"
                            placeholder="e.g. Title defense packet for adviser review — please sign the routing slip when complete."
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
                        class="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-muted/40 px-5 py-3.5"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">
                                Documents in this handoff
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                One line per document. PDF only, up to
                                {{ uploadMaxSizeMb }} MB each.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1"
                            @click="addItemRow"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add line
                        </Button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div
                            v-for="(row, idx) in form.items"
                            :key="idx"
                            class="rounded-lg border border-border bg-muted/20 p-3"
                        >
                            <div class="flex gap-2">
                                <Input
                                    v-model="form.items[idx].label"
                                    :placeholder="`Document ${idx + 1}`"
                                    class="flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="shrink-0 text-muted-foreground hover:text-destructive"
                                    :disabled="form.items.length <= 1"
                                    @click="removeItemRow(idx)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-2 border-t border-border/60 pt-2"
                            >
                                <FileText
                                    class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                                />
                                <Label
                                    :for="`item-file-${idx}`"
                                    class="cursor-pointer text-xs font-normal text-muted-foreground"
                                >
                                    PDF (optional)
                                </Label>
                                <input
                                    :id="`item-file-${idx}`"
                                    type="file"
                                    accept="application/pdf,.pdf"
                                    class="max-w-[200px] cursor-pointer text-xs file:mr-2 file:rounded-md file:border-0 file:bg-orange-500 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white hover:file:bg-orange-600"
                                    @change="onItemFileChange(idx, $event)"
                                />
                                <span
                                    v-if="row.file"
                                    class="flex min-w-0 items-center gap-1 text-xs text-foreground"
                                >
                                    <span class="truncate">{{
                                        row.file.name
                                    }}</span>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                                        title="Remove file"
                                        @click="clearItemFile(idx)"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                            <p
                                v-if="form.errors[`items.${idx}.label`]"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors[`items.${idx}.label`] }}
                            </p>
                            <p
                                v-if="form.errors[`items.${idx}.file`]"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors[`items.${idx}.file`] }}
                            </p>
                        </div>
                        <p
                            v-if="form.errors.items"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.items }}
                        </p>
                    </div>
                </section>

                <div class="flex justify-end gap-2 pb-8">
                    <Button variant="outline" type="button" as-child>
                        <Link :href="documentTransmissions.index.url()"
                            >Cancel</Link
                        >
                    </Button>
                    <Button
                        type="submit"
                        class="bg-orange-500 text-white hover:bg-orange-600"
                        :disabled="form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Create handoff
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
