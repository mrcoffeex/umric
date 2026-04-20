<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown, Search } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { useCountUp } from '@/composables/useCountUp';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { register, dashboard } from '@/routes';

defineProps<{
    canRegister: boolean;
    featuredPapers?: Array<{
        id: number;
        title: string;
        status: string;
        tracking_id: string;
    }>;
}>();

const trackingId = ref('');
const trackingError = ref('');
const isSearching = ref(false);
const page = usePage();

async function searchPaper() {
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
        trackingError.value = 'Error searching. Please try again.';
    } finally {
        isSearching.value = false;
    }
}

function scrollToFeatures() {
    document.getElementById('features')?.scrollIntoView({ behavior: 'smooth' });
}

// Animated counters for stats
const { target: statsRef, isVisible: statsVisible } = useScrollReveal(0.2);
const paperCount = useCountUp(1200, statsVisible, 2000);
const studentCount = useCountUp(800, statsVisible, 2000);
const deptCount = useCountUp(12, statsVisible, 1200);

const stats = [
    {
        key: 'papers',
        suffix: '+',
        label: 'Papers Tracked',
        color: 'text-orange-500',
    },
    {
        key: 'students',
        suffix: '+',
        label: 'Student Researchers',
        color: 'text-teal-500',
    },
    {
        key: 'depts',
        suffix: '+',
        label: 'Departments',
        color: 'text-orange-500',
    },
    {
        key: 'stages',
        value: '6',
        label: 'Research Stages',
        color: 'text-teal-500',
    },
];

// Reactive particle canvas
const canvasRef = ref<HTMLCanvasElement | null>(null);

interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
    radius: number;
    colorBase: string;
}

const mouse = { x: -9999, y: -9999 };
let animationId = 0;

function setupCanvas() {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d')!;

    const COLOR_BASES = ['249,115,22', '20,184,166', '148,163,184'];
    const PARTICLE_COUNT = 80;
    const CONNECTION_DISTANCE = 130;
    const REPEL_RADIUS = 120;
    const REPEL_FORCE = 0.6;

    let particles: Particle[] = [];
    let w = 0;
    let h = 0;

    function resize() {
        w = window.innerWidth;
        h = canvas!.closest('section')?.offsetHeight ?? window.innerHeight;
        canvas!.width = w;
        canvas!.height = h;
    }

    function spawn(): Particle {
        return {
            x: Math.random() * w,
            y: Math.random() * h,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            radius: Math.random() * 2 + 1,
            colorBase:
                COLOR_BASES[Math.floor(Math.random() * COLOR_BASES.length)],
        };
    }

    function init() {
        particles = Array.from({ length: PARTICLE_COUNT }, spawn);
    }

    function tick() {
        ctx.clearRect(0, 0, w, h);

        for (const p of particles) {
            const dx = p.x - mouse.x;
            const dy = p.y - mouse.y;
            const d = Math.sqrt(dx * dx + dy * dy);

            if (d < REPEL_RADIUS && d > 0) {
                const f = (REPEL_RADIUS - d) / REPEL_RADIUS;
                p.vx += (dx / d) * f * REPEL_FORCE;
                p.vy += (dy / d) * f * REPEL_FORCE;
            }

            p.vx *= 0.97;
            p.vy *= 0.97;
            const speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);

            if (speed > 2) {
                p.vx = (p.vx / speed) * 2;
                p.vy = (p.vy / speed) * 2;
            }

            p.x += p.vx;
            p.y += p.vy;

            if (p.x <= 0 || p.x >= w) {
                p.vx *= -1;
                p.x = Math.max(0, Math.min(w, p.x));
            }

            if (p.y <= 0 || p.y >= h) {
                p.vy *= -1;
                p.y = Math.max(0, Math.min(h, p.y));
            }

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.colorBase},0.55)`;
            ctx.fill();
        }

        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const d = Math.sqrt(dx * dx + dy * dy);

                if (d < CONNECTION_DISTANCE) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(148,163,184,${(1 - d / CONNECTION_DISTANCE) * 0.25})`;
                    ctx.lineWidth = 0.6;
                    ctx.stroke();
                }
            }
        }

        animationId = requestAnimationFrame(tick);
    }

    let resizeTimer: ReturnType<typeof setTimeout>;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            resize();
            init();
        }, 150);
    });

    resize();
    init();
    tick();
}

function onMouseMove(e: MouseEvent) {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const rect = canvas.parentElement!.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
}

function onMouseLeave() {
    mouse.x = -9999;
    mouse.y = -9999;
}

onMounted(() => nextTick(setupCanvas));
onUnmounted(() => cancelAnimationFrame(animationId));
</script>

<template>
    <section
        id="hero"
        class="relative flex min-h-screen flex-col justify-center overflow-hidden px-4 pt-30 pb-16 sm:px-6 lg:px-8"
        @mousemove="onMouseMove"
        @mouseleave="onMouseLeave"
    >
        <!-- Animated gradient background -->
        <div class="absolute inset-0 -z-10">
            <!-- Base bg -->
            <div class="absolute inset-0 bg-slate-50 dark:bg-slate-950" />
            <!-- Gradient mesh -->
            <div
                class="absolute inset-0 opacity-40 dark:opacity-20"
                style="
                    background:
                        radial-gradient(
                            ellipse 80% 60% at 50% -10%,
                            rgba(249, 115, 22, 0.25) 0%,
                            transparent 60%
                        ),
                        radial-gradient(
                            ellipse 60% 50% at 100% 60%,
                            rgba(20, 184, 166, 0.2) 0%,
                            transparent 60%
                        ),
                        radial-gradient(
                            ellipse 60% 50% at -5% 70%,
                            rgba(249, 115, 22, 0.15) 0%,
                            transparent 60%
                        );
                "
            />
            <!-- Animated blobs -->
            <div
                class="animate-blob absolute top-1/4 -left-20 h-72 w-72 rounded-full bg-orange-400/20 blur-3xl dark:bg-orange-500/10"
                style="animation-delay: 0s"
            />
            <div
                class="animate-blob absolute top-1/3 right-0 h-96 w-96 rounded-full bg-teal-400/15 blur-3xl dark:bg-teal-500/8"
                style="animation-delay: 3s"
            />
            <div
                class="animate-blob absolute bottom-1/4 left-1/3 h-64 w-64 rounded-full bg-orange-300/15 blur-3xl dark:bg-orange-600/8"
                style="animation-delay: 6s"
            />
            <!-- Reactive particles canvas -->
            <!-- canvas moved to section level for reliable sizing -->
        </div>

        <!-- Reactive particles canvas -->
        <canvas
            ref="canvasRef"
            class="pointer-events-none absolute inset-0"
            style="z-index: 0"
        />

        <div
            class="relative z-10 mx-auto flex w-full max-w-4xl flex-1 flex-col items-center justify-center"
        >
            <div class="w-full text-center">
                <!-- Headline -->
                <h1
                    class="hero-stagger-1 mb-6 text-4xl leading-[1.05] font-black tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
                >
                    <span class="text-slate-900 dark:text-white"
                        >Your Research,</span
                    >
                    <br />
                    <span class="text-shimmer">Every Step.</span>
                </h1>

                <!-- Subtext -->
                <p
                    class="hero-stagger-2 mx-auto mb-10 max-w-lg text-lg leading-relaxed text-slate-600 sm:text-xl dark:text-slate-400"
                >
                    Track every milestone of your
                    <strong class="dark: text-white">student research</strong> —
                    from
                    <strong class="dark: text-white">title proposal</strong> and
                    <strong class="dark: text-white">chapter submissions</strong
                    >, through panel review and oral defense, all the way to
                    final publication.
                </p>

                <!-- CTAs -->
                <div
                    class="hero-stagger-3 mb-14 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <template v-if="!page.props.auth.user">
                        <Link v-if="canRegister" :href="register.url()">
                            <button
                                class="group flex items-center gap-2 rounded-xl bg-orange-500 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:scale-[1.02] hover:bg-orange-600 hover:shadow-orange-500/50 active:scale-[0.98]"
                            >
                                Get Started
                                <ArrowRight
                                    class="h-4 w-4 transition-transform group-hover:translate-x-1"
                                />
                            </button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="dashboard.url()">
                            <button
                                class="group flex items-center gap-2 rounded-xl bg-teal-500 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:scale-[1.02] hover:bg-teal-600 hover:shadow-orange-500/50"
                            >
                                Go to Dashboard
                                <ArrowRight
                                    class="h-4 w-4 transition-transform group-hover:translate-x-1"
                                />
                            </button>
                        </Link>
                    </template>
                </div>

                <!-- Stats row -->
                <div
                    ref="statsRef"
                    class="hero-stagger-4 mx-auto grid max-w-lg grid-cols-2 gap-3 sm:grid-cols-4"
                >
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="text-center"
                    >
                        <div
                            :class="[
                                'text-2xl font-black tabular-nums',
                                stat.color,
                            ]"
                        >
                            <template v-if="stat.key === 'papers'"
                                >{{ paperCount.toLocaleString()
                                }}{{ stat.suffix }}</template
                            >
                            <template v-else-if="stat.key === 'students'"
                                >{{ studentCount.toLocaleString()
                                }}{{ stat.suffix }}</template
                            >
                            <template v-else-if="stat.key === 'depts'"
                                >{{ deptCount }}{{ stat.suffix }}</template
                            >
                            <template v-else>{{ stat.value }}</template>
                        </div>
                        <div
                            class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-500"
                        >
                            {{ stat.label }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick track widget -->
        <div
            class="hero-stagger-5 relative z-10 mx-auto mt-14 w-full max-w-2xl px-1"
        >
            <div
                class="relative flex gap-2 rounded-2xl border border-slate-200/80 bg-white p-1.5 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900 dark:shadow-black/20"
            >
                <div class="flex flex-1 items-center gap-3 pl-3">
                    <Search
                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                    />
                    <input
                        v-model="trackingId"
                        @keyup.enter="searchPaper"
                        type="text"
                        placeholder="Enter tracking ID  (e.g. RP-XXXXXXXX)"
                        class="flex-1 bg-transparent py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none dark:text-slate-200 dark:placeholder-slate-600"
                    />
                </div>
                <button
                    @click="searchPaper"
                    :disabled="isSearching"
                    class="rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-semibold whitespace-nowrap text-white shadow-md shadow-orange-500/20 transition-all duration-200 hover:scale-[1.02] hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ isSearching ? 'Searching…' : 'Track Paper' }}
                </button>
            </div>
            <p
                v-if="trackingError"
                class="mt-2 text-center text-sm text-red-500"
            >
                {{ trackingError }}
            </p>
            <p
                class="mt-2 text-center text-xs text-slate-400 dark:text-slate-600"
            >
                No login required · Public tracking is anonymous
            </p>
        </div>

        <!-- Scroll indicator -->
        <div class="relative z-10 mt-16 flex justify-center">
            <button
                @click="scrollToFeatures"
                class="flex flex-col items-center gap-2 text-slate-400 transition-colors hover:text-orange-500"
            >
                <span class="text-xs font-medium">Explore features</span>
                <ChevronDown class="h-5 w-5 animate-bounce" />
            </button>
        </div>
    </section>
</template>
