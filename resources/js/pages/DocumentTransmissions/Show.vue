<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCopy,
    Eye,
    FileDown,
    Forward,
    History,
    Link2,
    ListTree,
    Maximize2,
    PackageOpen,
    QrCode,
    User,
    X,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref, watch } from 'vue';
import EsignaturePad from '@/components/EsignaturePad.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

interface ItemActivity {
    id: string;
    event: string;
    meta: Record<string, unknown>;
    created_at: string;
    actor: Pick<UserRef, 'id' | 'name'> | null;
}

interface Item {
    id: string;
    label: string;
    sort_order: number;
    received_at: string | null;
    attachment: ItemAttachment | null;
    activities: ItemActivity[];
}

interface ForwardedFromSummary {
    id: string;
    purpose: string;
    created_at: string;
    sender: Pick<UserRef, 'id' | 'name'> | null;
}

interface Transmission {
    id: string;
    purpose: string;
    status: string;
    share_token: string;
    completed_at: string | null;
    created_at: string;
    forwarded_from: ForwardedFromSummary | null;
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

const page = usePage<{
    errors: Record<string, string | string[] | undefined>;
    auth: {
        user: {
            hasAccountEsignature?: boolean;
            accountEsignatureUrl?: string | null;
        } | null;
    };
}>();

const props = defineProps<{
    transmission: Transmission;
    handoffHistory: HandoffHistoryEntry[];
    claimUrl: string;
    isSender: boolean;
    isReceiver: boolean;
    canForward: boolean;
    hasAccountEsignature?: boolean;
    accountEsignatureUrl?: string | null;
}>();

/** Prefer page props; fall back to shared auth (all accounts / fresh session). */
const hasAccountEsignature = computed(
    () =>
        props.hasAccountEsignature ??
        page.props.auth.user?.hasAccountEsignature ??
        false,
);
const accountEsignatureUrl = computed(
    () =>
        props.accountEsignatureUrl ??
        page.props.auth.user?.accountEsignatureUrl ??
        null,
);

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
const documentActivityModalOpen = ref(false);
const copied = ref(false);
const pendingReceiptIds = ref<Set<string>>(new Set());
const confirming = ref(false);
const esignPad = ref<InstanceType<typeof EsignaturePad> | null>(null);
const signatureEmpty = ref(true);
/** When true, PDFs get an e-signature card and a drawn/saved signature is required (unless account already has one). */
const embedEsignatureInPdf = ref(true);

const checkedCount = computed(
    () => props.transmission.items.filter((i) => i.received_at !== null).length,
);
const totalCount = computed(() => props.transmission.items.length);
const isComplete = computed(() => props.transmission.status === 'completed');
/** Receiver can still add receipts until the handoff is complete; not after, and not after final completion. */
const canUseReceiveChecklist = computed(
    () => props.isReceiver && !isComplete.value,
);
const selectedToReceiveCount = computed(() => pendingReceiptIds.value.size);

const documentActivityEventCount = computed(() =>
    props.transmission.items.reduce(
        (n, i) => n + (i.activities?.length ?? 0),
        0,
    ),
);

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

    const row = props.transmission.items.find((i) => i.id === id);

    if (row?.received_at && !value) {
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
    if (!canUseReceiveChecklist.value) {
        return;
    }

    if (item.received_at) {
        return;
    }

    onTogglePending(item.id, !pendingReceiptIds.value.has(item.id));
}

function confirmReceipt() {
    if (!canUseReceiveChecklist.value) {
        return;
    }

    const drawn = esignPad.value?.getPngDataUrl() ?? '';
    const hasDrawn = drawn !== '' && !esignPad.value?.isEmpty();

    if (embedEsignatureInPdf.value) {
        if (!hasAccountEsignature.value && !hasDrawn) {
            return;
        }
    }

    confirming.value = true;

    const body: {
        item_ids: string[];
        embed_esignature: boolean;
        signature?: string;
    } = {
        item_ids: [...pendingReceiptIds.value],
        embed_esignature: embedEsignatureInPdf.value,
    };

    if (embedEsignatureInPdf.value && hasDrawn) {
        body.signature = drawn;
    }

    router.post(
        documentTransmissions.receive.url({
            transmission: props.transmission.id,
        }),
        body,
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

function itemActivityHeading(event: string): string {
    if (event === 'added') {
        return 'Added to handoff';
    }

    if (event === 'receipt_changed') {
        return 'Receipt updated';
    }

    if (event === 'forwarded_in') {
        return 'Forwarded in';
    }

    if (event === 'forwarded_out') {
        return 'Forwarded out';
    }

    return event.replaceAll('_', ' ');
}

function formatRecipientFromForwardMeta(
    meta: Record<string, unknown> | undefined,
): string {
    if (!meta) {
        return 'the next recipient';
    }

    const name =
        typeof meta.to_receiver_name === 'string' &&
        meta.to_receiver_name !== ''
            ? meta.to_receiver_name
            : null;
    const email =
        typeof meta.to_receiver_email === 'string' &&
        meta.to_receiver_email !== ''
            ? meta.to_receiver_email
            : null;

    if (name && email) {
        return `${name} (${email})`;
    }

    if (name) {
        return name;
    }

    if (email) {
        return email;
    }

    return 'the next recipient';
}

function itemActivityDescription(act: ItemActivity): string {
    if (act.event === 'receipt_changed') {
        return act.meta?.marked_received === true
            ? 'Marked as received'
            : 'Cleared (pending)';
    }

    if (act.event === 'forwarded_out') {
        const who = formatRecipientFromForwardMeta(act.meta);

        return `This line was included in a new handoff sent to ${who}.`;
    }

    if (act.event === 'forwarded_in') {
        const who = formatRecipientFromForwardMeta(act.meta);
        const fromLine =
            typeof act.meta.from_label === 'string' &&
            act.meta.from_label !== ''
                ? act.meta.from_label
                : 'the prior handoff’s line item';

        return `This row was created by forwarding. The new handoff is for ${who}. It continues the source document: “${fromLine}”.`;
    }

    return '';
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
            <Button
                v-if="canForward"
                size="sm"
                as-child
                class="bg-teal-500 text-white shadow-sm hover:bg-teal-600"
            >
                <Link
                    :href="
                        documentTransmissions.forward.create.url({
                            transmission: transmission.id,
                        })
                    "
                    class="inline-flex items-center gap-1.5"
                >
                    <Forward class="h-3.5 w-3.5" />
                    Forward to someone else
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

        <div
            v-if="transmission.forwarded_from"
            class="rounded-xl border border-teal-200/80 bg-teal-50/60 px-4 py-3 text-sm dark:border-teal-900/40 dark:bg-teal-950/25"
        >
            <p class="font-medium text-teal-900 dark:text-teal-200">
                Forwarded from a previous handoff
            </p>
            <p class="mt-1 text-xs text-teal-800/90 dark:text-teal-300/90">
                Original purpose:
                <span class="whitespace-pre-wrap text-foreground/90">{{
                    transmission.forwarded_from.purpose
                }}</span>
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Originally from
                {{ transmission.forwarded_from.sender?.name ?? '—' }} ·
                <Link
                    :href="
                        documentTransmissions.show.url({
                            transmission: transmission.forwarded_from.id,
                        })
                    "
                    class="font-medium text-teal-800 underline dark:text-teal-300"
                >
                    View source handoff
                </Link>
            </p>
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
                                    variant="outline"
                                    class="gap-1.5 border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
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
                <CardHeader class="space-y-3 pb-2">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0 space-y-1">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <PackageOpen class="h-4 w-4 text-teal-600" />
                                Receiving checklist
                            </CardTitle>
                            <p
                                v-if="canUseReceiveChecklist"
                                class="text-xs text-muted-foreground"
                            >
                                Tick the documents you are accepting, then press
                                <span class="font-medium text-foreground"
                                    >Confirm receipt</span
                                >. A line you mark as received stays
                                accepted—you cannot undo it. When every line is
                                received, the handoff is final. Download any
                                PDFs first.
                            </p>
                            <p
                                v-else-if="isReceiver && isComplete"
                                class="text-xs text-muted-foreground"
                            >
                                This handoff is complete. The receipt checklist
                                cannot be changed.
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Only
                                <span class="font-medium text-foreground">{{
                                    transmission.receiver?.name
                                }}</span>
                                can update this checklist.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 w-full shrink-0 gap-1.5 border-teal-500/50 text-teal-800 hover:bg-teal-50 sm:w-auto dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                            @click="documentActivityModalOpen = true"
                        >
                            <ListTree class="h-3.5 w-3.5" />
                            Document activity
                            <span
                                v-if="documentActivityEventCount > 0"
                                class="rounded-full bg-teal-200/90 px-1.5 py-px text-[10px] font-semibold text-teal-950 dark:bg-teal-800 dark:text-teal-100"
                            >
                                {{ documentActivityEventCount }}
                            </span>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div
                        v-for="item in transmission.items"
                        :key="item.id"
                        :class="[
                            'flex min-w-0 items-start gap-3 rounded-lg border border-border bg-card px-3 py-2.5',
                            canUseReceiveChecklist &&
                                !item.received_at &&
                                'cursor-pointer transition-colors hover:bg-muted/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        ]"
                        :tabindex="
                            canUseReceiveChecklist && !item.received_at
                                ? 0
                                : undefined
                        "
                        :aria-label="
                            canUseReceiveChecklist
                                ? `Toggle receive: ${item.label}`
                                : undefined
                        "
                        @click="
                            canUseReceiveChecklist && toggleRowPending(item)
                        "
                        @keydown.enter.prevent="
                            canUseReceiveChecklist && toggleRowPending(item)
                        "
                        @keydown.space.prevent="
                            canUseReceiveChecklist && toggleRowPending(item)
                        "
                    >
                        <div class="shrink-0 pt-0.5">
                            <div
                                v-if="canUseReceiveChecklist"
                                class="flex items-start gap-2"
                                @click.stop
                            >
                                <Checkbox
                                    :id="`handoff-receive-${item.id}`"
                                    :model-value="
                                        pendingReceiptIds.has(item.id)
                                    "
                                    class="mt-0.5"
                                    :disabled="!!item.received_at"
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
                        v-if="canUseReceiveChecklist"
                        class="mt-4 space-y-2 border-t border-border pt-4"
                    >
                        <div class="flex items-start gap-2">
                            <Checkbox
                                id="embed-esignature-pdf"
                                :model-value="embedEsignatureInPdf"
                                class="mt-0.5"
                                :aria-label="'Embed e-signature on PDF documents'"
                                @update:model-value="
                                    (v) => (embedEsignatureInPdf = v === true)
                                "
                            />
                            <label
                                for="embed-esignature-pdf"
                                class="cursor-pointer text-xs leading-snug text-foreground"
                            >
                                Embed e-signature on the last page of each PDF
                                you mark received
                            </label>
                        </div>
                        <template v-if="embedEsignatureInPdf">
                            <div class="space-y-1.5">
                                <p class="text-xs font-medium text-foreground">
                                    Your signature
                                </p>
                                <p
                                    v-if="hasAccountEsignature"
                                    class="text-xs text-muted-foreground"
                                >
                                    Your saved account signature is used for
                                    this action. Draw below to replace it and
                                    update your account. For each PDF you mark
                                    received, it is also embedded on the last
                                    page of that file.
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    Sign below once to save it to your account;
                                    the next time you can confirm with your
                                    saved signature only. For each PDF you mark
                                    received now, your signature is also
                                    embedded on the last page of that file.
                                </p>
                            </div>
                            <div
                                v-if="
                                    hasAccountEsignature && accountEsignatureUrl
                                "
                                class="mb-2 rounded-md border border-border bg-muted/30 p-2"
                            >
                                <p
                                    class="mb-1 text-[10px] font-medium text-muted-foreground"
                                >
                                    Current saved signature
                                </p>
                                <img
                                    :src="accountEsignatureUrl"
                                    alt="Saved signature"
                                    class="max-h-16 max-w-full object-contain dark:invert-0"
                                />
                            </div>
                            <EsignaturePad
                                ref="esignPad"
                                @change="(empty) => (signatureEmpty = empty)"
                            />
                            <p
                                v-if="
                                    typeof page.props.errors?.signature ===
                                    'string'
                                "
                                class="text-xs text-destructive"
                            >
                                {{ page.props.errors.signature }}
                            </p>
                        </template>
                        <p v-else class="text-xs text-muted-foreground">
                            PDF attachments will not be modified. You can
                            confirm receipt without signing.
                        </p>
                        <Button
                            type="button"
                            class="w-full gap-1.5 bg-orange-500 text-white hover:bg-orange-600 sm:w-auto"
                            :disabled="
                                confirming ||
                                (embedEsignatureInPdf &&
                                    !hasAccountEsignature &&
                                    signatureEmpty)
                            "
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
                    <History class="h-4 w-4 text-orange-600" />
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
                            class="absolute top-1.5 -left-[21px] flex h-2.5 w-2.5 rounded-full border-2 border-background bg-orange-500 ring-1 ring-border"
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
                                            typeof entry.meta
                                                .forwarded_from_id === 'string'
                                        "
                                        class="block text-teal-800 dark:text-teal-300/90"
                                    >
                                        Created by forwarding
                                        <span
                                            v-if="
                                                typeof entry.meta
                                                    .forwarded_from_purpose ===
                                                'string'
                                            "
                                        >
                                            from “{{
                                                String(
                                                    entry.meta
                                                        .forwarded_from_purpose,
                                                )
                                            }}”
                                        </span>
                                    </span>
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

        <Dialog v-model:open="documentActivityModalOpen">
            <DialogContent
                class="flex max-h-[min(90vh,52rem)] w-full max-w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-4xl"
            >
                <DialogHeader
                    class="shrink-0 space-y-1.5 border-b border-border px-6 py-4 pr-12 text-left"
                >
                    <DialogTitle class="text-lg">Document activity</DialogTitle>
                    <DialogDescription>
                        Per-document timeline: checklist changes, and where this
                        line was forwarded.
                    </DialogDescription>
                </DialogHeader>
                <div
                    class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-4"
                >
                    <div
                        v-for="item in transmission.items"
                        :key="item.id"
                        class="border-b border-border/80 pb-5 last:mb-0 last:border-b-0 last:pb-0"
                    >
                        <h3 class="text-sm font-semibold text-foreground">
                            {{ item.label }}
                        </h3>
                        <p
                            v-if="item.received_at"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            Checklist: received
                            <time
                                class="tabular-nums"
                                :datetime="item.received_at"
                            >
                                {{ formatHistoryWhen(item.received_at) }}
                            </time>
                        </p>
                        <ul
                            v-if="(item.activities ?? []).length"
                            class="mt-3 space-y-2.5"
                        >
                            <li
                                v-for="act in item.activities"
                                :key="act.id"
                                class="flex flex-col gap-1 rounded-lg border border-teal-200/60 bg-teal-50/50 px-3 py-2.5 text-sm sm:flex-row sm:items-start sm:justify-between sm:gap-4 dark:border-teal-900/40 dark:bg-teal-950/25"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-foreground">
                                        {{ itemActivityHeading(act.event) }}
                                    </p>
                                    <p
                                        v-if="itemActivityDescription(act)"
                                        class="mt-0.5 text-sm text-muted-foreground"
                                    >
                                        {{ itemActivityDescription(act) }}
                                    </p>
                                </div>
                                <div
                                    class="shrink-0 text-xs text-muted-foreground sm:text-right"
                                >
                                    <span
                                        class="font-medium text-foreground/80"
                                        >{{ act.actor?.name ?? '—' }}</span
                                    >
                                    <br class="sm:hidden" />
                                    <time
                                        class="mt-0.5 inline tabular-nums sm:mt-0 sm:ml-2"
                                        :datetime="act.created_at"
                                    >
                                        {{ formatHistoryWhen(act.created_at) }}
                                    </time>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-muted-foreground">
                            No activity recorded for this line yet.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

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
