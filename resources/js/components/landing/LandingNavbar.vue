<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical, Sun, Moon, Monitor, Menu, X } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { login, register, dashboard } from '@/routes';

defineProps<{ canRegister: boolean }>();

const { appearance, updateAppearance } = useAppearance();
const scrolled = ref(false);
const mobileOpen = ref(false);

function cycleTheme() {
    if (appearance.value === 'light') updateAppearance('dark');
    else if (appearance.value === 'dark') updateAppearance('system');
    else updateAppearance('light');
}

function scrollTo(id: string) {
    mobileOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

function handleScroll() {
    scrolled.value = window.scrollY > 24;
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
            class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5"
        >
            <!-- Logo -->
            <button
                @click="scrollTo('hero')"
                class="group flex shrink-0 items-center gap-2"
            >
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 shadow-md transition-transform group-hover:scale-105"
                >
                    <FlaskConical class="h-4 w-4 text-white" />
                </div>
                <span class="text-gradient text-xl font-black">UMRIC</span>
            </button>

            <!-- Desktop nav links -->
            <div class="hidden items-center gap-1 lg:flex">
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-orange-50 hover:text-orange-500 dark:text-slate-400 dark:hover:bg-orange-950/30 dark:hover:text-orange-400"
                >
                    {{ link.label }}
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
                            class="rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/25 transition-all duration-200 hover:scale-[1.02] hover:from-orange-600 hover:to-orange-700 hover:shadow-orange-500/40"
                        >
                            Get Started
                        </button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="dashboard.url()" class="hidden md:block">
                        <button
                            class="rounded-lg bg-gradient-to-r from-orange-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-[1.02] hover:shadow-lg"
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
                class="space-y-1 border-t border-slate-200/60 bg-white/95 px-5 py-4 backdrop-blur-xl lg:hidden dark:border-slate-800/60 dark:bg-slate-950/95"
            >
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="block w-full rounded-lg px-4 py-2.5 text-left text-sm font-medium text-slate-600 transition hover:bg-orange-50 hover:text-orange-500 dark:text-slate-400 dark:hover:bg-orange-950/30"
                >
                    {{ link.label }}
                </button>
                <div
                    class="flex gap-2 border-t border-slate-200/60 pt-3 dark:border-slate-800/60"
                >
                    <template v-if="!page.props.auth.user">
                        <Link :href="login.url()" class="flex-1">
                            <button
                                class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Sign in
                            </button>
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="flex-1"
                        >
                            <button
                                class="w-full rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/25 transition"
                            >
                                Get Started
                            </button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="dashboard.url()" class="flex-1">
                            <button
                                class="w-full rounded-lg bg-gradient-to-r from-orange-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white transition"
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
