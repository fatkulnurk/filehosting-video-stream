<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ webhook: Object, logs: Object, filters: Object });

const headers = [
    { key: 'event', label: 'Event', sortable: true },
    { key: 'status', label: 'Status', sortable: true, sortKey: 'status' },
    { key: 'response', label: 'Response', sortable: true, sortKey: 'response' },
    { key: 'duration', label: 'Duration', sortable: true, sortKey: 'duration' },
    { key: 'time', label: 'Time', sortable: true, sortKey: 'created_at' },
];
</script>

<template>
    <div>
        <div class="mb-4">
            <Link :href="route('webhooks.index')" class="text-sm text-brand-700 hover:underline">← Back to webhooks</Link>
        </div>

        <PageHeader title="Webhook Logs" :subtitle="String(webhook.url)" />

        <DataTable
            :headers="headers"
            :rows="logs.data"
            :paginator="logs"
            :filters="filters"
            route-name="webhooks.logs"
            :route-params="{ webhook: webhook.id }"
        >
            <template #cell-event="{ row }">{{ row.event }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.success ? 'success' : 'danger'">
                    {{ row.success ? 'Success' : 'Failed' }} {{ row.response_code ? '(' + row.response_code + ')' : '' }}
                </Badge>
            </template>
            <template #cell-response="{ row }">{{ row.response_body || '—' }}</template>
            <template #cell-duration="{ row }">{{ row.duration_ms ? row.duration_ms + 'ms' : '—' }}</template>
            <template #cell-time="{ row }">{{ new Date(row.created_at).toLocaleString() }}</template>
            <template #empty>
                <EmptyState title="No deliveries yet" />
            </template>
        </DataTable>
    </div>
</template>
