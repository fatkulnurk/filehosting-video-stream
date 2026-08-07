<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ packages: Array, currentPackage: Object, history: Object, filters: Object });
const subscribe = async (pkg) => {
    const ok = await confirm({
        message: `Switch to the ${pkg.name} plan?`,
        confirmLabel: 'Switch',
        danger: false,
    });
    if (!ok) return;
    router.post(route('subscriptions.subscribe'), { package_id: pkg.id });
};

const cancel = async () => {
    const ok = await confirm({ message: 'Cancel current subscription?', confirmLabel: 'Cancel subscription' });
    if (!ok) return;
    router.post(route('subscriptions.cancel'), {});
};

const historyHeaders = [
    { key: 'package', label: 'Package', sortable: true, sortKey: 'package' },
    { key: 'status', label: 'Status', sortable: true, sortKey: 'status' },
    { key: 'start', label: 'Start', sortable: true, sortKey: 'start' },
    { key: 'end', label: 'End', sortable: true, sortKey: 'end' },
    { key: 'payment', label: 'Payment', sortable: true, sortKey: 'payment' },
];

const filterOptions = [
    {
        key: 'status',
        label: 'All statuses',
        options: [
            { value: 'active', label: 'Active' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'expired', label: 'Expired' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="Plan & Billing" subtitle="Your current plan:" />

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="pkg in packages" :key="pkg.id"
                :class="[currentPackage?.id === pkg.id ? 'border-brand-500 ring-2 ring-brand-200' : 'border-line-soft', 'bg-surface rounded-xl shadow p-6 border flex flex-col']">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-ink">{{ pkg.name }}</span>
                    <span v-if="currentPackage?.id === pkg.id" class="bg-brand-50 text-brand-700 text-xs px-2 py-0.5 rounded-full">Current</span>
                </div>
                <div class="mt-3 text-3xl font-bold text-brand-700">
                    ${{ pkg.price_monthly }}<span class="text-sm text-ink-faint font-normal">/mo</span>
                </div>
                <div class="mt-4 space-y-1.5 text-sm text-ink-muted flex-1">
                    <p>{{ formatBytes(pkg.storage_limit) }} storage</p>
                    <p>Max file {{ pkg.formatted_max_file_size }}</p>
                    <p v-for="f in pkg.features" :key="f" class="text-success-600">✓ {{ f.replace(/_/g, ' ') }}</p>
                </div>
                <button type="button" @click="subscribe(pkg)"
                    :disabled="currentPackage?.id === pkg.id"
                    :class="currentPackage?.id === pkg.id ? 'bg-surface-soft text-ink-faint' : 'bg-brand-600 hover:bg-brand-700 text-white'"
                    class="mt-5 rounded-lg py-2 font-medium text-sm">
                    {{ currentPackage?.id === pkg.id ? 'Current Plan' : pkg.price_monthly > 0 ? 'Upgrade' : 'Switch' }}
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-ink">Subscription History</h2>
            <button v-if="currentPackage?.price_monthly > 0" type="button" @click="cancel" class="text-sm text-danger-600 hover:underline">Cancel subscription</button>
        </div>

        <DataTable
            :headers="historyHeaders"
            :rows="history.data"
            :paginator="history"
            :filters="filters"
            route-name="subscriptions.index"
            :filter-options="filterOptions"
        >
            <template #cell-package="{ row }">{{ row.storage_package?.name }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.status === 'active' ? 'success' : 'neutral'">{{ row.status }}</Badge>
            </template>
            <template #cell-start="{ row }">{{ new Date(row.start_date).toLocaleDateString() }}</template>
            <template #cell-end="{ row }">{{ row.end_date ? new Date(row.end_date).toLocaleDateString() : '—' }}</template>
            <template #cell-payment="{ row }">{{ row.payment_method || '—' }}</template>
            <template #empty>No subscription history.</template>
        </DataTable>
    </div>
</template>
