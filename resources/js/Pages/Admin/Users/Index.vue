<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ users: Object, filters: Object });

const showQuota = ref(null);
const quotaForm = reactive({ storage_limit: '' });
const showRole = ref(null);
const roleForm = reactive({ role: 'member' });
const showCreate = ref(false);
const createForm = reactive({ name: '', email: '', password: '', role: 'member', storage_limit: '' });

const openCreate = () => {
    Object.assign(createForm, { name: '', email: '', password: '', role: 'member', storage_limit: '' });
    showCreate.value = true;
};

const submitCreate = () => {
    router.post(route('admin.users.store'), {
        name: createForm.name,
        email: createForm.email,
        password: createForm.password,
        role: createForm.role,
        storage_limit: createForm.storage_limit || null,
    }, {
        preserveScroll: true,
        onSuccess: () => (showCreate.value = false),
    });
};

const openQuota = (user) => {
    showQuota.value = user;
    quotaForm.storage_limit = user.profile?.storage_limit ?? '';
};

const submitQuota = () => {
    router.post(route('admin.users.quota', showQuota.value.id), { storage_limit: quotaForm.storage_limit || null }, { preserveScroll: true });
    showQuota.value = null;
};

const openRole = (user) => {
    showRole.value = user;
    roleForm.role = user.role;
};

const submitRole = () => {
    router.post(route('admin.users.role', showRole.value.id), { role: roleForm.role }, { preserveScroll: true });
    showRole.value = null;
};

const remove = async (user) => {
    const ok = await confirm({ message: `Delete user "${user.name}"? This will delete all their files.` });
    if (!ok) return;
    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
};

const userHeaders = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Role', sortable: true },
    { key: 'used', label: 'Storage Used', sortable: true, sortKey: 'storage_used' },
    { key: 'quota', label: 'Quota', sortable: true, sortKey: 'storage_limit' },
    { key: 'files', label: 'Files', sortable: true, sortKey: 'files' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const filterOptions = [
    {
        key: 'role',
        label: 'All roles',
        options: [
            { value: 'member', label: 'Member' },
            { value: 'admin', label: 'Admin' },
        ],
    },
];
</script>

<template>
    <div>
        <PageHeader title="Manage Users">
            <template #actions>
                <Button @click="openCreate">
                    <PlusIcon class="w-4 h-4" /> New User
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :headers="userHeaders"
            :rows="users.data"
            :paginator="users"
            :filters="filters"
            route-name="admin.users.index"
            :filter-options="filterOptions"
        >
            <template #cell-name="{ row }">{{ row.name }}</template>
            <template #cell-email="{ row }">{{ row.email }}</template>
            <template #cell-role="{ row }">
                <Badge :variant="row.role === 'admin' ? 'accent' : 'neutral'">{{ row.role }}</Badge>
            </template>
            <template #cell-used="{ row }">{{ formatBytes(row.profile?.storage_used || 0) }}</template>
            <template #cell-quota="{ row }">{{ row.profile?.storage_limit ? formatBytes(row.profile.storage_limit) : 'Unlimited' }}</template>
            <template #cell-files="{ row }">{{ row.files_count ?? '—' }}</template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Link :href="route('admin.users.show', row.id)" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-2 py-1 rounded">View</Link>
                    <button type="button" @click="openRole(row)" class="text-xs bg-surface-soft hover:bg-line-soft text-ink-body px-2 py-1 rounded">Role</button>
                    <button type="button" @click="openQuota(row)" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-2 py-1 rounded">Quota</button>
                    <button type="button" @click="remove(row)" class="text-xs bg-danger-50 hover:bg-danger-100 text-danger-600 px-2 py-1 rounded">Delete</button>
                </div>
            </template>
            <template #empty>No users found.</template>
        </DataTable>

        <!-- Quota modal -->
        <Modal :show="!!showQuota" title="Set Storage Quota" @close="showQuota = null">
            <div v-if="showQuota" class="space-y-4">
                <p class="text-sm text-ink-muted">Set quota for <strong>{{ showQuota.name }}</strong></p>
                <input v-model="quotaForm.storage_limit" type="number" min="0" placeholder="Bytes (empty = unlimited)"
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showQuota = null" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="submitQuota" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </div>
        </Modal>

        <!-- Role modal -->
        <Modal :show="!!showRole" title="Change Role" @close="showRole = null">
            <div v-if="showRole" class="space-y-4">
                <p class="text-sm text-ink-muted">Change role for <strong>{{ showRole.name }}</strong></p>
                <select v-model="roleForm.role" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRole = null" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="submitRole" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </div>
        </Modal>

        <!-- Create user modal -->
        <Modal :show="showCreate" title="New User" @close="showCreate = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                    <input v-model="createForm.name" type="text" placeholder="Full name" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                    <input v-model="createForm.email" type="email" placeholder="user@example.com" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Password</label>
                    <input v-model="createForm.password" type="password" placeholder="Min. 8 characters" required minlength="8" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Role</label>
                        <select v-model="createForm.role" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Storage limit (bytes)</label>
                        <input v-model="createForm.storage_limit" type="number" min="0" placeholder="Empty = unlimited" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreate = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Create</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
