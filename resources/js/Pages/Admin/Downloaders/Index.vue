<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon, PencilIcon, TrashIcon, BoltIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const props = defineProps({
    servers: Array,
    drivers: Object,
    driverFields: Object,
});

const editing = ref(null);
const showAdd = ref(false);
const form = reactive({
    name: '',
    driver: 'local',
    is_active: true,
    config: {},
});

const requiredFields = (driver) => props.driverFields?.[driver] || [];

const openAdd = () => {
    editing.value = null;
    Object.assign(form, { name: '', driver: 'local', is_active: true, config: {} });
    showAdd.value = true;
};

const openEdit = (server) => {
    editing.value = server;
    Object.assign(form, {
        name: server.name,
        driver: server.driver,
        is_active: server.is_active,
        config: { ...(server.config || {}) },
    });
    showAdd.value = true;
};

const submit = () => {
    const payload = {
        name: form.name,
        driver: form.driver,
        is_active: form.is_active,
        config: form.config,
    };

    if (editing.value) {
        router.patch(route('admin.downloaders.update', editing.value.id), payload, { preserveScroll: true });
    } else {
        router.post(route('admin.downloaders.store'), payload, { preserveScroll: true });
    }
    showAdd.value = false;
};

const test = (server) => {
    router.post(route('admin.downloaders.test', server.id), {}, { preserveScroll: true });
};

const remove = async (server) => {
    const ok = await confirm({ message: `Delete downloader "${server.name}"?` });
    if (!ok) return;
    router.delete(route('admin.downloaders.destroy', server.id), { preserveScroll: true });
};
</script>

<template>
    <div>
        <PageHeader title="Downloader Clients">
            <template #actions>
                <Button @click="openAdd">
                    <PlusIcon class="w-4 h-4" /> Add Downloader
                </Button>
            </template>
        </PageHeader>

        <div v-if="servers.length === 0" class="panel p-10 text-center">
            <p class="text-sm text-soft">No downloader clients configured yet.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="server in servers" :key="server.id" class="panel p-5">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold text-ink-strong">{{ server.name }}</div>
                        <div class="text-xs text-soft mt-0.5">{{ server.driver_label }}</div>
                    </div>
                    <Badge :variant="server.is_active ? 'success' : 'neutral'">
                        {{ server.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>

                <dl class="mt-4 space-y-1 text-xs">
                    <template v-for="field in requiredFields(server.driver)" :key="field">
                        <div v-if="server.config[field]" class="flex justify-between gap-2">
                            <dt class="text-faint capitalize">{{ field.replace(/_/g, ' ') }}</dt>
                            <dd class="text-ink-body font-mono truncate max-w-[60%]">{{ server.config[field] }}</dd>
                        </div>
                    </template>
                </dl>

                <div class="flex items-center justify-end gap-1 mt-4 pt-3 border-t border-line">
                    <button
                        v-if="server.driver === 'torrent'"
                        type="button"
                        @click="test(server)"
                        class="btn-icon"
                        title="Test connection"
                    >
                        <BoltIcon class="w-4 h-4" />
                    </button>
                    <button type="button" @click="openEdit(server)" class="btn-icon" title="Edit">
                        <PencilIcon class="w-4 h-4" />
                    </button>
                    <button type="button" @click="remove(server)" class="btn-icon-danger" title="Delete">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit modal -->
        <Modal :show="showAdd" :title="editing ? 'Edit Downloader' : 'Add Downloader'" @close="showAdd = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="label">Name</label>
                    <input v-model="form.name" type="text" placeholder="e.g. Transmission 1" required class="input" />
                </div>

                <div>
                    <label class="label">Driver</label>
                    <select v-model="form.driver" class="input">
                        <option v-for="(label, value) in drivers" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>

                <div v-for="field in requiredFields(form.driver)" :key="field" class="grid grid-cols-1">
                    <label class="label capitalize">{{ field.replace(/_/g, ' ') }}</label>
                    <input
                        v-model="form.config[field]"
                        :type="field.includes('password') ? 'password' : 'text'"
                        placeholder="Enter value"
                        class="input"
                    />
                </div>

                <label class="flex items-center text-sm text-ink-body">
                    <input v-model="form.is_active" type="checkbox" class="rounded mr-2" /> Active
                </label>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAdd = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">
                        {{ editing ? 'Save' : 'Add Downloader' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
