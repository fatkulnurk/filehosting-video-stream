<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="flex items-start justify-between mb-6 gap-3 flex-wrap">
        <div>
            <h1 class="page-title">{{ title }}</h1>
            <p v-if="subtitle" class="text-soft mt-1 text-sm">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <slot name="actions" />
        </div>
    </div>
</template>