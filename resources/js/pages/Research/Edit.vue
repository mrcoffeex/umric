<template>
    <div class="flex h-full flex-1 justify-center bg-gray-50/50 p-4 md:p-6">
        <div class="w-full max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="index()">
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <ArrowLeft class="h-3.5 w-3.5" />
                            Back
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Edit Title Proposal
                    </h1>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section
                    class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                >
                    <h2
                        class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                    >
                        📋 Basic Information
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <Label class="mb-1.5 block text-sm font-medium text-gray-700">
                                Title
                            </Label>
                            <Input
                                v-model="form.title"
                                required
                                placeholder="Enter title proposal"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-100"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <Label class="mb-1.5 block text-sm font-medium text-gray-700">
                                Abstract
                            </Label>
                            <textarea
                                v-model="form.abstract"
                                rows="5"
                                required
                                placeholder="Write a concise abstract for this proposal"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            />
                            <p v-if="form.errors.abstract" class="mt-1 text-xs text-red-500">
                                {{ form.errors.abstract }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <section
                        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            🎯 SDG
                        </h2>
                        <Label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Sustainable Development Goal (SDG)
                        </Label>
                        <select
                            v-model="form.sdg_id"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
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
                        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            📌 Agenda
                        </h2>
                        <Label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Research Agenda
                        </Label>
                        <select
                            v-model="form.agenda_id"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
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
                    class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                >
                    <h2
                        class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                    >
                        👥 Proponents / Researchers
                    </h2>

                    <div class="space-y-2">
                        <div
                            v-for="(_, index) in form.proponents"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <Input
                                v-model="form.proponents[index]"
                                placeholder="Enter proponent name"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-100"
                            />
                            <button
                                type="button"
                                :disabled="form.proponents.length === 1"
                                class="h-10 w-10 text-gray-400 transition hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="removeProponent(index)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <p v-if="form.errors.proponents" class="mt-1 text-xs text-red-500">
                        {{ form.errors.proponents }}
                    </p>
                    <button
                        type="button"
                        class="mt-3 flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                        @click="addProponent"
                    >
                        <Plus class="h-4 w-4" />
                        Add Proponent
                    </button>
                </section>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <section
                        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            🏷️ Keywords
                        </h2>
                        <Label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Keywords
                        </Label>
                        <Input
                            v-model="form.keywords"
                            placeholder="e.g. machine learning, AI, neural networks"
                            class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-100"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Separate each keyword with commas.
                        </p>
                        <p v-if="form.errors.keywords" class="mt-1 text-xs text-red-500">
                            {{ form.errors.keywords }}
                        </p>
                    </section>

                    <section
                        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                    >
                        <h2
                            class="mb-4 border-l-4 border-indigo-500 pl-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            📄 File Upload
                        </h2>
                        <Label class="mb-1.5 block text-sm font-medium text-gray-700">
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
                            class="cursor-pointer rounded-xl border-2 border-dashed border-gray-200 p-8 text-center transition-colors hover:border-indigo-400 hover:bg-indigo-50/30"
                            @click="openFilePicker"
                            @dragover.prevent
                            @drop.prevent="handleFileDrop"
                        >
                            <Upload class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                            <p class="text-sm font-medium text-gray-700">
                                {{
                                    fileName ||
                                    'Drag and drop a new file here, or click to browse'
                                }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">PDF, DOC, DOCX</p>
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
                        class="rounded-lg bg-indigo-600 px-6 py-2 font-medium text-white transition hover:bg-indigo-700"
                    >
                        {{ form.processing ? 'Updating…' : 'Update Proposal' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, update } from '@/routes/papers';

interface Props {
    paper: {
        id: number;
        title: string;
        abstract?: string | null;
        sdg_id?: number | null;
        agenda_id?: number | null;
        proponents?: string[] | null;
        keywords?: string | null;
    };
    sdgs: Array<{ id: number; name: string; number?: number; color?: string }>;
    agendas: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();
const paper = props.paper;
const { sdgs, agendas } = props;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Research Papers', href: index() },
            { title: 'Edit Title Proposal' },
        ],
    },
});

const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref('');

const form = useForm({
    title: paper.title,
    abstract: paper.abstract ?? '',
    sdg_id: paper.sdg_id ?? '',
    agenda_id: paper.agenda_id ?? '',
    proponents: paper.proponents?.length ? paper.proponents : [''],
    keywords: paper.keywords ?? '',
    file: null as File | null,
});

function formatSdg(sdg: { id: number; name: string; number?: number }) {
    return sdg.number ? `SDG ${sdg.number}: ${sdg.name}` : sdg.name;
}

function addProponent() {
    form.proponents.push('');
}

function removeProponent(index: number) {
    if (form.proponents.length > 1) {
        form.proponents.splice(index, 1);
    }
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
    form.put(update.url(paper.id), {
        forceFormData: true,
    });
}
</script>
