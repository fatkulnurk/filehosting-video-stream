<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { TrashIcon, FolderIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ shares: Object, filters: Object });

const removeShare = async (share) => {
    const ok = await confirm({ message: 'Remove this share?' });
    if (!ok) return;
    router.delete(route('directory-shares.destroy', share.id), { preserveScroll: true });
};

const permissionVariant = (permission) => {
    if (permission === 'manage') return 'success';
    if (permission === 'upload') return 'info';
    return 'neutral';
};

const headers = [
    { key: 'dir', label: 'Directory', sortable: true, sortKey: 'dir' },
    { key: 'by', label: 'Shared By', sortable: true, sortKey: 'shared_by' },
    { key: 'permission', label: 'Permission', sortable: true, sortKey: 'permission' },
    { key: 'files', label: 'Files' },
    { key: 'share', label: 'Shared', sortable: true, sortKey: 'created_at' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'permission',
        label: 'All permissions',
        options: [
            { value: 'view', label: 'View' },
            { value: 'upload', label: 'Upload' },
            { value: 'manage', label: 'Manage' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="Shared Directories" />

        <DataTable
            :headers="headers"
            :rows="shares.data"
            :paginator="shares"
            :filters="filters"
            route-name="directories.shared-with-me"
            :filter-options="filterOptions"
        >
            <template #cell-dir="{ row }">
                <div class="flex items-center gap-2">
                    <FolderIcon class="w-4 h-4 text-warning-500" />
                    <span class="font-medium text-ink-strong">{{ row.directory.name }}</span>
                </div>
            </template>
            <template #cell-by="{ row }">{{ row.shared_by_user?.name || row.shared_by }}</template>
            <template #cell-permission="{ row }">
                <Badge :variant="permissionVariant(row.permission)">{{ row.permission }}</Badge>
            </template>
            <template #cell-files="{ row }">{{ row.directory.files?.length || 0 }}</template>
            <template #cell-share="{ row }">{{ new Date(row.created_at).toLocaleDateString() }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="removeShare(row)" class="btn-icon-danger"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="No directories shared with you" />
            </template>
        </DataTable>
    </div>
</template>