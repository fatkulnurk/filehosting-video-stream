<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    directoryTree: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const magnet = ref('');
const file = ref(null);
const directoryId = ref('');
const submitting = ref(false);
const error = ref('');

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

const hasInput = computed(() => magnet.value.trim() !== '' || file.value !== null);

const onFileChange = (event) => {
    const selected = event.target.files?.[0] || null;
    if (selected && !selected.name.endsWith('.torrent')) {
        error.value = 'Please select a .torrent file.';
        file.value = null;
        event.target.value = '';
        return;
    }
    error.value = '';
    file.value = selected;
};

const submit = () => {
    if (!hasInput.value || submitting.value) return;

    submitting.value = true;
    error.value = '';

    const payload = new FormData();
    if (magnet.value.trim()) payload.append('magnet', magnet.value.trim());
    if (file.value) payload.append('torrent_file', file.value);
    if (directoryId.value) payload.append('directory_id', directoryId.value);

    router.post(route('cloud-downloads.torrent'), payload, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onError: (errors) => {
            error.value = Object.values(errors).join(', ') || 'Failed to queue torrent.';
            submitting.value = false;
        },
        onFinish: () => (submitting.value = false),
    });
};
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="label">Magnet link</label>
            <input
                v-model="magnet"
                type="text"
                placeholder="magnet:?xt=urn:btih:…"
                class="input font-mono"
            />
        </div>

        <div class="hint text-center">or</div>

        <div>
            <label class="label">.torrent file</label>
            <input
                type="file"
                accept=".torrent"
                @change="onFileChange"
                class="block w-full text-sm text-soft file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium hover:file:bg-brand-100"
            />
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
            <button type="button" @click="emit('close')" class="btn btn-ghost">Cancel</button>
            <button
                type="button"
                @click="submit"
                :disabled="!hasInput || submitting"
                class="btn btn-primary btn-md"
            >
                <ArrowDownTrayIcon class="w-4 h-4" /> {{ submitting ? 'Queueing…' : 'Download Torrent' }}
            </button>
        </div>
    </div>
</template>
