<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Clock,
    DatabaseBackup,
    Download,
    RotateCcw,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BackupController from '@/actions/App/Http/Controllers/Admin/BackupController';
import FormSelect from '@/components/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

type BackupRow = {
    filename: string;
    size: number;
    size_label: string;
    created_at: string;
    created_at_formatted: string;
};

type ScheduleFrequency = {
    value: string;
    label: string;
    description: string;
};

type ScheduleData = {
    enabled: boolean;
    frequency: string;
    last_ran_at: string | null;
    last_ran_at_formatted: string | null;
    next_run_label: string;
    next_run_at_formatted: string | null;
    frequencies: ScheduleFrequency[];
};

const props = defineProps<{
    backups: BackupRow[];
    max_upload_megabytes: number;
    retention: number;
    schedule: ScheduleData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Backup & Restore', href: admin.backups.index.url() },
        ],
    },
});

const { confirm } = useConfirm();
const fileInput = ref<HTMLInputElement | null>(null);
const restoreOpen = ref(false);
const restoreTarget = ref<BackupRow | 'upload'>('upload');

const createForm = useForm({ backup: '' });
const deleteForm = useForm({ backup: '' });
const scheduleForm = useForm({
    enabled: props.schedule.enabled,
    frequency: props.schedule.frequency,
});

watch(
    () => props.schedule,
    (schedule) => {
        scheduleForm.enabled = schedule.enabled;
        scheduleForm.frequency = schedule.frequency;
        scheduleForm.clearErrors();
    },
    { deep: true },
);

const selectedFrequency = computed(
    () =>
        props.schedule.frequencies.find(
            (frequency) => frequency.value === scheduleForm.frequency,
        ) ?? props.schedule.frequencies[0],
);

function saveSchedule() {
    scheduleForm.put(BackupController.updateSchedule.url(), {
        preserveScroll: true,
    });
}
const restoreForm = useForm({
    password: '',
    confirmation: false,
    file: null as File | null,
    backup: '',
});

function createBackup() {
    createForm.post(BackupController.store.url(), { preserveScroll: true });
}

async function deleteBackup(backup: BackupRow) {
    const ok = await confirm(
        `Delete ${backup.filename}? This cannot be undone.`,
        {
            title: 'Delete backup',
            confirmLabel: 'Delete',
        },
    );

    if (!ok) {
        return;
    }

    deleteForm.delete(BackupController.destroy.url(backup.filename), {
        preserveScroll: true,
    });
}

function openRestore(target: BackupRow | 'upload') {
    restoreTarget.value = target;
    restoreForm.password = '';
    restoreForm.confirmation = false;
    restoreForm.file = null;
    restoreForm.clearErrors();

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    restoreOpen.value = true;
}

function onRestoreFile(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    restoreForm.file = file;
}

function submitRestore() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            restoreOpen.value = false;
        },
    };

    if (restoreTarget.value === 'upload') {
        restoreForm
            .transform((data) => ({
                ...data,
                confirmation: data.confirmation ? 1 : 0,
            }))
            .post(BackupController.restoreUpload.url(), {
                ...options,
                forceFormData: true,
            });

        return;
    }

    restoreForm
        .transform((data) => ({
            password: data.password,
            confirmation: data.confirmation ? 1 : 0,
        }))
        .post(
            BackupController.restore.url(restoreTarget.value.filename),
            options,
        );
}

const restoreTitle = computed(() =>
    restoreTarget.value === 'upload'
        ? 'Restore from uploaded file'
        : `Restore ${restoreTarget.value.filename}`,
);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Backup & Restore" />

        <div
            class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
        >
            <div class="mb-6 flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30"
                >
                    <DatabaseBackup
                        class="h-5 w-5 text-orange-600 dark:text-orange-400"
                    />
                </div>
                <div>
                    <h1 class="text-lg font-semibold tracking-tight sm:text-xl">
                        Backup &amp; Restore
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Download a full copy of the database and uploaded files,
                        or replace the current system from an archive. Only
                        administrators can do this. The last
                        {{ retention }} archives are kept automatically.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section class="rounded-lg border border-border p-4">
                    <h2
                        class="mb-2 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Create backup
                    </h2>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Packs research papers, users, settings, and stored files
                        into a downloadable zip. Automatic archives also appear
                        in the list below.
                    </p>
                    <InputError :message="createForm.errors.backup" />
                    <Button
                        class="min-h-11 w-full sm:w-auto"
                        :disabled="createForm.processing"
                        @click="createBackup"
                    >
                        {{
                            createForm.processing
                                ? 'Creating backup…'
                                : 'Create backup now'
                        }}
                    </Button>
                </section>

                <section class="rounded-lg border border-border p-4">
                    <h2
                        class="mb-2 border-l-4 border-orange-500 pl-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Restore from file
                    </h2>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Upload a previously downloaded UMRIC backup (max
                        {{ max_upload_megabytes }} MB). This replaces all
                        current data.
                    </p>
                    <Button
                        variant="outline"
                        class="min-h-11 w-full sm:w-auto"
                        @click="openRestore('upload')"
                    >
                        <Upload class="h-4 w-4" />
                        Restore from file
                    </Button>
                </section>
            </div>

            <section class="mt-4 rounded-lg border border-border p-4">
                <div class="mb-4 flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30"
                    >
                        <Clock
                            class="h-4 w-4 text-orange-600 dark:text-orange-400"
                        />
                    </div>
                    <div>
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Automatic backups
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            The server creates archives on a schedule. Download
                            them from the list below whenever you need a copy.
                        </p>
                    </div>
                </div>

                <form class="space-y-4" @submit.prevent="saveSchedule">
                    <div class="flex items-start gap-3 rounded-lg border p-4">
                        <Checkbox
                            id="schedule_enabled"
                            :model-value="scheduleForm.enabled"
                            class="mt-0.5"
                            @update:model-value="
                                (value) =>
                                    (scheduleForm.enabled = value === true)
                            "
                        />
                        <div class="space-y-1">
                            <Label
                                for="schedule_enabled"
                                class="cursor-pointer"
                            >
                                Enable automatic backups
                            </Label>
                            <p class="text-xs text-muted-foreground">
                                Turn this off if you only want manual backups.
                            </p>
                        </div>
                    </div>
                    <InputError :message="scheduleForm.errors.enabled" />

                    <div class="max-w-md space-y-2">
                        <Label for="schedule_frequency">How often</Label>
                        <FormSelect
                            id="schedule_frequency"
                            v-model="scheduleForm.frequency"
                            :disabled="!scheduleForm.enabled"
                            class="min-h-11 text-base md:text-sm"
                        >
                            <option
                                v-for="frequency in props.schedule.frequencies"
                                :key="frequency.value"
                                :value="frequency.value"
                            >
                                {{ frequency.label }}
                            </option>
                        </FormSelect>
                        <p class="text-xs text-muted-foreground">
                            {{ selectedFrequency.description }}
                        </p>
                        <InputError :message="scheduleForm.errors.frequency" />
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ props.schedule.next_run_label }}
                        <span v-if="props.schedule.last_ran_at_formatted">
                            Last automatic backup:
                            {{ props.schedule.last_ran_at_formatted }}.
                        </span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Archives stay here until you download them. Automatic
                        backups require the server scheduler to be running.
                    </p>

                    <Button
                        type="submit"
                        class="min-h-11 w-full sm:w-auto"
                        :disabled="scheduleForm.processing"
                    >
                        {{
                            scheduleForm.processing
                                ? 'Saving…'
                                : 'Save schedule'
                        }}
                    </Button>
                </form>
            </section>
        </div>

        <section
            class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
        >
            <h2 class="text-base font-semibold tracking-tight">
                Stored backups
            </h2>
            <p class="mt-1 mb-4 text-sm text-muted-foreground">
                Download an archive to keep a copy off-server, or restore it
                here.
            </p>

            <InputError :message="deleteForm.errors.backup" />

            <div
                v-if="props.backups.length === 0"
                class="rounded-lg border border-dashed border-border px-4 py-10 text-center text-sm text-muted-foreground"
            >
                No backups yet. Create one to get started.
            </div>

            <ul v-else class="divide-y divide-border rounded-lg border">
                <li
                    v-for="backup in props.backups"
                    :key="backup.filename"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ backup.filename }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ backup.created_at_formatted }} ·
                            {{ backup.size_label }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            class="min-h-11"
                            as="a"
                            :href="
                                BackupController.download.url(backup.filename)
                            "
                        >
                            <Download class="h-4 w-4" />
                            Download
                        </Button>
                        <Button
                            variant="outline"
                            class="min-h-11"
                            @click="openRestore(backup)"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Restore
                        </Button>
                        <Button
                            variant="destructive"
                            class="min-h-11"
                            :disabled="deleteForm.processing"
                            @click="deleteBackup(backup)"
                        >
                            <Trash2 class="h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </li>
            </ul>
        </section>

        <Dialog v-model:open="restoreOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ restoreTitle }}</DialogTitle>
                    <DialogDescription>
                        This replaces users, research records, settings, and
                        uploaded files with the backup contents.
                    </DialogDescription>
                </DialogHeader>

                <Alert variant="destructive">
                    <RotateCcw class="size-4" />
                    <AlertTitle>This cannot be undone</AlertTitle>
                    <AlertDescription>
                        Create a fresh backup first if you may need the current
                        data. You will be asked for your password to continue.
                    </AlertDescription>
                </Alert>

                <form class="space-y-4" @submit.prevent="submitRestore">
                    <div v-if="restoreTarget === 'upload'" class="space-y-2">
                        <Label for="backup_file">Backup file</Label>
                        <input
                            id="backup_file"
                            ref="fileInput"
                            type="file"
                            accept=".zip,application/zip"
                            class="block w-full text-base file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-2 file:text-sm file:font-medium md:text-sm"
                            @change="onRestoreFile"
                        />
                        <InputError :message="restoreForm.errors.file" />
                    </div>

                    <div class="space-y-2">
                        <Label for="restore_password">Your password</Label>
                        <PasswordInput
                            id="restore_password"
                            v-model="restoreForm.password"
                            autocomplete="current-password"
                            class="text-base md:text-sm"
                        />
                        <InputError :message="restoreForm.errors.password" />
                    </div>

                    <div class="flex items-start gap-3 rounded-lg border p-3">
                        <Checkbox
                            id="restore_confirmation"
                            :model-value="restoreForm.confirmation"
                            class="mt-0.5"
                            @update:model-value="
                                (value) =>
                                    (restoreForm.confirmation = value === true)
                            "
                        />
                        <Label
                            for="restore_confirmation"
                            class="cursor-pointer text-sm leading-5"
                        >
                            I understand this will replace all current system
                            data.
                        </Label>
                    </div>
                    <InputError :message="restoreForm.errors.confirmation" />
                    <InputError :message="restoreForm.errors.backup" />

                    <DialogFooter class="gap-2 sm:gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11"
                            @click="restoreOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            class="min-h-11"
                            :disabled="restoreForm.processing"
                        >
                            {{
                                restoreForm.processing
                                    ? 'Restoring…'
                                    : 'Restore now'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
