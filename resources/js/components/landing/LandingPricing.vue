<script setup lang="ts">
import { Check, Zap, Building2, Sparkles } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { Link } from '@inertiajs/vue3'
import { register, login } from '@/routes'

defineProps<{ canRegister: boolean }>()

const { target: sectionRef, isVisible } = useScrollReveal(0.1)

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
        ctaStyle: 'border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800',
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
        ctaStyle: 'text-white bg-gradient-to-r from-orange-500 to-orange-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 hover:from-orange-600 hover:to-orange-700',
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
        ctaStyle: 'border border-teal-200 dark:border-teal-700 text-teal-700 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/30',
        highlighted: false,
    },
]
</script>

<template>
    <section id="pricing" class="py-28 px-5 relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-slate-50 dark:bg-slate-950" />
        <!-- Decorative blobs -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-40 bg-gradient-to-b from-orange-100 to-transparent dark:from-orange-950/20 dark:to-transparent blur-2xl rounded-full -z-10" />

        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['text-center mb-16 reveal', { visible: isVisible }]"
            >
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold mb-6
                    bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-400
                    border border-orange-200/60 dark:border-orange-800/40">
                    Simple pricing
                </div>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-5">
                    <span class="text-slate-900 dark:text-white">Plans for every</span>
                    <span class="text-gradient"> researcher</span>
                </h2>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
                    Start free and scale as your research grows. No hidden fees, no surprises.
                </p>
            </div>

            <!-- Pricing cards -->
            <div :class="['grid grid-cols-1 md:grid-cols-3 gap-6 items-start reveal', { visible: isVisible }]">
                <div
                    v-for="(plan, i) in plans"
                    :key="plan.name"
                    :style="{ transitionDelay: `${i * 100}ms` }"
                    :class="[
                        'relative rounded-2xl p-8 transition-all duration-300',
                        plan.highlighted
                            ? 'bg-gradient-to-b from-white to-orange-50/30 dark:from-slate-900 dark:to-orange-950/20 border-2 border-orange-400/60 dark:border-orange-500/40 shadow-2xl shadow-orange-500/10 -translate-y-2 md:-translate-y-4'
                            : 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-sm hover:shadow-lg',
                    ]"
                >
                    <!-- Popular badge -->
                    <div v-if="plan.highlighted" class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <div class="px-4 py-1.5 rounded-full text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-teal-500 shadow-md shadow-orange-500/20 whitespace-nowrap">
                            Most Popular
                        </div>
                    </div>

                    <!-- Plan header -->
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <div :class="['w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center mb-3', plan.iconBg]">
                                <component :is="plan.icon" class="w-5 h-5 text-white" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ plan.name }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 leading-snug">{{ plan.description }}</p>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="mb-8">
                        <div class="flex items-end gap-1">
                            <span class="text-4xl font-black text-slate-900 dark:text-white">{{ plan.price }}</span>
                            <span v-if="plan.price !== 'Free' && plan.price !== 'Custom'" class="text-slate-500 dark:text-slate-400 text-sm mb-1.5">/ mo</span>
                        </div>
                        <div class="text-xs text-slate-400 dark:text-slate-600 mt-0.5">{{ plan.priceNote }}</div>
                    </div>

                    <!-- CTA -->
                    <Link :href="canRegister ? register.url() : login.url()">
                        <button :class="['w-full py-3 rounded-xl text-sm font-semibold transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] mb-8', plan.ctaStyle]">
                            {{ plan.cta }}
                        </button>
                    </Link>

                    <!-- Features -->
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-3">
                        <div v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm text-slate-600 dark:text-slate-400">
                            <Check :class="['w-4 h-4 shrink-0 mt-0.5', plan.highlighted ? 'text-orange-500' : 'text-teal-500']" />
                            {{ feature }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
