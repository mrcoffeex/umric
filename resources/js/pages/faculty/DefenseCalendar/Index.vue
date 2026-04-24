<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock,
    GraduationCap,
    ScrollText,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { index as classesIndex } from '@/routes/faculty/classes';
import { index as calendarIndex } from '@/routes/faculty/defense-calendar';

interface ClassItem {
    id: string;
    name: string;
    section: string | null;
}

interface DefenseEvent {
    id: string;
    paper_id: number;
    tracking_id: string;
    title: string;
    type: 'outline_defense' | 'final_defense' | 'title_defense';
    schedule: string;
    step_status: string | null;
    student: { name: string; email: string } | null;
    panel_members: string[];
    proponents?: string[];
    school_class: { name: string; section: string | null } | null;
}

type Props = {
    events: DefenseEvent[];
    month: number;
    year: number;
    classes: ClassItem[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Defense Calendar', href: '' }],
    },
});

// --- Calendar Grid ---
const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];
const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/** Max defense chips per day before "+N more" (2 shown before + 4 more = 6). */
const maxVisibleDayEvents = 6;

const today = new Date();
const todayDay = today.getDate();
const todayMonth = today.getMonth() + 1;
const todayYear = today.getFullYear();

const daysInMonth = computed(() =>
    new Date(props.year, props.month, 0).getDate(),
);
const firstWeekday = computed(() =>
    new Date(props.year, props.month - 1, 1).getDay(),
);

const calendarCells = computed<(number | null)[]>(() => {
    const cells: (number | null)[] = [];

    for (let i = 0; i < firstWeekday.value; i++) {
        cells.push(null);
    }

    for (let d = 1; d <= daysInMonth.value; d++) {
        cells.push(d);
    }

    return cells;
});

const eventsByDay = computed(() => {
    const map: Record<number, DefenseEvent[]> = {};

    for (const event of props.events) {
        const d = new Date(event.schedule).getDate();

        if (!map[d]) {
            map[d] = [];
        }

        map[d].push(event);
    }

    return map;
});

// --- Navigation ---
function navigate(direction: -1 | 1) {
    let m = props.month + direction;
    let y = props.year;

    if (m < 1) {
        m = 12;
        y--;
    }

    if (m > 12) {
        m = 1;
        y++;
    }

    router.visit(calendarIndex.url({ query: { month: m, year: y } }), {
        preserveScroll: true,
    });
}

function goToToday() {
    router.visit(
        calendarIndex.url({ query: { month: todayMonth, year: todayYear } }),
        { preserveScroll: true },
    );
}

// --- Detail Sheet ---
const selectedEvent = ref<DefenseEvent | null>(null);
const sheetOpen = ref(false);

function openEvent(event: DefenseEvent) {
    selectedEvent.value = event;
    sheetOpen.value = true;
}

const overflowDayLabel = ref<number | null>(null);
const overflowEvents = ref<DefenseEvent[]>([]);
const overflowSheetOpen = ref(false);

function openDayOverflow(day: number, events: DefenseEvent[]) {
    overflowDayLabel.value = day;
    overflowEvents.value = events;
    overflowSheetOpen.value = true;
}

function pickOverflowEvent(event: DefenseEvent) {
    overflowSheetOpen.value = false;
    openEvent(event);
}

// --- Helpers ---
function formatTime(iso: string) {
    return new Date(iso).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function formatDateTime(iso: string) {
    return new Date(iso).toLocaleString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function isToday(day: number) {
    return (
        day === todayDay &&
        props.month === todayMonth &&
        props.year === todayYear
    );
}

function eventClasses(
    type: 'outline_defense' | 'final_defense' | 'title_defense',
) {
    if (type === 'outline_defense') {
        return 'bg-indigo-100 text-indigo-700 border-indigo-200 hover:bg-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800';
    }
    if (type === 'title_defense') {
        return 'bg-violet-100 text-violet-800 border-violet-200 hover:bg-violet-200 dark:bg-violet-950/50 dark:text-violet-300 dark:border-violet-800';
    }
    return 'bg-orange-100 text-orange-700 border-orange-200 hover:bg-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:border-orange-800';
}

function eventLabel(
    type: 'outline_defense' | 'final_defense' | 'title_defense',
) {
    if (type === 'outline_defense') {
        return 'Outline';
    }
    if (type === 'title_defense') {
        return 'Title';
    }
    return 'Final';
}

function defenseTypeLongLabel(
    type: 'outline_defense' | 'final_defense' | 'title_defense' | undefined,
) {
    if (type === 'outline_defense') {
        return 'Outline Defense';
    }
    if (type === 'title_defense') {
        return 'Title Defense';
    }
    return 'Final Defense';
}

function defenseTypeSheetBadgeClass(
    type: 'outline_defense' | 'final_defense' | 'title_defense' | undefined,
) {
    if (type === 'outline_defense') {
        return 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300';
    }
    if (type === 'title_defense') {
        return 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-800 dark:bg-violet-950/30 dark:text-violet-300';
    }
    return 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-950/30 dark:text-orange-300';
}

function statusBadgeClass(status: string | null) {
    switch (status) {
        case 'passed':
            return 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300';
        case 'failed':
            return 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300';
        case 'pending':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
}
</script>

<template>
    <div class="flex h-full flex-col gap-5 p-4 md:p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-950/40"
                >
                    <CalendarDays
                        class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                    />
                </div>
                <div>
                    <h1 class="text-lg font-bold text-foreground">
                        Defense Calendar
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Schedules from your enrolled classes
                    </p>
                </div>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300"
                >
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Outline Defense
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-medium text-violet-800 dark:border-violet-800 dark:bg-violet-950/30 dark:text-violet-300"
                >
                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                    Title Defense
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700 dark:border-orange-800 dark:bg-orange-950/30 dark:text-orange-300"
                >
                    <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                    Final Defense
                </span>
            </div>
        </div>

        <!-- No classes notice -->
        <div
            v-if="props.classes.length === 0"
            class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950/20"
        >
            <GraduationCap
                class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"
            />
            <p class="text-sm text-amber-700 dark:text-amber-300">
                You have no active classes.
                <a
                    :href="classesIndex.url()"
                    class="font-semibold underline underline-offset-2"
                    >Manage your classes</a
                >
                to see defense schedules here.
            </p>
        </div>

        <!-- Calendar card -->
        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            <!-- Month nav -->
            <div
                class="flex items-center justify-between gap-3 border-b border-border px-5 py-3.5"
            >
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    @click="navigate(-1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-foreground">
                        {{ MONTH_NAMES[props.month - 1] }} {{ props.year }}
                    </h2>
                    <button
                        v-if="
                            !(
                                props.month === todayMonth &&
                                props.year === todayYear
                            )
                        "
                        class="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-medium text-muted-foreground transition-colors hover:bg-muted/80"
                        @click="goToToday"
                    >
                        Today
                    </button>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-8 w-8"
                    @click="navigate(1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 border-b border-border">
                <div
                    v-for="day in DAY_NAMES"
                    :key="day"
                    class="py-2 text-center text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Calendar grid -->
            <div class="grid auto-rows-[minmax(100px,auto)] grid-cols-7">
                <div
                    v-for="(cell, idx) in calendarCells"
                    :key="idx"
                    class="border-r border-b border-border/50 p-1.5"
                    :class="cell === null ? 'bg-muted/20' : 'bg-card'"
                >
                    <!-- Day number -->
                    <div
                        v-if="cell !== null"
                        class="mb-1 flex items-center justify-end px-0.5"
                    >
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                            :class="
                                isToday(cell)
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ cell }}
                        </span>
                    </div>

                    <!-- Events -->
                    <template v-if="cell !== null && eventsByDay[cell]">
                        <button
                            v-for="event in eventsByDay[cell].slice(
                                0,
                                maxVisibleDayEvents,
                            )"
                            :key="event.id"
                            class="mb-0.5 w-full cursor-pointer truncate rounded border px-1.5 py-0.5 text-left text-[11px] font-medium transition-colors"
                            :class="eventClasses(event.type)"
                            @click="openEvent(event)"
                        >
                            <span class="font-bold">{{
                                eventLabel(event.type)
                            }}</span>
                            <span class="ml-1 opacity-80">{{
                                formatTime(event.schedule)
                            }}</span>
                        </button>
                        <button
                            v-if="
                                eventsByDay[cell].length > maxVisibleDayEvents
                            "
                            class="w-full rounded px-1.5 py-0.5 text-left text-[11px] font-medium text-muted-foreground transition-colors hover:text-foreground"
                            @click="
                                openDayOverflow(
                                    cell,
                                    eventsByDay[cell].slice(
                                        maxVisibleDayEvents,
                                    ),
                                )
                            "
                        >
                            +{{
                                eventsByDay[cell].length - maxVisibleDayEvents
                            }}
                            more
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Empty state (has classes but no events) -->
        <div
            v-if="props.classes.length > 0 && props.events.length === 0"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card py-16 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/30"
            >
                <CalendarDays class="h-7 w-7 text-indigo-400" />
            </div>
            <h3 class="text-sm font-bold text-foreground">
                No defenses scheduled
            </h3>
            <p class="mt-1 text-xs text-muted-foreground">
                No outline or final defenses are scheduled for
                {{ MONTH_NAMES[props.month - 1] }} {{ props.year }}.
            </p>
        </div>

        <Sheet v-model:open="overflowSheetOpen">
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader class="pb-2">
                    <SheetTitle class="text-base">
                        More schedules
                        <span
                            v-if="overflowDayLabel !== null"
                            class="text-muted-foreground"
                        >
                            · {{ MONTH_NAMES[props.month - 1] }}
                            {{ overflowDayLabel }}, {{ props.year }}
                        </span>
                    </SheetTitle>
                    <SheetDescription class="sr-only">
                        Additional defense events on this day
                    </SheetDescription>
                </SheetHeader>
                <div
                    class="flex max-h-[70vh] flex-col gap-1.5 overflow-y-auto p-3"
                >
                    <button
                        v-for="event in overflowEvents"
                        :key="event.id"
                        type="button"
                        class="w-full cursor-pointer rounded-lg border px-3 py-2.5 text-left text-sm font-medium transition-colors"
                        :class="eventClasses(event.type)"
                        @click="pickOverflowEvent(event)"
                    >
                        <span class="font-bold">{{
                            eventLabel(event.type)
                        }}</span>
                        <span class="ml-2 opacity-80">{{
                            formatTime(event.schedule)
                        }}</span>
                        <span
                            class="mt-0.5 block truncate text-xs font-normal opacity-90"
                        >
                            {{ event.title }}
                        </span>
                    </button>
                </div>
            </SheetContent>
        </Sheet>

        <!-- Event Detail Sheet -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader class="pb-4">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
                            :class="
                                defenseTypeSheetBadgeClass(selectedEvent?.type)
                            "
                        >
                            {{ defenseTypeLongLabel(selectedEvent?.type) }}
                        </span>
                        <Badge
                            v-if="selectedEvent?.step_status"
                            variant="outline"
                            class="text-[10px]"
                            :class="statusBadgeClass(selectedEvent.step_status)"
                        >
                            {{ selectedEvent.step_status }}
                        </Badge>
                    </div>
                    <SheetTitle class="mt-2 text-base leading-snug">
                        {{ selectedEvent?.title }}
                    </SheetTitle>
                    <SheetDescription class="sr-only">
                        Defense event details for {{ selectedEvent?.title }}
                    </SheetDescription>
                </SheetHeader>

                <div v-if="selectedEvent" class="space-y-4 p-3">
                    <!-- Schedule -->
                    <div
                        class="flex items-start gap-3 rounded-xl bg-muted/50 p-3.5"
                    >
                        <Clock
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p
                                class="mb-0.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Scheduled
                            </p>
                            <p class="text-sm font-medium text-foreground">
                                {{ formatDateTime(selectedEvent.schedule) }}
                            </p>
                        </div>
                    </div>

                    <!-- Panel Members -->
                    <div
                        v-if="selectedEvent.panel_members?.length"
                        class="flex items-start gap-3 rounded-xl bg-muted/50 p-3.5"
                    >
                        <Users
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Panel Members
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="member in selectedEvent.panel_members"
                                    :key="member"
                                    class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-0.5 text-xs font-medium text-foreground"
                                >
                                    {{ member }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Student -->
                    <div
                        v-if="selectedEvent.student"
                        class="flex items-start gap-3 rounded-xl bg-muted/50 p-3.5"
                    >
                        <GraduationCap
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p
                                class="mb-0.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Student
                            </p>
                            <p class="text-sm font-medium text-foreground">
                                {{ selectedEvent.student.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ selectedEvent.student.email }}
                            </p>
                        </div>
                    </div>

                    <!-- Co-Proponents -->
                    <div
                        v-if="selectedEvent.proponents?.length"
                        class="flex items-start gap-3 rounded-xl bg-muted/50 p-3.5"
                    >
                        <Users
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p
                                class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Co-Proponents
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="name in selectedEvent.proponents"
                                    :key="name"
                                    class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-0.5 text-xs font-medium text-foreground"
                                >
                                    {{ name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Class -->
                    <div
                        v-if="selectedEvent.school_class"
                        class="flex items-start gap-3 rounded-xl bg-muted/50 p-3.5"
                    >
                        <ScrollText
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p
                                class="mb-0.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Class
                            </p>
                            <p class="text-sm font-medium text-foreground">
                                {{ selectedEvent.school_class.name }}
                                <span
                                    v-if="selectedEvent.school_class.section"
                                    class="text-muted-foreground"
                                >
                                    —
                                    {{
                                        selectedEvent.school_class.section
                                    }}</span
                                >
                            </p>
                        </div>
                    </div>

                    <!-- Tracking ID -->
                    <div
                        class="rounded-xl border border-border bg-card px-4 py-3"
                    >
                        <p class="text-xs text-muted-foreground">Tracking ID</p>
                        <p
                            class="mt-0.5 font-mono text-sm font-semibold text-foreground"
                        >
                            {{ selectedEvent.tracking_id }}
                        </p>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
