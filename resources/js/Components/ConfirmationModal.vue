<script setup>
import Modal from '@/Components/Modal.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '@/composables/useConfirm';

const { state, resolve } = useConfirm();
</script>

<template>
    <Modal :show="state.visible" :title="state.title" @close="resolve(false)">
        <div class="space-y-5">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-warning-50">
                    <ExclamationTriangleIcon class="w-6 h-6 text-warning-500" />
                </div>
                <div>
                    <p class="mt-1 text-sm text-ink-soft">{{ state.message }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="resolve(false)"
                    class="px-4 py-2 text-sm text-ink-muted hover:bg-surface-soft rounded-lg">
                    {{ state.cancelLabel }}
                </button>
                <button type="button" @click="resolve(true)"
                    :class="state.danger ? 'bg-danger-600 hover:bg-danger-700' : 'bg-brand-600 hover:bg-brand-700'"
                    class="px-4 py-2 text-sm text-white rounded-lg font-medium">
                    {{ state.confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>