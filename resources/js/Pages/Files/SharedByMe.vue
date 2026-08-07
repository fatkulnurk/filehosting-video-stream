<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ shares: Object, filters: Object });
const removeShare = async (share) => {
    const ok = await confirm({ message: 'Remove this share?' });
    if (!ok) return;
    router.delete(route('shares.destroy', share.id), { preserveScroll: true });
};

const headers = [
    { key: 'file', label: 'File', sortable: true, sortKey: 'file' },
    { key: 'with', label: 'Shared With', sortable: true, sortKey: 'shared_with' },
    { key: 'permission', label: 'Permission', sortable: true, sortKey: 'permission' },
    { key: 'expires', label: 'Expires', sortable: true, sortKey: 'expires' },
    { key: 'share', label: 'Shared', sortable: true, sortKey: 'created_at' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'permission',
        label: 'All permissions',
        options: [
            { value: 'view', label: 'View' },
            { value: 'download', label: 'Download' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="Shared By Me" />

        <DataTable
            :headers="headers"
            :rows="shares.data"
            :paginator="shares"
            :filters="filters"
            route-name="files.shared-by-me"
            :filter-options="filterOptions"
        >
            <template #cell-file="{ row }">
                <Link :href="route('files.show', row.file.id)" class="text-brand-700 hover:underline font-medium">{{ row.file.name }}</Link>
            </template>
            <template #cell-with="{ row }">{{ row.shared_with_user?.email || row.shared_with }}</template>
            <template #cell-permission="{ row }">
                <Badge :variant="row.permission === 'download' ? 'success' : 'neutral'">{{ row.permission }}</Badge>
            </template>
            <template #cell-expires="{ row }">{{ row.expires_at ? new Date(row.expires_at).toLocaleDateString() : 'Never' }}</template>
            <template #cell-share="{ row }">{{ new Date(row.created_at).toLocaleDateString() }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="removeShare(row)" class="p-1.5 hover:bg-danger-100 rounded text-ink-faint hover:text-danger-600"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="You haven't shared any files" />
            </template>
        </DataTable>
    </div>
</template>