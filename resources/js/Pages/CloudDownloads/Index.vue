<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Modal from '@/Components/Modal.vue';
import FromUrlModal from '@/Components/FromUrlModal.vue';
import FromTorrentModal from '@/Components/FromTorrentModal.vue';
import { ArrowDownTrayIcon, LinkIcon, ArrowPathIcon, XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tasks: Object,
    filters: Object,
    directoryTree: Array,
});

const showUrlModal = ref(false);
const showTorrentModal = ref(false);

const hasActive = () =>
    (props.tasks?.data || []).some(
        (t) => !['completed', 'failed', 'cancelled'].includes(t.status),
    );

let pollTimer = null;

const poll = () => {
    if (!hasActive()) return;
    router.reload({
        only: ['tasks'],
        preserveState: true,
        preserveScroll: true,
    });
};

onMounted(() => {
    if (hasActive()) {
        pollTimer = setInterval(poll, 4000);
    }
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const progressWidth = (task) => Math.max(0, Math.min(100, Math.round((task.progress || 0) * 100)));
const openFile = (task) => {
    if (task.file_id) {
        router.visit(route('files.show', task.file_id));
    }
};
</script>

<template>
    <div>
        <PageHeader title="Cloud Downloads">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button variant="secondary" @click="showUrlModal = true">
                        <LinkIcon class="w-4 h-4" /> From URL
                    </Button>
                    <Button @click="showTorrentModal = true">
                        <ArrowDownTrayIcon class="w-4 h-4" /> From Torrent
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div v-if="tasks.data.length === 0" class="bg-surface rounded-xl shadow p-10 text-center">
            <p class="text-sm text-ink-soft">No downloads yet. Upload files from a URL or torrent magnet link.</p>
        </div>

        <div v-else class="bg-surface rounded-xl shadow divide-y divide-line">
            <div v-for="task in tasks.data" :key="task.id" class="p-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs uppercase tracking-wider px-2 py-0.5 rounded bg-surface-soft text-ink-soft"
                        >{{ task.type }}</span>
                        <span class="text-sm font-medium text-ink-strong truncate">{{ task.source_display }}</span>
                    </div>
                    <div class="text-xs text-ink-faint mt-1">
                        {{ new Date(task.created_at).toLocaleString() }}
                        <span v-if="task.torrent_id && task.torrent"> · {{ task.torrent.name }}</span>
                    </div>

                    <div v-if="!['completed', 'failed', 'cancelled'].includes(task.status)" class="mt-2 max-w-md">
                        <div class="w-full bg-surface-soft rounded-full h-1.5">
                            <div
                                class="h-1.5 rounded-full bg-brand-600 transition-all"
                                :style="{ width: progressWidth(task) + '%' }"
                            ></div>
                        </div>
                        <div class="text-xs text-ink-faint mt-1">
                            <span v-if="task.status === 'downloading'">{{ progressWidth(task) }}%</span>
                            <span v-else>{{ task.status === 'queued' ? 'Queued…' : 'Processing…' }}</span>
                        </div>
                    </div>

                    <div v-if="task.error" class="text-xs text-danger-600 mt-1">{{ task.error }}</div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <Badge :variant="task.status_badge_variant">{{ task.status_label }}</Badge>

                    <button
                        v-if="task.file_id"
                        type="button"
                        @click="openFile(task)"
                        class="p-2 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700"
                        title="View file"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                    </button>

                    <button
                        v-if="['failed', 'cancelled'].includes(task.status)"
                        type="button"
                        @click="router.post(route('cloud-downloads.retry', task.id))"
                        class="p-2 rounded-lg bg-surface-soft hover:bg-line-soft text-ink-body"
                        title="Retry"
                    >
                        <ArrowPathIcon class="w-4 h-4" />
                    </button>

                    <button
                        v-if="!['completed', 'failed', 'cancelled'].includes(task.status)"
                        type="button"
                        @click="router.post(route('cloud-downloads.cancel', task.id))"
                        class="p-2 rounded-lg bg-danger-50 hover:bg-danger-100 text-danger-600"
                        title="Cancel"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <div v-if="tasks.links && tasks.last_page > 1" class="mt-4">
            <div class="flex items-center gap-1 flex-wrap">
                <Link
                    v-for="(link, index) in tasks.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border',
                        link.active ? 'bg-brand-600 text-white border-brand-600' : 'bg-surface border-line text-ink-body hover:bg-surface-soft',
                        link.url ? '' : 'opacity-50 pointer-events-none',
                    ]"
                    v-html="link.label"
                ></Link>
            </div>
        </div>

        <Modal :show="showUrlModal" title="Download from URL" @close="showUrlModal = false">
            <FromUrlModal :directory-tree="directoryTree" @close="showUrlModal = false" />
        </Modal>

        <Modal :show="showTorrentModal" title="Download from Torrent" @close="showTorrentModal = false">
            <FromTorrentModal :directory-tree="directoryTree" @close="showTorrentModal = false" />
        </Modal>
    </div>
</template>
