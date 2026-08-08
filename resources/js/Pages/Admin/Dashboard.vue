<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { UsersIcon, DocumentIcon, EyeIcon, ArrowDownTrayIcon, ServerIcon } from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const props = defineProps({ stats: Object, latestUsers: Array, storageServers: Array });

const userHeaders = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Role', sortable: true },
];

const serverHeaders = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'driver', label: 'Driver', sortable: true },
    { key: 'status', label: 'Status' },
];

const statCards = [
    { key: 'users', label: 'Users', icon: UsersIcon },
    { key: 'files', label: 'Files', icon: DocumentIcon },
    { key: 'views', label: 'Views', icon: EyeIcon },
    { key: 'downloads', label: 'Downloads', icon: ArrowDownTrayIcon },
    { key: 'storage', label: 'Storage', icon: ServerIcon },
];
</script>

<template>
    <div>
        <PageHeader title="Admin Dashboard" />

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
                <div class="text-sm text-soft mt-0.5">{{ stat.label }}</div>
            </Card>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <h2 class="section-title mb-4">Latest Users</h2>
                <DataTable :headers="userHeaders" :rows="latestUsers" :show-search="false" client-sort>
                    <template #cell-name="{ row }">{{ row.name }}</template>
                    <template #cell-email="{ row }">{{ row.email }}</template>
                    <template #cell-role="{ row }">
                        <Badge :variant="row.role === 'admin' ? 'accent' : 'neutral'">{{ row.role }}</Badge>
                    </template>
                    <template #empty>No users.</template>
                </DataTable>
                <Link :href="route('admin.users.index')" class="block text-center link text-sm mt-3">View all users</Link>
            </div>

            <div>
                <h2 class="section-title mb-4">Latest Storage Servers</h2>
                <DataTable :headers="serverHeaders" :rows="storageServers" :show-search="false" client-sort>
                    <template #cell-name="{ row }">{{ row.name }}</template>
                    <template #cell-driver="{ row }">{{ row.driver }}</template>
                    <template #cell-status="{ row }">
                        <Badge :variant="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
                    </template>
                    <template #empty>No servers.</template>
                </DataTable>
                <Link :href="route('admin.storage.index')" class="block text-center text-sm link mt-3">View all storage</Link>
            </div>
        </div>
    </div>
</template>