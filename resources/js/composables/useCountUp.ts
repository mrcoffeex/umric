import { ref, watch } from 'vue';
import type { Ref } from 'vue';

export function useCountUp(
    end: number,
    trigger: Ref<boolean>,
    duration = 1800,
): Ref<number> {
    const current = ref(0);

    watch(trigger, (visible) => {
        if (!visible) {
            return;
        }

        const start = performance.now();

        function tick(now: number) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            current.value = Math.round(eased * end);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        }

        requestAnimationFrame(tick);
    });

    return current;
}
