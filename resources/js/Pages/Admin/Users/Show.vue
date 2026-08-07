<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import { FolderIcon, DocumentIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';
import { formatBytes } from '@/utils/format';
import { mediaOf } from '@/utils/file';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({ user: Object, files: Array, directories: Array, stats: Object, packages: Array });
const showEdit = ref(false);
const editForm = reactive({ name: '', email: '' });

const openEdit = () => {
    editForm.name = props.user.name;
    editForm.email = props.user.email;
    showEdit.value = true;
};

const submitEdit = () => {
    router.patch(route('admin.users.update', props.user.id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => (showEdit.value = false),
    });
};

const showRole = ref(false);
const roleForm = reactive({ role: 'member' });

const openRole = () => {
    roleForm.role = props.user.role;
    showRole.value = true;
};

const submitRole = () => {
    router.post(route('admin.users.role', props.user.id), { role: roleForm.role }, {
        preserveScroll: true,
        onSuccess: () => (showRole.value = false),
    });
};

const showQuota = ref(false);
const quotaForm = reactive({ storage_limit: '' });

const openQuota = () => {
    quotaForm.storage_limit = props.user.profile?.storage_limit ?? '';
    showQuota.value = true;
};

const submitQuota = () => {
    router.post(route('admin.users.quota', props.user.id), { storage_limit: quotaForm.storage_limit || null }, {
        preserveScroll: true,
        onSuccess: () => (showQuota.value = false),
    });
};

const showAssign = ref(false);
const assignForm = reactive({ package_id: '' });

const openAssign = () => {
    assignForm.package_id = props.packages[0]?.id || '';
    showAssign.value = true;
};

const submitAssign = () => {
    if (!assignForm.package_id) return;
    router.post(route('admin.subscriptions.store'), {
        user_id: props.user.id,
        package_id: assignForm.package_id,
    }, {
        preserveScroll: true,
        onSuccess: () => (showAssign.value = false),
    });
};

const remove = async () => {
    const ok = await confirm({ message: `Delete user "${props.user.name}"? This will delete all their files.`, danger: true });
    if (!ok) return;
    router.delete(route('admin.users.destroy', props.user.id));
};
</script>

<template>
    <div>
        <div class="mb-4">
            <Link :href="route('admin.users.index')" class="text-sm text-brand-700 hover:underline">← Back to Users</Link>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="space-y-6">
                <div class="bg-surface rounded-xl shadow p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 rounded-full bg-brand-600 text-white flex items-center justify-center text-xl font-bold">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-ink">{{ user.name }}</h1>
                            <p class="text-sm text-ink-soft">{{ user.email }}</p>
                            <Badge :variant="user.role === 'admin' ? 'accent' : 'neutral'">{{ user.role }}</Badge>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-ink-soft">Storage used</span><span class="font-medium">{{ formatBytes(stats.storage_used) }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-soft">Quota</span><span class="font-medium">{{ stats.storage_limit ? formatBytes(stats.storage_limit) : 'Unlimited' }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-soft">Files</span><span class="font-medium">{{ stats.files }}</span></div>
                        <div class="flex justify-between"><span class="text-ink-soft">Directories</span><span class="font-medium">{{ stats.directories }}</span></div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-5">
                        <button type="button" @click="openEdit" class="text-xs bg-surface-soft hover:bg-line-soft text-ink-body px-3 py-1.5 rounded">Edit</button>
                        <button type="button" @click="openRole" class="text-xs bg-surface-soft hover:bg-line-soft text-ink-body px-3 py-1.5 rounded">Role</button>
                        <button type="button" @click="openQuota" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded">Quota</button>
                        <button type="button" @click="remove" class="text-xs bg-danger-50 hover:bg-danger-100 text-danger-600 px-3 py-1.5 rounded">Delete</button>
                    </div>
                </div>

                <div class="bg-surface rounded-xl shadow p-5">
                    <h2 class="text-lg font-semibold text-ink mb-3">Subscription</h2>
                    <div v-if="user.subscriptions.length > 0" class="space-y-2">
                        <div v-for="sub in user.subscriptions" :key="sub.id" class="bg-surface-muted rounded-lg px-3 py-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-ink-strong">{{ sub.storage_package?.name || 'Package' }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full" :class="sub.status === 'active' ? 'bg-success-50 text-success-600' : 'bg-surface-soft text-ink-soft'">
                                    {{ sub.status }}
                                </span>
                            </div>
                            <div class="text-xs text-ink-faint mt-1">
                                {{ new Date(sub.start_date).toLocaleDateString() }} — {{ sub.end_date ? new Date(sub.end_date).toLocaleDateString() : 'No end' }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ink-faint mb-3">No subscription.</p>
                    <button type="button" @click="openAssign" class="mt-2 text-xs bg-brand-600 hover:bg-brand-700 text-white px-3 py-1.5 rounded">
                        Assign Package
                    </button>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-surface rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 flex items-center justify-between border-b">
                        <h2 class="text-lg font-semibold text-ink">Recent Files</h2>
                        <Link :href="route('admin.files.index', { user_id: user.id })" class="text-sm text-brand-700 hover:underline">View all</Link>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-surface-muted text-left text-xs uppercase text-ink-soft">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Size</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="file in files" :key="file.id" class="border-t hover:bg-surface-muted">
                                <td class="px-4 py-3 font-medium text-ink-strong truncate max-w-[280px]">{{ file.name }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ formatBytes(mediaOf(file)?.size || 0) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="file.deleted_at ? 'bg-surface-soft text-ink-soft' : 'bg-success-50 text-success-600'" class="px-2 py-0.5 rounded-full text-xs">
                                        {{ file.deleted_at ? 'Trashed' : file.is_public ? 'Public' : 'Private' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.files.show', file.id)" class="text-xs text-brand-700 hover:underline">View</Link>
                                </td>
                            </tr>
                            <tr v-if="files.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-ink-faint">No files.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-surface rounded-xl shadow overflow-hidden">
                    <div class="px-5 py-4 flex items-center justify-between border-b">
                        <h2 class="text-lg font-semibold text-ink">Recent Directories</h2>
                        <Link :href="route('admin.directories.index', { user_id: user.id })" class="text-sm text-brand-700 hover:underline">View all</Link>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-surface-muted text-left text-xs uppercase text-ink-soft">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Path</th>
                                <th class="px-4 py-3">Files</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dir in directories" :key="dir.id" class="border-t hover:bg-surface-muted">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 font-medium text-ink-strong">
                                        <FolderIcon class="w-4 h-4 text-warning-500" /> {{ dir.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-ink-soft text-xs font-mono">{{ dir.path }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ dir.files_count }}</td>
                            </tr>
                            <tr v-if="directories.length === 0">
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-ink-faint">No directories.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit modal -->
        <Modal :show="showEdit" title="Edit User" @close="showEdit = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <input v-model="editForm.name" type="text" placeholder="Full name" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                <input v-model="editForm.email" type="email" placeholder="user@example.com" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showEdit = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </form>
        </Modal>

        <!-- Role modal -->
        <Modal :show="showRole" title="Change Role" @close="showRole = false">
            <div class="space-y-4">
                <select v-model="roleForm.role" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRole = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="submitRole" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </div>
        </Modal>

        <!-- Quota modal -->
        <Modal :show="showQuota" title="Set Storage Quota" @close="showQuota = false">
            <div class="space-y-4">
                <input v-model="quotaForm.storage_limit" type="number" min="0" placeholder="Bytes (empty = unlimited)"
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showQuota = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="submitQuota" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Save</button>
                </div>
            </div>
        </Modal>

        <!-- Assign modal -->
        <Modal :show="showAssign" title="Assign Package" @close="showAssign = false">
            <div class="space-y-4">
                <select v-model="assignForm.package_id" class="w-full rounded-lg border-line border px-3 py-2 text-sm">
                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAssign = false" class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">Cancel</button>
                    <button type="button" @click="submitAssign" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">Assign</button>
                </div>
            </div>
        </Modal>
    </div>
</template>
