<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, FileText, Megaphone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface AppNotification {
    id: string;
    category: string;
    title: string;
    body: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
}

const page = usePage();
const open = ref(false);
const loading = ref(false);
const items = ref<AppNotification[]>([]);
const localUnread = ref<number | null>(null);

const unreadCount = computed(
    () => localUnread.value ?? page.props.notifications?.unreadCount ?? 0,
);

const badgeLabel = computed(() =>
    unreadCount.value > 9 ? '9+' : String(unreadCount.value),
);

function xsrfToken(): string {
    const raw = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return raw ? decodeURIComponent(raw) : '';
}

function formatTime(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const diffMs = Date.now() - date.getTime();
    const minutes = Math.floor(diffMs / 60000);

    if (minutes < 1) {
        return 'Just now';
    }

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

async function loadFeed(): Promise<void> {
    loading.value = true;

    try {
        const response = await fetch('/notifications/feed', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            unread_count: number;
            notifications: AppNotification[];
        };

        items.value = data.notifications;
        localUnread.value = data.unread_count;
    } finally {
        loading.value = false;
    }
}

function onOpenChange(next: boolean): void {
    open.value = next;

    if (next) {
        void loadFeed();
    }
}

async function markAllRead(): Promise<void> {
    await fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
    });

    items.value = items.value.map((item) => ({
        ...item,
        read_at: item.read_at ?? new Date().toISOString(),
    }));
    localUnread.value = 0;
    router.reload({ only: ['notifications'] });
}

function openNotification(item: AppNotification): void {
    void fetch(`/notifications/${item.id}/read`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
    }).finally(() => {
        if (
            !item.read_at &&
            localUnread.value !== null &&
            localUnread.value > 0
        ) {
            localUnread.value -= 1;
        }

        item.read_at = item.read_at ?? new Date().toISOString();

        if (item.url) {
            router.visit(item.url);
        }
    });
}
</script>

<template>
    <DropdownMenu :open="open" @update:open="onOpenChange">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="relative inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground"
                aria-label="Notifications"
            >
                <Bell class="size-4" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold text-white"
                >
                    {{ badgeLabel }}
                </span>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[22rem] p-0 sm:w-96">
            <div class="flex items-center justify-between px-3 py-2.5">
                <DropdownMenuLabel class="p-0 text-sm font-semibold">
                    Notifications
                </DropdownMenuLabel>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-medium text-orange-600 hover:text-orange-700 dark:text-orange-400"
                    @click="markAllRead"
                >
                    <CheckCheck class="size-3.5" />
                    Mark all read
                </button>
            </div>

            <DropdownMenuSeparator class="m-0" />

            <div class="max-h-80 overflow-y-auto">
                <div
                    v-if="loading && items.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Loading…
                </div>

                <div
                    v-else-if="items.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    You're all caught up.
                </div>

                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="flex w-full items-start gap-3 px-3 py-3 text-left transition hover:bg-muted/60"
                    :class="
                        !item.read_at
                            ? 'bg-orange-50/50 dark:bg-orange-950/20'
                            : ''
                    "
                    @click="openNotification(item)"
                >
                    <span
                        :class="[
                            'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg',
                            item.category === 'research'
                                ? 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400'
                                : 'bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
                        ]"
                    >
                        <FileText
                            v-if="item.category === 'research'"
                            class="size-3.5"
                        />
                        <Megaphone v-else class="size-3.5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-sm font-semibold text-foreground">
                                {{ item.title }}
                            </span>
                            <span
                                v-if="!item.read_at"
                                class="mt-1 size-2 shrink-0 rounded-full bg-orange-500"
                            />
                        </span>
                        <span
                            class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
                        >
                            {{ item.body }}
                        </span>
                        <span
                            class="mt-1 block text-[11px] text-muted-foreground/80"
                        >
                            {{ formatTime(item.created_at) }}
                        </span>
                    </span>
                </button>
            </div>

            <DropdownMenuSeparator class="m-0" />

            <div class="p-2">
                <Link
                    href="/notifications"
                    class="flex min-h-9 w-full items-center justify-center rounded-lg text-xs font-semibold text-orange-600 transition hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-950/30"
                    @click="open = false"
                >
                    View all notifications
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
