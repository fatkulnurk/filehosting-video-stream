<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';
import {
    ArrowDownTrayIcon,
    DocumentIcon,
    PencilIcon,
    TrashIcon,
    GlobeAltIcon,
} from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ file: Object, streamUrl: String, subtitles: Array, stats: Object, versions: Array });

const showEdit = ref(false);
const editForm = reactive({ name: '', is_public: true });
const media = props.file.media?.find((m) => m.collection_name === 'default') || props.file.media?.[0];
const isVideo = media && String(media.mime_type).startsWith('video/');
const isImage = media && String(media.mime_type).startsWith('image/');

const openEdit = () => {
    editForm.name = props.file.name;
    editForm.is_public = !!props.file.is_public;
    showEdit.value = true;
};

const submitEdit = () => {
    router.patch(route('admin.files.update', props.file.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => (showEdit.value = false),
    });
};

const trash = async () => {
    const ok = await confirm({ message: `Move "${props.file.name}" to trash?` });
    if (!ok) return;
    router.delete(route('admin.files.destroy', props.file.id), { preserveScroll: true });
};

const forceDelete = async () => {
    const ok = await confirm({
        message: `Permanently delete "${props.file.name}"? This cannot be undone.`,
        danger: true,
    });
    if (!ok) return;
    router.delete(route('admin.files.forceDestroy', props.file.id));
};
</script>

<template>
    <div>
        <div class="mb-4">
            <Link :href="route('admin.files.index')" class="text-sm text-brand-700 hover:underline">← Back to Files</Link>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-surface rounded-xl shadow p-5">
                    <div v-if="isVideo" class="mb-4">
                        <VideoPlayer :src="streamUrl" :type="media.mime_type" :subtitles="subtitles || []" />
                    </div>
                    <div v-else-if="isImage" class="mb-4">
                        <img :src="route('stream.show', file.id)" :alt="file.name" class="rounded-lg max-h-96 mx-auto" />
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <DocumentIcon v-if="!isVideo && !isImage" class="w-8 h-8 text-ink-faint" />
                            <div>
                                <h1 class="text-xl font-bold text-ink">{{ file.name }}</h1>
                                <p class="text-sm text-ink-soft">
                                    {{ file.user?.name || 'Unknown' }} — {{ file.user?.email || '' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('files.download', file.id)" title="Download"
                                class="bg-brand-600 hover:bg-brand-700 text-white p-2 rounded-lg">
                                <ArrowDownTrayIcon class="w-5 h-5" />
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-center">
                        <div class="bg-surface-muted rounded-lg p-3">
                            <div class="text-sm text-ink-soft">Size</div>
                            <div class="font-semibold text-ink-strong">{{ formatBytes(media?.size || 0) }}</div>
                        </div>
                        <div class="bg-surface-muted rounded-lg p-3">
                            <div class="text-sm text-ink-soft">Type</div>
                            <div class="font-semibold text-ink-strong truncate">{{ media?.mime_type || '—' }}</div>
                        </div>
                        <div class="bg-surface-muted rounded-lg p-3">
                            <div class="text-sm text-ink-soft">Views</div>
                            <div class="font-semibold text-ink-strong">{{ (file.views || 0).toLocaleString() }}</div>
                        </div>
                        <div class="bg-surface-muted rounded-lg p-3">
                            <div class="text-sm text-ink-soft">Downloads</div>
                            <div class="font-semibold text-ink-strong">{{ (file.downloads || 0).toLocaleString() }}</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-6">
                        <button type="button" @click="openEdit" class="inline-flex items-center gap-1.5 text-sm bg-surface border border-line hover:bg-surface-muted px-3 py-2 rounded-lg font-medium">
                            <PencilIcon class="w-4 h-4" /> Edit
                        </button>
                        <button type="button" @click="trash" class="inline-flex items-center gap-1.5 text-sm bg-warning-50 text-warning-700 border border-warning-200 hover:bg-warning-100 px-3 py-2 rounded-lg font-medium">
                            <TrashIcon class="w-4 h-4" /> Move to Trash
                        </button>
                        <button type="button" @click="forceDelete" class="inline-flex items-center gap-1.5 text-sm bg-danger-50 text-danger-600 border border-danger-200 hover:bg-danger-100 px-3 py-2 rounded-lg font-medium">
                            <TrashIcon class="w-4 h-4" /> Delete Permanently
                        </button>
                    </div>
                </div>

                <div v-if="versions.length > 0" class="bg-surface rounded-xl shadow p-5">
                    <h2 class="text-lg font-semibold text-ink mb-4">Versions</h2>
                    <ul class="space-y-2">
                        <li v-for="v in versions" :key="v.id" class="flex items-center justify-between bg-surface-muted rounded-lg px-4 py-3 text-sm">
                            <div>
                                <span class="font-medium text-ink-strong">v{{ v.version }}</span>
                                <span class="text-ink-soft ml-2">{{ v.name }} ({{ formatBytes(v.size) }})</span>
                                <div class="text-xs text-ink-faint mt-0.5">{{ v.changelog || 'No changelog' }}</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-surface rounded-xl shadow p-5">
                    <h2 class="text-lg font-semibold text-ink mb-4">Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-ink-soft">Status</span>
                            <span :class="file.is_public ? 'text-success-600' : 'text-ink-muted'">{{ file.is_public ? 'Public' : 'Private' }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-ink-soft">Trashed</span>
                            <span class="font-medium">{{ file.deleted_at ? 'Yes' : 'No' }}</span>
                        </div>
                        <div v-if="file.directory" class="flex justify-between"><span class="text-ink-soft">Directory</span>
                            <span class="font-medium text-right">{{ file.directory.path }}</span>
                        </div>
                        <div v-if="file.expires_at" class="flex justify-between"><span class="text-ink-soft">Expires</span>
                            <span class="font-medium">{{ new Date(file.expires_at).toLocaleDateString() }}</span>
                        </div>
                        <div v-if="file.download_limit" class="flex justify-between">
                            <span class="text-ink-soft">Download limit</span>
                            <span class="font-medium">{{ file.download_count }} / {{ file.download_limit }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-ink-soft">Uploaded</span>
                            <span class="font-medium">{{ new Date(file.created_at).toLocaleString() }}</span>
                        </div>
                    </dl>
                </div>

                <div class="bg-surface rounded-xl shadow p-5">
                    <h2 class="text-lg font-semibold text-ink mb-4">Subtitles</h2>
                    <ul v-if="subtitles.length > 0" class="space-y-1 text-sm text-ink-muted">
                        <li v-for="t in subtitles" :key="t.id">{{ t.label }} <span class="text-xs text-ink-faint">({{ t.srclang }})</span></li>
                    </ul>
                    <p v-else class="text-sm text-ink-faint">No subtitles.</p>
                </div>
            </div>
        </div>

        <!-- Edit modal -->
        <Modal :show="showEdit" title="Edit File" @close="showEdit = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                    <input v-model="editForm.name" type="text" placeholder="File name" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <label class="flex items-center text-sm text-ink-body">
                    <input v-model="editForm.is_public" type="checkbox" class="rounded mr-2" />
                    Public (accessible via link)
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showEdit = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
