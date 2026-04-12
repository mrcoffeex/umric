<template>
  <div class="neumorph-card" :style="cardStyle">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  hover?: boolean
  padding?: string
  isDark?: boolean
  raised?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  hover: true,
  padding: '1.5rem',
  isDark: false,
  raised: false,
})

const cardStyle = computed(() => ({
  padding: props.padding,
}))
</script>

<style scoped>
.neumorph-card {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  border-radius: 20px;
  box-shadow:
    9px 9px 16px rgba(0, 0, 0, 0.1),
    -8px -8px 16px rgba(255, 255, 255, 0.7),
    inset 1px 1px 0 rgba(255, 255, 255, 0.2);
  transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
  border: 1px solid rgba(255, 255, 255, 0.5);
}

.neumorph-card:hover {
  box-shadow:
    4px 4px 10px rgba(0, 0, 0, 0.08),
    -4px -4px 10px rgba(255, 255, 255, 0.8),
    inset 1px 1px 0 rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

/* Dark mode — driven by html.dark set by useAppearance, avoids SSR mismatch */
:global(.dark) .neumorph-card {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  box-shadow:
    9px 9px 16px rgba(0, 0, 0, 0.4),
    -8px -8px 16px rgba(255, 255, 255, 0.05),
    inset 1px 1px 0 rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.1);
}

:global(.dark) .neumorph-card:hover {
  box-shadow:
    4px 4px 10px rgba(0, 0, 0, 0.5),
    -4px -4px 10px rgba(255, 255, 255, 0.08),
    inset 1px 1px 0 rgba(255, 255, 255, 0.15);
}
</style>
