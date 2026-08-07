<script setup>
import { computed, ref } from 'vue';
import { ArrowUpTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';

const props = defineProps({
    modelValue: { type: [File, Array], default: null },
    multiple: Boolean,
    accept: String,
    label: String,
    hint: String,
    error: String,
    compact: Boolean,
    disabled: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const inputEl = ref(null);
const dragging = ref(false);

const fileList = computed(() => {
    if (!props.modelValue) return [];
    return Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue];
});
const openPicker = () => {
    if (props.disabled) return;
    inputEl.value?.click();
};

const selectFiles = (event) => {
    const picked = Array.from(event.target.files || []);
    if (picked.length === 0) return;
    emit('update:modelValue', props.multiple ? [...fileList.value, ...picked] : picked[0]);
    event.target.value = '';
};

const onDragOver = (event) => {
    event.preventDefault();
    if (!props.disabled) dragging.value = true;
};

const onDragLeave = () => {
    dragging.value = false;
};

const dropFiles = (event) => {
    event.preventDefault();
    dragging.value = false;
    const dropped = Array.from(event.dataTransfer.files || []);
    if (dropped.length === 0) return;
    emit('update:modelValue', props.multiple ? [...fileList.value, ...dropped] : dropped[0]);
};

const removeFile = (index) => {
    if (!props.multiple) {
        emit('update:modelValue', null);
        return;
    }
    emit('update:modelValue', fileList.value.filter((_, i) => i !== index));
};
</script>

<template>
    <div class="space-y-3">
        <label v-if="label" class="block text-sm font-medium text-ink-body">{{ label }}</label>

        <div
            class="border-2 border-dashed rounded-xl text-center cursor-pointer transition flex flex-col items-center justify-center"
            :class="[
                compact ? 'p-4' : 'p-6',
                dragging ? 'border-brand-500 bg-brand-50' : 'border-line hover:border-brand-400 hover:bg-surface-muted',
                disabled ? 'opacity-50 cursor-not-allowed' : '',
            ]"
            @dragover.prevent="onDragOver"
            @dragleave="onDragLeave"
            @drop.prevent="dropFiles"
            @click="openPicker"
        >
            <input
                ref="inputEl"
                type="file"
                class="hidden"
                :multiple="multiple"
                :accept="accept"
                :disabled="disabled"
                @change="selectFiles"
            />
            <ArrowUpTrayIcon class="w-8 h-8 mx-auto text-ink-faint" />
            <p class="mt-2 text-sm text-ink-muted">
                <span class="text-brand-700 font-medium">Browse</span> or drag &amp; drop
            </p>
            <p v-if="hint" class="text-xs text-ink-faint mt-1">{{ hint }}</p>
        </div>

        <ul v-if="fileList.length > 0" class="space-y-2">
            <li v-for="(file, i) in fileList" :key="i">
                <div class="flex items-center justify-between bg-surface-muted rounded-lg px-3 py-2 text-sm">
                    <span class="truncate text-ink-body">{{ file.name }}</span>
                    <div class="flex items-center gap-2 shrink-0 ml-2">
                        <span class="text-xs text-ink-faint">{{ formatBytes(file.size) }}</span>
                        <button type="button" @click="removeFile(i)" class="text-ink-faint hover:text-danger-500" :disabled="disabled">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </li>
        </ul>

        <p v-if="error" class="text-sm text-danger-600">{{ error }}</p>
    </div>
</template>
