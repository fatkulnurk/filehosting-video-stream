<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { formatBytes } from '@/utils/format';
import { mediaOf } from '@/utils/file';

defineOptions({ layout: AppLayout });

const props = defineProps({ shares: Object, filters: Object });

const headers = [
    { key: 'file', label: 'File', sortable: true, sortKey: 'file' },
    { key: 'by', label: 'Shared By', sortable: true, sortKey: 'shared_by' },
    { key: 'permission', label: 'Permission', sortable: true, sortKey: 'permission' },
    { key: 'size', label: 'Size', sortable: true, sortKey: 'size' },
    { key: 'expires', label: 'Expires', sortable: true, sortKey: 'expires' },
    { key: 'share', label: 'Shared', sortable: true, sortKey: 'created_at' },
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
        <PageHeader title="Shared With Me" />

        <DataTable
            :headers="headers"
            :rows="shares.data"
            :paginator="shares"
            :filters="filters"
            route-name="files.shared-with-me"
            :filter-options="filterOptions"
        >
            <template #cell-file="{ row }">
                <Link :href="route('files.show', row.file.id)" class="link font-medium">{{ row.file.name }}</Link>
            </template>
            <template #cell-by="{ row }">{{ row.shared_by_user?.name || row.shared_by }}</template>
            <template #cell-permission="{ row }">
                <Badge :variant="row.permission === 'download' ? 'success' : 'neutral'">{{ row.permission }}</Badge>
            </template>
            <template #cell-size="{ row }">{{ formatBytes(mediaOf(row.file)?.size || 0) }}</template>
            <template #cell-expires="{ row }">{{ row.expires_at ? new Date(row.expires_at).toLocaleDateString() : 'Never' }}</template>
            <template #cell-share="{ row }">{{ new Date(row.created_at).toLocaleDateString() }}</template>
            <template #empty>
                <EmptyState title="No files shared with you" />
            </template>
        </DataTable>
    </div>
</template>