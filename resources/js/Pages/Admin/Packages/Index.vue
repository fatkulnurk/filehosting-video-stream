<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ packages: Object, features: Object, filters: Object });

const showModal = ref(false);
const editing = ref(null);
const form = reactive({
    name: '',
    slug: '',
    description: '',
    storage_limit: '',
    max_file_size: '',
    features: [],
    price_monthly: 0,
    price_yearly: 0,
    is_active: true,
    is_default: false,
    sort_order: 0,
});

const openAdd = () => {
    editing.value = null;
    Object.assign(form, { name: '', slug: '', description: '', storage_limit: '', max_file_size: '', features: [], price_monthly: 0, price_yearly: 0, is_active: true, is_default: false, sort_order: 0 });
    showModal.value = true;
};

const openEdit = (pkg) => {
    editing.value = pkg;
    Object.assign(form, {
        name: pkg.name,
        slug: pkg.slug,
        description: pkg.description || '',
        storage_limit: pkg.storage_limit,
        max_file_size: pkg.max_file_size || '',
        features: pkg.features || [],
        price_monthly: pkg.price_monthly,
        price_yearly: pkg.price_yearly,
        is_active: pkg.is_active,
        is_default: pkg.is_default,
        sort_order: pkg.sort_order,
    });
    showModal.value = true;
};

const submit = () => {
    const payload = { ...form };
    if (editing.value) {
        router.patch(route('admin.packages.update', editing.value.id), payload, { preserveScroll: true });
    } else {
        router.post(route('admin.packages.store'), payload, { preserveScroll: true });
    }
    showModal.value = false;
};

const remove = async (pkg) => {
    const ok = await confirm({ message: `Delete package "${pkg.name}"?` });
    if (!ok) return;
    router.delete(route('admin.packages.destroy', pkg.id), { preserveScroll: true });
};

const packageHeaders = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'storage', label: 'Storage', sortable: true, sortKey: 'storage' },
    { key: 'max_file', label: 'Max File', sortable: true, sortKey: 'max_file' },
    { key: 'price', label: 'Monthly', sortable: true, sortKey: 'price' },
    { key: 'features', label: 'Features' },
    { key: 'status', label: 'Status', sortable: true, sortKey: 'status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'status',
        label: 'All statuses',
        options: [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="Storage Packages">
            <template #actions>
                <Button @click="openAdd">
                    <PlusIcon class="w-4 h-4" /> New Package
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="packageHeaders"
            :rows="packages.data"
            :paginator="packages"
            :filters="filters"
            route-name="admin.packages.index"
            :filter-options="filterOptions"
        >
            <template #cell-name="{ row }">
                <span class="font-medium text-ink-strong">{{ row.name }}</span>
                <Badge v-if="row.is_default" variant="brand" class="ml-2">Default</Badge>
            </template>
            <template #cell-storage="{ row }">{{ formatBytes(row.storage_limit) }}</template>
            <template #cell-max_file="{ row }">{{ row.max_file_size ? formatBytes(row.max_file_size) : 'Unlimited' }}</template>
            <template #cell-price="{ row }">${{ row.price_monthly }}</template>
            <template #cell-features="{ row }">
                <span class="text-xs max-w-[200px]">{{ (row.features || []).join(', ') || '—' }}</span>
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="openEdit(row)" class="p-1.5 hover:bg-surface-soft rounded text-ink-soft hover:text-brand-800"><PencilIcon class="w-4 h-4" /></button>
                    <button type="button" @click="remove(row)" class="p-1.5 hover:bg-danger-100 rounded text-ink-soft hover:text-danger-600"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>No packages found.</template>
        </DataTable>

        <!-- Modal -->
        <Modal :show="showModal" :title="editing ? 'Edit Package' : 'New Package'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Pro" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Slug</label>
                        <input v-model="form.slug" type="text" placeholder="e.g. pro" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Description</label>
                    <textarea v-model="form.description" rows="2" placeholder="Short description of this package" class="w-full rounded-lg border-line border px-3 py-2 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Storage limit (bytes)</label>
                        <input v-model="form.storage_limit" type="number" min="0" placeholder="e.g. 107374182400" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Max file size (bytes)</label>
                        <input v-model="form.max_file_size" type="number" min="1" placeholder="Empty = unlimited" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Price monthly</label>
                        <input v-model="form.price_monthly" type="number" min="0" step="0.01" placeholder="0.00" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Price yearly</label>
                        <input v-model="form.price_yearly" type="number" min="0" step="0.01" placeholder="0.00" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Features</label>
                    <div class="grid grid-cols-2 gap-1.5 max-h-32 overflow-y-auto">
                        <label v-for="(label, value) in features" :key="value" class="flex items-center text-sm text-ink-body">
                            <input v-model="form.features" type="checkbox" :value="value" class="rounded mr-2" />
                            {{ label }}
                        </label>
                    </div>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center text-sm text-ink-body">
                        <input v-model="form.is_active" type="checkbox" class="rounded mr-2" /> Active
                    </label>
                    <label class="flex items-center text-sm text-ink-body">
                        <input v-model="form.is_default" type="checkbox" class="rounded mr-2" /> Default for new users
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Sort order</label>
                    <input v-model="form.sort_order" type="number" placeholder="0" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">{{ editing ? 'Save' : 'Create' }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
