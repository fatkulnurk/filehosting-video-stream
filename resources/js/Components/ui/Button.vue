<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost
    size: { type: String, default: 'md' }, // sm | md
    type: { type: String, default: 'button' },
    href: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const variants = computed(() => ({
    primary:
        'inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg disabled:opacity-50',
    secondary:
        'inline-flex items-center justify-center gap-1.5 bg-surface border border-line hover:bg-surface-muted text-ink-body font-medium rounded-lg disabled:opacity-50',
    danger:
        'inline-flex items-center justify-center gap-1.5 bg-danger-50 text-danger-600 border border-danger-200 hover:bg-danger-100 font-medium rounded-lg disabled:opacity-50',
    ghost:
        'inline-flex items-center justify-center gap-1.5 text-ink-muted hover:bg-surface-soft hover:text-ink-strong font-medium rounded-lg disabled:opacity-50',
}));

const sizes = computed(() => ({
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
}));
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :type="href ? undefined : type"
        :href="href"
        :disabled="disabled"
        :class="[variants[variant], sizes[size]]"
        @click="emit('click')"
    >
        <slot />
    </component>
</template>