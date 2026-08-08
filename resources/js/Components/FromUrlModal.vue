<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { LinkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    directoryTree: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const urlsText = ref('');
const directoryId = ref('');
const submitting = ref(false);
const error = ref('');

const urls = computed(() =>
    urlsText.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== ''),
);

const flattenedTree = computed(() => {
    const rows = [];
    const walk = (dirs, ancestors = []) => {
        (dirs || []).forEach((dir) => {
            rows.push({ id: dir.id, label: [...ancestors, dir.name].join(' / ') });
            walk(dir.children || [], [...ancestors, dir.name]);
        });
    };
    walk(props.directoryTree);
    return rows;
});

const submit = async () => {
    if (urls.value.length === 0 || submitting.value) return;

    submitting.value = true;
    error.value = '';

    try {
        for (const url of urls.value) {
            await new Promise((resolve, reject) => {
                router.post(
                    route('files.store-url'),
                    { url, directory_id: directoryId.value || null },
                    {
                        preserveScroll: true,
                        onSuccess: resolve,
                        onError: (errors) => reject(new Error(Object.values(errors).join(', ') || 'Failed')),
                    },
                );
            });
        }
        router.visit(route('cloud-downloads.index'));
    } catch (e) {
        error.value = e.message;
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="label">File URL(s)</label>
            <textarea
                v-model="urlsText"
                rows="5"
                placeholder="https://example.com/video.mp4&#10;https://example.com/photo.jpg"
                class="input font-mono"
            ></textarea>
            <p class="hint mt-1">Paste one URL per line. Each will be downloaded to your storage.</p>
        </div>

        <div v-if="flattenedTree.length > 0">
            <label class="label">Upload to directory</label>
            <select v-model="directoryId" class="input">
                <option value="">Root</option>
                <option v-for="dir in flattenedTree" :key="dir.id" :value="dir.id">{{ dir.label }}</option>
            </select>
        </div>

        <div v-if="error" class="alert alert-danger-soft">
            {{ error }}
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" @click="$emit('close')" class="btn btn-ghost">Cancel</button>
            <button
                type="button"
                @click="submit"
                :disabled="urls.length === 0 || submitting"
                class="btn btn-primary btn-md"
            >
                <LinkIcon class="w-4 h-4" /> {{ submitting ? 'Queueing...' : `Queue ${urls.length} URL(s)` }}
            </button>
        </div>
    </div>
</template>