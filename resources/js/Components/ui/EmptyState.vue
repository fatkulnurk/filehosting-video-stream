<script setup>
defineProps({
    title: { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="px-4 py-16 text-center">
        <p class="text-faint text-sm font-medium">{{ title }}</p>
        <p v-if="description" class="text-soft text-sm mt-1">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>