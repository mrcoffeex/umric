<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Megaphone, Pencil, Pin, Plus, Trash2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import admin from '@/routes/admin';

interface AnnouncementItem {
    id: number;
    title: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    is_pinned: boolean;
    is_active: boolean;
    target_roles: string[] | null;
    published_at: string | null;
    expires_at: string | null;
    created_by_name: string;
    created_at: string;
}

defineProps<{
    announcements: AnnouncementItem[];
}>();

const typeColors: Record<AnnouncementItem['type'], string> = {
    info: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    success:
        'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300',
    warning:
        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    danger: 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300',
};

const showForm = ref(false);
const editing = ref<AnnouncementItem | null>(null);
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    is_pinned: false,
    is_active: true,
    target_roles: [] as string[],
    published_at: '',
    expires_at: '',
});

function toDateInput(value: string | null): string {
    return value ? value.slice(0, 10) : '';
}

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.type = 'info';
    form.is_active = true;
    form.is_pinned = false;
    form.target_roles = [];
    form.clearErrors();
    showForm.value = true;
}

function openEdit(item: AnnouncementItem): void {
    editing.value = item;
    form.title = item.title;
    form.content = item.content;
    form.type = item.type;
    form.is_pinned = item.is_pinned;
    form.is_active = item.is_active;
    form.target_roles = item.target_roles ?? [];
    form.published_at = toDateInput(item.published_at);
    form.expires_at = toDateInput(item.expires_at);
    form.clearErrors();
    showForm.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(admin.announcements.update.url({ announcement: editing.value.id }), {
            onSuccess: () => {
                showForm.value = false;
            },
        });

        return;
    }

    form.post(admin.announcements.store.url(), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}

function remove(item: AnnouncementItem): void {
    if (!confirm(`Delete announcement: ${item.title}?`)) {
        return;
    }

    useForm({}).delete(admin.announcements.destroy.url({ announcement: item.id }));
}

function toggleActive(item: AnnouncementItem): void {
    useForm({
        title: item.title,
        content: item.content,
        type: item.type,
        is_pinned: item.is_pinned,
        is_active: !item.is_active,
        target_roles: item.target_roles ?? [],
        published_at: toDateInput(item.published_at),
        expires_at: toDateInput(item.expires_at),
    }).put(admin.announcements.update.url({ announcement: item.id }));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Announcements', href: admin.announcements.index() },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    Announcements
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Publish, pin, and target announcements by role.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" /> New Announcement
            </button>
        </div>

        <div v-if="announcements.length === 0" class="rounded-2xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30">
                <Megaphone class="h-6 w-6 text-orange-500" />
            </div>
            <p class="font-semibold text-foreground">No announcements yet</p>
            <p class="mt-1 text-sm text-muted-foreground">Create your first announcement for users.</p>
        </div>

        <div v-else class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="item in announcements"
                :key="item.id"
                class="rounded-2xl border border-border bg-card p-5"
            >
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="line-clamp-1 text-base font-bold text-foreground">
                        {{ item.title }}
                    </p>
                    <div class="flex items-center gap-1.5">
                        <span :class="['rounded-full px-2 py-0.5 text-[11px] font-semibold', typeColors[item.type]]">
                            {{ item.type }}
                        </span>
                        <span v-if="item.is_pinned" class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 text-[11px] font-semibold text-orange-700 dark:bg-orange-950/30 dark:text-orange-300">
                            <Pin class="h-3 w-3" /> Pinned
                        </span>
                    </div>
                </div>

                <p class="line-clamp-3 text-sm text-muted-foreground">
                    {{ item.content }}
                </p>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span
                        v-for="role in item.target_roles ?? []"
                        :key="role"
                        class="rounded-full border border-border px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                    >
                        {{ role }}
                    </span>
                    <span v-if="(item.target_roles ?? []).length === 0" class="text-xs text-muted-foreground">All roles</span>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2 border-t border-border pt-3 text-xs text-muted-foreground">
                    <div>
                        <p>Published: {{ formatDate(item.published_at) }}</p>
                        <p>Expires: {{ formatDate(item.expires_at) }}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="toggleActive(item)"
                            :class="[
                                'rounded-lg px-2.5 py-1 font-semibold',
                                item.is_active
                                    ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </button>
                        <button type="button" class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground" @click="openEdit(item)">
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button type="button" class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground" @click="remove(item)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <div v-if="isMounted && showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-2xl rounded-2xl border border-border bg-card p-6 shadow-lg">
            <h2 class="text-base font-bold text-foreground">
                    {{ editing ? 'Edit Announcement' : 'New Announcement' }}
            </h2>

            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Title</label>
                    <input v-model="form.title" type="text" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" required />
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Content</label>
                    <textarea v-model="form.content" rows="4" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" required />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Type</label>
                        <select v-model="form.type" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm text-foreground">
                            <input v-model="form.is_pinned" type="checkbox" /> Pinned
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm text-foreground">
                            <input v-model="form.is_active" type="checkbox" /> Active
                        </label>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Target Roles</p>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                        <label v-for="role in ['student', 'faculty', 'staff', 'admin']" :key="role" class="inline-flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm text-foreground">
                            <input v-model="form.target_roles" type="checkbox" :value="role" />
                            {{ role }}
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Published At</label>
                        <input v-model="form.published_at" type="date" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Expires At</label>
                        <input v-model="form.expires_at" type="date" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground" @click="showForm = false">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" :disabled="form.processing">
                        {{ editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
