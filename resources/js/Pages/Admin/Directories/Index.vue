<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { FolderIcon, PencilIcon, TrashIcon, FolderPlusIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ directories: Object, users: Array, filters: Object });

const showCreate = ref(false);
const createForm = reactive({ user_id: '', name: '', parent_id: '' });
const createDirs = ref([]);

const loadUserDirs = async (userId) => {
    createDirs.value = [];
    createForm.parent_id = '';
    if (!userId) return;

    const res = await fetch(route('admin.files.directories', { user_id: userId }), {
        headers: { 'Accept': 'application/json' },
    });

    if (res.ok) {
        const data = await res.json();
        createDirs.value = data.directories || [];
    }
};

const openCreate = () => {
    createForm.user_id = props.filters?.user_id || props.users[0]?.id || '';
    createForm.name = '';
    createForm.parent_id = '';
    loadUserDirs(createForm.user_id);
    showCreate.value = true;
};

const submitCreate = () => {
    if (!createForm.user_id || !createForm.name) return;
    router.post(route('admin.directories.store'), {
        user_id: createForm.user_id,
        name: createForm.name,
        parent_id: createForm.parent_id || null,
    }, {
        preserveScroll: true,
        onSuccess: () => (showCreate.value = false),
    });
};

const renameForm = reactive({ name: '' });
const renaming = ref(null);

const openRename = (dir) => {
    renaming.value = dir;
    renameForm.name = dir.name;
};

const submitRename = () => {
    if (!renaming.value) return;
    router.patch(route('admin.directories.update', renaming.value.id), { name: renameForm.name }, {
        preserveScroll: true,
        onSuccess: () => (renaming.value = null),
    });
};

const trash = async (dir) => {
    const ok = await confirm({ message: `Move directory "${dir.name}" to trash? Its files will be trashed too.` });
    if (!ok) return;
    router.delete(route('admin.directories.destroy', dir.id), { preserveScroll: true });
};

const forceDelete = async (dir) => {
    const ok = await confirm({ message: `Permanently delete directory "${dir.name}"? This cannot be undone.`, danger: true });
    if (!ok) return;
    router.delete(route('admin.directories.forceDestroy', dir.id));
};

const headers = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'user', label: 'Owner', sortable: true, sortKey: 'user' },
    { key: 'path', label: 'Path', sortable: true },
    { key: 'files', label: 'Files', sortable: true, sortKey: 'files' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'user_id',
        label: 'All users',
        options: props.users.map((u) => ({ value: u.id, label: u.name })),
    },
    {
        key: 'status',
        label: 'All statuses',
        options: [
            { value: 'active', label: 'Active' },
            { value: 'trashed', label: 'Trashed' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="All Directories">
            <template #actions>
                <Button @click="openCreate">
                    <FolderPlusIcon class="w-4 h-4" /> New Directory
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="headers"
            :rows="directories.data"
            :paginator="directories"
            :filters="filters"
            route-name="admin.directories.index"
            :filter-options="filterOptions"
        >
            <template #cell-name="{ row }">
                <span class="inline-flex items-center gap-2 font-medium text-ink-strong">
                    <FolderIcon class="w-4 h-4 text-warning-500" /> {{ row.name }}
                </span>
                <Badge v-if="row.deleted_at" variant="neutral" class="ml-2">Trashed</Badge>
            </template>
            <template #cell-user="{ row }">{{ row.user?.name || '—' }}</template>
            <template #cell-path="{ row }">{{ row.path }}</template>
            <template #cell-files="{ row }">{{ row.files_count }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.deleted_at ? 'neutral' : 'success'">
                    {{ row.deleted_at ? 'Trashed' : 'Active' }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="openRename(row)" title="Rename" class="btn-icon"><PencilIcon class="w-4 h-4" /></button>
                    <button type="button" @click="trash(row)" title="Trash" class="btn-icon hover:bg-warning-100 hover:text-warning-700"><TrashIcon class="w-4 h-4" /></button>
                    <button type="button" @click="forceDelete(row)" title="Delete permanently" class="btn-icon-danger"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>No directories found.</template>
        </DataTable>

        <!-- Create modal -->
        <Modal :show="showCreate" title="Create Directory" @close="showCreate = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="label">User</label>
                    <select v-model="createForm.user_id" required @change="loadUserDirs(createForm.user_id)" class="input">
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                </div>
                <div>
                    <label class="label">Name</label>
                    <input v-model="createForm.name" type="text" placeholder="Folder name" required class="input" />
                </div>
                <div>
                    <label class="label">Parent directory</label>
                    <select v-model="createForm.parent_id" class="input">
                        <option value="">Root</option>
                        <option v-for="d in createDirs" :key="d.id" :value="d.id">{{ d.label.join(' / ') }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreate = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">Create</button>
                </div>
            </form>
        </Modal>

        <!-- Rename modal -->
        <Modal :show="!!renaming" title="Rename Directory" @close="renaming = null">
            <form @submit.prevent="submitRename" class="space-y-4">
                <input v-model="renameForm.name" type="text" placeholder="Folder name" required class="input" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="renaming = null" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">Save</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
