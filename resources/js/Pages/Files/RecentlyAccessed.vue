<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { ClockIcon } from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';
import { mediaOf } from '@/utils/file';

defineOptions({ layout: AppLayout });

const props = defineProps({ files: Object, filters: Object });

const headers = [
    { key: 'name', label: 'Name', sortable: true, sortKey: 'name' },
    { key: 'size', label: 'Size', sortable: true, sortKey: 'size' },
    { key: 'views', label: 'Views', sortable: true, sortKey: 'views' },
    { key: 'downloads', label: 'Downloads', sortable: true, sortKey: 'downloads' },
    { key: 'accessed', label: 'Accessed', sortable: true, sortKey: 'created_at' },
];
</script>

<template>
    <div>
        <PageHeader title="Recently Accessed" />

        <DataTable
            :headers="headers"
            :rows="files.data"
            :paginator="files"
            :filters="filters"
            route-name="files.recently-accessed"
        >
            <template #cell-name="{ row }">
                <div class="flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-ink-faint" />
                    <Link :href="route('files.show', row.file.id)" class="text-brand-700 hover:underline font-medium">{{ row.file.name }}</Link>
                </div>
            </template>
            <template #cell-size="{ row }">{{ formatBytes(mediaOf(row.file)?.size || 0) }}</template>
            <template #cell-views="{ row }">{{ row.file.views.toLocaleString() }}</template>
            <template #cell-downloads="{ row }">{{ row.file.downloads.toLocaleString() }}</template>
            <template #cell-accessed="{ row }">{{ new Date(row.created_at).toLocaleString() }}</template>
            <template #empty>
                <EmptyState title="No recently accessed files" />
            </template>
        </DataTable>
    </div>
</template>