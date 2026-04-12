<template>
  <button
    :class="{
      'neu-btn': true,
      'neu-btn-primary': variant === 'primary',
      'neu-btn-secondary': variant === 'secondary',
      'neu-btn-danger': variant === 'danger',
      'neu-btn-success': variant === 'success',
      'dark': isDark,
      'w-full': fullWidth,
      [size]: true,
    }"
    :disabled="disabled"
  >
    <span class="flex items-center justify-center gap-2">
      <slot name="icon" />
      <slot />
    </span>
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'success'
  size?: 'sm' | 'md' | 'lg'
  fullWidth?: boolean
  disabled?: boolean
  isDark?: boolean
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  fullWidth: false,
  disabled: false,
  isDark: false,
})
</script>

<style scoped>
.neu-btn {
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
  box-shadow:
    5px 5px 12px rgba(0, 0, 0, 0.1),
    -5px -5px 12px rgba(255, 255, 255, 0.7);
  position: relative;
  overflow: hidden;
}

.neu-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.neu-btn:active:not(:disabled) {
  box-shadow:
    2px 2px 6px rgba(0, 0, 0, 0.08),
    -2px -2px 6px rgba(255, 255, 255, 0.8),
    inset 2px 2px 6px rgba(0, 0, 0, 0.1);
}

/* Sizes */
.sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.md {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.lg {
  padding: 1rem 2rem;
  font-size: 1.125rem;
}

/* Variants */
.neu-btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.neu-btn-primary:hover:not(:disabled) {
  box-shadow:
    7px 7px 16px rgba(102, 126, 234, 0.3),
    -7px -7px 16px rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
}

.neu-btn-secondary {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  color: #333;
}

.neu-btn-secondary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.neu-btn-danger {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.neu-btn-danger:hover:not(:disabled) {
  box-shadow:
    7px 7px 16px rgba(245, 87, 108, 0.3),
    -7px -7px 16px rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
}

.neu-btn-success {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.neu-btn-success:hover:not(:disabled) {
  box-shadow:
    7px 7px 16px rgba(17, 153, 142, 0.3),
    -7px -7px 16px rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
}

/* Dark mode */
.neu-btn.dark {
  box-shadow:
    5px 5px 12px rgba(0, 0, 0, 0.3),
    -5px -5px 12px rgba(255, 255, 255, 0.1);
}

.neu-btn.dark:active:not(:disabled) {
  box-shadow:
    2px 2px 6px rgba(0, 0, 0, 0.4),
    -2px -2px 6px rgba(255, 255, 255, 0.15),
    inset 2px 2px 6px rgba(0, 0, 0, 0.2);
}
</style>
