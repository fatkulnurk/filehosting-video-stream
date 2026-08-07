<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import FileInput from '@/Components/FileInput.vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';
import Card from '@/Components/ui/Card.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';
import {
    ArrowDownTrayIcon,
    LinkIcon,
    CodeBracketIcon,
    StarIcon,
    ArrowUpTrayIcon,
    ArrowPathIcon,
    ShareIcon,
    Cog6ToothIcon,
    DocumentIcon,
    TrashIcon,
    PencilIcon,
    EyeIcon,
    FilmIcon,
} from '@heroicons/vue/24/outline';
defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({
    file: Object,
    streamUrl: String,
    subtitles: Array,
    isFavorited: Boolean,
    stats: Object,
    versions: Array,
});

const showShareModal = ref(false);
const showSettingsModal = ref(false);
const showVersionModal = ref(false);
const copySuccess = ref('');

const shareForm = ref({ shared_with: '', permission: 'view', password: '', expires_at: '', download_limit: '' });
const settingsForm = ref({ name: '', is_public: true, expires_at: '', password: '', download_limit: '' });
const versionForm = ref({ changelog: '' });
const versionFile = ref(null);
const media = props.file.media?.find((m) => m.collection_name === 'default') || props.file.media?.[0];
const isVideo = media && String(media.mime_type).startsWith('video/');
const isImage = media && String(media.mime_type).startsWith('image/');

const subtitleForm = ref({ language: 'en', label: '', default: false });
const subtitleFile = ref(null);

const submitSubtitle = () => {
    if (!subtitleFile.value) return;
    const form = new FormData();
    form.append('subtitle', subtitleFile.value);
    form.append('language', subtitleForm.value.language);
    form.append('label', subtitleForm.value.label);
    form.append('default', subtitleForm.value.default ? '1' : '0');
    router.post(route('files.subtitles.store', props.file.id), form, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            subtitleFile.value = null;
            subtitleForm.value = { language: 'en', label: '', default: false };
        },
    });
};

const deleteSubtitle = async (track) => {
    const ok = await confirm({ message: `Delete subtitle "${track.label}"?` });
    if (!ok) return;
    router.delete(route('files.subtitles.destroy', [props.file.id, track.id]), { preserveScroll: true });
};

const copyText = (text, msg) => {
    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = msg;
        setTimeout(() => (copySuccess.value = ''), 2000);
    });
};

const toggleFavorite = () => {
    router.post(route('files.favorite', props.file.id), {}, { preserveScroll: true });
};

const openShare = () => {
    shareForm.value = { shared_with: '', permission: 'view', password: '', expires_at: '', download_limit: '' };
    showShareModal.value = true;
};

const submitShare = () => {
    router.post(route('files.share', props.file.id), shareForm.value, {
        preserveScroll: true,
        onSuccess: () => (showShareModal.value = false),
    });
};

const openSettings = () => {
    settingsForm.value = {
        name: props.file.name,
        is_public: props.file.is_public,
        expires_at: props.file.expires_at || '',
        password: '',
        download_limit: props.file.download_limit || '',
    };
    showSettingsModal.value = true;
};

const submitSettings = () => {
    router.patch(route('files.update', props.file.id), settingsForm.value, {
        preserveScroll: true,
        onSuccess: () => (showSettingsModal.value = false),
    });
};

const submitVersion = () => {
    if (!versionFile.value) return;
    const form = new FormData();
    form.append('file', versionFile.value);
    form.append('changelog', versionForm.value.changelog);
    router.post(route('files.versions.store', props.file.id), form, {
        forceFormData: true,
        onSuccess: () => {
            showVersionModal.value = false;
            versionForm.value.changelog = '';
        },
    });
};

const restoreVersion = async (version) => {
    const ok = await confirm({ message: 'Restore this version?' });
    if (!ok) return;
    router.post(route('files.versions.restore', [props.file.id, version.id]), {}, { preserveScroll: true });
};

const deleteVersion = async (version) => {
    const ok = await confirm({ message: 'Delete this version permanently?' });
    if (!ok) return;
    router.delete(route('files.versions.destroy', [props.file.id, version.id]), { preserveScroll: true });
};

const deleteFile = async () => {
    const ok = await confirm({ message: `Move "${props.file.name}" to trash?` });
    if (!ok) return;
    router.delete(route('files.destroy', props.file.id));
};
</script>

<template>
    <div class="grid lg:grid-cols-3 gap-6">
        <div v-if="copySuccess" class="fixed bottom-4 right-4 bg-ink text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50">
            {{ copySuccess }}
        </div>

        <!-- Main -->
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
                            <p class="text-sm text-ink-soft">{{ media.file_name }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="toggleFavorite" title="Favorite"
                            :class="isFavorited ? 'text-warning-500' : 'text-ink-faint hover:text-warning-500'">
                            <StarIcon class="w-6 h-6" />
                        </button>
                        <Link :href="route('files.download', file.id)" title="Download"
                            class="bg-brand-600 hover:bg-brand-700 text-white p-2 rounded-lg">
                            <ArrowDownTrayIcon class="w-5 h-5" />
                        </Link>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <Card gradient :icon="DocumentIcon" iconColor="text-brand-600" iconBg="bg-brand-100" padding="p-4">
                        <div class="text-sm text-ink-soft">Size</div>
                        <div class="font-semibold text-ink-strong">{{ formatBytes(media.size) }}</div>
                    </Card>
                    <Card gradient :icon="FilmIcon" iconColor="text-brand-600" iconBg="bg-brand-100" padding="p-4">
                        <div class="text-sm text-ink-soft">Type</div>
                        <div class="font-semibold text-ink-strong">{{ media.mime_type }}</div>
                    </Card>
                    <Card gradient :icon="EyeIcon" iconColor="text-brand-600" iconBg="bg-brand-100" padding="p-4">
                        <div class="text-sm text-ink-soft">Views</div>
                        <div class="font-semibold text-ink-strong">{{ file.views.toLocaleString() }}</div>
                    </Card>
                    <Card gradient :icon="ArrowDownTrayIcon" iconColor="text-brand-600" iconBg="bg-brand-100" padding="p-4">
                        <div class="text-sm text-ink-soft">Downloads</div>
                        <div class="font-semibold text-ink-strong">{{ file.downloads.toLocaleString() }}</div>
                    </Card>
                </div>

                <!-- Public link / embed -->
                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-2">
                        <input :value="route('public.show', file.id)" readonly class="flex-1 text-xs bg-surface-muted border rounded-lg px-3 py-2" />
                        <button type="button" @click="copyText(route('public.show', file.id), 'Link copied!')" class="inline-flex items-center gap-1 text-sm bg-surface-soft hover:bg-line-soft px-3 py-2 rounded-lg">
                            <LinkIcon class="w-4 h-4" /> Copy Link
                        </button>
                        <button type="button" @click="copyText(file.embed_code, 'Embed code copied!')" class="inline-flex items-center gap-1 text-sm bg-surface-soft hover:bg-line-soft px-3 py-2 rounded-lg">
                            <CodeBracketIcon class="w-4 h-4" /> Copy Embed
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-2 mt-6">
                    <button type="button" @click="openShare" class="inline-flex items-center gap-1.5 text-sm bg-surface border border-line hover:bg-surface-muted px-3 py-2 rounded-lg font-medium">
                        <ShareIcon class="w-4 h-4" /> Share
                    </button>
                    <button type="button" @click="showVersionModal = true" class="inline-flex items-center gap-1.5 text-sm bg-surface border border-line hover:bg-surface-muted px-3 py-2 rounded-lg font-medium">
                        <ArrowUpTrayIcon class="w-4 h-4" /> New Version
                    </button>
                    <button type="button" @click="openSettings" class="inline-flex items-center gap-1.5 text-sm bg-surface border border-line hover:bg-surface-muted px-3 py-2 rounded-lg font-medium">
                        <Cog6ToothIcon class="w-4 h-4" /> Settings
                    </button>
                    <button type="button" @click="deleteFile" class="inline-flex items-center gap-1.5 text-sm bg-danger-50 text-danger-600 border border-danger-200 hover:bg-danger-100 px-3 py-2 rounded-lg font-medium">
                        <TrashIcon class="w-4 h-4" /> Delete
                    </button>
                </div>
            </div>

            <!-- Versions -->
            <div class="bg-surface rounded-xl shadow p-5">
                <h2 class="text-lg font-semibold text-ink mb-4">Versions</h2>
                <div v-if="versions.length === 0" class="text-sm text-ink-faint">No versions yet.</div>
                <ul v-else class="space-y-2">
                    <li v-for="v in versions" :key="v.id" class="flex items-center justify-between bg-surface-muted rounded-lg px-4 py-3 text-sm">
                        <div>
                            <span class="font-medium text-ink-strong">v{{ v.version }}</span>
                            <span class="text-ink-soft ml-2">{{ v.name }} ({{ formatBytes(v.size) }})</span>
                            <div class="text-xs text-ink-faint mt-0.5">{{ v.changelog || 'No changelog' }} — {{ new Date(v.created_at).toLocaleString() }}</div>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" @click="restoreVersion(v)" title="Restore" class="p-1.5 hover:bg-brand-100 rounded text-brand-700"><ArrowPathIcon class="w-4 h-4" /></button>
                            <button type="button" @click="deleteVersion(v)" title="Delete" class="p-1.5 hover:bg-danger-100 rounded text-danger-500"><TrashIcon class="w-4 h-4" /></button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Subtitles -->
            <div v-if="isVideo" class="bg-surface rounded-xl shadow p-5">
                <h2 class="text-lg font-semibold text-ink mb-4">Subtitles</h2>

                <ul v-if="subtitles.length > 0" class="space-y-2 mb-4">
                    <li v-for="t in subtitles" :key="t.id" class="flex items-center justify-between bg-surface-muted rounded-lg px-4 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-ink-strong">{{ t.label }}</span>
                            <span class="text-xs uppercase text-ink-faint">{{ t.srclang }}</span>
                            <span v-if="t.default" class="text-xs bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full">Default</span>
                        </div>
                        <button type="button" @click="deleteSubtitle(t)" title="Delete" class="p-1.5 hover:bg-danger-100 rounded text-danger-500">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </li>
                </ul>
                <div v-else class="text-sm text-ink-faint mb-4">No subtitles yet.</div>

                <form @submit.prevent="submitSubtitle" class="space-y-3 bg-surface-muted rounded-lg p-4">
                    <FileInput v-model="subtitleFile" accept=".vtt,.srt" hint="VTT or SRT files" />
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-ink-body mb-1">Language</label>
                            <input v-model="subtitleForm.language" type="text" placeholder="en" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-body mb-1">Label</label>
                            <input v-model="subtitleForm.label" type="text" placeholder="English" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <label class="flex items-center text-sm text-ink-body">
                        <input v-model="subtitleForm.default" type="checkbox" class="rounded mr-2" />
                        Set as default
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Add Subtitle</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-surface rounded-xl shadow p-5">
                <h2 class="text-lg font-semibold text-ink mb-4">Statistics</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-ink-soft">Total views</span><span class="font-medium">{{ stats.views.toLocaleString() }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-soft">Total downloads</span><span class="font-medium">{{ stats.downloads.toLocaleString() }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-soft">Status</span><span :class="file.is_public ? 'text-success-600' : 'text-ink-muted'">{{ file.is_public ? 'Public' : 'Private' }}</span></div>
                    <div v-if="file.expires_at" class="flex justify-between"><span class="text-ink-soft">Expires</span><span class="font-medium">{{ new Date(file.expires_at).toLocaleDateString() }}</span></div>
                    <div v-if="file.download_limit" class="flex justify-between">
                        <span class="text-ink-soft">Download limit</span>
                        <span class="font-medium">{{ file.download_count }} / {{ file.download_limit }}</span>
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-ink-body mt-6 mb-2">Recent views</h3>
                <ul class="space-y-1 text-xs text-ink-soft">
                    <li v-for="v in stats.recent_views" :key="v.id">{{ v.ip || '—' }} — {{ new Date(v.created_at).toLocaleString() }}</li>
                    <li v-if="stats.recent_views.length === 0">No views yet</li>
                </ul>
            </div>
        </div>

        <!-- Share modal -->
        <Modal :show="showShareModal" title="Share File" @close="showShareModal = false">
            <form @submit.prevent="submitShare" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Share with (email)</label>
                    <input v-model="shareForm.shared_with" type="email" placeholder="friend@example.com" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Permission</label>
                    <select v-model="shareForm.permission" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                        <option value="view">View</option>
                        <option value="download">View + Download</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Password (optional)</label>
                    <input v-model="shareForm.password" type="text" placeholder="Optional password" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Expires</label>
                        <input v-model="shareForm.expires_at" type="datetime-local" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Download limit</label>
                        <input v-model="shareForm.download_limit" type="number" min="1" placeholder="No limit" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showShareModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Share</button>
                </div>
            </form>
        </Modal>

        <!-- Settings modal -->
        <Modal :show="showSettingsModal" title="File Settings" @close="showSettingsModal = false">
            <form @submit.prevent="submitSettings" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                    <input v-model="settingsForm.name" type="text" placeholder="File name" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <label class="flex items-center text-sm text-ink-body">
                    <input v-model="settingsForm.is_public" type="checkbox" class="rounded mr-2" />
                    Public (accessible via link)
                </label>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Expires at</label>
                    <input v-model="settingsForm.expires_at" type="datetime-local" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Password (new)</label>
                        <input v-model="settingsForm.password" type="text" placeholder="Optional password" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Download limit</label>
                        <input v-model="settingsForm.download_limit" type="number" min="1" placeholder="No limit" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showSettingsModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </form>
        </Modal>

        <!-- New version modal -->
        <Modal :show="showVersionModal" title="Upload New Version" @close="showVersionModal = false">
            <form @submit.prevent="submitVersion" class="space-y-4">
                <FileInput v-model="versionFile" label="Replacement file" hint="Upload a file as a new version" />
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Changelog</label>
                    <input v-model="versionForm.changelog" type="text" placeholder="What changed in this version?" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showVersionModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Upload</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
