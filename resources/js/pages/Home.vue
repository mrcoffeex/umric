<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import NeuCard from '@/components/NeuCard.vue';
import NeuButton from '@/components/NeuButton.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { login, register, dashboard } from '@/routes';
import { create as papersCreate } from '@/routes/papers';

interface Props {
    canRegister: boolean;
    featuredPapers?: Array<{
        id: number;
        title: string;
        description: string;
        status: string;
        category: { name: string };
        tracking_id: string;
    }>;
    categories?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: true,
});

const isDark = ref(false);
const trackingId = ref('');
const trackingError = ref('');
const isSearching = ref(false);
const mobileMenuOpen = ref(false);
const contactForm = ref({ name: '', email: '', message: '' });
const contactSuccess = ref(false);

onMounted(() => {
    const saved = localStorage.getItem('darkMode');
    if (saved) isDark.value = saved === 'true';
    else
        isDark.value = window.matchMedia(
            '(prefers-color-scheme: dark)',
        ).matches;
    applyTheme();
});

const applyTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('darkMode', isDark.value.toString());
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    applyTheme();
};

const searchPaper = async () => {
    if (!trackingId.value.trim()) {
        trackingError.value = 'Please enter a tracking ID';
        return;
    }
    isSearching.value = true;
    trackingError.value = '';
    try {
        const response = await fetch(
            `/track/${encodeURIComponent(trackingId.value.trim())}`,
        );
        if (response.ok) {
            window.location.href = `/track/${encodeURIComponent(trackingId.value.trim())}`;
        } else {
            trackingError.value =
                'Paper not found. Please check the tracking ID.';
        }
    } catch {
        trackingError.value = 'Error searching for paper. Please try again.';
    } finally {
        isSearching.value = false;
    }
};

const handleKeyPress = (e: KeyboardEvent) => {
    if (e.key === 'Enter') searchPaper();
};

const submitContact = () => {
    contactSuccess.value = true;
    contactForm.value = { name: '', email: '', message: '' };
    setTimeout(() => {
        contactSuccess.value = false;
    }, 4000);
};

const scrollTo = (id: string) => {
    mobileMenuOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head title="UMRIC - Research Paper Tracking & Management">
        <meta
            name="description"
            content="Track, manage, and collaborate on research papers with real-time status updates."
        />
    </Head>

    <div
        :class="{
            'min-h-screen transition-colors duration-300': true,
            'dark bg-gray-900 text-white': isDark,
            'bg-stone-50 text-gray-900': !isDark,
        }"
    >
        <!-- ======== NAVIGATION ======== -->
        <nav
            class="sticky top-0 z-50 border-b backdrop-blur-md"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <NeuCard
                :is-dark="isDark"
                class="!rounded-none !rounded-b-2xl"
                padding="0"
            >
                <div
                    class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3"
                >
                    <Link
                        href="/"
                        class="flex items-center gap-2 text-2xl font-extrabold"
                    >
                        <span class="text-orange-500">🔬</span>
                        <span
                            class="bg-gradient-to-r from-orange-500 to-teal-500 bg-clip-text text-transparent"
                            >UMRIC</span
                        >
                    </Link>

                    <!-- Desktop links -->
                    <div
                        class="hidden items-center gap-6 text-sm font-medium md:flex"
                    >
                        <button
                            @click="scrollTo('hero')"
                            class="transition hover:text-orange-500 dark:hover:text-orange-400"
                        >
                            Home
                        </button>
                        <button
                            @click="scrollTo('research')"
                            class="transition hover:text-orange-500 dark:hover:text-orange-400"
                        >
                            Research
                        </button>
                        <button
                            @click="scrollTo('about')"
                            class="transition hover:text-orange-500 dark:hover:text-orange-400"
                        >
                            About
                        </button>
                        <button
                            @click="scrollTo('docs')"
                            class="transition hover:text-orange-500 dark:hover:text-orange-400"
                        >
                            Docs
                        </button>
                        <button
                            @click="scrollTo('contact')"
                            class="transition hover:text-orange-500 dark:hover:text-orange-400"
                        >
                            Contact
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleTheme"
                            class="rounded-lg p-2 text-lg transition hover:bg-black/5 dark:hover:bg-white/10"
                        >
                            {{ isDark ? '☀️' : '🌙' }}
                        </button>
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="login.url()"
                            class="hidden md:block"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="secondary"
                                size="sm"
                                >Login</NeuButton
                            >
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register.url()"
                            class="hidden md:block"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                size="sm"
                                >Register</NeuButton
                            >
                        </Link>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard.url()"
                            class="hidden md:block"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="primary"
                                size="sm"
                                >Dashboard</NeuButton
                            >
                        </Link>
                        <!-- Mobile menu toggle -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="rounded-lg p-2 transition hover:bg-black/5 md:hidden dark:hover:bg-white/10"
                        >
                            {{ mobileMenuOpen ? '✕' : '☰' }}
                        </button>
                    </div>
                </div>
                <!-- Mobile menu -->
                <div
                    v-if="mobileMenuOpen"
                    class="space-y-2 px-6 pb-4 text-sm font-medium md:hidden"
                >
                    <button
                        @click="scrollTo('hero')"
                        class="block w-full py-2 text-left transition hover:text-orange-500"
                    >
                        Home
                    </button>
                    <button
                        @click="scrollTo('research')"
                        class="block w-full py-2 text-left transition hover:text-orange-500"
                    >
                        Research
                    </button>
                    <button
                        @click="scrollTo('about')"
                        class="block w-full py-2 text-left transition hover:text-orange-500"
                    >
                        About
                    </button>
                    <button
                        @click="scrollTo('docs')"
                        class="block w-full py-2 text-left transition hover:text-orange-500"
                    >
                        Docs
                    </button>
                    <button
                        @click="scrollTo('contact')"
                        class="block w-full py-2 text-left transition hover:text-orange-500"
                    >
                        Contact
                    </button>
                    <div class="flex gap-2 pt-2">
                        <Link v-if="!$page.props.auth.user" :href="login.url()">
                            <NeuButton
                                :is-dark="isDark"
                                variant="secondary"
                                size="sm"
                                >Login</NeuButton
                            >
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register.url()"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                size="sm"
                                >Register</NeuButton
                            >
                        </Link>
                    </div>
                </div>
            </NeuCard>
        </nav>

        <!-- ======== HERO SECTION (with Tracking) ======== -->
        <section id="hero" class="px-6 pt-16 pb-20 md:pt-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <div
                        class="mb-6 inline-block rounded-full px-4 py-1.5 text-sm font-semibold"
                        :class="
                            isDark
                                ? 'bg-teal-900/40 text-teal-300'
                                : 'bg-teal-100 text-teal-700'
                        "
                    >
                        University Research Management
                    </div>
                    <h1
                        class="mb-6 text-5xl leading-tight font-black md:text-7xl"
                    >
                        <span
                            class="bg-gradient-to-r from-orange-500 via-orange-400 to-teal-500 bg-clip-text text-transparent dark:from-orange-400 dark:via-orange-300 dark:to-teal-400"
                        >
                            Track Your Research
                        </span>
                    </h1>
                    <p
                        class="mx-auto max-w-2xl text-lg leading-relaxed md:text-xl"
                        :class="isDark ? 'text-gray-400' : 'text-gray-600'"
                    >
                        Real-time paper tracking, management, and collaboration
                        platform for academics and researchers.
                    </p>
                </div>

                <!-- Tracking Widget -->
                <div class="mx-auto max-w-2xl">
                    <NeuCard :is-dark="isDark" padding="2rem">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="text-2xl">🔍</span>
                            <div>
                                <h2 class="text-xl font-bold">Track a Paper</h2>
                                <p
                                    class="text-sm"
                                    :class="
                                        isDark
                                            ? 'text-gray-400'
                                            : 'text-gray-500'
                                    "
                                >
                                    Enter a tracking ID to see real-time status
                                    updates
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <input
                                v-model="trackingId"
                                @keypress="handleKeyPress"
                                type="text"
                                placeholder="e.g. RP-XXXXXXXX"
                                class="flex-1 rounded-xl border-none px-4 py-3 text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none"
                                :class="
                                    isDark
                                        ? 'bg-gray-800 text-white placeholder-gray-500'
                                        : 'bg-white text-gray-900 placeholder-gray-400 shadow-inner'
                                "
                            />
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                :disabled="isSearching"
                                @click="searchPaper"
                            >
                                {{ isSearching ? '...' : 'Track' }}
                            </NeuButton>
                        </div>
                        <p
                            v-if="trackingError"
                            class="mt-2 text-sm text-red-500"
                        >
                            {{ trackingError }}
                        </p>
                        <p
                            class="mt-3 text-xs"
                            :class="isDark ? 'text-gray-500' : 'text-gray-400'"
                        >
                            🔓 No login required — public tracking is anonymous.
                        </p>
                    </NeuCard>
                </div>

                <!-- Quick stats row -->
                <div
                    class="mx-auto mt-14 grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-4"
                >
                    <NeuCard
                        :is-dark="isDark"
                        padding="1rem"
                        class="text-center"
                    >
                        <div class="text-2xl font-black text-orange-500">
                            1 000+
                        </div>
                        <p
                            class="mt-1 text-xs"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Papers Tracked
                        </p>
                    </NeuCard>
                    <NeuCard
                        :is-dark="isDark"
                        padding="1rem"
                        class="text-center"
                    >
                        <div class="text-2xl font-black text-teal-500">
                            500+
                        </div>
                        <p
                            class="mt-1 text-xs"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Researchers
                        </p>
                    </NeuCard>
                    <NeuCard
                        :is-dark="isDark"
                        padding="1rem"
                        class="text-center"
                    >
                        <div class="text-2xl font-black text-orange-500">
                            150+
                        </div>
                        <p
                            class="mt-1 text-xs"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Universities
                        </p>
                    </NeuCard>
                    <NeuCard
                        :is-dark="isDark"
                        padding="1rem"
                        class="text-center"
                    >
                        <div class="text-2xl font-black text-teal-500">
                            99.9%
                        </div>
                        <p
                            class="mt-1 text-xs"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Uptime
                        </p>
                    </NeuCard>
                </div>
            </div>
        </section>

        <!-- ======== RESEARCH SECTION ======== -->
        <section
            id="research"
            class="border-t px-6 py-20"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <h2 class="mb-3 text-4xl font-black">
                        <span
                            class="bg-gradient-to-r from-teal-500 to-orange-500 bg-clip-text text-transparent"
                            >Featured Research</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-lg"
                        :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                    >
                        Browse recently published papers from our community of
                        researchers.
                    </p>
                </div>

                <div
                    v-if="
                        props.featuredPapers && props.featuredPapers.length > 0
                    "
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="paper in props.featuredPapers.slice(0, 6)"
                        :key="paper.id"
                        :href="`/track/${paper.tracking_id}`"
                        class="group no-underline"
                    >
                        <NeuCard
                            :is-dark="isDark"
                            class="h-full cursor-pointer transition-all hover:shadow-lg"
                        >
                            <StatusBadge :status="paper.status" />
                            <h3
                                class="mt-4 mb-2 line-clamp-2 text-lg font-bold transition group-hover:text-orange-500"
                            >
                                {{ paper.title }}
                            </h3>
                            <p
                                class="mb-4 line-clamp-2 text-sm"
                                :class="
                                    isDark ? 'text-gray-400' : 'text-gray-500'
                                "
                            >
                                {{ paper.description }}
                            </p>
                            <div
                                class="flex items-center justify-between border-t pt-3"
                                :class="
                                    isDark
                                        ? 'border-gray-700'
                                        : 'border-stone-200'
                                "
                            >
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        isDark
                                            ? 'bg-teal-900/40 text-teal-300'
                                            : 'bg-teal-100 text-teal-700'
                                    "
                                >
                                    {{ paper.category.name }}
                                </span>
                                <code
                                    class="font-mono text-xs text-orange-500"
                                    >{{ paper.tracking_id }}</code
                                >
                            </div>
                        </NeuCard>
                    </Link>
                </div>

                <div v-else class="py-12 text-center">
                    <NeuCard :is-dark="isDark" class="mx-auto max-w-sm">
                        <p class="mb-4 text-4xl">📄</p>
                        <p class="mb-2 font-semibold">
                            No published papers yet
                        </p>
                        <p
                            class="mb-4 text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Be the first to submit your research!
                        </p>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="papersCreate.url()"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                full-width
                                >Submit Paper</NeuButton
                            >
                        </Link>
                        <Link v-else :href="register.url()">
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                full-width
                                >Create Account</NeuButton
                            >
                        </Link>
                    </NeuCard>
                </div>

                <!-- Categories -->
                <div
                    v-if="props.categories && props.categories.length > 0"
                    class="mt-14"
                >
                    <h3
                        class="mb-6 text-center text-lg font-bold"
                        :class="isDark ? 'text-gray-300' : 'text-gray-700'"
                    >
                        Research Categories
                    </h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <NeuCard
                            v-for="cat in props.categories"
                            :key="cat.id"
                            :is-dark="isDark"
                            padding="0.5rem 1rem"
                            class="cursor-default text-sm font-medium transition-all hover:shadow-md"
                        >
                            {{ cat.name }}
                        </NeuCard>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======== ABOUT SECTION ======== -->
        <section
            id="about"
            class="border-t px-6 py-20"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <h2 class="mb-3 text-4xl font-black">
                        <span
                            class="bg-gradient-to-r from-orange-500 to-teal-500 bg-clip-text text-transparent"
                            >Why UMRIC?</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-lg"
                        :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                    >
                        A purpose-built platform for university research
                        management.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">📊</div>
                        <h3 class="mb-2 text-lg font-bold">
                            Real-time Tracking
                        </h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Follow your paper through every stage — from
                            submission to publication — with instant status
                            updates.
                        </p>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">👥</div>
                        <h3 class="mb-2 text-lg font-bold">
                            Multi-Author Collaboration
                        </h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Add co-authors, manage contributions, and keep
                            everyone in the loop throughout the process.
                        </p>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">📁</div>
                        <h3 class="mb-2 text-lg font-bold">File Management</h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Upload PDFs, manage revisions, and keep all your
                            research documents organized in one place.
                        </p>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">📝</div>
                        <h3 class="mb-2 text-lg font-bold">
                            Citations & Metadata
                        </h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Track publications, DOIs, and citations. Export
                            metadata in multiple academic formats.
                        </p>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">🔓</div>
                        <h3 class="mb-2 text-lg font-bold">Public Tracking</h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Share a tracking link with anyone — no login
                            required to check a paper's progress.
                        </p>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="text-center">
                        <div class="mb-4 text-4xl">🔐</div>
                        <h3 class="mb-2 text-lg font-bold">Secure & Private</h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            Two-factor authentication, role-based access, and
                            encrypted storage keep your research safe.
                        </p>
                    </NeuCard>
                </div>
            </div>
        </section>

        <!-- ======== DOCS SECTION ======== -->
        <section
            id="docs"
            class="border-t px-6 py-20"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-5xl">
                <div class="mb-14 text-center">
                    <h2 class="mb-3 text-4xl font-black">
                        <span
                            class="bg-gradient-to-r from-teal-500 to-orange-500 bg-clip-text text-transparent"
                            >Getting Started</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-lg"
                        :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                    >
                        Three simple steps to start managing your research.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <NeuCard :is-dark="isDark" class="relative">
                        <div
                            class="absolute -top-4 -left-2 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-orange-600 text-lg font-black text-white shadow-lg"
                        >
                            1
                        </div>
                        <div class="pt-4">
                            <h3 class="mb-2 text-lg font-bold">
                                Create an Account
                            </h3>
                            <p
                                class="text-sm"
                                :class="
                                    isDark ? 'text-gray-400' : 'text-gray-500'
                                "
                            >
                                Sign up with your university email. Enable
                                two-factor auth for extra security.
                            </p>
                        </div>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="relative">
                        <div
                            class="absolute -top-4 -left-2 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-teal-600 text-lg font-black text-white shadow-lg"
                        >
                            2
                        </div>
                        <div class="pt-4">
                            <h3 class="mb-2 text-lg font-bold">
                                Submit Your Paper
                            </h3>
                            <p
                                class="text-sm"
                                :class="
                                    isDark ? 'text-gray-400' : 'text-gray-500'
                                "
                            >
                                Fill in the details, add co-authors, upload your
                                PDF, and get a unique tracking ID.
                            </p>
                        </div>
                    </NeuCard>
                    <NeuCard :is-dark="isDark" class="relative">
                        <div
                            class="absolute -top-4 -left-2 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-teal-500 text-lg font-black text-white shadow-lg"
                        >
                            3
                        </div>
                        <div class="pt-4">
                            <h3 class="mb-2 text-lg font-bold">
                                Track Progress
                            </h3>
                            <p
                                class="text-sm"
                                :class="
                                    isDark ? 'text-gray-400' : 'text-gray-500'
                                "
                            >
                                Monitor status changes in real-time. Share the
                                public tracking link with collaborators.
                            </p>
                        </div>
                    </NeuCard>
                </div>

                <div class="mt-12 text-center">
                    <NeuCard
                        :is-dark="isDark"
                        padding="1.5rem 2rem"
                        class="inline-block"
                    >
                        <p class="mb-1 text-sm font-medium">
                            API & Integrations
                        </p>
                        <p
                            class="text-xs"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            REST API available for programmatic access. Full
                            documentation coming soon.
                        </p>
                    </NeuCard>
                </div>
            </div>
        </section>

        <!-- ======== CONTACT SECTION ======== -->
        <section
            id="contact"
            class="border-t px-6 py-20"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-3xl">
                <div class="mb-14 text-center">
                    <h2 class="mb-3 text-4xl font-black">
                        <span
                            class="bg-gradient-to-r from-orange-500 to-teal-500 bg-clip-text text-transparent"
                            >Get in Touch</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-lg"
                        :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                    >
                        Questions, feedback, or partnership inquiries? We'd love
                        to hear from you.
                    </p>
                </div>

                <NeuCard :is-dark="isDark" padding="2rem">
                    <div v-if="contactSuccess" class="py-8 text-center">
                        <div class="mb-3 text-4xl">✅</div>
                        <p class="text-lg font-bold">Message Sent!</p>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            We'll get back to you as soon as possible.
                        </p>
                    </div>
                    <form
                        v-else
                        @submit.prevent="submitContact"
                        class="space-y-5"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Name</label
                                >
                                <input
                                    v-model="contactForm.name"
                                    type="text"
                                    required
                                    placeholder="Your name"
                                    class="w-full rounded-xl border-none px-4 py-3 text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none"
                                    :class="
                                        isDark
                                            ? 'bg-gray-800 text-white placeholder-gray-500'
                                            : 'bg-white text-gray-900 placeholder-gray-400 shadow-inner'
                                    "
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >Email</label
                                >
                                <input
                                    v-model="contactForm.email"
                                    type="email"
                                    required
                                    placeholder="you@university.edu"
                                    class="w-full rounded-xl border-none px-4 py-3 text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none"
                                    :class="
                                        isDark
                                            ? 'bg-gray-800 text-white placeholder-gray-500'
                                            : 'bg-white text-gray-900 placeholder-gray-400 shadow-inner'
                                    "
                                />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Message</label
                            >
                            <textarea
                                v-model="contactForm.message"
                                required
                                rows="4"
                                placeholder="How can we help?"
                                class="w-full resize-none rounded-xl border-none px-4 py-3 text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none"
                                :class="
                                    isDark
                                        ? 'bg-gray-800 text-white placeholder-gray-500'
                                        : 'bg-white text-gray-900 placeholder-gray-400 shadow-inner'
                                "
                            ></textarea>
                        </div>
                        <div class="text-right">
                            <NeuButton :is-dark="isDark" variant="success"
                                >Send Message</NeuButton
                            >
                        </div>
                    </form>
                </NeuCard>
            </div>
        </section>

        <!-- ======== LOGIN CTA ======== -->
        <section
            id="login"
            class="border-t px-6 py-20"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-4xl text-center">
                <NeuCard :is-dark="isDark" padding="3rem">
                    <h2 class="mb-4 text-3xl font-black md:text-4xl">
                        Ready to Start?
                    </h2>
                    <p
                        class="mx-auto mb-8 max-w-xl text-lg"
                        :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                    >
                        Join researchers across 150+ universities managing their
                        work with UMRIC.
                    </p>
                    <div class="flex flex-col justify-center gap-4 sm:flex-row">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="register.url()"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="success"
                                size="lg"
                                >Create Free Account</NeuButton
                            >
                        </Link>
                        <Link v-if="!$page.props.auth.user" :href="login.url()">
                            <NeuButton
                                :is-dark="isDark"
                                variant="secondary"
                                size="lg"
                                >Sign In</NeuButton
                            >
                        </Link>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard.url()"
                        >
                            <NeuButton
                                :is-dark="isDark"
                                variant="primary"
                                size="lg"
                                >Go to Dashboard</NeuButton
                            >
                        </Link>
                    </div>
                </NeuCard>
            </div>
        </section>

        <!-- ======== FOOTER ======== -->
        <footer
            class="border-t px-6 py-10"
            :class="isDark ? 'border-gray-800' : 'border-stone-200'"
        >
            <div class="mx-auto max-w-6xl">
                <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div>
                        <h3 class="mb-3 text-lg font-extrabold">
                            <span
                                class="bg-gradient-to-r from-orange-500 to-teal-500 bg-clip-text text-transparent"
                                >UMRIC</span
                            >
                        </h3>
                        <p
                            class="text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            University research paper tracking with neumorphic
                            design.
                        </p>
                    </div>
                    <div>
                        <h4 class="mb-3 font-semibold">Platform</h4>
                        <ul
                            class="space-y-2 text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            <li>
                                <button
                                    @click="scrollTo('research')"
                                    class="transition hover:text-orange-500"
                                >
                                    Research
                                </button>
                            </li>
                            <li>
                                <button
                                    @click="scrollTo('about')"
                                    class="transition hover:text-orange-500"
                                >
                                    Features
                                </button>
                            </li>
                            <li>
                                <button
                                    @click="scrollTo('docs')"
                                    class="transition hover:text-orange-500"
                                >
                                    Docs
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-3 font-semibold">University</h4>
                        <ul
                            class="space-y-2 text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            <li>
                                <button
                                    @click="scrollTo('about')"
                                    class="transition hover:text-orange-500"
                                >
                                    About
                                </button>
                            </li>
                            <li>
                                <button
                                    @click="scrollTo('contact')"
                                    class="transition hover:text-orange-500"
                                >
                                    Contact
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-3 font-semibold">Legal</h4>
                        <ul
                            class="space-y-2 text-sm"
                            :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                        >
                            <li>
                                <a
                                    href="#"
                                    class="transition hover:text-orange-500"
                                    >Privacy</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition hover:text-orange-500"
                                    >Terms</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div
                    class="flex flex-col items-center justify-between gap-4 border-t pt-6 md:flex-row"
                    :class="isDark ? 'border-gray-800' : 'border-stone-200'"
                >
                    <p
                        class="text-xs"
                        :class="isDark ? 'text-gray-500' : 'text-gray-400'"
                    >
                        &copy; 2026 UMRIC. All rights reserved.
                    </p>
                    <div
                        class="flex gap-4 text-xs"
                        :class="isDark ? 'text-gray-500' : 'text-gray-400'"
                    >
                        <a href="#" class="transition hover:text-orange-500"
                            >Twitter</a
                        >
                        <a href="#" class="transition hover:text-orange-500"
                            >GitHub</a
                        >
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
