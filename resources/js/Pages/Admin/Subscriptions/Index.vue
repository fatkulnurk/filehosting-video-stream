<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon, PencilIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ subscriptions: Object, users: Array, packages: Array, filters: Object });

const statusVariant = (status) => {
    const map = {
        active: 'success',
        cancelled: 'neutral',
        expired: 'warning',
    };
    return map[status] || 'neutral';
};

const showAssign = ref(false);
const assignForm = reactive({ user_id: '', package_id: '' });

const openAssign = () => {
    assignForm.user_id = '';
    assignForm.package_id = props.packages[0]?.id || '';
    showAssign.value = true;
};

const submitAssign = () => {
    if (!assignForm.user_id || !assignForm.package_id) return;
    router.post(route('admin.subscriptions.store'), assignForm.value, {
        preserveScroll: true,
        onSuccess: () => (showAssign.value = false),
    });
};

const editing = ref(null);
const editForm = reactive({ package_id: '', status: '', end_date: '' });

const openEdit = (sub) => {
    editing.value = sub;
    editForm.package_id = sub.storage_package_id;
    editForm.status = sub.status;
    editForm.end_date = sub.end_date ? sub.end_date.slice(0, 10) : '';
};

const submitEdit = () => {
    if (!editing.value) return;
    router.patch(route('admin.subscriptions.update', editing.value.id), {
        package_id: editForm.package_id,
        status: editForm.status,
        end_date: editForm.end_date || null,
    }, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
};

const cancel = async (sub) => {
    const ok = await confirm({ message: `Cancel subscription for ${sub.user?.name}?` });
    if (!ok) return;
    router.post(route('admin.subscriptions.cancel', sub.user_id), {}, { preserveScroll: true });
};

const subscriptionHeaders = [
    { key: 'user', label: 'User', sortable: true, sortKey: 'user' },
    { key: 'package', label: 'Package', sortable: true, sortKey: 'package' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'start', label: 'Start', sortable: true, sortKey: 'start' },
    { key: 'end', label: 'End', sortable: true, sortKey: 'end' },
    { key: 'actions', label: 'Actions', align: 'right' },
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
    {
        key: 'package_id',
        label: 'All packages',
        options: props.packages.map((pkg) => ({ value: pkg.id, label: pkg.name })),
    },
];
</script>

<template>
    <div>
        <PageHeader title="Subscriptions">
            <template #actions>
                <Button @click="openAssign">
                    <PlusIcon class="w-4 h-4" /> Assign
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="subscriptionHeaders"
            :rows="subscriptions.data"
            :paginator="subscriptions"
            :filters="filters"
            route-name="admin.subscriptions.index"
            :filter-options="filterOptions"
        >
            <template #cell-user="{ row }">
                <span class="font-medium text-ink-strong">{{ row.user?.name || '—' }}</span>
                <div class="text-xs text-faint">{{ row.user?.email }}</div>
            </template>
            <template #cell-package="{ row }">{{ row.storage_package?.name || '—' }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant(row.status)">{{ row.status }}</Badge>
            </template>
            <template #cell-start="{ row }">{{ new Date(row.start_date).toLocaleDateString() }}</template>
            <template #cell-end="{ row }">{{ row.end_date ? new Date(row.end_date).toLocaleDateString() : '—' }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="openEdit(row)" title="Edit" class="btn-icon"><PencilIcon class="w-4 h-4" /></button>
                    <button type="button" @click="cancel(row)" title="Cancel" class="btn-icon-danger"><XMarkIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>No subscriptions found.</template>
        </DataTable>

        <!-- Assign modal -->
        <Modal :show="showAssign" title="Assign Subscription" @close="showAssign = false">
            <form @submit.prevent="submitAssign" class="space-y-4">
                <div>
                    <label class="label">User</label>
                    <select v-model="assignForm.user_id" required class="input">
                        <option value="" disabled>Select user...</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                </div>
                <div>
                    <label class="label">Package</label>
                    <select v-model="assignForm.package_id" required class="input">
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAssign = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">Assign</button>
                </div>
            </form>
        </Modal>

        <!-- Edit modal -->
        <Modal :show="!!editing" title="Edit Subscription" @close="editing = null">
            <form v-if="editing" @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="label">Package</label>
                    <select v-model="editForm.package_id" class="input">
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">Status</label>
                    <select v-model="editForm.status" class="input">
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div>
                    <label class="label">End date</label>
                    <input v-model="editForm.end_date" type="date" class="input" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="editing = null" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">Save</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
