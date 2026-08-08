<script setup>
import { reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { PlusIcon, TrashIcon, ArrowPathIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const { confirm } = useConfirm();

const page = usePage();
const props = defineProps({ webhooks: Array, events: Array });

const showAdd = ref(false);
const showSecret = ref(false);
const secretValue = ref('');
const editing = ref(null);
const form = reactive({ url: '', events: ['file.uploaded'], secret: '', is_active: true });

const pageSecret = () => page.props.flash?.webhookSecret;

const openAdd = () => {
    editing.value = null;
    Object.assign(form, { url: '', events: ['file.uploaded'], secret: '', is_active: true });
    showAdd.value = true;
};

const openEdit = (webhook) => {
    editing.value = webhook;
    Object.assign(form, {
        url: webhook.url,
        events: webhook.events,
        secret: '',
        is_active: webhook.is_active,
    });
    showAdd.value = true;
};

const submit = () => {
    const payload = { url: form.url, events: form.events, secret: form.secret, is_active: form.is_active };
    if (editing.value) {
        router.patch(route('webhooks.update', editing.value.id), payload, { preserveScroll: true });
    } else {
        router.post(route('webhooks.store'), payload, {
            preserveScroll: true,
            onSuccess: () => {
                secretValue.value = pageSecret();
                showSecret.value = !!secretValue.value;
            },
        });
    }
    showAdd.value = false;
};

const remove = async (webhook) => {
    const ok = await confirm({ message: 'Delete this webhook?' });
    if (!ok) return;
    router.delete(route('webhooks.destroy', webhook.id), { preserveScroll: true });
};

const regenerate = async (webhook) => {
    const ok = await confirm({
        message: 'Regenerate webhook secret?',
        confirmLabel: 'Regenerate',
        danger: false,
    });
    if (!ok) return;
    router.post(route('webhooks.regenerate', webhook.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            secretValue.value = pageSecret();
            showSecret.value = true;
        },
    });
};

const copySecret = () => navigator.clipboard.writeText(secretValue.value);
</script>

<template>
    <div>
        <PageHeader title="Webhooks" subtitle="Receive notifications when events happen in your account.">
            <template #actions>
                <Button @click="openAdd">
                    <PlusIcon class="w-4 h-4" /> New Webhook
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-3">
            <div v-for="webhook in webhooks" :key="webhook.id" class="panel p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-ink-strong break-all">{{ webhook.url }}</span>
                            <Badge :variant="webhook.is_active ? 'success' : 'neutral'">{{ webhook.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <Badge v-for="ev in webhook.events" :key="ev" variant="brand">{{ ev }}</Badge>
                        </div>
                        <div class="mt-2 text-xs text-faint">
                            {{ webhook.failure_count }} failure(s) · Last triggered: {{ webhook.last_triggered_at ? new Date(webhook.last_triggered_at).toLocaleString() : 'Never' }}
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <Link :href="route('webhooks.logs', webhook.id)" class="btn-icon" title="Logs"><DocumentTextIcon class="w-4 h-4" /></Link>
                        <button type="button" @click="regenerate(webhook)" class="btn-icon hover:text-warning-600" title="Regenerate secret"><ArrowPathIcon class="w-4 h-4" /></button>
                        <button type="button" @click="remove(webhook)" class="btn-icon-danger" title="Delete"><TrashIcon class="w-4 h-4" /></button>
                    </div>
                </div>
            </div>
            <div v-if="webhooks.length === 0" class="panel p-16 text-center text-faint">
                No webhooks configured yet.
            </div>
        </div>

        <!-- Add/Edit modal -->
        <Modal :show="showAdd" :title="editing ? 'Edit Webhook' : 'Create Webhook'" @close="showAdd = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="label">Endpoint URL</label>
                    <input v-model="form.url" type="url" required placeholder="https://example.com/webhook" class="input" />
                </div>
                <div>
                    <label class="label mb-2">Events</label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto">
                        <label v-for="ev in events" :key="ev" class="flex items-center text-sm text-ink-body">
                            <input v-model="form.events" type="checkbox" :value="ev" class="rounded mr-2" />
                            {{ ev }}
                        </label>
                    </div>
                </div>
                <div>
                    <label class="label">Secret (optional, for HMAC signing)</label>
                    <input v-model="form.secret" type="text" :placeholder="editing ? '(keep blank to keep current)' : 'Auto-generated if empty'" class="input" />
                </div>
                <label class="flex items-center text-sm text-ink-body">
                    <input v-model="form.is_active" type="checkbox" class="rounded mr-2" /> Active
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAdd = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-md">{{ editing ? 'Save' : 'Create' }}</button>
                </div>
            </form>
        </Modal>

        <!-- Secret modal -->
        <Modal :show="showSecret" title="Webhook Secret" @close="showSecret = false">
            <div class="space-y-4">
                <p class="text-sm text-muted">Save this secret — it's shown only once. Use it to verify incoming signatures.</p>
                <div class="bg-surface-muted border rounded-lg p-3 flex items-center gap-2">
                    <code class="text-sm break-all flex-1">{{ secretValue }}</code>
                    <button type="button" @click="copySecret" class="link text-sm">Copy</button>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="showSecret = false" class="btn btn-primary btn-md">Done</button>
                </div>
            </div>
        </Modal>
    </div>
</template>
