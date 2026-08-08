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
    primary: 'btn btn-primary',
    secondary: 'btn btn-secondary',
    danger: 'btn btn-danger',
    ghost: 'btn btn-ghost',
}));

const sizes = computed(() => ({
    sm: 'btn-sm',
    md: 'btn-md',
}));
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :type="href ? undefined : type"
        :href="href"
        :disabled="disabled"
        :class="['btn', variants[variant], sizes[size]]"
        @click="emit('click')"
    >
        <slot />
    </component>
</template>