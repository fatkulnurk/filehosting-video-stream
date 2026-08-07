<script setup>
import { ref, onMounted } from 'vue';

defineProps({
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const inputEl = ref(null);

onMounted(() => {
    if (inputEl.value?.matches(':autofill')) {
        // noop, keep simple
    }
});
</script>

<template>
    <input
        ref="inputEl"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
        :class="{ 'bg-surface-soft opacity-70': disabled }"
    />
</template>