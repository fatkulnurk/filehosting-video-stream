<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'neutral' }, // neutral | success | danger | warning | info | accent | brand
    size: { type: String, default: 'sm' },
});

const variants = computed(() => ({
    neutral: 'bg-surface-soft text-ink-soft',
    success: 'bg-success-50 text-success-600',
    danger: 'bg-danger-50 text-danger-600',
    warning: 'bg-warning-50 text-warning-600',
    info: 'bg-info-50 text-info-600',
    accent: 'bg-accent-50 text-accent-600',
    brand: 'bg-brand-50 text-brand-700',
}));

const sizes = computed(() => ({
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-xs',
}));
</script>

<template>
    <span :class="['inline-flex items-center rounded-full font-medium', variants[variant], sizes[size]]">
        <slot />
    </span>
</template>