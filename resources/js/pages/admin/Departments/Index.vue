<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import {
    Building2, ChevronDown, ChevronRight, Pencil, Plus,
    ToggleLeft, ToggleRight, Trash2, X,
} from 'lucide-vue-next'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import admin from '@/routes/admin'

interface Program {
    id: number
    name: string
    code: string
    description: string | null
    is_active: boolean
}

interface Department {
    id: number
    name: string
    code: string
    description: string | null
    is_active: boolean
    programs_count: number
    programs: Program[]
}

defineProps<{ departments: Department[] }>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Departments & Programs', href: admin.departments.index() },
        ],
    },
})

// Expanded rows — use array, not Set, for Vue reactivity
const expanded = ref<number[]>([])
function isExpanded(id: number) { return expanded.value.includes(id) }
function toggleExpand(id: number) {
    const idx = expanded.value.indexOf(id)
    if (idx === -1) expanded.value.push(id)
    else expanded.value.splice(idx, 1)
}

// Department form
const showDeptForm = ref(false)
const editingDept = ref<Department | null>(null)
const deptForm = useForm({ name: '', code: '', description: '', is_active: true })

function openNewDept() {
    editingDept.value = null
    deptForm.reset()
    showDeptForm.value = true
}

function openEditDept(dept: Department) {
    editingDept.value = dept
    deptForm.name = dept.name
    deptForm.code = dept.code
    deptForm.description = dept.description ?? ''
    deptForm.is_active = dept.is_active
    showDeptForm.value = true
}

function submitDept() {
    if (editingDept.value) {
        deptForm.patch(admin.departments.update(editingDept.value.id), {
            onSuccess: () => { showDeptForm.value = false },
        })
    } else {
        deptForm.post(admin.departments.store(), {
            onSuccess: () => { showDeptForm.value = false; deptForm.reset() },
        })
    }
}

function deleteDept(dept: Department) {
    if (!confirm(`Delete "${dept.name}"? All its programs will also be removed.`)) return
    useForm({}).delete(admin.departments.destroy(dept.id))
}

// Program form
const showProgForm = ref(false)
const editingProg = ref<Program | null>(null)
const progDeptId = ref<number>(0)
const progForm = useForm({ department_id: 0, name: '', code: '', description: '', is_active: true })

function openNewProg(dept: Department) {
    editingProg.value = null
    progDeptId.value = dept.id
    progForm.reset()
    progForm.department_id = dept.id
    showProgForm.value = true
}

function openEditProg(prog: Program, deptId: number) {
    editingProg.value = prog
    progDeptId.value = deptId
    progForm.department_id = deptId
    progForm.name = prog.name
    progForm.code = prog.code
    progForm.description = prog.description ?? ''
    progForm.is_active = prog.is_active
    showProgForm.value = true
}

function submitProg() {
    if (editingProg.value) {
        progForm.patch(admin.programs.update(editingProg.value.id), {
            onSuccess: () => { showProgForm.value = false },
        })
    } else {
        progForm.post(admin.programs.store(), {
            onSuccess: () => { showProgForm.value = false; progForm.reset() },
        })
    }
}

function deleteProg(prog: Program) {
    if (!confirm(`Delete program "${prog.name}"?`)) return
    useForm({}).delete(admin.programs.destroy(prog.id))
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">Departments & Programs</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">Manage college departments and their academic programs.</p>
            </div>
            <Button @click="openNewDept" class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700 border-0 shadow shadow-orange-500/25">
                <Plus class="w-4 h-4" />
                New Department
            </Button>
        </div>

        <!-- Department list -->
        <div v-if="departments.length === 0" class="rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 dark:bg-orange-950/30 flex items-center justify-center mx-auto mb-4">
                <Building2 class="w-7 h-7 text-orange-400" />
            </div>
            <p class="font-semibold text-slate-700 dark:text-slate-300">No departments yet</p>
            <p class="text-sm text-muted-foreground mt-1">Create your first department to get started.</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="dept in departments"
                :key="dept.id"
                class="rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar overflow-hidden"
            >
                <!-- Department row -->
                <div class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <button @click="toggleExpand(dept.id)" class="shrink-0 text-muted-foreground hover:text-foreground">
                        <ChevronRight v-if="!isExpanded(dept.id)" class="w-4 h-4 transition-transform" />
                        <ChevronDown v-else class="w-4 h-4" />
                    </button>
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-teal-500 flex items-center justify-center shrink-0">
                        <Building2 class="w-5 h-5 text-white" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900 dark:text-white truncate">{{ dept.name }}</span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-400">{{ dept.code }}</span>
                            <span v-if="!dept.is_active" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">Inactive</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5 truncate">
                            {{ dept.description || 'No description' }} · {{ dept.programs_count }} program{{ dept.programs_count !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-orange-500" @click="openEditDept(dept)">
                            <Pencil class="w-3.5 h-3.5" />
                        </Button>
                        <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-red-500" @click="deleteDept(dept)">
                            <Trash2 class="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>

                <!-- Programs (expanded) -->
                <div v-if="isExpanded(dept.id)" class="border-t border-sidebar-border/70 bg-slate-50/70 dark:bg-slate-800/30 px-5 py-3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Programs</span>
                        <Button variant="ghost" size="sm" class="h-7 text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 gap-1" @click="openNewProg(dept)">
                            <Plus class="w-3 h-3" />
                            Add Program
                        </Button>
                    </div>
                    <div v-if="dept.programs.length === 0" class="py-4 text-center text-xs text-muted-foreground">
                        No programs yet. Click "Add Program" to create one.
                    </div>
                    <div v-else class="space-y-1.5">
                        <div
                            v-for="prog in dept.programs"
                            :key="prog.id"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 bg-white dark:bg-slate-900 border border-sidebar-border/50"
                        >
                            <div class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center shrink-0">
                                <Building2 class="w-3.5 h-3.5 text-teal-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ prog.name }}</span>
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400">{{ prog.code }}</span>
                                </div>
                                <p v-if="prog.description" class="text-[11px] text-muted-foreground truncate mt-0.5">{{ prog.description }}</p>
                            </div>
                            <component :is="prog.is_active ? ToggleRight : ToggleLeft"
                                class="w-4 h-4 shrink-0"
                                :class="prog.is_active ? 'text-teal-500' : 'text-muted-foreground'"
                            />
                            <Button variant="ghost" size="icon" class="h-7 w-7 text-muted-foreground hover:text-orange-500 shrink-0" @click="openEditProg(prog, dept.id)">
                                <Pencil class="w-3 h-3" />
                            </Button>
                            <Button variant="ghost" size="icon" class="h-7 w-7 text-muted-foreground hover:text-red-500 shrink-0" @click="deleteProg(prog)">
                                <Trash2 class="w-3 h-3" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Modal -->
    <Teleport to="body">
        <div v-if="showDeptForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl">
                <div class="flex items-center justify-between px-6 py-5 border-b border-sidebar-border/70">
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">{{ editingDept ? 'Edit Department' : 'New Department' }}</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Fill in the department details below.</p>
                    </div>
                    <button @click="showDeptForm = false" class="text-muted-foreground hover:text-foreground">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitDept" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department Name <span class="text-orange-500">*</span></label>
                        <input v-model="deptForm.name" type="text" placeholder="e.g. College of Information Technology" required
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400" />
                        <p v-if="deptForm.errors.name" class="text-xs text-red-500 mt-1">{{ deptForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Code <span class="text-orange-500">*</span></label>
                        <input v-model="deptForm.code" type="text" placeholder="e.g. CIT" required maxlength="20"
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 uppercase" />
                        <p v-if="deptForm.errors.code" class="text-xs text-red-500 mt-1">{{ deptForm.errors.code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                        <textarea v-model="deptForm.description" rows="2" placeholder="Brief description..."
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 resize-none" />
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" v-model="deptForm.is_active" class="rounded accent-orange-500" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <div class="flex gap-3 pt-2">
                        <Button type="button" variant="outline" class="flex-1" @click="showDeptForm = false">Cancel</Button>
                        <Button type="submit" :disabled="deptForm.processing"
                            class="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700">
                            {{ deptForm.processing ? 'Saving…' : (editingDept ? 'Update' : 'Create') }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Program Modal -->
    <Teleport to="body">
        <div v-if="showProgForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl">
                <div class="flex items-center justify-between px-6 py-5 border-b border-sidebar-border/70">
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">{{ editingProg ? 'Edit Program' : 'New Program' }}</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Fill in the program details below.</p>
                    </div>
                    <button @click="showProgForm = false" class="text-muted-foreground hover:text-foreground">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitProg" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Program Name <span class="text-orange-500">*</span></label>
                        <input v-model="progForm.name" type="text" placeholder="e.g. Bachelor of Science in Information Technology" required
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-400" />
                        <p v-if="progForm.errors.name" class="text-xs text-red-500 mt-1">{{ progForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Code <span class="text-orange-500">*</span></label>
                        <input v-model="progForm.code" type="text" placeholder="e.g. BSIT" required maxlength="20"
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-400 uppercase" />
                        <p v-if="progForm.errors.code" class="text-xs text-red-500 mt-1">{{ progForm.errors.code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                        <textarea v-model="progForm.description" rows="2" placeholder="Brief description..."
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-400/50 focus:border-teal-400 resize-none" />
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" v-model="progForm.is_active" class="rounded accent-teal-500" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <div class="flex gap-3 pt-2">
                        <Button type="button" variant="outline" class="flex-1" @click="showProgForm = false">Cancel</Button>
                        <Button type="submit" :disabled="progForm.processing"
                            class="flex-1 bg-gradient-to-r from-teal-500 to-teal-600 text-white border-0 hover:from-teal-600 hover:to-teal-700">
                            {{ progForm.processing ? 'Saving…' : (editingProg ? 'Update' : 'Create') }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
