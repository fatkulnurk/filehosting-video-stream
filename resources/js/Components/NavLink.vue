<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    active: { type: Boolean, default: false },
});
</script>

<template>
    <Link
        :href="href"
        :class="active
            ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-brand-800 text-white text-sm font-medium'
            : 'flex items-center gap-3 px-3 py-2 rounded-lg text-brand-200 hover:bg-brand-800 hover:text-white text-sm font-medium'"
    >
        <slot />
    </Link>
</template>
