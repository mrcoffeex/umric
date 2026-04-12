<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import {
    Ban, ChevronLeft, ChevronRight, Check, Search, Shield,
    ShieldCheck, Trash2, User as UserIcon, X,
} from 'lucide-vue-next'
import { onMounted, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import admin from '@/routes/admin'

interface UserRow {
    id: number
    name: string
    email: string
    role: string | null
    avatar_url: string | null
    department: string | null
    program: string | null
    created_at: string
    approved_at: string | null
    status: 'active' | 'pending' | 'blocked'
}

interface Paginator {
    data: UserRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    users: Paginator
    filters: { search?: string; role?: string }
}>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Users', href: admin.users.index() },
        ],
    },
})

// Role config
const roleColors: Record<string, string> = {
    admin:   'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400',
    staff:   'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400',
    faculty: 'bg-teal-100 dark:bg-teal-950/40 text-teal-700 dark:text-teal-400',
    student: 'bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400',
}

// Filters
const search = ref(props.filters.search ?? '')
const roleFilter = ref(props.filters.role ?? '')
let debounce: ReturnType<typeof setTimeout>

const isMounted = ref(false)
onMounted(() => { isMounted.value = true })

watch([search, roleFilter], () => {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        router.get(
            admin.users.index(),
            { search: search.value || undefined, role: roleFilter.value || undefined },
            { preserveState: true, replace: true },
        )
    }, 350)
})

// Role change modal
const showRole = ref(false)
const roleTarget = ref<UserRow | null>(null)
const roleForm = useForm({ role: '' })

function openRole(u: UserRow) {
    roleTarget.value = u
    roleForm.role = u.role ?? ''
    showRole.value = true
}

function submitRole() {
    if (!roleTarget.value) return
    roleForm.patch(admin.users.update.url(roleTarget.value.id), {
        onSuccess: () => { showRole.value = false },
    })
}

// Block toggle
const showBlock = ref(false)
const blockTarget = ref<UserRow | null>(null)

function openBlock(u: UserRow) {
    blockTarget.value = u
    showBlock.value = true
}

function doBlock() {
    if (!blockTarget.value) return
    useForm({}).post(admin.users.block.url(blockTarget.value.id), {
        onSuccess: () => { showBlock.value = false; router.reload() },
    })
}

// Delete
const showDelete = ref(false)
const deleting = ref<UserRow | null>(null)

function openDelete(u: UserRow) {
    deleting.value = u
    showDelete.value = true
}

function confirmDelete() {
    if (!deleting.value) return
    useForm({}).delete(admin.users.destroy.url(deleting.value.id), {
        onSuccess: () => { showDelete.value = false },
    })
}

// Initials
function initials(name: string) {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

// Approve/Reject confirmation modal
const showApprove = ref(false)
const showReject = ref(false)
const actioning = ref<UserRow | null>(null)

function confirmApprove(u: UserRow) {
    actioning.value = u
    showApprove.value = true
}

function confirmReject(u: UserRow) {
    actioning.value = u
    showReject.value = true
}

function doApprove() {
    if (!actioning.value) return
    useForm({}).post(admin.users.approve.url(actioning.value.id), {
        onSuccess: () => { showApprove.value = false; router.reload() },
    })
}

function doReject() {
    if (!actioning.value) return
    useForm({}).delete(admin.users.destroy.url(actioning.value.id), {
        onSuccess: () => { showReject.value = false; router.reload() },
    })
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">Users</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">Manage user accounts, roles, and affiliations.</p>
            </div>
            <div class="text-sm text-muted-foreground">
                {{ users.total }} user{{ users.total !== 1 ? 's' : '' }}
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-white dark:bg-sidebar border border-slate-200 dark:border-sidebar-border text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400"
                />
            </div>
            <select
                v-model="roleFilter"
                class="px-4 py-2.5 rounded-xl text-sm bg-white dark:bg-sidebar border border-slate-200 dark:border-sidebar-border text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400"
            >
                <option value="">All roles</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="faculty">Faculty</option>
                <option value="student">Student</option>
            </select>
        </div>

        <!-- Table -->
        <div class="rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar overflow-hidden">
            <div v-if="users.data.length === 0" class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                    <UserIcon class="w-7 h-7 text-slate-400" />
                </div>
                <p class="font-semibold text-slate-700 dark:text-slate-300">No users found</p>
                <p class="text-sm text-muted-foreground mt-1">Try adjusting your search or filter.</p>
            </div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-sidebar-border/70 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-5 py-3">User</th>
                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 hidden sm:table-cell">Role</th>
                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 hidden md:table-cell">Status</th>
                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 hidden lg:table-cell">Affiliation</th>
                        <th class="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 hidden xl:table-cell">Joined</th>
                        <th class="px-4 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/50">
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                    >
                        <!-- User -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden bg-gradient-to-br from-orange-400 to-teal-400 flex items-center justify-center">
                                    <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover" />
                                    <span v-else class="text-[11px] font-bold text-white">{{ initials(user.name) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-slate-100 truncate">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <!-- Role -->
                        <td class="px-4 py-3.5 hidden sm:table-cell">
                            <span
                                v-if="user.role"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold capitalize"
                                :class="roleColors[user.role] ?? 'bg-slate-100 text-slate-600'"
                            >
                                <Shield class="w-2.5 h-2.5" />
                                {{ user.role }}
                            </span>
                            <span v-else class="text-xs text-muted-foreground">–</span>
                        </td>
                        <!-- Status -->
                        <td class="px-4 py-3.5 hidden md:table-cell">
                            <span v-if="user.status === 'blocked'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400">
                                Blocked
                            </span>
                            <span v-else-if="user.status === 'pending'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                                Pending
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400">
                                Active
                            </span>
                        </td>
                        <!-- Affiliation -->
                        <td class="px-4 py-3.5 hidden lg:table-cell">
                            <div v-if="user.department || user.program">
                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate max-w-48">{{ user.department }}</p>
                                <p class="text-[11px] text-muted-foreground truncate max-w-48">{{ user.program }}</p>
                            </div>
                            <span v-else class="text-xs text-muted-foreground">–</span>
                        </td>
                        <!-- Joined -->
                        <td class="px-4 py-3.5 hidden xl:table-cell text-xs text-muted-foreground">{{ user.created_at }}</td>
                        <!-- Actions -->
                        <td class="px-4 py-3.5">
                            <!-- Unapproved faculty: approve/reject -->
                            <div v-if="user.role === 'faculty' && !user.approved_at" class="flex items-center justify-end gap-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 px-2 text-xs border-green-200 dark:border-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/20"
                                    @click="confirmApprove(user)"
                                >
                                    <Check class="w-3 h-3" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 px-2 text-xs border-red-200 dark:border-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20"
                                    @click="confirmReject(user)"
                                >
                                    <X class="w-3 h-3" />
                                </Button>
                            </div>
                            <!-- Approved or other roles -->
                            <div v-else class="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                                    title="Change role"
                                    @click="openRole(user)"
                                >
                                    <ShieldCheck class="w-3.5 h-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    :class="user.status === 'blocked'
                                        ? 'h-8 w-8 text-green-600 dark:text-green-400 hover:text-green-700'
                                        : 'h-8 w-8 text-muted-foreground hover:text-amber-600'"
                                    :title="user.status === 'blocked' ? 'Unblock user' : 'Block user'"
                                    @click="openBlock(user)"
                                >
                                    <Ban v-if="user.status !== 'blocked'" class="w-3.5 h-3.5" />
                                    <ShieldCheck v-else class="w-3.5 h-3.5" />
                                </Button>
                                <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-red-500" @click="openDelete(user)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="flex items-center justify-between text-sm">
            <p class="text-muted-foreground">
                Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
            </p>
            <div class="flex items-center gap-1">
                <template v-for="link in users.links" :key="link.label">
                    <button
                        v-if="link.label === '&laquo; Previous'"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        class="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button
                        v-else-if="link.label === 'Next &raquo;'"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        class="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <button
                        v-else
                        @click="link.url && router.get(link.url)"
                        :class="[
                            'min-w-[2rem] px-2 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            link.active
                                ? 'bg-orange-500 text-white'
                                : 'text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800',
                        ]"
                        :disabled="!link.url"
                    >{{ link.label }}</button>
                </template>
            </div>
        </div>
    </div>

    <!-- Role Modal -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="showRole" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl">
                <div class="flex items-center justify-between px-6 py-5 border-b border-sidebar-border/70">
                    <div>
                        <h3 class="font-black text-slate-900 dark:text-white">Change Role</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ roleTarget?.name }}</p>
                    </div>
                    <button @click="showRole = false" class="text-muted-foreground hover:text-foreground">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitRole" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Role <span class="text-orange-500">*</span></label>
                        <select v-model="roleForm.role" required
                            class="w-full px-4 py-2.5 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400">
                            <option value="">Select role…</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                            <option value="faculty">Faculty</option>
                            <option value="student">Student</option>
                        </select>
                        <p v-if="roleForm.errors.role" class="text-xs text-red-500 mt-1">{{ roleForm.errors.role }}</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="button" variant="outline" class="flex-1" @click="showRole = false">Cancel</Button>
                        <Button type="submit" :disabled="roleForm.processing"
                            class="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white border-0 hover:from-orange-600 hover:to-orange-700">
                            {{ roleForm.processing ? 'Saving…' : 'Update' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Block Confirm -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="showBlock" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl p-6">
                <div
                    class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                    :class="blockTarget?.status === 'blocked' ? 'bg-green-50 dark:bg-green-950/30' : 'bg-amber-50 dark:bg-amber-950/30'"
                >
                    <Ban v-if="blockTarget?.status !== 'blocked'" class="w-6 h-6 text-amber-500" />
                    <ShieldCheck v-else class="w-6 h-6 text-green-500" />
                </div>
                <h3 class="font-black text-slate-900 dark:text-white mb-1">
                    {{ blockTarget?.status === 'blocked' ? 'Unblock user?' : 'Block user?' }}
                </h3>
                <p class="text-sm text-muted-foreground mb-6">
                    <strong class="text-slate-700 dark:text-slate-300">{{ blockTarget?.name }}</strong>
                    <template v-if="blockTarget?.status === 'blocked'"> will be unblocked and regain access to UMRIC.</template>
                    <template v-else> will be blocked and immediately logged out.</template>
                </p>
                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" @click="showBlock = false">Cancel</Button>
                    <Button
                        type="button"
                        class="flex-1 text-white border-0"
                        :class="blockTarget?.status === 'blocked' ? 'bg-green-500 hover:bg-green-600' : 'bg-amber-500 hover:bg-amber-600'"
                        @click="doBlock"
                    >
                        {{ blockTarget?.status === 'blocked' ? 'Unblock' : 'Block' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Delete Confirm -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl p-6">
                <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center mb-4">
                    <Trash2 class="w-6 h-6 text-red-500" />
                </div>
                <h3 class="font-black text-slate-900 dark:text-white mb-1">Delete user?</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    <strong class="text-slate-700 dark:text-slate-300">{{ deleting?.name }}</strong> will be permanently removed. This cannot be undone.
                </p>
                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" @click="showDelete = false">Cancel</Button>
                    <Button type="button" class="flex-1 bg-red-500 hover:bg-red-600 text-white border-0" @click="confirmDelete">Delete</Button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Approve Confirm -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="showApprove" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl p-6">
                <div class="w-12 h-12 rounded-2xl bg-green-50 dark:bg-green-950/30 flex items-center justify-center mb-4">
                    <Check class="w-6 h-6 text-green-500" />
                </div>
                <h3 class="font-black text-slate-900 dark:text-white mb-1">Approve faculty?</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    <strong class="text-slate-700 dark:text-slate-300">{{ actioning?.name }}</strong> will be granted access to UMRIC as a faculty member.
                </p>
                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" @click="showApprove = false">Cancel</Button>
                    <Button type="button" class="flex-1 bg-green-500 hover:bg-green-600 text-white border-0" @click="doApprove">Approve</Button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Reject Confirm -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="showReject" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-sidebar-border/70 shadow-2xl p-6">
                <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center mb-4">
                    <Trash2 class="w-6 h-6 text-red-500" />
                </div>
                <h3 class="font-black text-slate-900 dark:text-white mb-1">Reject registration?</h3>
                <p class="text-sm text-muted-foreground mb-6">
                    <strong class="text-slate-700 dark:text-slate-300">{{ actioning?.name }}</strong>'s account will be permanently deleted. This cannot be undone.
                </p>
                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" @click="showReject = false">Cancel</Button>
                    <Button type="button" class="flex-1 bg-red-500 hover:bg-red-600 text-white border-0" @click="doReject">Reject & Delete</Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
