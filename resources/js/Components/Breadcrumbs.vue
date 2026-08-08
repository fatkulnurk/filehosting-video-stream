<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <nav class="flex items-center flex-wrap gap-1 text-sm text-soft">
        <Link :href="route('files.index')" class="hover:text-brand-800">Home</Link>
        <template v-for="(item, i) in items" :key="item.id">
            <span class="text-ink-fainter">/</span>
            <Link
                v-if="i < items.length - 1"
                :href="route('files.index', { directory: item.id })"
                class="hover:text-brand-800"
            >{{ item.name }}</Link>
            <span v-else class="font-medium text-ink-strong">{{ item.name }}</span>
        </template>
    </nav>
</template>
