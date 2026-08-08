<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import FileInput from '@/Components/FileInput.vue';
import { EyeIcon, TrashIcon, ArrowUpTrayIcon, DocumentIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';
import { mediaOf, typeLabel } from '@/utils/file';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ files: Object, users: Array, filters: Object });

const showUpload = ref(false);
const uploadForm = reactive({ user_id: '', directory_id: '', files: [] });
const uploadDirs = ref([]);

const loadUserDirs = async (userId) => {
    uploadDirs.value = [];
    uploadForm.directory_id = '';
    if (!userId) return;

    const res = await fetch(route('admin.files.directories', { user_id: userId }), {
        headers: { 'Accept': 'application/json' },
    });

    if (res.ok) {
        const data = await res.json();
        uploadDirs.value = data.directories || [];
    }
};

const openUpload = () => {
    uploadForm.user_id = props.filters?.user_id || props.users[0]?.id || '';
    uploadForm.directory_id = '';
    uploadForm.files = [];
    loadUserDirs(uploadForm.user_id);
    showUpload.value = true;
};

const submitUpload = () => {
    if (uploadForm.files.length === 0 || !uploadForm.user_id) return;

    const form = new FormData();
    uploadForm.files.forEach((f) => form.append('files[]', f));
    form.append('user_id', uploadForm.user_id);
    if (uploadForm.directory_id) form.append('directory_id', uploadForm.directory_id);

    router.post(route('admin.files.store'), form, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUpload.value = false;
            uploadForm.files = [];
        },
    });
};

const trash = async (file) => {
    const ok = await confirm({ message: `Move "${file.name}" to trash?` });
    if (!ok) return;
    router.delete(route('admin.files.destroy', file.id), { preserveScroll: true });
};

const headers = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'user', label: 'Owner', sortable: true, sortKey: 'user' },
    { key: 'type', label: 'Type' },
    { key: 'size', label: 'Size', sortable: true, sortKey: 'size' },
    { key: 'views', label: 'Views', sortable: true, sortKey: 'views' },
    { key: 'downloads', label: 'Downloads', sortable: true, sortKey: 'downloads' },
    { key: 'status', label: 'Status', sortable: true, sortKey: 'is_public' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'user_id',
        label: 'All owners',
        options: props.users.map((u) => ({ value: u.id, label: u.name })),
    },
    {
        key: 'status',
        label: 'All statuses',
        options: [
            { value: 'public', label: 'Public' },
            { value: 'private', label: 'Private' },
            { value: 'active', label: 'Active' },
            { value: 'trashed', label: 'Trashed' },
        ],
    },
    {
        key: 'type',
        label: 'All types',
        options: [
            { value: 'video', label: 'Video' },
            { value: 'image', label: 'Image' },
            { value: 'document', label: 'Document' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="All Files">
            <template #actions>
                <Button @click="openUpload">
                    <ArrowUpTrayIcon class="w-4 h-4" /> Upload
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="headers"
            :rows="files.data"
            :paginator="files"
            :filters="filters"
            route-name="admin.files.index"
            :filter-options="filterOptions"
        >
            <template #cell-name="{ row }">
                <span class="font-medium text-ink-strong">{{ row.name }}</span>
                <Badge v-if="row.deleted_at" variant="neutral" class="ml-2">Trashed</Badge>
            </template>
            <template #cell-user="{ row }">{{ row.user?.name || '—' }}</template>
            <template #cell-type="{ row }">
                <span class="inline-flex items-center gap-1.5 text-soft">
                    <DocumentIcon class="w-4 h-4" /> {{ typeLabel(row) }}
                </span>
            </template>
            <template #cell-size="{ row }">{{ formatBytes(mediaOf(row)?.size || 0) }}</template>
            <template #cell-views="{ row }">{{ (row.views || 0).toLocaleString() }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.is_public ? 'success' : 'neutral'">
                    {{ row.is_public ? 'Public' : 'Private' }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Link :href="route('admin.files.show', row.id)" title="View" class="btn-icon">
                        <EyeIcon class="w-4 h-4" />
                    </Link>
                    <button type="button" @click="trash(row)" title="Trash" class="btn-icon-danger">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </template>
            <template #empty>No files found.</template>
        </DataTable>

        <!-- Upload modal -->
        <Modal :show="showUpload" title="Upload Files on Behalf of User" @close="showUpload = false">
            <form @submit.prevent="submitUpload" class="space-y-4">
                <div>
                    <label class="label">User</label>
                    <select v-model="uploadForm.user_id" required @change="loadUserDirs(uploadForm.user_id)" class="input">
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                </div>
                <div>
                    <label class="label">Directory</label>
                    <select v-model="uploadForm.directory_id" class="input">
                        <option value="">Root</option>
                        <option v-for="d in uploadDirs" :key="d.id" :value="d.id">{{ d.label.join(' / ') }}</option>
                    </select>
                </div>
                <FileInput v-model="uploadForm.files" multiple hint="Files will be stored under the selected user's account" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showUpload = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" :disabled="uploadForm.files.length === 0" class="btn btn-primary btn-md">
                        Upload {{ uploadForm.files.length }} file(s)
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
