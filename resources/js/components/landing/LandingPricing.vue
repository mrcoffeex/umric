<script setup lang="ts">
import { Check, Zap, Building2, Sparkles } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { Link } from '@inertiajs/vue3';
import { register, login } from '@/routes';

defineProps<{ canRegister: boolean }>();

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const plans = [
    {
        name: 'Starter',
        price: 'Free',
        priceNote: 'forever',
        description: 'Perfect for individual researchers getting started.',
        icon: Sparkles,
        iconBg: 'from-slate-400 to-slate-500',
        features: [
            'Up to 5 research papers',
            'Basic tracking',
            'Public tracking links',
            'PDF uploads (50 MB)',
            'Email support',
        ],
        cta: 'Get Started Free',
        ctaStyle:
            'border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800',
        highlighted: false,
    },
    {
        name: 'Professional',
        price: '$29',
        priceNote: 'per month',
        description: 'For active researchers with multiple ongoing papers.',
        icon: Zap,
        iconBg: 'from-orange-500 to-teal-500',
        features: [
            'Unlimited papers',
            'Advanced analytics',
            'Multi-author collaboration',
            'PDF uploads (2 GB)',
            'Citation export (BibTeX, APA, MLA)',
            'Priority support',
            'API access',
        ],
        cta: 'Start Free Trial',
        ctaStyle:
            'text-white bg-gradient-to-r from-orange-500 to-orange-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 hover:from-orange-600 hover:to-orange-700',
        highlighted: true,
    },
    {
        name: 'Enterprise',
        price: 'Custom',
        priceNote: 'contact us',
        description: 'For institutions managing large research programmes.',
        icon: Building2,
        iconBg: 'from-teal-500 to-teal-600',
        features: [
            'Everything in Professional',
            'Custom domain',
            'SSO / SAML integration',
            'Dedicated account manager',
            'SLA guarantee',
            'Custom integrations',
            'Audit logs',
        ],
        cta: 'Contact Sales',
        ctaStyle:
            'border border-teal-200 dark:border-teal-700 text-teal-700 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/30',
        highlighted: false,
    },
];
</script>

<template>
    <section id="pricing" class="relative overflow-hidden px-5 py-28">
        <div class="absolute inset-0 -z-10 bg-slate-50 dark:bg-slate-950" />
        <!-- Decorative blobs -->
        <div
            class="absolute top-0 left-1/2 -z-10 h-40 w-[600px] -translate-x-1/2 rounded-full bg-gradient-to-b from-orange-100 to-transparent blur-2xl dark:from-orange-950/20 dark:to-transparent"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-16 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-700 dark:border-orange-800/40 dark:bg-orange-950/50 dark:text-orange-400"
                >
                    Simple pricing
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >Plans for every</span
                    >
                    <span class="text-gradient"> researcher</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg text-slate-500 dark:text-slate-400"
                >
                    Start free and scale as your research grows. No hidden fees,
                    no surprises.
                </p>
            </div>

            <!-- Pricing cards -->
            <div
                :class="[
                    'reveal grid grid-cols-1 items-start gap-6 md:grid-cols-3',
                    { visible: isVisible },
                ]"
            >
                <div
                    v-for="(plan, i) in plans"
                    :key="plan.name"
                    :style="{ transitionDelay: `${i * 100}ms` }"
                    :class="[
                        'relative rounded-2xl p-8 transition-all duration-300',
                        plan.highlighted
                            ? '-translate-y-2 border-2 border-orange-400/60 bg-gradient-to-b from-white to-orange-50/30 shadow-2xl shadow-orange-500/10 md:-translate-y-4 dark:border-orange-500/40 dark:from-slate-900 dark:to-orange-950/20'
                            : 'border border-slate-200/80 bg-white shadow-sm hover:shadow-lg dark:border-slate-800/80 dark:bg-slate-900',
                    ]"
                >
                    <!-- Popular badge -->
                    <div
                        v-if="plan.highlighted"
                        class="absolute -top-4 left-1/2 -translate-x-1/2"
                    >
                        <div
                            class="rounded-full bg-gradient-to-r from-orange-500 to-teal-500 px-4 py-1.5 text-xs font-bold whitespace-nowrap text-white shadow-md shadow-orange-500/20"
                        >
                            Most Popular
                        </div>
                    </div>

                    <!-- Plan header -->
                    <div class="mb-6 flex items-start justify-between">
                        <div>
                            <div
                                :class="[
                                    'mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br',
                                    plan.iconBg,
                                ]"
                            >
                                <component
                                    :is="plan.icon"
                                    class="h-5 w-5 text-white"
                                />
                            </div>
                            <h3
                                class="text-lg font-bold text-slate-900 dark:text-slate-100"
                            >
                                {{ plan.name }}
                            </h3>
                            <p
                                class="mt-1 text-sm leading-snug text-slate-500 dark:text-slate-400"
                            >
                                {{ plan.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="mb-8">
                        <div class="flex items-end gap-1">
                            <span
                                class="text-4xl font-black text-slate-900 dark:text-white"
                                >{{ plan.price }}</span
                            >
                            <span
                                v-if="
                                    plan.price !== 'Free' &&
                                    plan.price !== 'Custom'
                                "
                                class="mb-1.5 text-sm text-slate-500 dark:text-slate-400"
                                >/ mo</span
                            >
                        </div>
                        <div
                            class="mt-0.5 text-xs text-slate-400 dark:text-slate-600"
                        >
                            {{ plan.priceNote }}
                        </div>
                    </div>

                    <!-- CTA -->
                    <Link :href="canRegister ? register.url() : login.url()">
                        <button
                            :class="[
                                'mb-8 w-full rounded-xl py-3 text-sm font-semibold transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]',
                                plan.ctaStyle,
                            ]"
                        >
                            {{ plan.cta }}
                        </button>
                    </Link>

                    <!-- Features -->
                    <div
                        class="space-y-3 border-t border-slate-100 pt-6 dark:border-slate-800"
                    >
                        <div
                            v-for="feature in plan.features"
                            :key="feature"
                            class="flex items-start gap-2.5 text-sm text-slate-600 dark:text-slate-400"
                        >
                            <Check
                                :class="[
                                    'mt-0.5 h-4 w-4 shrink-0',
                                    plan.highlighted
                                        ? 'text-orange-500'
                                        : 'text-teal-500',
                                ]"
                            />
                            {{ feature }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
