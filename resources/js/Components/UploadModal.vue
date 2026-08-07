<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import FileInput from '@/Components/FileInput.vue';

const props = defineProps({
    directoryId: { type: String, default: null },
    quota: { type: Object, default: () => ({ used: 0, limit: null }) },
});

const emit = defineEmits(['close']);

const files = ref([]);
const uploading = ref(false);
const progress = ref({});
const results = ref(null);

const submit = () => {
    if (files.value.length === 0) return;

    uploading.value = true;
    results.value = null;

    const form = new FormData();
    files.value.forEach((f) => form.append('files[]', f));
    form.append('directory_id', props.directoryId || '');

    router.post(route('files.store'), form, {
        forceFormData: true,
        onUploadProgress: (e) => {
            if (e.total) {
                progress.value = Math.round((e.loaded / e.total) * 100);
            }
        },
        onSuccess: () => {
            uploading.value = false;
            emit('close');
        },
        onError: (errors) => {
            uploading.value = false;
            results.value = {
                success: false,
                message: Object.values(errors).flat()[0] || 'Upload failed.',
            };
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <FileInput v-model="files" multiple hint="Max 100 MB per file" />

        <div v-if="uploading" class="space-y-1">
            <div class="w-full bg-surface-soft rounded-full h-2">
                <div class="bg-brand-600 h-2 rounded-full transition-all" :style="{ width: progress + '%' }"></div>
            </div>
            <p class="text-xs text-ink-soft text-center">{{ progress }}% uploading...</p>
        </div>

        <div v-if="results && results.success === false" class="bg-danger-50 border border-danger-200 text-danger-600 px-4 py-3 rounded-lg text-sm">
            {{ results.message || 'Upload failed. Check file size and quota.' }}
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
            <button type="button" @click="submit" :disabled="files.length === 0 || uploading"
                class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg disabled:opacity-50 font-medium">
                Upload {{ files.length }} file(s)
            </button>
        </div>
    </div>
</template>
