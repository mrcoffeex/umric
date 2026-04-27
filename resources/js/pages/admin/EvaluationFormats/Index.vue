<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Layers,
    Pencil,
    Plus,
    SlidersHorizontal,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

type PdfForm = {
    enabled: boolean;
    logo_path: string;
    header_institution: string;
    form_title: string;
    form_subtitle: string;
    show_research_title: boolean;
    show_proponents: boolean;
    show_instruction: boolean;
    instruction_text: string;
    show_rating_scale: boolean;
    show_sdg: boolean;
    show_pass_fail: boolean;
    show_signature_block: boolean;
    passing_score: number;
    document: {
        code: string;
        revision: string;
        effectivity: string;
    };
};

type FormatRow = {
    id: string;
    name: string;
    evaluation_type: 'scoring' | 'checklist';
    use_weights: boolean;
    can_change_type: boolean;
    can_change_use_weights: boolean;
    panel_defenses_count: number;
    total_max: number;
    target_total: number;
    is_ready: boolean;
    pdf_settings: PdfForm;
    pdf_logo_url: string | null;
};

type EvaluationFormatForm = {
    name: string;
    evaluation_type: 'scoring' | 'checklist';
    use_weights: boolean;
    pdf_settings: PdfForm;
    pdf_logo: File | null;
};

function defaultPdfSettings(): PdfForm {
    return {
        enabled: true,
        logo_path: '',
        header_institution: 'RESEARCH AND INNOVATION CENTER',
        form_title: 'THESIS OUTLINE DEFENSE EVALUATION FORM',
        form_subtitle: '(For Students)',
        show_research_title: true,
        show_proponents: true,
        show_instruction: true,
        instruction_text:
            'Enclosed are the criteria-indicators and rating scale to help you evaluate the various components of this thesis work. Please read the criteria carefully and place the corresponding score of each item on the blank provided for. Multiply the score with its corresponding weight and enter it on the last column. Add the points in the last column to compute the final grade. Space is provided for your comments at the bottom of the page. Thank you.',
        show_rating_scale: true,
        show_sdg: true,
        show_pass_fail: true,
        show_signature_block: true,
        passing_score: 85,
        document: {
            code: 'F-13100-012',
            revision: '3',
            effectivity: 'January 8, 2026',
        },
    };
}

function mergePdfSettings(mergedFromServer: PdfForm): PdfForm {
    const d = defaultPdfSettings();

    return {
        ...d,
        ...mergedFromServer,
        document: {
            ...d.document,
            ...mergedFromServer.document,
        },
    };
}

defineProps<{
    formats: FormatRow[];
}>();

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            {
                title: 'Evaluation formats',
                href: admin.evaluationFormats.index.url(),
            },
        ],
    },
});

const showCreate = ref(false);
const editing = ref<FormatRow | null>(null);
const logoInput = ref<HTMLInputElement | null>(null);
const localLogoPreview = ref<string | null>(null);

const form = useForm({
    name: '',
    evaluation_type: 'scoring' as 'scoring' | 'checklist',
    use_weights: false,
    pdf_settings: defaultPdfSettings(),
    pdf_logo: null as File | null,
});

const logoPreviewUrl = computed(() => {
    if (localLogoPreview.value) {
        return localLogoPreview.value;
    }

    return editing.value?.pdf_logo_url ?? null;
});

function openNew() {
    editing.value = null;
    // Clear any transform left over from a previous edit `.patch()` — otherwise
    // `post()` would still use it and drop fields (e.g. evaluation_type).
    form.transform((data) => data);
    form.reset();
    form.evaluation_type = 'scoring';
    form.use_weights = false;
    form.pdf_settings = defaultPdfSettings();
    form.pdf_logo = null;
    resetLogoPreview();
    form.clearErrors();
    showCreate.value = true;
}

function openEdit(f: FormatRow) {
    editing.value = f;
    form.name = f.name;
    form.evaluation_type = f.evaluation_type;
    form.use_weights = f.evaluation_type === 'scoring' && f.use_weights;
    form.pdf_settings = mergePdfSettings(f.pdf_settings);
    form.pdf_logo = null;
    resetLogoPreview();
    form.clearErrors();
    showCreate.value = true;
}

function resetLogoPreview() {
    if (localLogoPreview.value) {
        URL.revokeObjectURL(localLogoPreview.value);
        localLogoPreview.value = null;
    }

    if (logoInput.value) {
        logoInput.value.value = '';
    }
}

function pickLogo() {
    logoInput.value?.click();
}

function onLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.pdf_logo = file;

    if (localLogoPreview.value) {
        URL.revokeObjectURL(localLogoPreview.value);
        localLogoPreview.value = null;
    }

    if (file) {
        localLogoPreview.value = URL.createObjectURL(file);
    }
}

function submit() {
    if (editing.value) {
        const editPayload = (data: EvaluationFormatForm) => {
            const payload: Record<string, unknown> = { name: data.name };
            const type = data.evaluation_type as string;

            if (editing.value?.can_change_type) {
                payload.evaluation_type = type;
            }

            if (editing.value?.can_change_use_weights) {
                payload.use_weights =
                    type === 'scoring' && Boolean(data.use_weights);
            }

            if (editing.value?.can_change_type && type === 'checklist') {
                payload.use_weights = false;
            }

            payload.pdf_settings = data.pdf_settings;

            if (data.pdf_logo instanceof File) {
                payload.pdf_logo = data.pdf_logo;
            }

            return payload;
        };

        const url = admin.evaluationFormats.update.url({
            evaluation_format: editing.value.id,
        });
        const options = {
            onSuccess: () => {
                showCreate.value = false;
                resetLogoPreview();
            },
        };

        if (form.pdf_logo instanceof File) {
            form.transform((data) => ({
                ...editPayload(data),
                _method: 'PATCH' as const,
            })).post(url, { ...options, forceFormData: true });
        } else {
            form.transform(editPayload);
            form.patch(url, options);
        }
    } else {
        form.transform((data) => data);
        form.post(admin.evaluationFormats.store.url(), {
            forceFormData: form.pdf_logo instanceof File,
            onSuccess: () => {
                showCreate.value = false;
                form.reset();
                form.evaluation_type = 'scoring';
                form.use_weights = false;
                form.pdf_settings = defaultPdfSettings();
                form.pdf_logo = null;
                resetLogoPreview();
            },
        });
    }
}

async function deleteRow(f: FormatRow) {
    const ok = await confirm(`Delete “${f.name}” and all of its criteria?`, {
        title: 'Delete format',
        confirmLabel: 'Delete',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(
        admin.evaluationFormats.destroy.url({ evaluation_format: f.id }),
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Evaluation formats" />

        <div
            class="grid gap-6"
            :class="
                showCreate
                    ? 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]'
                    : 'grid-cols-1'
            "
        >
            <div class="space-y-4">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            Evaluation formats
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            <strong>Scoring</strong>: unweighted (each line
                            1–100, total = average) or weighted (each line has a
                            %, total 100%). <strong>Checklist</strong> is yes /
                            no per item.
                        </p>
                    </div>
                    <Button class="shrink-0" @click="openNew">
                        <Plus class="mr-1.5 h-4 w-4" />
                        New format
                    </Button>
                </div>

                <div
                    v-if="!formats.length"
                    class="rounded-2xl border border-dashed border-border bg-muted/20 p-12 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-border bg-muted/50 text-primary"
                    >
                        <Layers class="h-7 w-7" />
                    </div>
                    <p class="font-medium text-foreground">No formats yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Add a format, then add criteria.
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="f in formats"
                        :key="f.id"
                        class="flex items-start gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40"
                        >
                            <SlidersHorizontal class="h-5 w-5 text-primary" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-foreground">
                                {{ f.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <span class="font-medium text-foreground/90">{{
                                    f.evaluation_type === 'checklist'
                                        ? 'Checklist (yes / no)'
                                        : f.use_weights
                                          ? 'Scoring (weighted)'
                                          : 'Scoring (average)'
                                }}</span>
                                ·
                                <template
                                    v-if="f.evaluation_type === 'checklist'"
                                >
                                    {{ f.total_max }} item(s)
                                    <span
                                        v-if="f.is_ready"
                                        class="ml-1 text-emerald-600 dark:text-emerald-400"
                                    >
                                        (ready)
                                    </span>
                                    <span
                                        v-else
                                        class="ml-1 text-amber-700 dark:text-amber-500"
                                    >
                                        (add at least one item)
                                    </span>
                                </template>
                                <template v-else-if="f.use_weights">
                                    weight {{ f.total_max }} /
                                    {{ f.target_total }}
                                    <span
                                        v-if="f.is_ready"
                                        class="ml-1 text-emerald-600 dark:text-emerald-400"
                                    >
                                        (ready)
                                    </span>
                                    <span
                                        v-else
                                        class="ml-1 text-amber-700 dark:text-amber-500"
                                    >
                                        (complete weights to 100)
                                    </span>
                                </template>
                                <template v-else>
                                    {{ f.total_max / 100 }} criterion{{
                                        f.total_max / 100 === 1 ? '' : 'a'
                                    }}
                                    (1–100 each)
                                    <span
                                        v-if="f.is_ready"
                                        class="ml-1 text-emerald-600 dark:text-emerald-400"
                                    >
                                        (ready)
                                    </span>
                                    <span
                                        v-else
                                        class="ml-1 text-amber-700 dark:text-amber-500"
                                    >
                                        (add at least one)
                                    </span>
                                </template>
                                — {{ f.panel_defenses_count }} schedule(s)
                            </p>
                        </div>
                        <div class="flex flex-wrap justify-end gap-1">
                            <Button variant="secondary" size="sm" as-child>
                                <Link
                                    :href="
                                        admin.evaluationFormats.criteria.url({
                                            evaluationFormat: f.id,
                                        })
                                    "
                                >
                                    <SlidersHorizontal
                                        class="mr-1 h-3.5 w-3.5"
                                    />
                                    {{
                                        f.evaluation_type === 'checklist'
                                            ? 'Items'
                                            : 'Criteria'
                                    }}
                                </Link>
                            </Button>
                            <Button
                                variant="secondary"
                                size="icon"
                                @click="openEdit(f)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="secondary"
                                size="icon"
                                @click="deleteRow(f)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showCreate"
                class="h-fit space-y-4 rounded-2xl border border-border bg-card p-5 shadow-sm"
            >
                <h2 class="text-sm font-semibold text-foreground">
                    {{ editing ? 'Edit' : 'New' }} format
                </h2>
                <form class="space-y-3" @submit.prevent="submit">
                    <div>
                        <Label for="f-name">Name</Label>
                        <Input
                            id="f-name"
                            v-model="form.name"
                            class="mt-1.5 bg-background"
                            required
                        />
                        <InputError
                            v-if="form.errors.name"
                            :message="form.errors.name"
                        />
                    </div>
                    <div>
                        <Label for="f-type">Evaluation type</Label>
                        <FormSelect
                            id="f-type"
                            v-model="form.evaluation_type"
                            class="mt-1.5"
                            :disabled="!!editing && !editing.can_change_type"
                        >
                            <option value="scoring">Scoring</option>
                            <option value="checklist">
                                Checklist (yes / no)
                            </option>
                        </FormSelect>
                        <p
                            v-if="editing && !editing.can_change_type"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Type is fixed after criteria exist or the format is
                            used on a schedule.
                        </p>
                        <InputError
                            v-if="form.errors.evaluation_type"
                            :message="String(form.errors.evaluation_type)"
                        />
                    </div>
                    <div
                        v-if="form.evaluation_type === 'scoring'"
                        class="flex items-start gap-2.5 rounded-lg border border-border bg-muted/30 p-3"
                    >
                        <Checkbox
                            id="f-uw"
                            :model-value="form.use_weights"
                            :disabled="
                                !!editing && !editing.can_change_use_weights
                            "
                            class="mt-0.5"
                            @update:model-value="
                                (v) => (form.use_weights = v === true)
                            "
                        />
                        <div class="min-w-0">
                            <Label for="f-uw" class="cursor-pointer text-sm"
                                >Use percentage weights per criterion</Label
                            >
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                On: each line has a % weight (totals 100). Off:
                                each line is scored 1–100; the panel score is
                                the average.
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="
                            editing &&
                            form.evaluation_type === 'scoring' &&
                            !editing.can_change_use_weights
                        "
                        class="text-xs text-muted-foreground"
                    >
                        Weight / average mode is fixed after criteria exist.
                    </p>
                    <p v-if="!editing" class="text-xs text-muted-foreground">
                        After creating, open Criteria or Items to build the
                        rubric.
                    </p>
                    <div
                        v-if="showCreate"
                        class="space-y-3 rounded-lg border border-border bg-muted/25 p-3"
                    >
                        <h3
                            class="text-xs font-semibold tracking-wide text-foreground uppercase"
                        >
                            PDF form
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Used when a panelist downloads the official
                            evaluation form (titles, document code, passing
                            score, and rating table).
                        </p>
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="pdf-en"
                                :model-value="form.pdf_settings.enabled"
                                class="mt-0.5"
                                @update:model-value="
                                    (v) =>
                                        (form.pdf_settings.enabled = v === true)
                                "
                            />
                            <Label for="pdf-en" class="cursor-pointer text-sm"
                                >Enable PDF download for this format</Label
                            >
                        </div>
                        <div>
                            <Label for="pdf-title">Form title</Label>
                            <Input
                                id="pdf-title"
                                v-model="form.pdf_settings.form_title"
                                class="mt-1.5 bg-background"
                                :disabled="!form.pdf_settings.enabled"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>PDF logo</Label>
                            <p class="text-xs text-muted-foreground">
                                Upload a PNG, JPEG, WebP, or SVG logo for this
                                evaluation format. Max 2 MB. If blank, the
                                default RIC logo is used.
                            </p>
                            <div
                                v-if="logoPreviewUrl"
                                class="flex max-w-xs items-center gap-3 rounded-lg border border-border bg-background p-3"
                            >
                                <img
                                    :src="logoPreviewUrl"
                                    alt=""
                                    class="h-12 w-12 object-contain"
                                />
                                <span class="text-xs text-muted-foreground">
                                    {{
                                        form.pdf_logo
                                            ? form.pdf_logo.name
                                            : 'Current logo'
                                    }}
                                </span>
                            </div>
                            <input
                                ref="logoInput"
                                type="file"
                                class="sr-only"
                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                :disabled="!form.pdf_settings.enabled"
                                @change="onLogoChange"
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                :disabled="!form.pdf_settings.enabled"
                                @click="pickLogo"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                {{
                                    logoPreviewUrl
                                        ? 'Replace logo'
                                        : 'Upload logo'
                                }}
                            </Button>
                            <InputError :message="form.errors.pdf_logo" />
                        </div>
                        <div>
                            <Label for="pdf-inst"
                                >Institution / center line</Label
                            >
                            <Input
                                id="pdf-inst"
                                v-model="form.pdf_settings.header_institution"
                                class="mt-1.5 bg-background"
                                :disabled="!form.pdf_settings.enabled"
                            />
                        </div>
                        <div>
                            <Label for="pdf-sub">Subtitle</Label>
                            <Input
                                id="pdf-sub"
                                v-model="form.pdf_settings.form_subtitle"
                                class="mt-1.5 bg-background"
                                :disabled="!form.pdf_settings.enabled"
                            />
                        </div>
                        <div>
                            <Label for="pdf-instruction"
                                >Instruction text (edit anytime; shown on the
                                PDF when “Show instruction” is on)</Label
                            >
                            <textarea
                                id="pdf-instruction"
                                v-model="form.pdf_settings.instruction_text"
                                rows="3"
                                class="mt-1.5 flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!form.pdf_settings.enabled"
                            />
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div>
                                <Label for="pdf-code">Form code</Label>
                                <Input
                                    id="pdf-code"
                                    v-model="form.pdf_settings.document.code"
                                    class="mt-1.5 bg-background"
                                    :disabled="!form.pdf_settings.enabled"
                                />
                            </div>
                            <div>
                                <Label for="pdf-rev">Revision</Label>
                                <Input
                                    id="pdf-rev"
                                    v-model="
                                        form.pdf_settings.document.revision
                                    "
                                    class="mt-1.5 bg-background"
                                    :disabled="!form.pdf_settings.enabled"
                                />
                            </div>
                            <div>
                                <Label for="pdf-eff">Effectivity</Label>
                                <Input
                                    id="pdf-eff"
                                    v-model="
                                        form.pdf_settings.document.effectivity
                                    "
                                    class="mt-1.5 bg-background"
                                    :disabled="!form.pdf_settings.enabled"
                                />
                            </div>
                        </div>
                        <div>
                            <Label for="pdf-pass"
                                >Passing score (scoring)</Label
                            >
                            <Input
                                id="pdf-pass"
                                v-model.number="form.pdf_settings.passing_score"
                                type="number"
                                min="0"
                                max="100"
                                class="mt-1.5 bg-background"
                                :disabled="!form.pdf_settings.enabled"
                            />
                        </div>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap"
                        >
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-rt"
                                    :model-value="
                                        form.pdf_settings.show_research_title
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_research_title =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-rt" class="text-sm"
                                    >Show research title</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-pr"
                                    :model-value="
                                        form.pdf_settings.show_proponents
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_proponents =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-pr" class="text-sm"
                                    >Show proponent(s)</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-in"
                                    :model-value="
                                        form.pdf_settings.show_instruction
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_instruction =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-in" class="text-sm"
                                    >Show instruction</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-rs"
                                    :model-value="
                                        form.pdf_settings.show_rating_scale
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_rating_scale =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-rs" class="text-sm"
                                    >Show rating scale (Score, Equivalent,
                                    Narrative description)</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-sdg"
                                    :model-value="form.pdf_settings.show_sdg"
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_sdg =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-sdg" class="text-sm"
                                    >Show SDG(s) for the research paper</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-pf"
                                    :model-value="
                                        form.pdf_settings.show_pass_fail
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_pass_fail =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-pf" class="text-sm"
                                    >Show passed / failed</Label
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="pdf-sig"
                                    :model-value="
                                        form.pdf_settings.show_signature_block
                                    "
                                    :disabled="!form.pdf_settings.enabled"
                                    @update:model-value="
                                        (v) =>
                                            (form.pdf_settings.show_signature_block =
                                                v === true)
                                    "
                                />
                                <Label for="pdf-sig" class="text-sm"
                                    >Show signature & date</Label
                                >
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="showCreate = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="form.processing"
                            >Save</Button
                        >
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
