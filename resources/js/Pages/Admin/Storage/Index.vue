<script setup>
import { reactive, ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon, PencilIcon, TrashIcon, PowerIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({
    servers: Object,
    drivers: Object,
    driverFields: Object,
    backupOptions: Array,
    filters: Object,
});

const editing = ref(null);
const showAdd = ref(false);
const showDeactivate = ref(null);
const targetStorage = ref('');
const config = reactive({});

const form = reactive({
    name: '',
    driver: 'local',
    is_primary: false,
    auto_backup: false,
    backup_storage_server_id: '',
    storage_limit: '',
});

const requiredFields = computed(() => props.driverFields[form.driver] || []);

const openAdd = () => {
    editing.value = null;
    Object.assign(form, { name: '', driver: 'local', is_primary: false, auto_backup: false, backup_storage_server_id: '', storage_limit: '' });
    Object.keys(config).forEach((k) => delete config[k]);
    showAdd.value = true;
};

const openEdit = (server) => {
    editing.value = server;
    Object.assign(form, {
        name: server.name,
        driver: server.driver,
        is_primary: server.is_primary,
        auto_backup: server.auto_backup,
        backup_storage_server_id: server.backup_storage_server_id || '',
        storage_limit: server.storage_limit || '',
    });
    Object.keys(config).forEach((k) => delete config[k]);
    Object.assign(config, server.config || {});
    showAdd.value = true;
};

const submit = () => {
    const payload = {
        name: form.name,
        driver: form.driver,
        is_primary: form.is_primary,
        auto_backup: form.auto_backup,
        backup_storage_server_id: form.backup_storage_server_id || null,
        storage_limit: form.storage_limit || null,
        config,
    };

    if (editing.value) {
        router.patch(route('admin.storage.update', editing.value.id), payload, { preserveScroll: true });
    } else {
        router.post(route('admin.storage.store'), payload, { preserveScroll: true });
    }
    showAdd.value = false;
};

const confirmDeactivate = (server) => {
    showDeactivate.value = server;
    targetStorage.value = '';
};

const deactivate = () => {
    router.post(route('admin.storage.deactivate', showDeactivate.value.id), {
        target_storage_id: targetStorage.value || null,
    }, { preserveScroll: true });
    showDeactivate.value = null;
};

const removeServer = async (server) => {
    const ok = await confirm({ message: `Delete storage "${server.name}"?` });
    if (!ok) return;
    router.delete(route('admin.storage.destroy', server.id), { preserveScroll: true });
};
const serverHeaders = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'driver', label: 'Driver', sortable: true },
    { key: 'used', label: 'Used', sortable: true, sortKey: 'storage_used' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'driver',
        label: 'All drivers',
        options: Object.entries(props.drivers || {}).map(([value, label]) => ({ value, label })),
    },
    {
        key: 'status',
        label: 'All statuses',
        options: [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="All Storage Servers">
            <template #actions>
                <Button @click="openAdd">
                    <PlusIcon class="w-4 h-4" /> Add Storage
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="serverHeaders"
            :rows="servers.data"
            :paginator="servers"
            :filters="filters"
            route-name="admin.storage.index"
            :filter-options="filterOptions"
        >
            <template #cell-name="{ row }">{{ row.name }}</template>
            <template #cell-driver="{ row }">{{ row.driver }}</template>
            <template #cell-used="{ row }">{{ formatBytes(row.storage_used) }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <Link :href="route('admin.storage.show', row.id)" class="p-1.5 hover:bg-surface-soft rounded text-ink-soft hover:text-brand-800 text-xs">View</Link>
                    <button type="button" @click="openEdit(row)" class="p-1.5 hover:bg-surface-soft rounded text-ink-soft hover:text-brand-800"><PencilIcon class="w-4 h-4" /></button>
                    <button v-if="row.is_active" type="button" @click="confirmDeactivate(row)" class="p-1.5 hover:bg-warning-100 rounded text-ink-soft hover:text-warning-600"><PowerIcon class="w-4 h-4" /></button>
                    <button type="button" @click="removeServer(row)" class="p-1.5 hover:bg-danger-100 rounded text-ink-soft hover:text-danger-600"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>No storage servers found.</template>
        </DataTable>

        <!-- Add/Edit modal -->
        <Modal :show="showAdd" :title="editing ? 'Edit Storage' : 'Add Storage Server'" @close="showAdd = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Disk Name</label>
                    <input v-model="form.name" type="text" required placeholder="my-storage"
                        class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Driver</label>
                    <select v-model="form.driver" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                        <option v-for="(label, value) in drivers" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>

                <div v-for="field in requiredFields" :key="field" class="grid grid-cols-1">
                    <label class="block text-sm font-medium text-ink-body mb-1 capitalize">{{ field.replace(/_/g, ' ') }}</label>
                    <input v-model="config[field]" :type="field.includes('password') || field === 'secret' || field === 'api_key' ? 'password' : 'text'" placeholder="Enter value"
                        class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>

                <div class="flex gap-4">
                    <label class="flex items-center text-sm text-ink-body">
                        <input v-model="form.is_primary" type="checkbox" class="rounded mr-2" /> Set as primary
                    </label>
                    <label class="flex items-center text-sm text-ink-body">
                        <input v-model="form.auto_backup" type="checkbox" class="rounded mr-2" /> Enable auto backup
                    </label>
                </div>

                <div v-if="backupOptions.length > 0">
                    <label class="block text-sm font-medium text-ink-body mb-1">Backup target</label>
                    <select v-model="form.backup_storage_server_id" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                        <option value="">None</option>
                        <option v-for="s in backupOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Storage limit (bytes)</label>
                    <input v-model="form.storage_limit" type="number" min="0" placeholder="Leave empty for unlimited"
                        class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">
                        {{ editing ? 'Save' : 'Add Storage' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Deactivate modal -->
        <Modal :show="!!showDeactivate" title="Deactivate Storage" @close="showDeactivate = null">
            <div class="space-y-4">
                <p class="text-sm text-ink-muted">
                    Files on <strong>{{ showDeactivate?.name }}</strong> must be migrated to another storage first. Choose a target:
                </p>
                <select v-model="targetStorage" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                    <option value="">No files (just deactivate)</option>
                    <option v-for="s in servers.data.filter((x) => x.is_active && x.id !== showDeactivate?.id)" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showDeactivate = null" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="deactivate" class="px-4 py-2 text-sm bg-warning-600 hover:bg-warning-700 text-white rounded-lg font-medium">Deactivate</button>
                </div>
            </div>
        </Modal>
    </div>
</template>