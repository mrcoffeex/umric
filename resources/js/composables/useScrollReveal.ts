import { useIntersectionObserver } from '@vueuse/core';
import { ref } from 'vue';
import type { Ref } from 'vue';

export function useScrollReveal(threshold = 0.15): {
    target: Ref<HTMLElement | null>;
    isVisible: Ref<boolean>;
} {
    const target = ref<HTMLElement | null>(null);
    const isVisible = ref(false);

    useIntersectionObserver(
        target,
        ([entry]) => {
            if (entry?.isIntersecting) {
                isVisible.value = true;
            }
        },
        { threshold },
    );

    return { target, isVisible };
}
