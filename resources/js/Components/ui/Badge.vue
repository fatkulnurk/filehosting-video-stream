<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'neutral' }, // neutral | success | danger | warning | info | accent | brand
    size: { type: String, default: 'sm' },
});

const variants = computed(() => ({
    neutral: 'badge-neutral',
    success: 'badge-success',
    danger: 'badge-danger',
    warning: 'badge-warning',
    info: 'badge-info',
    accent: 'badge-accent',
    brand: 'badge-brand',
}));

const sizes = computed(() => ({
    sm: 'badge-sm',
    md: 'badge-md',
}));
</script>

<template>
    <span :class="['badge', variants[variant], sizes[size]]">
        <slot />
    </span>
</template>