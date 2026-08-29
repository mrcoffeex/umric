<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    Clock,
    DatabaseBackup,
    Edit,
    Laptop,
    Lock,
    MapPin,
    MessageSquare,
    Plus,
    Search,
    ShieldAlert,
    Trash2,
    Unlock,
    Users,
    Wifi,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import admin from '@/routes/admin';

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface ActivityLog {
    id: number | string;
    description: string;
    event: string | null;
    causer: string | null;
    causer_name: string | null;
    subject_type: string | null;
    subject_id: string | number | null;
    subject_name: string | null;
    properties: Record<string, unknown>;
    ip_address: string;
    browser: string;
    device: string;
    location: string;
    action_details: string;
    created_at: string;
}

interface Props {
    logs: Pagination<ActivityLog>;
    filters: {
        search: string;
        event: string;
        causer: string;
        risk: string;
        date_from: string;
        date_to: string;
    };
    options: {
        events: string[];
        causers: Array<{
            id: string;
            name: string | null;
            email: string | null;
        }>;
    };
    stats: {
        total_logs: number;
        today_logs: number;
        high_risk_logs: number;
        active_admins: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const selectedEvent = ref(props.filters.event ?? '');
const selectedCauser = ref(props.filters.causer ?? '');
const selectedRisk = ref(props.filters.risk ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            {
                title: 'Activity Logs',
                href: admin.activityLogs.index(),
            },
        ],
    },
});

const statCards = computed(() => [
    {
        label: 'Total Logs',
        value: props.stats.total_logs,
        icon: Clock,
        iconClass: 'text-orange-600 dark:text-orange-400',
        iconBg: 'bg-orange-100 dark:bg-orange-950/40',
    },
    {
        label: 'Today',
        value: props.stats.today_logs,
        icon: CheckCircle,
        iconClass: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-950/40',
    },
    {
        label: 'High Risk',
        value: props.stats.high_risk_logs,
        icon: ShieldAlert,
        iconClass: 'text-rose-600 dark:text-rose-400',
        iconBg: 'bg-rose-100 dark:bg-rose-950/40',
    },
    {
        label: 'Active Admins',
        value: props.stats.active_admins,
        icon: Users,
        iconClass: 'text-indigo-600 dark:text-indigo-400',
        iconBg: 'bg-indigo-100 dark:bg-indigo-950/40',
    },
]);

const getEventIcon = (event: string | null) => {
    return (
        {
            created: Plus,
            updated: Edit,
            deleted: Trash2,
            approved: CheckCircle,
            rejected: AlertCircle,
            blocked: Lock,
            unblocked: Unlock,
            assigned: MessageSquare,
            rated: CheckCircle,
            backed_up: DatabaseBackup,
        }[event ?? ''] || Clock
    );
};

const getEventColor = (event: string | null) => {
    return (
        {
            created: 'text-emerald-600 dark:text-emerald-400',
            updated: 'text-blue-600 dark:text-blue-400',
            deleted: 'text-rose-600 dark:text-rose-400',
            approved: 'text-emerald-600 dark:text-emerald-400',
            rejected: 'text-amber-600 dark:text-amber-400',
            blocked: 'text-rose-600 dark:text-rose-400',
            unblocked: 'text-emerald-600 dark:text-emerald-400',
            assigned: 'text-indigo-600 dark:text-indigo-400',
            rated: 'text-purple-600 dark:text-purple-400',
            backed_up: 'text-orange-600 dark:text-orange-400',
        }[event ?? ''] || 'text-gray-600 dark:text-gray-400'
    );
};

const formatTimeAgo = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days} day${days > 1 ? 's' : ''} ago`;
    }

    const months = Math.floor(days / 30);

    if (months < 12) {
        return `${months} month${months > 1 ? 's' : ''} ago`;
    }

    const years = Math.floor(months / 12);

    return `${years} year${years > 1 ? 's' : ''} ago`;
};

const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

function applySearch(): void {
    router.get(
        admin.activityLogs.index(),
        {
            search: search.value || undefined,
            event: selectedEvent.value || undefined,
            causer: selectedCauser.value || undefined,
            risk: selectedRisk.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function clearSearch(): void {
    search.value = '';
    selectedEvent.value = '';
    selectedCauser.value = '';
    selectedRisk.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applySearch();
}

function subjectTypeLabel(log: ActivityLog): string {
    if (!log.subject_type) {
        return 'System';
    }

    return log.subject_type.split('\\').pop() ?? log.subject_type;
}

function subjectLabel(log: ActivityLog): string {
    if (log.subject_name) {
        return log.subject_name;
    }

    if (!log.subject_type) {
        return 'No subject';
    }

    return log.subject_id
        ? `${subjectTypeLabel(log)} #${log.subject_id}`
        : subjectTypeLabel(log);
}
</script>

<template>
    <Head title="Activity Logs" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    System Logs
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Monitor admin actions with device, browser, IP, and location
                    context.
                </p>
            </div>
        </div>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in statCards"
                :key="card.label"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-2xl font-bold text-foreground">
                            {{ card.value }}
                        </p>
                    </div>
                    <div :class="['rounded-lg p-2', card.iconBg]">
                        <component
                            :is="card.icon"
                            :class="['h-4 w-4', card.iconClass]"
                        />
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-xl border border-border bg-card p-4">
            <form class="flex flex-col gap-3" @submit.prevent="applySearch">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by action, admin, event, IP, browser, location, payload..."
                        class="h-11 w-full rounded-lg border border-input bg-background pr-3 pl-9 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5"
                >
                    <select
                        v-model="selectedEvent"
                        class="h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    >
                        <option value="">All events</option>
                        <option
                            v-for="eventName in props.options.events"
                            :key="eventName"
                            :value="eventName"
                        >
                            {{ eventName }}
                        </option>
                    </select>
                    <select
                        v-model="selectedCauser"
                        class="h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    >
                        <option value="">All admins</option>
                        <option
                            v-for="causer in props.options.causers"
                            :key="causer.id"
                            :value="String(causer.id)"
                        >
                            {{
                                causer.name ||
                                causer.email ||
                                `User #${causer.id}`
                            }}
                        </option>
                    </select>
                    <select
                        v-model="selectedRisk"
                        class="h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    >
                        <option value="">All risk levels</option>
                        <option value="high">High risk</option>
                        <option value="normal">Normal</option>
                    </select>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                    <input
                        v-model="dateTo"
                        type="date"
                        class="h-11 rounded-lg border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-11 min-w-28 items-center justify-center rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white transition hover:bg-orange-700 active:opacity-90"
                    >
                        Apply Filters
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-11 min-w-24 items-center justify-center rounded-lg border border-input px-4 text-sm font-semibold text-foreground transition hover:bg-muted active:opacity-90"
                        @click="clearSearch"
                    >
                        Clear
                    </button>
                </div>
            </form>
        </section>

        <section
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Action
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Details
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Device & Network
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                When
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/80">
                        <tr
                            v-for="log in props.logs.data"
                            :key="log.id"
                            class="align-top transition hover:bg-muted/30"
                        >
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="getEventIcon(log.event)"
                                        :class="[
                                            'mt-0.5 h-5 w-5 shrink-0',
                                            getEventColor(log.event),
                                        ]"
                                    />
                                    <div class="min-w-0 space-y-1">
                                        <p
                                            class="font-semibold text-foreground"
                                        >
                                            {{ log.description }}
                                        </p>
                                        <div
                                            class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                        >
                                            <span
                                                class="rounded-full bg-muted px-2 py-0.5"
                                                >{{
                                                    log.event || 'event'
                                                }}</span
                                            >
                                            <span
                                                >by
                                                {{
                                                    log.causer_name || 'System'
                                                }}</span
                                            >
                                            <span>{{
                                                log.causer || 'N/A'
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="space-y-1.5 text-xs">
                                    <p class="font-medium text-foreground">
                                        {{ subjectLabel(log) }}
                                    </p>
                                    <p class="text-muted-foreground">
                                        {{ subjectTypeLabel(log) }}
                                    </p>
                                    <p
                                        class="max-w-xl break-words whitespace-pre-wrap text-muted-foreground"
                                    >
                                        {{
                                            log.action_details ||
                                            'No additional payload details.'
                                        }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="space-y-2 text-xs text-foreground">
                                    <div class="flex items-center gap-2">
                                        <Laptop
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                        <span
                                            >{{ log.device }} ·
                                            {{ log.browser }}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Wifi
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                        <span>{{ log.ip_address }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <MapPin
                                            class="h-3.5 w-3.5 text-muted-foreground"
                                        />
                                        <span>{{ log.location }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-medium text-foreground">
                                    {{ formatTimeAgo(log.created_at) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(log.created_at) }}
                                </p>
                            </td>
                        </tr>

                        <tr v-if="props.logs.data.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-16 text-center text-muted-foreground"
                            >
                                No logs found for the current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="props.logs.last_page > 1"
            class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-muted-foreground">
                Showing {{ props.logs.from }}–{{ props.logs.to }} of
                {{ props.logs.total }}
            </p>
            <div class="flex flex-wrap items-center gap-1">
                <template v-for="link in props.logs.links" :key="link.label">
                    <button
                        v-if="link.label === '&laquo; Previous'"
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-else-if="link.label === 'Next &raquo;'"
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        type="button"
                        class="min-w-11 rounded-lg px-2 py-2 text-sm font-medium transition-colors"
                        :class="
                            link.active
                                ? 'bg-orange-600 text-white'
                                : 'text-muted-foreground hover:bg-muted'
                        "
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </section>

        <section
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/30 dark:bg-amber-900/10 dark:text-amber-300"
        >
            Logs are retained for 90 days. Metadata like IP, browser, device,
            and location are captured for new admin actions.
        </section>
    </div>
</template>
