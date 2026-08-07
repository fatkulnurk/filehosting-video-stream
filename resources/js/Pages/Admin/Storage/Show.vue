<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const props = defineProps({ server: Object });
</script>

<template>
    <div>
        <div class="mb-4">
            <Link :href="route('admin.storage.index')" class="text-sm text-brand-700 hover:underline">← Back</Link>
        </div>

        <div class="bg-surface rounded-xl shadow p-6 max-w-lg">
            <h1 class="text-xl font-bold text-ink mb-4">{{ server.name }}</h1>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink-soft">Driver</dt><dd class="font-medium">{{ server.driver }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Used</dt><dd class="font-medium">{{ formatBytes(server.storage_used) }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Limit</dt><dd class="font-medium">{{ server.storage_limit ? formatBytes(server.storage_limit) : 'Unlimited' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Primary</dt><dd class="font-medium">{{ server.is_primary ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Active</dt><dd class="font-medium">{{ server.is_active ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Backup</dt><dd class="font-medium">{{ server.backup_storage_server?.name || 'None' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Auto backup</dt><dd class="font-medium">{{ server.auto_backup ? 'Yes' : 'No' }}</dd></div>
            </dl>

            <h2 class="font-semibold text-ink-body mt-6 mb-2">Config</h2>
            <pre class="bg-surface-muted rounded-lg p-3 text-xs overflow-x-auto">{{ JSON.stringify(server.config, null, 2) }}</pre>
        </div>
    </div>
</template>
