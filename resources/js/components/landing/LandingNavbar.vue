<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical, Sun, Moon, Monitor, Menu, X } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { useBranding } from '@/composables/useBranding';
import { dashboard, documentation, faq, login, register } from '@/routes';

const branding = useBranding();

defineProps<{ canRegister: boolean }>();

const { appearance, updateAppearance } = useAppearance();
const scrolled = ref(false);
const mobileOpen = ref(false);
const activeSection = ref('hero');

function cycleTheme() {
    if (appearance.value === 'light') {
        updateAppearance('dark');
    } else if (appearance.value === 'dark') {
        updateAppearance('system');
    } else {
        updateAppearance('light');
    }
}

function scrollTo(id: string) {
    mobileOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

const sectionIds = [
    'hero',
    'features',
    'showcase',
    'how-it-works',
    'testimonials',
    'contact',
];

function handleScroll() {
    scrolled.value = window.scrollY > 24;

    // Scroll-spy: find current section
    let current = 'hero';

    for (const id of sectionIds) {
        const el = document.getElementById(id);

        if (el) {
            const rect = el.getBoundingClientRect();

            if (rect.top <= 120) {
                current = id;
            }
        }
    }

    activeSection.value = current;
}

onMounted(() =>
    window.addEventListener('scroll', handleScroll, { passive: true }),
);
onUnmounted(() => window.removeEventListener('scroll', handleScroll));

const page = usePage();

const navLinks = [
    { label: 'Features', id: 'features' },
    { label: 'Showcase', id: 'showcase' },
    { label: 'How It Works', id: 'how-it-works' },
    { label: 'Reviews', id: 'testimonials' },
    { label: 'Contact', id: 'contact' },
];
</script>

<template>
    <nav
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
        :class="[
            scrolled
                ? 'py-2 shadow-lg shadow-black/5 dark:shadow-black/30'
                : 'py-4',
            'border-b border-slate-200/60 bg-white/80 backdrop-blur-xl dark:border-slate-800/60 dark:bg-slate-950/80',
        ]"
    >
        <div
            class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6"
        >
            <!-- Logo -->
            <button
                @click="scrollTo('hero')"
                class="group flex min-w-0 shrink-0 items-center gap-2"
            >
                <div
                    v-if="branding.logoUrl"
                    class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted shadow-md transition-transform group-hover:scale-105"
                >
                    <img
                        :src="branding.logoUrl"
                        :alt="branding.name"
                        class="h-full w-full object-contain"
                    />
                </div>
                <div
                    v-else
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 shadow-md transition-transform group-hover:scale-105"
                >
                    <FlaskConical class="h-4 w-4 text-white" />
                </div>
                <span class="text-gradient truncate text-xl font-black">{{
                    branding.name
                }}</span>
            </button>

            <!-- Desktop nav links -->
            <div class="hidden items-center gap-1 lg:flex">
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="relative rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200"
                    :class="[
                        activeSection === link.id
                            ? 'text-orange-600 dark:text-orange-400'
                            : 'text-slate-600 hover:bg-orange-50 hover:text-orange-500 dark:text-slate-400 dark:hover:bg-orange-950/30 dark:hover:text-orange-400',
                    ]"
                >
                    {{ link.label }}
                    <span
                        v-if="activeSection === link.id"
                        class="nav-active-indicator absolute inset-x-2 -bottom-0.5 h-0.5 rounded-full bg-gradient-to-r from-orange-500 to-teal-500"
                    />
                </button>
            </div>

            <!-- Right side -->
            <div class="flex items-center gap-2">
                <!-- Theme toggle -->
                <button
                    @click="cycleTheme"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-orange-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-400"
                    :title="
                        appearance === 'light'
                            ? 'Light mode'
                            : appearance === 'dark'
                              ? 'Dark mode'
                              : 'System mode'
                    "
                >
                    <Sun v-if="appearance === 'light'" class="h-4 w-4" />
                    <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
                    <Monitor v-else class="h-4 w-4" />
                </button>

                <Link
                    :href="documentation.url()"
                    class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 md:block dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    Docs
                </Link>
                <Link
                    :href="faq.url()"
                    class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 md:block dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    FAQ
                </Link>

                <template v-if="!page.props.auth.user">
                    <Link :href="login.url()" class="hidden md:block">
                        <button
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Sign in
                        </button>
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register.url()"
                        class="hidden md:block"
                    >
                        <button
                            class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/25 transition-all duration-200 hover:scale-[1.02] hover:bg-orange-600 hover:shadow-orange-500/40"
                        >
                            Get Started
                        </button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="dashboard.url()" class="hidden md:block">
                        <button
                            class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-[1.02] hover:bg-orange-600 hover:shadow-lg"
                        >
                            Dashboard →
                        </button>
                    </Link>
                </template>

                <!-- Mobile toggle -->
                <button
                    @click="mobileOpen = !mobileOpen"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                >
                    <X v-if="mobileOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="mobileOpen"
                class="space-y-1 border-t border-slate-200/60 bg-white/95 px-4 py-4 backdrop-blur-xl sm:px-6 lg:hidden dark:border-slate-800/60 dark:bg-slate-950/95"
            >
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="block w-full rounded-lg px-4 py-3 text-left text-sm font-medium transition"
                    :class="[
                        activeSection === link.id
                            ? 'bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-400'
                            : 'text-slate-600 hover:bg-orange-50 hover:text-orange-500 dark:text-slate-400 dark:hover:bg-orange-950/30',
                    ]"
                >
                    {{ link.label }}
                </button>
                <div
                    class="flex flex-col gap-2 border-t border-slate-200/60 pt-3 dark:border-slate-800/60"
                >
                    <Link :href="documentation.url()" class="w-full">
                        <button
                            class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Documentation
                        </button>
                    </Link>
                    <Link :href="faq.url()" class="w-full">
                        <button
                            class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            FAQ
                        </button>
                    </Link>

                    <template v-if="!page.props.auth.user">
                        <Link :href="login.url()" class="w-full">
                            <button
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Sign in
                            </button>
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="w-full"
                        >
                            <button
                                class="w-full rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/25 transition hover:bg-orange-600"
                            >
                                Get Started
                            </button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="dashboard.url()" class="w-full">
                            <button
                                class="w-full rounded-lg bg-teal-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-600"
                            >
                                Dashboard →
                            </button>
                        </Link>
                    </template>
                </div>
            </div>
        </Transition>
    </nav>
</template>
