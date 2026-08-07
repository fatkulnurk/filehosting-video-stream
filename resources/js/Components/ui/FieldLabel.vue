<script setup>
defineProps({
    label: { type: String, required: true },
    htmlFor: { type: String, default: '' },
    required: { type: Boolean, default: false },
    hint: { type: String, default: '' },
});
</script>

<template>
    <label :for="htmlFor" class="block text-sm font-medium text-ink-body mb-1">
        {{ label }}<span v-if="required" class="text-danger-600"> *</span>
    </label>
    <p v-if="hint" class="text-xs text-ink-faint mb-1 -mt-1">{{ hint }}</p>
</template>