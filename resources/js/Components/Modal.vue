<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxWidth: { type: String, default: 'lg' },
});

const emit = defineEmits(['close']);

const widthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="$emit('close')"></div>
                <div :class="['relative panel shadow-xl w-full', widthClass[maxWidth] || 'max-w-lg']">
                    <div class="flex items-center justify-between px-5 py-4 border-b">
                        <h3 class="text-lg font-semibold text-ink">{{ title }}</h3>
                        <button type="button" @click="$emit('close')" class="text-ink-faint hover:text-ink-muted text-2xl leading-none">&times;</button>
                    </div>
                    <div class="p-5">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
