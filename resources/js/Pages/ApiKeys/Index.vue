<script setup>
import { reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import { PlusIcon, TrashIcon, KeyIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const page = usePage();
const props = defineProps({ apiKeys: Object, filters: Object });

const showAdd = ref(false);
const showKey = ref(false);
const createdKey = ref('');
const form = reactive({ name: '', rate_limit: 60, permissions: ['file.read', 'file.write'] });

const allPermissions = ['file.read', 'file.write', 'file.delete', 'directory.manage', 'storage.view', 'webhook.manage'];

const pageKey = () => page.props.flash?.newApiKey;

const submit = () => {
    router.post(route('api-keys.store'), form, {
        preserveScroll: true,
        onSuccess: () => {
            createdKey.value = pageKey();
            showKey.value = true;
            showAdd.value = false;
            form.name = '';
        },
    });
};

const copyKey = () => {
    navigator.clipboard.writeText(createdKey.value);
};

const revoke = async (key) => {
    const ok = await confirm({ message: `Revoke API key "${key.name}"?`, confirmLabel: 'Revoke' });
    if (!ok) return;
    router.delete(route('api-keys.destroy', key.id), { preserveScroll: true });
};

const headers = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'skey', label: 'Key' },
    { key: 'permissions', label: 'Permissions' },
    { key: 'rate', label: 'Rate Limit', sortable: true, sortKey: 'rate' },
    { key: 'last_used', label: 'Last Used', sortable: true, sortKey: 'last_used' },
    { key: 'created', label: 'Created', sortable: true, sortKey: 'created_at' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <div>
        <PageHeader title="API Keys" subtitle="Use the API keys to integrate with the FHV REST API.">
            <template #actions>
                <Button @click="showAdd = true">
                    <PlusIcon class="w-4 h-4" /> New API Key
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="headers"
            :rows="apiKeys.data"
            :paginator="apiKeys"
            :filters="filters"
            route-name="api-keys.index"
        >
            <template #cell-name="{ row }">{{ row.name }}</template>
            <template #cell-skey="{ row }">{{ row.key.slice(0, 12) }}...</template>
            <template #cell-permissions="{ row }">{{ (row.permissions || []).join(', ') }}</template>
            <template #cell-rate="{ row }">{{ row.rate_limit }}/min</template>
            <template #cell-last_used="{ row }">{{ row.last_used_at ? new Date(row.last_used_at).toLocaleString() : 'Never' }}</template>
            <template #cell-created="{ row }">{{ new Date(row.created_at).toLocaleDateString() }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" @click="revoke(row)" class="p-1.5 hover:bg-danger-100 rounded text-ink-faint hover:text-danger-600"><TrashIcon class="w-4 h-4" /></button>
                </div>
            </template>
            <template #empty>No API keys yet. Create one to get started.</template>
        </DataTable>

        <!-- Create modal -->
        <Modal :show="showAdd" title="Create API Key" @close="showAdd = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                    <input v-model="form.name" type="text" required placeholder="My App"
                        class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">Permissions</label>
                    <div class="space-y-1.5">
                        <label v-for="perm in allPermissions" :key="perm" class="flex items-center text-sm text-ink-body">
                            <input v-model="form.permissions" type="checkbox" :value="perm" class="rounded mr-2" />
                            {{ perm }}
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Rate limit (requests/min)</label>
                    <input v-model="form.rate_limit" type="number" min="1" max="10000" placeholder="e.g. 60"
                        class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Create</button>
                </div>
            </form>
        </Modal>

        <!-- Show key modal -->
        <Modal :show="showKey" title="API Key Created" @close="showKey = false">
            <div class="space-y-4">
                <p class="text-sm text-ink-muted">Copy your API key now. It won't be shown again:</p>
                <div class="bg-surface-muted border rounded-lg p-3 flex items-center gap-2">
                    <KeyIcon class="w-5 h-5 text-brand-700 flex-shrink-0" />
                    <code class="text-sm break-all flex-1">{{ createdKey }}</code>
                    <button type="button" @click="copyKey" class="text-sm text-brand-700 hover:underline">Copy</button>
                </div>
                <div class="bg-surface-muted rounded-lg p-3 text-xs text-ink-soft">
                    <p class="font-medium text-ink-body mb-1">Usage:</p>
                    <code class="block">curl -H "X-API-Key: {{ createdKey ? createdKey.slice(0, 8) + '...' : '' }}" {{ window?.location?.origin || '' }}/api/v1/files</code>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showKey = false" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Done</button>
                </div>
            </div>
        </Modal>
    </div>
</template>
