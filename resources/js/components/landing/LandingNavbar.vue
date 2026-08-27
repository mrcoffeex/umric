<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import LandingLockup from '@/components/landing/LandingLockup.vue';
import { dashboard, login, register } from '@/routes';

defineProps<{ canRegister: boolean }>();

const scrolled = ref(false);
const mobileOpen = ref(false);
const activeSection = ref('hero');

function scrollTo(id: string) {
    mobileOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

const sectionIds = [
    'hero',
    'features',
    'showcase',
    'how-it-works',
    'who-its-for',
    'contact',
];

function handleScroll() {
    scrolled.value = window.scrollY > 12;

    let current = 'hero';

    for (const id of sectionIds) {
        const el = document.getElementById(id);

        if (el) {
            const rect = el.getBoundingClientRect();

            if (rect.top <= 140) {
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
    { label: 'Research process', id: 'features' },
    { label: 'Overview', id: 'showcase' },
    { label: 'How it works', id: 'how-it-works' },
    { label: 'Who it is for', id: 'who-its-for' },
    { label: 'Contact', id: 'contact' },
];
</script>

<template>
    <header class="fixed inset-x-0 top-0 z-50">
        <div class="bg-um-maroon px-4 py-1.5 text-white sm:px-6">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-3 text-[11px] font-semibold tracking-[0.12em] uppercase"
            >
                <p class="truncate">
                    UM Digos College · Research and Innovation Center
                </p>
                <p class="hidden shrink-0 sm:block">
                    Digos City, Davao del Sur
                </p>
            </div>
        </div>
        <nav
            class="border-b border-black/8 bg-white transition-shadow duration-200"
            :class="scrolled ? 'shadow-sm' : ''"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2.5 sm:px-6"
            >
                <button
                    type="button"
                    class="group min-w-0 shrink-0"
                    @click="scrollTo('hero')"
                >
                    <LandingLockup />
                </button>

                <div class="hidden items-center gap-0.5 lg:flex">
                    <button
                        v-for="link in navLinks"
                        :key="link.id"
                        type="button"
                        class="relative min-h-11 px-3 text-sm font-medium transition-colors"
                        :class="
                            activeSection === link.id
                                ? 'text-um-maroon'
                                : 'text-black hover:text-um-maroon'
                        "
                        @click="scrollTo(link.id)"
                    >
                        {{ link.label }}
                        <span
                            v-if="activeSection === link.id"
                            class="absolute inset-x-3 bottom-2 h-0.5 bg-um-gold"
                        />
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <template v-if="!page.props.auth.user">
                        <Link
                            :href="login.url()"
                            class="hidden min-h-11 items-center px-3 text-sm font-semibold text-black transition hover:text-um-maroon md:inline-flex"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="hidden min-h-11 items-center rounded-[3px] bg-um-gold px-4 text-sm font-bold text-white transition hover:bg-um-gold-hover md:inline-flex"
                        >
                            Submit research
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="dashboard.url()"
                        class="hidden min-h-11 items-center rounded-[3px] bg-um-gold px-4 text-sm font-bold text-white transition hover:bg-um-gold-hover md:inline-flex"
                    >
                        Dashboard
                    </Link>

                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center text-um-maroon lg:hidden"
                        :aria-expanded="mobileOpen"
                        aria-label="Toggle menu"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div
                v-if="mobileOpen"
                class="border-t border-black/8 bg-um-wash px-4 py-3 lg:hidden"
            >
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    type="button"
                    class="block min-h-11 w-full px-3 text-left text-sm font-medium"
                    :class="
                        activeSection === link.id
                            ? 'bg-white text-um-maroon'
                            : 'text-black'
                    "
                    @click="scrollTo(link.id)"
                >
                    {{ link.label }}
                </button>
                <div
                    class="mt-3 flex flex-col gap-2 border-t border-black/8 pt-3"
                >
                    <template v-if="!page.props.auth.user">
                        <Link
                            :href="login.url()"
                            class="inline-flex min-h-11 items-center justify-center border border-um-maroon px-4 text-sm font-bold text-um-maroon"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="inline-flex min-h-11 items-center justify-center rounded-[3px] bg-um-gold px-4 text-sm font-bold text-white"
                        >
                            Submit research
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="dashboard.url()"
                        class="inline-flex min-h-11 items-center justify-center rounded-[3px] bg-um-gold px-4 text-sm font-bold text-white"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </nav>
    </header>
</template>
