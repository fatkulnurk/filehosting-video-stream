<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import { ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';
import { mediaOf } from '@/utils/file';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ files: Object, empty: Boolean, filters: Object });
const restore = (file) => router.post(route('trash.restore', file.id), {}, { preserveScroll: true });
const destroy = async (file) => {
    const ok = await confirm({ message: `Permanently delete "${file.name}"? This cannot be undone.` });
    if (!ok) return;
    router.delete(route('trash.destroy', file.id), { preserveScroll: true });
};
const purge = async () => {
    const ok = await confirm({ message: 'Permanently delete ALL files in trash?' });
    if (!ok) return;
    router.post(route('trash.purge'), {}, { preserveScroll: true });
};

const headers = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'size', label: 'Size', sortable: true, sortKey: 'size' },
    { key: 'deleted', label: 'Deleted At', sortable: true, sortKey: 'deleted_at' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <div>
        <PageHeader title="Trash">
            <template #actions>
                <Button v-if="files.data.length > 0" variant="danger" @click="purge">
                    <TrashIcon class="w-4 h-4" /> Empty Trash
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="headers"
            :rows="files.data"
            :paginator="files"
            :filters="filters"
            route-name="trash.index"
        >
            <template #cell-name="{ row }">{{ row.name }}</template>
            <template #cell-size="{ row }">{{ formatBytes(mediaOf(row)?.size || 0) }}</template>
            <template #cell-deleted="{ row }">{{ new Date(row.deleted_at).toLocaleString() }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button size="sm" variant="secondary" @click="restore(row)">
                        <ArrowPathIcon class="w-4 h-4" /> Restore
                    </Button>
                    <Button size="sm" variant="danger" @click="destroy(row)">
                        <TrashIcon class="w-4 h-4" /> Delete
                    </Button>
                </div>
            </template>
            <template #empty>Trash is empty.</template>
        </DataTable>
    </div>
</template>
