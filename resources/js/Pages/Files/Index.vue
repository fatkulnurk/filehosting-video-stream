<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import Modal from '@/Components/Modal.vue';
import UploadModal from '@/Components/UploadModal.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';
import {
    FolderIcon,
    FolderPlusIcon,
    ArrowUpTrayIcon,
    MagnifyingGlassIcon,
    DocumentIcon,
    PencilIcon,
    ArrowRightOnRectangleIcon,
    TrashIcon,
    LinkIcon,
    CodeBracketIcon,
    StarIcon,
    ArrowDownTrayIcon,
    PlayIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({
    files: Object,
    directories: Array,
    currentDirectory: String,
    breadcrumbs: Array,
    directoryTree: Array,
    storageQuota: Object,
    filters: Object,
});

const showUpload = ref(false);
const showFolderModal = ref(false);
const showRenameModal = ref(false);
const showMoveModal = ref(false);
const showEmbedModal = ref(false);
const renameTarget = ref(null);
const moveTarget = ref(null);
const embedTarget = ref(null);
const folderName = ref('');
const newFolderParent = ref(props.currentDirectory);
const renameName = ref('');
const moveFolderId = ref('');
const selected = ref([]);
const copySuccess = ref('');

const isVideo = (file) => file.media?.[0] && String(file.media[0].mime_type).startsWith('video/');
const fileSize = (file) => formatBytes(file.media?.[0]?.size || 0);

const headers = [
    { key: 'select', label: '', align: 'left' },
    { key: 'name', label: 'Name', sortable: true, sortKey: 'name' },
    { key: 'size', label: 'Size', sortable: true, sortKey: 'size' },
    { key: 'views', label: 'Views', sortable: true, sortKey: 'views' },
    { key: 'downloads', label: 'Downloads', sortable: true, sortKey: 'downloads' },
    { key: 'created_at', label: 'Date', sortable: true, sortKey: 'created_at' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const createFolder = () => {
    router.post(route('directories.store'), {
        name: folderName.value,
        parent_id: props.currentDirectory,
    }, { preserveScroll: true });
    showFolderModal.value = false;
    folderName.value = '';
};

const startRename = (target) => {
    renameTarget.value = target;
    renameName.value = target.name;
    showRenameModal.value = true;
};

const submitRename = () => {
    router.post(route(renameTarget.value.media ? 'files.rename' : 'directories.update', renameTarget.value.id), {
        name: renameName.value,
    }, { preserveScroll: true });
    showRenameModal.value = false;
};

const startMove = (target) => {
    moveTarget.value = target;
    moveFolderId.value = '';
    showMoveModal.value = true;
};

const submitMove = () => {
    const isFile = !!moveTarget.value.media;
    router.post(
        route(isFile ? 'files.move' : 'directories.move', moveTarget.value.id),
        { directory_id: moveFolderId.value || null },
        { preserveScroll: true },
    );
    showMoveModal.value = false;
};

const removeFile = async (file) => {
    const ok = await confirm({ message: `Delete "${file.name}"? It will be moved to trash.` });
    if (!ok) return;
    router.delete(route('files.destroy', file.id), { preserveScroll: true });
};

const removeFolder = async (dir) => {
    const ok = await confirm({ message: `Delete folder "${dir.name}" and all its contents?` });
    if (!ok) return;
    router.delete(route('directories.destroy', dir.id));
};

const toggleFavorite = (file) => {
    router.post(route('files.favorite', file.id), {}, {
        preserveScroll: true,
    });
};

const copyLink = (file) => {
    navigator.clipboard.writeText(route('public.show', file.id)).then(() => {
        copySuccess.value = `${file.name} link copied!`;
        setTimeout(() => (copySuccess.value = ''), 2000);
    });
};

const showEmbed = (file) => {
    embedTarget.value = file;
    showEmbedModal.value = true;
};

const copyEmbed = () => {
    navigator.clipboard.writeText(embedTarget.value.embed_code).then(() => {
        copySuccess.value = 'Embed code copied!';
        setTimeout(() => (copySuccess.value = ''), 2000);
    });
};

const downloadBatch = () => {
    if (selected.value.length === 0) return;
    router.post(route('batch.download'), { file_ids: selected.value }, { forceFormData: true });
};

const toggleSelect = (id) => {
    const i = selected.value.indexOf(id);
    if (i >= 0) selected.value.splice(i, 1);
    else selected.value.push(id);
};

const allSelected = computed(() =>
    props.files?.data?.length > 0 && selected.value.length === props.files.data.length,
);

const toggleSelectAll = () => {
    if (allSelected.value) selected.value = [];
    else selected.value = props.files.data.map((f) => f.id);
};

const searchForm = ref({ search: props.filters?.search || '' });
const submitSearch = () => {
    router.get(route('files.index'), { search: searchForm.value.search, directory: props.currentDirectory }, { preserveState: true });
};
</script>

<template>
    <div>
        <div v-if="copySuccess" class="fixed bottom-4 right-4 bg-ink text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50">
            {{ copySuccess }}
        </div>

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <Breadcrumbs :items="breadcrumbs" />

            <div class="flex items-center gap-2 flex-wrap">
                <form @submit.prevent="submitSearch" class="relative">
                    <input v-model="searchForm.search" type="text" placeholder="Search files..."
                        class="input pl-9 w-48" />
                    <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-2.5 text-faint" />
                </form>
                <button type="button" @click="showFolderModal = true" class="btn btn-secondary btn-sm">
                    <FolderPlusIcon class="w-4 h-4" /> New Folder
                </button>
                <Link :href="route('cloud-downloads.index')" class="btn btn-secondary btn-sm">
                    <LinkIcon class="w-4 h-4" /> From URL
                </Link>
                <button type="button" @click="showUpload = true" class="btn btn-primary btn-sm">
                    <ArrowUpTrayIcon class="w-4 h-4" /> Upload
                </button>
                <button v-if="selected.length > 0" type="button" @click="downloadBatch"
                    class="btn btn-sm bg-success-600 hover:bg-success-700 text-white">
                    <ArrowDownTrayIcon class="w-4 h-4" /> Download ({{ selected.length }})
                </button>
            </div>
        </div>

        <!-- Folders -->
        <div v-if="directories.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
            <div v-for="dir in directories" :key="dir.id"
                class="group bg-surface rounded-xl shadow-sm border p-4 hover:shadow transition cursor-pointer relative">
                <Link :href="route('files.index', { directory: dir.id })" class="block">
                    <FolderIcon class="w-10 h-10 text-warning-500" />
                    <div class="mt-2 font-medium text-sm text-ink-strong truncate">{{ dir.name }}</div>
                    <div class="text-xs text-faint">{{ dir.files_count }} file(s)</div>
                </Link>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 flex gap-1">
                    <button type="button" @click.stop="startRename(dir)" title="Rename" class="p-1 bg-surface rounded shadow text-soft hover:text-brand-800"><PencilIcon class="w-3.5 h-3.5" /></button>
                    <button type="button" @click.stop="startMove(dir)" title="Move" class="p-1 bg-surface rounded shadow text-soft hover:text-brand-800"><ArrowRightOnRectangleIcon class="w-3.5 h-3.5" /></button>
                    <button type="button" @click.stop="removeFolder(dir)" title="Delete" class="p-1 bg-surface rounded shadow text-soft hover:text-danger-600"><TrashIcon class="w-3.5 h-3.5" /></button>
                </div>
            </div>
        </div>

        <!-- Files -->
        <DataTable
            :headers="headers"
            :rows="files.data"
            :paginator="files"
            :filters="filters"
            route-name="files.index"
            :route-params="{ directory: currentDirectory }"
            :show-search="false"
        >
            <template #header-select>
                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="rounded" />
            </template>
            <template #cell-select="{ row }">
                <input type="checkbox" :checked="selected.includes(row.id)" @change="toggleSelect(row.id)" class="rounded" />
            </template>
            <template #cell-name="{ row }">
                <div class="flex items-center gap-2">
                    <DocumentIcon v-if="!isVideo(row)" class="w-4 h-4 text-ink-faint" />
                    <PlayIcon v-else class="w-4 h-4 text-brand-700" />
                    <Link :href="route('files.show', row.id)" class="text-ink-strong hover:text-brand-800 font-medium truncate">{{ row.name }}</Link>
                </div>
            </template>
            <template #cell-size="{ row }">{{ fileSize(row) }}</template>
            <template #cell-views="{ row }">{{ row.views.toLocaleString() }}</template>
            <template #cell-downloads="{ row }">{{ row.downloads.toLocaleString() }}</template>
            <template #cell-created_at="{ row }">{{ new Date(row.created_at).toLocaleDateString() }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="toggleFavorite(row)" title="Favorite" class="btn-icon hover:text-warning-500"><StarIcon class="w-4 h-4" /></button>
                    <button type="button" @click="copyLink(row)" title="Copy link" class="btn-icon"><LinkIcon class="w-4 h-4" /></button>
                    <button type="button" @click="showEmbed(row)" title="Embed code" class="btn-icon"><CodeBracketIcon class="w-4 h-4" /></button>
                    <button type="button" @click="startRename(row)" title="Rename" class="btn-icon"><PencilIcon class="w-4 h-4" /></button>
                    <button type="button" @click="startMove(row)" title="Move" class="btn-icon"><ArrowRightOnRectangleIcon class="w-4 h-4" /></button>
                    <button type="button" @click="removeFile(row)" title="Delete" class="btn-icon-danger"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>
                <div v-if="directories.length === 0">
                    No files here. Upload your first file or create a folder.
                </div>
                <div v-else>
                    No files in this folder.
                </div>
            </template>
        </DataTable>

        <!-- Upload modal -->
        <Modal :show="showUpload" title="Upload Files" @close="showUpload = false">
            <UploadModal :directory-id="currentDirectory" :quota="storageQuota" @close="showUpload = false" />
        </Modal>

        <!-- New folder modal -->
        <Modal :show="showFolderModal" title="New Folder" @close="showFolderModal = false">
            <form @submit.prevent="createFolder" class="space-y-4">
                <input v-model="folderName" type="text" placeholder="Folder name" autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm" required />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFolderModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Create</button>
                </div>
            </form>
        </Modal>

        <!-- Rename modal -->
        <Modal :show="showRenameModal" title="Rename" @close="showRenameModal = false">
            <form @submit.prevent="submitRename" class="space-y-4">
                <input v-model="renameName" type="text" placeholder="New name" class="w-full rounded-lg border-line border px-3 py-2 text-sm" required />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRenameModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </form>
        </Modal>

        <!-- Move modal -->
        <Modal :show="showMoveModal" title="Move to Folder" @close="showMoveModal = false">
            <form @submit.prevent="submitMove" class="space-y-4">
                <select v-model="moveFolderId" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                    <option value="">Root</option>
                    <template v-for="dir in directoryTree" :key="dir.id">
                        <option :value="dir.id">{{ dir.name }}</option>
                        <template v-for="child in dir.children || []" :key="child.id">
                            <option :value="child.id">&nbsp;&nbsp;{{ dir.name }} / {{ child.name }}</option>
                        </template>
                    </template>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showMoveModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Move</button>
                </div>
            </form>
        </Modal>

        <!-- Embed modal -->
        <Modal :show="showEmbedModal" title="Embed Code" @close="showEmbedModal = false">
            <div v-if="embedTarget" class="space-y-4">
                <textarea readonly class="w-full rounded-lg border border-line px-3 py-2 text-xs bg-surface-muted font-mono" rows="3">{{ embedTarget.embed_code }}</textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showEmbedModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Close</button>
                    <button type="button" @click="copyEmbed" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Copy</button>
                </div>
            </div>
        </Modal>
    </div>
</template>
