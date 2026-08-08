<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ logs: Object, filters: Object, actions: Array });

const headers = [
    { key: 'action', label: 'Action', sortable: true },
    { key: 'description', label: 'Description', sortable: true, sortKey: 'description' },
    { key: 'ip', label: 'IP', sortable: true, sortKey: 'ip' },
    { key: 'time', label: 'Time', sortable: true, sortKey: 'created_at' },
];

const filterOptions = [
    {
        key: 'action',
        label: 'All actions',
        options: (props.actions || []).map((a) => ({ value: a, label: a })),
    },
];
</script>

<template>
    <div>
        <PageHeader title="Activity Logs" />

        <DataTable
            :headers="headers"
            :rows="logs.data"
            :paginator="logs"
            :filters="filters"
            route-name="activity-logs.index"
            :filter-options="filterOptions"
        >
            <template #cell-action="{ row }">
                <code class="bg-surface-soft text-ink-body text-xs px-2 py-1 rounded">{{ row.action }}</code>
            </template>
            <template #cell-description="{ row }">
                {{ row.description }} <span v-if="row.properties?.name" class="text-faint">— {{ row.properties.name }}</span>
            </template>
            <template #cell-ip="{ row }">{{ row.ip_address || '—' }}</template>
            <template #cell-time="{ row }">{{ new Date(row.created_at).toLocaleString() }}</template>
            <template #empty>
                <EmptyState title="No activity yet" />
            </template>
        </DataTable>
    </div>
</template>