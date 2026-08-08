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
            <p class="text-xs text-soft text-center">{{ progress }}% uploading...</p>
        </div>

        <div v-if="results && results.success === false" class="alert alert-danger-soft">
            {{ results.message || 'Upload failed. Check file size and quota.' }}
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" @click="$emit('close')" class="btn btn-ghost">Cancel</button>
            <button type="button" @click="submit" :disabled="files.length === 0 || uploading" class="btn btn-primary btn-md">
                Upload {{ files.length }} file(s)
            </button>
        </div>
    </div>
</template>
