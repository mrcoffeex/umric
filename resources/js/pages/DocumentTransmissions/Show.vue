<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCopy,
    Eye,
    FileDown,
    History,
    Link2,
    Maximize2,
    PackageOpen,
    QrCode,
    User,
    X,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import documentTransmissions from '@/routes/document-transmissions';

interface UserRef {
    id: string;
    name: string;
    email: string;
}

interface ItemAttachment {
    file_name: string;
    file_size: number | null;
    preview_url: string;
    download_url: string;
}

interface Item {
    id: string;
    label: string;
    sort_order: number;
    received_at: string | null;
    attachment: ItemAttachment | null;
}

interface Transmission {
    id: string;
    purpose: string;
    status: string;
    share_token: string;
    completed_at: string | null;
    created_at: string;
    sender: UserRef | null;
    receiver: UserRef | null;
    items: Item[];
}

interface HandoffHistoryEntry {
    id: string;
    event: string;
    meta: Record<string, unknown>;
    created_at: string;
    actor: Pick<UserRef, 'id' | 'name'> | null;
}

const props = defineProps<{
    transmission: Transmission;
    handoffHistory: HandoffHistoryEntry[];
    claimUrl: string;
    isSender: boolean;
    isReceiver: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'Document handoffs',
                href: documentTransmissions.index.url(),
            },
            { title: 'Handoff detail' },
        ],
    },
});

const qrModalOpen = ref(false);
const copied = ref(false);
const pendingReceiptIds = ref<Set<string>>(new Set());
const confirming = ref(false);

const checkedCount = computed(
    () => props.transmission.items.filter((i) => i.received_at !== null).length,
);
const totalCount = computed(() => props.transmission.items.length);
const isComplete = computed(() => props.transmission.status === 'completed');
const selectedToReceiveCount = computed(() => pendingReceiptIds.value.size);

function syncPendingFromProps() {
    pendingReceiptIds.value = new Set(
        props.transmission.items
            .filter((i) => i.received_at !== null)
            .map((i) => i.id),
    );
}

watch(
    () =>
        props.transmission.items
            .map((i) => `${i.id}:${i.received_at ?? ''}`)
            .join('|'),
    () => {
        syncPendingFromProps();
    },
    { immediate: true },
);

function onTogglePending(id: string, value: boolean | 'indeterminate') {
    if (value === 'indeterminate') {
        return;
    }

    const next = new Set(pendingReceiptIds.value);

    if (value) {
        next.add(id);
    } else {
        next.delete(id);
    }

    pendingReceiptIds.value = next;
}

function toggleRowPending(item: Item) {
    if (!props.isReceiver) {
        return;
    }

    onTogglePending(item.id, !pendingReceiptIds.value.has(item.id));
}

function confirmReceipt() {
    confirming.value = true;
    router.post(
        documentTransmissions.receive.url({
            transmission: props.transmission.id,
        }),
        { item_ids: [...pendingReceiptIds.value] },
        {
            preserveScroll: true,
            onFinish: () => {
                confirming.value = false;
            },
        },
    );
}

function copyClaimUrl() {
    void navigator.clipboard.writeText(props.claimUrl).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
}

function openClaimInNewTab() {
    window.open(props.claimUrl, '_blank', 'noopener,noreferrer');
}

function formatFileSize(bytes: number | null) {
    if (bytes == null || bytes === 0) {
        return '';
    }

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`;
}

type CreatedDocMeta = {
    label: string;
    has_attachment?: boolean;
    file_name?: string | null;
};

function metaDocuments(meta: Record<string, unknown>): CreatedDocMeta[] {
    const raw = meta.documents;

    if (!Array.isArray(raw)) {
        return [];
    }

    return raw.filter(
        (row): row is CreatedDocMeta =>
            typeof row === 'object' &&
            row !== null &&
            'label' in row &&
            typeof (row as CreatedDocMeta).label === 'string',
    );
}

function metaStringList(meta: Record<string, unknown>, key: string): string[] {
    const raw = meta[key];

    if (!Array.isArray(raw)) {
        return [];
    }

    return raw.filter((v): v is string => typeof v === 'string');
}

function historyHeading(entry: HandoffHistoryEntry): string {
    if (entry.event === 'handoff_created') {
        return 'Handoff created';
    }

    if (entry.event === 'receipt_confirmed') {
        return 'Receipt confirmed';
    }

    return entry.event.replaceAll('_', ' ');
}

function formatHistoryWhen(iso: string) {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Document handoff" />

        <div class="flex flex-wrap items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link
                    :href="documentTransmissions.index.url()"
                    class="gap-1.5 text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    All handoffs
                </Link>
            </Button>
        </div>

        <div
            class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
        >
            <div
                class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
            >
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="isComplete"
                            class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            Complete
                        </span>
                        <span
                            v-else
                            class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/25 dark:text-amber-200"
                        >
                            Awaiting checklist
                        </span>
                    </div>
                    <h1 class="text-lg font-bold text-foreground md:text-xl">
                        Document handoff
                    </h1>
                    <p class="text-sm whitespace-pre-wrap text-foreground">
                        {{ transmission.purpose }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ checkedCount }} / {{ totalCount }} documents checked
                        · Created
                        {{ new Date(transmission.created_at).toLocaleString() }}
                    </p>
                </div>
                <div
                    class="shrink-0 rounded-xl border border-border bg-muted/40 px-4 py-3 text-sm"
                >
                    <p class="text-xs font-medium text-muted-foreground">
                        Parties
                    </p>
                    <p class="mt-1 font-medium text-foreground">
                        From: {{ transmission.sender?.name ?? '—' }}
                    </p>
                    <p class="font-medium text-foreground">
                        To: {{ transmission.receiver?.name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card class="border-border">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-base font-semibold"
                    >
                        <QrCode class="h-4 w-4 text-orange-600" />
                        Share with recipient
                    </CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Scan the code or open the link on the receiver’s
                        account. They must be signed in.
                    </p>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-start gap-3">
                        <button
                            type="button"
                            class="relative shrink-0 cursor-pointer rounded-xl border-2 border-border bg-white p-2 transition hover:border-orange-400"
                            title="Enlarge QR"
                            @click="qrModalOpen = true"
                        >
                            <QrcodeVue :value="claimUrl" :size="88" level="M" />
                            <span
                                class="absolute right-1 bottom-1 rounded bg-black/30 p-0.5"
                            >
                                <Maximize2 class="h-2.5 w-2.5 text-white" />
                            </span>
                        </button>
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="gap-1.5"
                                    @click="copyClaimUrl"
                                >
                                    <ClipboardCopy class="h-3.5 w-3.5" />
                                    Copy link
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    class="gap-1.5"
                                    @click="openClaimInNewTab"
                                >
                                    <Link2 class="h-3.5 w-3.5" />
                                    Open
                                </Button>
                            </div>
                            <code
                                class="block max-h-20 overflow-auto rounded-lg bg-muted px-2 py-1.5 font-mono text-[10px] break-all text-muted-foreground"
                                >{{ claimUrl }}</code
                            >
                            <p v-if="copied" class="text-xs text-green-600">
                                Copied to clipboard.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-base font-semibold"
                    >
                        <PackageOpen class="h-4 w-4 text-teal-600" />
                        Receiving checklist
                    </CardTitle>
                    <p v-if="isReceiver" class="text-xs text-muted-foreground">
                        Tick the documents you are accepting, then press
                        <span class="font-medium text-foreground"
                            >Confirm receipt</span
                        >
                        once. Unchecked rows stay pending. Download any PDFs
                        first.
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Only
                        <span class="font-medium text-foreground">{{
                            transmission.receiver?.name
                        }}</span>
                        can update this checklist.
                    </p>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div
                        v-for="item in transmission.items"
                        :key="item.id"
                        :class="[
                            'flex min-w-0 items-start gap-3 rounded-lg border border-border bg-card px-3 py-2.5',
                            isReceiver &&
                                'cursor-pointer transition-colors hover:bg-muted/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        ]"
                        :tabindex="isReceiver ? 0 : undefined"
                        :aria-label="
                            isReceiver
                                ? `Toggle receive: ${item.label}`
                                : undefined
                        "
                        @click="isReceiver && toggleRowPending(item)"
                        @keydown.enter.prevent="
                            isReceiver && toggleRowPending(item)
                        "
                        @keydown.space.prevent="
                            isReceiver && toggleRowPending(item)
                        "
                    >
                        <div class="shrink-0 pt-0.5">
                            <div
                                v-if="isReceiver"
                                class="flex items-start gap-2"
                                @click.stop
                            >
                                <Checkbox
                                    :id="`handoff-receive-${item.id}`"
                                    :model-value="
                                        pendingReceiptIds.has(item.id)
                                    "
                                    class="mt-0.5"
                                    :aria-label="`Receive document: ${item.label}`"
                                    @update:model-value="
                                        (v) => onTogglePending(item.id, v)
                                    "
                                />
                            </div>
                            <div
                                v-else
                                class="flex min-h-8 items-center"
                                :title="
                                    item.received_at
                                        ? 'Receiver marked this received'
                                        : 'Awaiting receiver'
                                "
                            >
                                <CheckCircle2
                                    v-if="item.received_at"
                                    class="h-5 w-5 shrink-0 text-green-600 dark:text-green-400"
                                />
                                <div
                                    v-else
                                    class="h-4 w-4 shrink-0 rounded-full border-2 border-muted-foreground/35"
                                />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1 space-y-1.5 overflow-hidden">
                            <span
                                class="block text-sm leading-snug text-foreground"
                                :class="
                                    item.received_at
                                        ? 'text-muted-foreground line-through'
                                        : ''
                                "
                            >
                                {{ item.label }}
                            </span>
                            <div
                                v-if="item.attachment"
                                class="min-w-0 space-y-2"
                            >
                                <p
                                    class="truncate text-xs font-medium text-foreground"
                                    :title="item.attachment.file_name"
                                >
                                    {{ item.attachment.file_name }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        :href="item.attachment.preview_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                        @click.stop
                                    >
                                        <Eye class="h-3.5 w-3.5 shrink-0" />
                                        Preview
                                    </a>
                                    <a
                                        :href="item.attachment.download_url"
                                        download
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                        @click.stop
                                    >
                                        <FileDown
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        Download
                                    </a>
                                    <span
                                        v-if="
                                            formatFileSize(
                                                item.attachment.file_size,
                                            )
                                        "
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        {{
                                            formatFileSize(
                                                item.attachment.file_size,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="isReceiver"
                        class="mt-4 space-y-2 border-t border-border pt-4"
                    >
                        <Button
                            type="button"
                            class="w-full gap-1.5 sm:w-auto"
                            :disabled="confirming"
                            @click="confirmReceipt"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Confirm receipt
                        </Button>
                        <p class="text-xs text-muted-foreground">
                            {{ selectedToReceiveCount }} of
                            {{ totalCount }} selected. Confirming updates the
                            handoff: checked rows are marked received; unchecked
                            rows are cleared.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="border-border">
            <CardHeader class="pb-2">
                <CardTitle
                    class="flex items-center gap-2 text-base font-semibold"
                >
                    <History class="h-4 w-4 text-violet-600" />
                    Handoff history
                </CardTitle>
                <p class="text-xs text-muted-foreground">
                    Every create and receipt confirmation is logged with who did
                    it and what changed.
                </p>
            </CardHeader>
            <CardContent>
                <p
                    v-if="handoffHistory.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No history entries yet. New events appear here after the
                    handoff is created or receipt is confirmed.
                </p>
                <ol
                    v-else
                    class="relative space-y-0 border-l border-border pl-6"
                >
                    <li
                        v-for="entry in handoffHistory"
                        :key="entry.id"
                        class="relative pb-8 last:pb-0"
                    >
                        <span
                            class="absolute top-1.5 -left-[21px] flex h-2.5 w-2.5 rounded-full border-2 border-background bg-violet-500 ring-1 ring-border"
                            aria-hidden="true"
                        />
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between"
                        >
                            <div class="space-y-1">
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ historyHeading(entry) }}
                                </p>
                                <p
                                    v-if="entry.event === 'handoff_created'"
                                    class="text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="
                                            typeof entry.meta.receiver_name ===
                                            'string'
                                        "
                                    >
                                        Sent to
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                entry.meta.receiver_name
                                            }}</span
                                        >
                                        ·
                                    </span>
                                    <span
                                        v-if="
                                            typeof entry.meta.item_count ===
                                            'number'
                                        "
                                    >
                                        {{ entry.meta.item_count }} document(s)
                                        listed
                                    </span>
                                </p>
                                <p
                                    v-else-if="
                                        entry.event === 'receipt_confirmed'
                                    "
                                    class="text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="
                                            typeof entry.meta.received_count ===
                                                'number' &&
                                            typeof entry.meta.total_count ===
                                                'number'
                                        "
                                    >
                                        {{ entry.meta.received_count }} of
                                        {{ entry.meta.total_count }} marked
                                        received
                                    </span>
                                    <span
                                        v-if="entry.meta.status === 'completed'"
                                        class="ml-1 font-medium text-green-700 dark:text-green-400"
                                    >
                                        · Handoff complete
                                    </span>
                                </p>
                                <div
                                    v-if="entry.event === 'handoff_created'"
                                    class="mt-2 space-y-1.5 rounded-md border border-border bg-muted/30 px-3 py-2"
                                >
                                    <p
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        Documents in this handoff
                                    </p>
                                    <ul
                                        class="list-inside list-disc space-y-0.5 text-xs text-foreground"
                                    >
                                        <li
                                            v-for="(doc, idx) in metaDocuments(
                                                entry.meta,
                                            )"
                                            :key="idx"
                                        >
                                            <span>{{ doc.label }}</span>
                                            <span
                                                v-if="doc.has_attachment"
                                                class="text-muted-foreground"
                                            >
                                                — file:
                                                {{
                                                    doc.file_name ?? 'attached'
                                                }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                            >
                                                — no file
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div
                                    v-else-if="
                                        entry.event === 'receipt_confirmed'
                                    "
                                    class="mt-2 grid gap-2 sm:grid-cols-2"
                                >
                                    <div
                                        v-if="
                                            metaStringList(
                                                entry.meta,
                                                'received_document_labels',
                                            ).length
                                        "
                                        class="rounded-md border border-green-200/80 bg-green-50/80 px-3 py-2 dark:border-green-900/50 dark:bg-green-950/25"
                                    >
                                        <p
                                            class="mb-1 text-[11px] font-medium text-green-800 dark:text-green-300"
                                        >
                                            Marked received
                                        </p>
                                        <ul
                                            class="space-y-0.5 text-xs text-green-900 dark:text-green-100"
                                        >
                                            <li
                                                v-for="(
                                                    label, idx
                                                ) in metaStringList(
                                                    entry.meta,
                                                    'received_document_labels',
                                                )"
                                                :key="'r-' + idx"
                                            >
                                                {{ label }}
                                            </li>
                                        </ul>
                                    </div>
                                    <div
                                        v-if="
                                            metaStringList(
                                                entry.meta,
                                                'pending_document_labels',
                                            ).length
                                        "
                                        class="rounded-md border border-amber-200/80 bg-amber-50/80 px-3 py-2 dark:border-amber-900/50 dark:bg-amber-950/25"
                                    >
                                        <p
                                            class="mb-1 text-[11px] font-medium text-amber-900 dark:text-amber-200"
                                        >
                                            Still pending
                                        </p>
                                        <ul
                                            class="space-y-0.5 text-xs text-amber-950 dark:text-amber-100"
                                        >
                                            <li
                                                v-for="(
                                                    label, idx
                                                ) in metaStringList(
                                                    entry.meta,
                                                    'pending_document_labels',
                                                )"
                                                :key="'p-' + idx"
                                            >
                                                {{ label }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 text-xs text-muted-foreground">
                                <p
                                    class="flex items-center gap-1 font-medium text-foreground"
                                >
                                    <User class="h-3 w-3 shrink-0" />
                                    {{ entry.actor?.name ?? 'Unknown user' }}
                                </p>
                                <time
                                    class="mt-0.5 block tabular-nums"
                                    :datetime="entry.created_at"
                                >
                                    {{ formatHistoryWhen(entry.created_at) }}
                                </time>
                            </div>
                        </div>
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="qrModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                    @click.self="qrModalOpen = false"
                >
                    <div
                        class="relative flex w-full max-w-sm flex-col items-center gap-5 rounded-2xl border border-border bg-card p-8 shadow-2xl"
                    >
                        <button
                            type="button"
                            class="absolute top-3 right-3 rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            @click="qrModalOpen = false"
                        >
                            <X class="h-4 w-4" />
                        </button>
                        <h2 class="text-base font-bold text-foreground">
                            Handoff link QR
                        </h2>
                        <div
                            class="rounded-2xl border-4 border-orange-400 bg-white p-4"
                        >
                            <QrcodeVue
                                :value="claimUrl"
                                :size="220"
                                level="H"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                            @click="copyClaimUrl"
                        >
                            <ClipboardCopy class="h-3.5 w-3.5" />
                            Copy link
                        </Button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
