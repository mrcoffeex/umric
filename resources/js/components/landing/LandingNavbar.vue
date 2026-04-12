<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { FlaskConical, Sun, Moon, Monitor, Menu, X } from 'lucide-vue-next'
import { useAppearance } from '@/composables/useAppearance'
import { login, register, dashboard } from '@/routes'

defineProps<{ canRegister: boolean }>()

const { appearance, updateAppearance } = useAppearance()
const scrolled = ref(false)
const mobileOpen = ref(false)

function cycleTheme() {
    if (appearance.value === 'light') updateAppearance('dark')
    else if (appearance.value === 'dark') updateAppearance('system')
    else updateAppearance('light')
}

function scrollTo(id: string) {
    mobileOpen.value = false
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

function handleScroll() {
    scrolled.value = window.scrollY > 24
}

onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))

const page = usePage()

const navLinks = [
    { label: 'Features', id: 'features' },
    { label: 'Showcase', id: 'showcase' },
    { label: 'How It Works', id: 'how-it-works' },
    { label: 'Reviews', id: 'testimonials' },
    { label: 'Contact', id: 'contact' },
]
</script>

<template>
    <nav
        class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
        :class="[
            scrolled
                ? 'py-2 shadow-lg shadow-black/5 dark:shadow-black/30'
                : 'py-4',
            'backdrop-blur-xl bg-white/80 dark:bg-slate-950/80 border-b border-slate-200/60 dark:border-slate-800/60',
        ]"
    >
        <div class="max-w-7xl mx-auto px-5 flex items-center justify-between gap-6">
            <!-- Logo -->
            <button @click="scrollTo('hero')" class="flex items-center gap-2 group shrink-0">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                    <FlaskConical class="w-4 h-4 text-white" />
                </div>
                <span class="text-xl font-black text-gradient">UMRIC</span>
            </button>

            <!-- Desktop nav links -->
            <div class="hidden lg:flex items-center gap-1">
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-orange-500 dark:hover:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-950/30 transition-all duration-200"
                >
                    {{ link.label }}
                </button>
            </div>

            <!-- Right side -->
            <div class="flex items-center gap-2">
                <!-- Theme toggle -->
                <button
                    @click="cycleTheme"
                    class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-orange-500 dark:hover:text-orange-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200"
                    :title="appearance === 'light' ? 'Light mode' : appearance === 'dark' ? 'Dark mode' : 'System mode'"
                >
                    <Sun v-if="appearance === 'light'" class="w-4 h-4" />
                    <Moon v-else-if="appearance === 'dark'" class="w-4 h-4" />
                    <Monitor v-else class="w-4 h-4" />
                </button>

                <template v-if="!page.props.auth.user">
                    <Link :href="login.url()" class="hidden md:block">
                        <button class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200">
                            Sign in
                        </button>
                    </Link>
                    <Link v-if="canRegister" :href="register.url()" class="hidden md:block">
                        <button class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 shadow-md shadow-orange-500/25 hover:shadow-orange-500/40 transition-all duration-200 hover:scale-[1.02]">
                            Get Started
                        </button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="dashboard.url()" class="hidden md:block">
                        <button class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-orange-500 to-teal-500 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                            Dashboard →
                        </button>
                    </Link>
                </template>

                <!-- Mobile toggle -->
                <button
                    @click="mobileOpen = !mobileOpen"
                    class="lg:hidden w-9 h-9 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    <X v-if="mobileOpen" class="w-5 h-5" />
                    <Menu v-else class="w-5 h-5" />
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
            <div v-if="mobileOpen" class="lg:hidden border-t border-slate-200/60 dark:border-slate-800/60 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl px-5 py-4 space-y-1">
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-950/30 transition"
                >
                    {{ link.label }}
                </button>
                <div class="flex gap-2 pt-3 border-t border-slate-200/60 dark:border-slate-800/60">
                    <template v-if="!page.props.auth.user">
                        <Link :href="login.url()" class="flex-1">
                            <button class="w-full px-4 py-2 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Sign in</button>
                        </Link>
                        <Link v-if="canRegister" :href="register.url()" class="flex-1">
                            <button class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-orange-500 to-orange-600 shadow-md shadow-orange-500/25 transition">Get Started</button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="dashboard.url()" class="flex-1">
                            <button class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-orange-500 to-teal-500 transition">Dashboard →</button>
                        </Link>
                    </template>
                </div>
            </div>
        </Transition>
    </nav>
</template>
