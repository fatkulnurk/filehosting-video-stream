<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import { DocumentIcon, EyeIcon, ArrowDownTrayIcon, ServerIcon } from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const props = defineProps({
    stats: Object,
    recentFiles: Array,
});

const headers = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'size', label: 'Size', sortable: true, sortValue: (r) => r.media?.[0]?.size || 0 },
    { key: 'views', label: 'Views', sortable: true },
    { key: 'downloads', label: 'Downloads', sortable: true },
];

const statCards = [
    { key: 'files', label: 'Total Files', icon: DocumentIcon },
    { key: 'views', label: 'Views', icon: EyeIcon },
    { key: 'downloads', label: 'Downloads', icon: ArrowDownTrayIcon },
    { key: 'storage', label: 'Storage Used', icon: ServerIcon },
];
</script>

<template>
    <div>
        <h1 class="page-title mb-1">Welcome back!</h1>
        <p class="text-soft mb-6">Manage your files, storage, and analytics.</p>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <Card
                v-for="stat in statCards"
                :key="stat.key"
                gradient
                :icon="stat.icon"
            >
                <div class="text-2xl font-bold text-brand-700">
                    {{ stat.key === 'storage' ? formatBytes(stats.storage_used) : stats[stat.key].toLocaleString() }}
                </div>
                <div class="text-sm text-soft mt-0.5">
                    {{ stat.label }}
                    <template v-if="stat.key === 'storage' && stats.storage_limit">/ {{ formatBytes(stats.storage_limit) }}</template>
                    <template v-else-if="stat.key === 'storage'">(unlimited)</template>
                </div>
            </Card>
        </div>

        <DataTable :headers="headers" :rows="recentFiles" client-sort :show-search="false">
            <template #cell-name="{ row }">
                <Link :href="route('files.show', row.id)" class="link">{{ row.name }}</Link>
            </template>
            <template #cell-size="{ row }">{{ formatBytes(row.media?.[0]?.size || 0) }}</template>
            <template #cell-views="{ row }">{{ row.views.toLocaleString() }}</template>
            <template #cell-downloads="{ row }">{{ row.downloads.toLocaleString() }}</template>
            <template #empty>
                No files yet. <Link :href="route('files.index')" class="link">Upload your first file</Link>
            </template>
        </DataTable>
    </div>
</template>