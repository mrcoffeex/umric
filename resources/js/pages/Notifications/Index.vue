<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, FileText, Megaphone } from 'lucide-vue-next';
import { computed } from 'vue';

interface AppNotification {
    id: string;
    category: string;
    title: string;
    body: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
}

interface PaginatedNotifications {
    data: AppNotification[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    notifications: PaginatedNotifications;
}>();

const hasUnread = computed(() =>
    props.notifications.data.some((item) => !item.read_at),
);

function formatTime(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function markAllRead(): void {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}

function openNotification(item: AppNotification): void {
    router.post(
        `/notifications/${item.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (item.url) {
                    router.visit(item.url);
                }
            },
        },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notifications', href: '/notifications' }],
    },
});
</script>

<template>
    <Head title="Notifications" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4"
            >
                <div class="flex items-center gap-2.5">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/40"
                    >
                        <Bell class="h-4 w-4 text-orange-500" />
                    </span>
                    <div>
                        <h1 class="text-lg font-bold text-foreground">
                            Notifications
                        </h1>
                        <p class="text-xs text-muted-foreground">
                            Announcements and research paper updates
                        </p>
                    </div>
                </div>
                <button
                    v-if="hasUnread"
                    type="button"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border px-3 text-xs font-semibold text-foreground transition hover:bg-muted"
                    @click="markAllRead"
                >
                    <CheckCheck class="size-3.5" />
                    Mark all as read
                </button>
            </div>

            <div v-if="notifications.data.length === 0" class="px-5 py-12 text-center">
                <p class="text-sm font-semibold text-foreground">No notifications yet</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    You'll be notified about new announcements and paper changes.
                </p>
            </div>

            <ul v-else class="divide-y divide-border">
                <li v-for="item in notifications.data" :key="item.id">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 px-5 py-4 text-left transition hover:bg-muted/50"
                        :class="!item.read_at ? 'bg-orange-50/40 dark:bg-orange-950/15' : ''"
                        @click="openNotification(item)"
                    >
                        <span
                            :class="[
                                'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl',
                                item.category === 'research'
                                    ? 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400'
                                    : 'bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
                            ]"
                        >
                            <FileText
                                v-if="item.category === 'research'"
                                class="size-4"
                            />
                            <Megaphone v-else class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-3">
                                <span class="text-sm font-semibold text-foreground">
                                    {{ item.title }}
                                </span>
                                <span
                                    v-if="!item.read_at"
                                    class="mt-1.5 size-2 shrink-0 rounded-full bg-orange-500"
                                />
                            </span>
                            <span class="mt-1 block text-sm text-muted-foreground">
                                {{ item.body }}
                            </span>
                            <span class="mt-2 block text-xs text-muted-foreground/80">
                                {{ formatTime(item.created_at) }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>

            <div
                v-if="notifications.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-2 border-t border-border p-4"
            >
                <template v-for="link in notifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="
                            link.active
                                ? 'bg-orange-500 text-white'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3 py-1.5 text-xs text-muted-foreground/50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </section>
    </div>
</template>
