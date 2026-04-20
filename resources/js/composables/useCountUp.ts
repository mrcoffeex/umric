import { ref, unref, watch } from 'vue';
import type { MaybeRef, Ref } from 'vue';

export function useCountUp(
    end: MaybeRef<number>,
    trigger: Ref<boolean>,
    duration = 1800,
): Ref<number> {
    const current = ref(0);

    watch(trigger, (visible) => {
        if (!visible) {
            return;
        }

        const target = unref(end);
        const start = performance.now();

        function tick(now: number) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            current.value = Math.round(eased * target);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        }

        requestAnimationFrame(tick);
    });

    return current;
}
