<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Building2,
    ChevronDown,
    ChevronRight,
    Pencil,
    Plus,
    ToggleLeft,
    ToggleRight,
    Trash2,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

interface Program {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
}

interface Department {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    programs_count: number;
    programs: Program[];
}

defineProps<{ departments: Department[] }>();

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            {
                title: 'Departments & Programs',
                href: admin.departments.index(),
            },
        ],
    },
});

// Expanded rows — use array, not Set, for Vue reactivity
const expanded = ref<number[]>([]);
function isExpanded(id: number) {
    return expanded.value.includes(id);
}
function toggleExpand(id: number) {
    const idx = expanded.value.indexOf(id);

    if (idx === -1) {
        expanded.value.push(id);
    } else {
        expanded.value.splice(idx, 1);
    }
}

// Department form
const showDeptForm = ref(false);
const editingDept = ref<Department | null>(null);
const deptForm = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

function openNewDept() {
    editingDept.value = null;
    deptForm.reset();
    showDeptForm.value = true;
}

function openEditDept(dept: Department) {
    editingDept.value = dept;
    deptForm.name = dept.name;
    deptForm.code = dept.code;
    deptForm.description = dept.description ?? '';
    deptForm.is_active = dept.is_active;
    showDeptForm.value = true;
}

function submitDept() {
    if (editingDept.value) {
        deptForm.patch(admin.departments.update.url(editingDept.value.id), {
            onSuccess: () => {
                showDeptForm.value = false;
            },
        });
    } else {
        deptForm.post(admin.departments.store.url(), {
            onSuccess: () => {
                showDeptForm.value = false;
                deptForm.reset();
            },
        });
    }
}

async function deleteDept(dept: Department) {
    const ok = await confirm(
        `Delete "${dept.name}"? All its programs will also be removed.`,
        { title: 'Delete Department', confirmLabel: 'Delete' },
    );

    if (!ok) {
        return;
    }

    useForm({}).delete(admin.departments.destroy.url(dept.id));
}

// Program form
const showProgForm = ref(false);
const editingProg = ref<Program | null>(null);
const progDeptId = ref<number>(0);
const progForm = useForm({
    department_id: 0,
    name: '',
    code: '',
    description: '',
    is_active: true,
});

function openNewProg(dept: Department) {
    editingProg.value = null;
    progDeptId.value = dept.id;
    progForm.reset();
    progForm.department_id = dept.id;
    showProgForm.value = true;
}

function openEditProg(prog: Program, deptId: number) {
    editingProg.value = prog;
    progDeptId.value = deptId;
    progForm.department_id = deptId;
    progForm.name = prog.name;
    progForm.code = prog.code;
    progForm.description = prog.description ?? '';
    progForm.is_active = prog.is_active;
    showProgForm.value = true;
}

function submitProg() {
    if (editingProg.value) {
        progForm.patch(admin.programs.update.url(editingProg.value.id), {
            onSuccess: () => {
                showProgForm.value = false;
            },
        });
    } else {
        progForm.post(admin.programs.store.url(), {
            onSuccess: () => {
                showProgForm.value = false;
                progForm.reset();
            },
        });
    }
}

async function deleteProg(prog: Program) {
    const ok = await confirm(`Delete program "${prog.name}"?`, {
        title: 'Delete Program',
        confirmLabel: 'Delete',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(admin.programs.destroy.url(prog.id));
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                    Departments & Programs
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Manage college departments and their academic programs.
                </p>
            </div>
            <Button
                @click="openNewDept"
                class="flex items-center gap-2 border-0 bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow shadow-orange-500/25 hover:from-orange-600 hover:to-orange-700"
            >
                <Plus class="h-4 w-4" />
                New Department
            </Button>
        </div>

        <!-- Department list -->
        <div
            v-if="departments.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-white p-12 text-center dark:bg-sidebar"
        >
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
            >
                <Building2 class="h-7 w-7 text-orange-400" />
            </div>
            <p class="font-semibold text-slate-700 dark:text-slate-300">
                No departments yet
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Create your first department to get started.
            </p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="dept in departments"
                :key="dept.id"
                class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar"
            >
                <!-- Department row -->
                <div
                    class="flex items-center gap-3 px-5 py-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                >
                    <button
                        @click="toggleExpand(dept.id)"
                        class="shrink-0 text-muted-foreground hover:text-foreground"
                    >
                        <ChevronRight
                            v-if="!isExpanded(dept.id)"
                            class="h-4 w-4 transition-transform"
                        />
                        <ChevronDown v-else class="h-4 w-4" />
                    </button>
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-teal-500"
                    >
                        <Building2 class="h-5 w-5 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="truncate font-bold text-slate-900 dark:text-white"
                                >{{ dept.name }}</span
                            >
                            <span
                                class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-950/50 dark:text-orange-400"
                                >{{ dept.code }}</span
                            >
                            <span
                                v-if="!dept.is_active"
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-slate-800"
                                >Inactive</span
                            >
                        </div>
                        <p
                            class="mt-0.5 truncate text-xs text-muted-foreground"
                        >
                            {{ dept.description || 'No description' }} ·
                            {{ dept.programs_count }} program{{
                                dept.programs_count !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                            @click="openEditDept(dept)"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-muted-foreground hover:text-red-500"
                            @click="deleteDept(dept)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>

                <!-- Programs (expanded) -->
                <div
                    v-if="isExpanded(dept.id)"
                    class="border-t border-sidebar-border/70 bg-slate-50/70 px-5 py-3 dark:bg-slate-800/30"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Programs</span
                        >
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 gap-1 text-xs text-teal-600 hover:text-teal-700 dark:text-teal-400"
                            @click="openNewProg(dept)"
                        >
                            <Plus class="h-3 w-3" />
                            Add Program
                        </Button>
                    </div>
                    <div
                        v-if="dept.programs.length === 0"
                        class="py-4 text-center text-xs text-muted-foreground"
                    >
                        No programs yet. Click "Add Program" to create one.
                    </div>
                    <div v-else class="space-y-1.5">
                        <div
                            v-for="prog in dept.programs"
                            :key="prog.id"
                            class="flex items-center gap-3 rounded-xl border border-sidebar-border/50 bg-white px-3 py-2.5 dark:bg-slate-900"
                        >
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950/30"
                            >
                                <Building2 class="h-3.5 w-3.5 text-teal-500" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200"
                                        >{{ prog.name }}</span
                                    >
                                    <span
                                        class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/50 dark:text-teal-400"
                                        >{{ prog.code }}</span
                                    >
                                </div>
                                <p
                                    v-if="prog.description"
                                    class="mt-0.5 truncate text-[11px] text-muted-foreground"
                                >
                                    {{ prog.description }}
                                </p>
                            </div>
                            <component
                                :is="prog.is_active ? ToggleRight : ToggleLeft"
                                class="h-4 w-4 shrink-0"
                                :class="
                                    prog.is_active
                                        ? 'text-teal-500'
                                        : 'text-muted-foreground'
                                "
                            />
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7 shrink-0 text-muted-foreground hover:text-orange-500"
                                @click="openEditProg(prog, dept.id)"
                            >
                                <Pencil class="h-3 w-3" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7 shrink-0 text-muted-foreground hover:text-red-500"
                                @click="deleteProg(prog)"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Modal -->
    <Teleport to="body">
        <div
            v-if="showDeptForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-sidebar-border/70 bg-white shadow-2xl dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 px-6 py-5"
                >
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">
                            {{
                                editingDept
                                    ? 'Edit Department'
                                    : 'New Department'
                            }}
                        </h3>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Fill in the department details below.
                        </p>
                    </div>
                    <button
                        @click="showDeptForm = false"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form @submit.prevent="submitDept" class="space-y-4 p-6">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Department Name
                            <span class="text-orange-500">*</span></label
                        >
                        <input
                            v-model="deptForm.name"
                            type="text"
                            placeholder="e.g. College of Information Technology"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <p
                            v-if="deptForm.errors.name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ deptForm.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Code <span class="text-orange-500">*</span></label
                        >
                        <input
                            v-model="deptForm.code"
                            type="text"
                            placeholder="e.g. CIT"
                            required
                            maxlength="20"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 uppercase focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <p
                            v-if="deptForm.errors.code"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ deptForm.errors.code }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Description</label
                        >
                        <textarea
                            v-model="deptForm.description"
                            rows="2"
                            placeholder="Brief description..."
                            class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input
                            type="checkbox"
                            v-model="deptForm.is_active"
                            class="rounded accent-orange-500"
                        />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                            >Active</span
                        >
                    </label>
                    <div class="flex gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="showDeptForm = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="deptForm.processing"
                            class="flex-1 border-0 bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700"
                        >
                            {{
                                deptForm.processing
                                    ? 'Saving…'
                                    : editingDept
                                      ? 'Update'
                                      : 'Create'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Program Modal -->
    <Teleport to="body">
        <div
            v-if="showProgForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-sidebar-border/70 bg-white shadow-2xl dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 px-6 py-5"
                >
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">
                            {{ editingProg ? 'Edit Program' : 'New Program' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Fill in the program details below.
                        </p>
                    </div>
                    <button
                        @click="showProgForm = false"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form @submit.prevent="submitProg" class="space-y-4 p-6">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Program Name
                            <span class="text-orange-500">*</span></label
                        >
                        <input
                            v-model="progForm.name"
                            type="text"
                            placeholder="e.g. Bachelor of Science in Information Technology"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-teal-400 focus:ring-2 focus:ring-teal-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <p
                            v-if="progForm.errors.name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ progForm.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Code <span class="text-orange-500">*</span></label
                        >
                        <input
                            v-model="progForm.code"
                            type="text"
                            placeholder="e.g. BSIT"
                            required
                            maxlength="20"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 uppercase focus:border-teal-400 focus:ring-2 focus:ring-teal-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <p
                            v-if="progForm.errors.code"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ progForm.errors.code }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >Description</label
                        >
                        <textarea
                            v-model="progForm.description"
                            rows="2"
                            placeholder="Brief description..."
                            class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-teal-400 focus:ring-2 focus:ring-teal-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input
                            type="checkbox"
                            v-model="progForm.is_active"
                            class="rounded accent-teal-500"
                        />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                            >Active</span
                        >
                    </label>
                    <div class="flex gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="showProgForm = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="progForm.processing"
                            class="flex-1 border-0 bg-gradient-to-r from-teal-500 to-teal-600 text-white hover:from-teal-600 hover:to-teal-700"
                        >
                            {{
                                progForm.processing
                                    ? 'Saving…'
                                    : editingProg
                                      ? 'Update'
                                      : 'Create'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
