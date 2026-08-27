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
const page = usePage();

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
    'audience',
    'contact',
];

const navLinks = [
    { label: 'Features', id: 'features' },
    { label: 'Showcase', id: 'showcase' },
    { label: 'How it works', id: 'how-it-works' },
    { label: 'Who it’s for', id: 'audience' },
    { label: 'Contact', id: 'contact' },
];

function handleScroll() {
    scrolled.value = window.scrollY > 16;

    let current = 'hero';

    for (const id of sectionIds) {
        const el = document.getElementById(id);

        if (el && el.getBoundingClientRect().top <= 120) {
            current = id;
        }
    }

    activeSection.value = current;
}

onMounted(() =>
    window.addEventListener('scroll', handleScroll, { passive: true }),
);
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <nav
        class="fixed inset-x-0 top-0 z-50 border-b transition-all duration-300"
        :class="[
            scrolled
                ? 'border-slate-200/80 bg-white/90 py-2 shadow-sm backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/90'
                : 'border-transparent bg-transparent py-3',
        ]"
    >
        <div
            class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
        >
            <button
                type="button"
                class="group flex min-h-11 min-w-0 shrink-0 items-center gap-2"
                @click="scrollTo('hero')"
            >
                <div
                    v-if="branding.logoUrl"
                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
                >
                    <img
                        :src="branding.logoUrl"
                        :alt="branding.name"
                        class="h-full w-full object-contain"
                    />
                </div>
                <div
                    v-else
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500"
                >
                    <FlaskConical class="h-4 w-4 text-white" />
                </div>
                <span
                    class="font-display truncate text-lg font-extrabold tracking-tight text-slate-900 dark:text-white"
                    >{{ branding.name }}</span
                >
            </button>

            <div class="hidden items-center gap-0.5 lg:flex">
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    type="button"
                    class="relative min-h-11 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="
                        activeSection === link.id
                            ? 'text-orange-600 dark:text-orange-400'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    "
                    @click="scrollTo(link.id)"
                >
                    {{ link.label }}
                    <span
                        v-if="activeSection === link.id"
                        class="nav-active-indicator absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-orange-500"
                    />
                </button>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-orange-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-400"
                    :title="
                        appearance === 'light'
                            ? 'Light mode'
                            : appearance === 'dark'
                              ? 'Dark mode'
                              : 'System mode'
                    "
                    @click="cycleTheme"
                >
                    <Sun v-if="appearance === 'light'" class="h-4 w-4" />
                    <Moon v-else-if="appearance === 'dark'" class="h-4 w-4" />
                    <Monitor v-else class="h-4 w-4" />
                </button>

                <Link
                    :href="documentation.url()"
                    class="hidden min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900 md:inline-flex dark:text-slate-300 dark:hover:text-white"
                >
                    Docs
                </Link>
                <Link
                    :href="faq.url()"
                    class="hidden min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900 md:inline-flex dark:text-slate-300 dark:hover:text-white"
                >
                    FAQ
                </Link>

                <template v-if="!page.props.auth.user">
                    <Link
                        :href="login.url()"
                        class="hidden min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 md:inline-flex dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register.url()"
                        class="hidden min-h-11 items-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600 md:inline-flex"
                    >
                        Get Started
                    </Link>
                </template>
                <Link
                    v-else
                    :href="dashboard.url()"
                    class="hidden min-h-11 items-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600 md:inline-flex"
                >
                    Dashboard
                </Link>

                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-300 dark:hover:bg-slate-800"
                    :aria-expanded="mobileOpen"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="mobileOpen"
                class="border-t border-slate-200/80 bg-white/95 px-4 py-3 backdrop-blur-xl lg:hidden dark:border-slate-800/80 dark:bg-slate-950/95"
            >
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    type="button"
                    class="block w-full rounded-lg px-4 py-3 text-left text-sm font-medium"
                    :class="
                        activeSection === link.id
                            ? 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
                            : 'text-slate-700 dark:text-slate-300'
                    "
                    @click="scrollTo(link.id)"
                >
                    {{ link.label }}
                </button>

                <div
                    class="mt-2 flex flex-col gap-2 border-t border-slate-200/80 pt-3 dark:border-slate-800/80"
                >
                    <Link
                        :href="documentation.url()"
                        class="rounded-lg border border-slate-200 px-4 py-3 text-center text-sm font-semibold dark:border-slate-700"
                        @click="mobileOpen = false"
                    >
                        Documentation
                    </Link>
                    <Link
                        :href="faq.url()"
                        class="rounded-lg border border-slate-200 px-4 py-3 text-center text-sm font-semibold dark:border-slate-700"
                        @click="mobileOpen = false"
                    >
                        FAQ
                    </Link>
                    <template v-if="!page.props.auth.user">
                        <Link
                            :href="login.url()"
                            class="rounded-lg border border-slate-200 px-4 py-3 text-center text-sm font-semibold dark:border-slate-700"
                            @click="mobileOpen = false"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="rounded-lg bg-orange-500 px-4 py-3 text-center text-sm font-semibold text-white"
                            @click="mobileOpen = false"
                        >
                            Get Started
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="dashboard.url()"
                        class="rounded-lg bg-orange-500 px-4 py-3 text-center text-sm font-semibold text-white"
                        @click="mobileOpen = false"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </Transition>
    </nav>
</template>
