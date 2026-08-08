<script setup>
defineProps({
    label: { type: String, required: true },
    htmlFor: { type: String, default: '' },
    required: { type: Boolean, default: false },
    hint: { type: String, default: '' },
});
</script>

<template>
    <label :for="htmlFor" class="label">
        {{ label }}<span v-if="required" class="text-danger-600"> *</span>
    </label>
    <p v-if="hint" class="hint mb-1 -mt-1">{{ hint }}</p>
</template>