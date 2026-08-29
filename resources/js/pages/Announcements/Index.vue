<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ArrowDown, Megaphone, Pin, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { index as announcementsIndex } from '@/routes/announcements';

interface AnnouncementThumbnail {
    path: string;
    url: string;
}

interface AnnouncementItem {
    id: string;
    title: string;
    content: string;
    thumbnails: AnnouncementThumbnail[];
    type: 'info' | 'success' | 'warning' | 'danger';
    is_pinned: boolean;
    published_at: string | null;
    expires_at: string | null;
    created_by_name?: string | null;
}

const props = defineProps<{
    announcements: AnnouncementItem[];
    role: string;
}>();

const typeLabels: Record<AnnouncementItem['type'], string> = {
    info: 'Notice',
    success: 'Update',
    warning: 'Alert',
    danger: 'Urgent',
};

const typeAccent: Record<AnnouncementItem['type'], string> = {
    info: 'text-blue-700 dark:text-blue-300',
    success: 'text-teal-700 dark:text-teal-300',
    warning: 'text-amber-700 dark:text-amber-300',
    danger: 'text-red-700 dark:text-red-300',
};

const searchQuery = ref('');
const debouncedSearch = ref('');
watchDebounced(
    searchQuery,
    (val) => {
        debouncedSearch.value = val;
    },
    { debounce: 300 },
);
const activeType = ref<string>('all');
const readingId = ref<string | null>(null);

const filteredAnnouncements = computed(() => {
    let result = props.announcements;

    const q = debouncedSearch.value.trim().toLowerCase();

    if (q) {
        result = result.filter(
            (a) =>
                a.title.toLowerCase().includes(q) ||
                a.content.toLowerCase().includes(q),
        );
    }

    if (activeType.value !== 'all') {
        result = result.filter((a) => a.type === activeType.value);
    }

    return result;
});

const featured = computed(() => filteredAnnouncements.value[0] ?? null);
const feed = computed(() => filteredAnnouncements.value.slice(1));

const issueDate = computed(() => formatDateLong(new Date().toISOString()));

function coverImage(item: AnnouncementItem): string | null {
    return item.thumbnails?.[0]?.url ?? null;
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatDateLong(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function excerpt(text: string, length = 180): string {
    const clean = text.replace(/\s+/g, ' ').trim();

    if (clean.length <= length) {
        return clean;
    }

    return `${clean.slice(0, length).trimEnd()}…`;
}

function openStory(id: string): void {
    readingId.value = readingId.value === id ? null : id;
}

function scrollToFeed(): void {
    document
        .getElementById('announcement-feed')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Announcements', href: announcementsIndex() }],
    },
});
</script>

<template>
    <Head title="Announcements" />

    <div
        class="relative flex h-full flex-1 flex-col overflow-x-hidden bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-orange-50/80 via-background to-background dark:from-orange-950/20"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[linear-gradient(to_right,transparent_0%,rgba(194,65,12,0.06)_50%,transparent_100%)]"
            aria-hidden="true"
        />

        <div
            class="relative mx-auto w-full max-w-5xl px-4 py-6 md:px-6 md:py-10"
        >
            <!-- Masthead -->
            <header
                class="motion-safe:animate-in motion-safe:duration-500 motion-safe:fade-in-0 motion-safe:slide-in-from-top-2"
            >
                <div
                    class="flex flex-wrap items-end justify-between gap-3 border-b-4 border-foreground pb-4"
                >
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-[0.22em] text-orange-600 uppercase dark:text-orange-400"
                        >
                            UMRIC Desk
                        </p>
                        <h1
                            class="mt-1 font-display text-4xl font-black tracking-tight text-foreground sm:text-5xl md:text-6xl"
                        >
                            The Bulletin
                        </h1>
                    </div>
                    <div class="text-right text-xs text-muted-foreground">
                        <p class="font-semibold tracking-wide uppercase">
                            {{ issueDate }}
                        </p>
                        <p class="mt-0.5">
                            {{ announcements.length }}
                            {{
                                announcements.length === 1 ? 'story' : 'stories'
                            }}
                            · {{ role }} edition
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="relative min-w-0 flex-1 sm:max-w-md">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-0 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search the bulletin…"
                            class="h-11 w-full border-0 border-b border-border bg-transparent pr-2 pl-7 text-base outline-none placeholder:text-muted-foreground focus:border-orange-500 md:text-sm"
                        />
                    </div>

                    <nav
                        class="flex flex-nowrap gap-1 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        aria-label="Filter by type"
                    >
                        <button
                            v-for="tab in [
                                { key: 'all', label: 'All' },
                                { key: 'info', label: 'Notice' },
                                { key: 'success', label: 'Update' },
                                { key: 'warning', label: 'Alert' },
                                { key: 'danger', label: 'Urgent' },
                            ]"
                            :key="tab.key"
                            type="button"
                            :class="[
                                'min-h-9 shrink-0 px-3 text-xs font-bold tracking-[0.12em] uppercase transition',
                                activeType === tab.key
                                    ? 'bg-foreground text-background'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="activeType = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>
            </header>

            <!-- Empty -->
            <div
                v-if="filteredAnnouncements.length === 0"
                class="mt-16 text-center"
            >
                <Megaphone class="mx-auto h-10 w-10 text-muted-foreground/50" />
                <p class="mt-4 font-display text-2xl font-bold text-foreground">
                    {{
                        announcements.length === 0
                            ? 'Quiet newsroom'
                            : 'No matching stories'
                    }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        announcements.length === 0
                            ? 'Fresh posts for your role will land here.'
                            : 'Try another search or section filter.'
                    }}
                </p>
            </div>

            <template v-else>
                <!-- Lead story -->
                <article
                    v-if="featured"
                    class="mt-8 motion-safe:animate-in motion-safe:duration-700 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2"
                >
                    <div
                        class="group relative overflow-hidden bg-foreground text-background"
                    >
                        <div
                            class="relative aspect-[16/10] w-full sm:aspect-[21/9]"
                        >
                            <img
                                v-if="coverImage(featured)"
                                :src="coverImage(featured)!"
                                :alt="featured.title"
                                class="h-full w-full object-cover opacity-90 transition duration-700 group-hover:scale-[1.03]"
                            />
                            <div
                                v-else
                                class="absolute inset-0 bg-gradient-to-br from-orange-700 via-orange-600 to-amber-700"
                            >
                                <div
                                    class="absolute inset-0 [background-image:radial-gradient(circle_at_20%_20%,white_1px,transparent_1px)] [background-size:18px_18px] opacity-30"
                                />
                            </div>
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/10"
                            />
                        </div>

                        <div
                            class="absolute inset-x-0 bottom-0 z-10 space-y-3 p-5 sm:p-8 md:p-10"
                        >
                            <div
                                class="flex flex-wrap items-center gap-2 text-[11px] font-bold tracking-[0.16em] uppercase"
                            >
                                <span class="text-orange-300">
                                    {{ typeLabels[featured.type] }}
                                </span>
                                <span
                                    v-if="featured.is_pinned"
                                    class="inline-flex items-center gap-1 text-background/80"
                                >
                                    <Pin class="h-3 w-3" /> Front page
                                </span>
                                <span class="text-background/60">·</span>
                                <time class="text-background/70">
                                    {{ formatDate(featured.published_at) }}
                                </time>
                            </div>

                            <h2
                                class="max-w-3xl font-display text-3xl leading-[1.1] font-black tracking-tight text-balance sm:text-4xl md:text-5xl"
                            >
                                {{ featured.title }}
                            </h2>

                            <p
                                class="max-w-2xl text-sm leading-relaxed text-background/85 sm:text-base"
                            >
                                {{
                                    readingId === featured.id
                                        ? featured.content
                                        : excerpt(featured.content, 220)
                                }}
                            </p>

                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-2 bg-background px-4 text-sm font-bold text-foreground transition hover:bg-orange-50 active:scale-[0.98]"
                                    @click="openStory(featured.id)"
                                >
                                    {{
                                        readingId === featured.id
                                            ? 'Close story'
                                            : 'Read full story'
                                    }}
                                </button>
                                <p
                                    class="text-xs tracking-wide text-background/65"
                                >
                                    {{
                                        featured.created_by_name
                                            ? `By ${featured.created_by_name}`
                                            : 'Official desk'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="
                            readingId === featured.id &&
                            (featured.thumbnails?.length ?? 0) > 1
                        "
                        class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4"
                    >
                        <a
                            v-for="thumb in featured.thumbnails.slice(1)"
                            :key="thumb.path"
                            :href="thumb.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="aspect-[4/3] overflow-hidden"
                        >
                            <img
                                :src="thumb.url"
                                :alt="featured.title"
                                class="h-full w-full object-cover transition hover:scale-[1.02]"
                            />
                        </a>
                    </div>

                    <button
                        v-if="feed.length > 0"
                        type="button"
                        class="mt-6 inline-flex min-h-11 items-center gap-2 text-xs font-bold tracking-[0.14em] text-muted-foreground uppercase transition hover:text-foreground"
                        @click="scrollToFeed"
                    >
                        More in this edition
                        <ArrowDown class="h-3.5 w-3.5" />
                    </button>
                </article>

                <!-- Blog feed -->
                <section
                    id="announcement-feed"
                    class="mt-10 scroll-mt-24 space-y-0 border-t border-foreground/15 pt-2"
                >
                    <article
                        v-for="(item, index) in feed"
                        :key="item.id"
                        class="group grid gap-5 border-b border-foreground/10 py-8 motion-safe:animate-in motion-safe:duration-500 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-1 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] md:gap-8"
                        :style="{
                            animationDelay: `${Math.min(index, 6) * 40}ms`,
                        }"
                    >
                        <div class="order-2 min-w-0 md:order-1">
                            <div
                                class="flex flex-wrap items-center gap-2 text-[11px] font-bold tracking-[0.14em] uppercase"
                            >
                                <span :class="typeAccent[item.type]">
                                    {{ typeLabels[item.type] }}
                                </span>
                                <span
                                    v-if="item.is_pinned"
                                    class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400"
                                >
                                    <Pin class="h-3 w-3" /> Pinned
                                </span>
                                <span class="text-muted-foreground/50">·</span>
                                <time class="text-muted-foreground">
                                    {{ formatDate(item.published_at) }}
                                </time>
                            </div>

                            <h3
                                class="mt-2 font-display text-2xl leading-tight font-bold tracking-tight text-foreground transition group-hover:text-orange-700 sm:text-3xl dark:group-hover:text-orange-400"
                            >
                                <button
                                    type="button"
                                    class="text-left"
                                    @click="openStory(item.id)"
                                >
                                    {{ item.title }}
                                </button>
                            </h3>

                            <p
                                class="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-[15px]"
                            >
                                {{
                                    readingId === item.id
                                        ? item.content
                                        : excerpt(item.content)
                                }}
                            </p>

                            <div
                                class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2"
                            >
                                <button
                                    type="button"
                                    class="text-xs font-bold tracking-[0.12em] text-orange-600 uppercase transition hover:text-orange-700 dark:text-orange-400"
                                    @click="openStory(item.id)"
                                >
                                    {{
                                        readingId === item.id
                                            ? 'Show less'
                                            : 'Continue reading'
                                    }}
                                </button>
                                <span class="text-xs text-muted-foreground">
                                    {{
                                        item.created_by_name
                                            ? item.created_by_name
                                            : 'Official desk'
                                    }}
                                </span>
                            </div>

                            <div
                                v-if="
                                    readingId === item.id &&
                                    (item.thumbnails?.length ?? 0) > 1
                                "
                                class="mt-4 grid grid-cols-3 gap-2"
                            >
                                <a
                                    v-for="thumb in item.thumbnails.slice(1)"
                                    :key="thumb.path"
                                    :href="thumb.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="aspect-square overflow-hidden"
                                >
                                    <img
                                        :src="thumb.url"
                                        :alt="item.title"
                                        class="h-full w-full object-cover"
                                    />
                                </a>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="order-1 overflow-hidden md:order-2"
                            :aria-label="`Open ${item.title}`"
                            @click="openStory(item.id)"
                        >
                            <div
                                v-if="coverImage(item)"
                                class="aspect-[16/10] w-full overflow-hidden bg-muted"
                            >
                                <img
                                    :src="coverImage(item)!"
                                    :alt="item.title"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                />
                            </div>
                            <div
                                v-else
                                class="flex aspect-[16/10] w-full items-center justify-center bg-gradient-to-br from-muted to-orange-100/60 dark:to-orange-950/30"
                            >
                                <Megaphone
                                    class="h-8 w-8 text-muted-foreground/40"
                                />
                            </div>
                        </button>
                    </article>
                </section>
            </template>
        </div>
    </div>
</template>
