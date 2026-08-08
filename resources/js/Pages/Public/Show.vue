<script setup>
import { ref } from 'vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';
import Footer from '@/Components/Footer.vue';
import { ArrowDownTrayIcon, CodeBracketIcon, LinkIcon } from '@heroicons/vue/24/outline';

defineProps({ file: Object, subtitles: Array });

const copySuccess = ref('');

const copyText = (text, msg) => {
    navigator.clipboard.writeText(text).then(() => {
        copySuccess.value = msg;
        setTimeout(() => (copySuccess.value = ''), 2000);
    });
};
</script>

<template>
    <div class="min-h-screen bg-surface-soft">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <div v-if="copySuccess" class="fixed bottom-4 right-4 bg-ink text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50">
                {{ copySuccess }}
            </div>

            <div class="bg-surface rounded-2xl shadow-lg overflow-hidden">
                <div v-if="file.is_video" class="bg-black">
                    <VideoPlayer :src="file.media_url" :subtitles="subtitles || []" />
                </div>
                <div v-else-if="file.is_image" class="p-4">
                    <img :src="file.media_url" :alt="file.name" class="rounded-lg mx-auto max-h-96" />
                </div>
                <div v-else class="p-8 text-center">
                    <svg class="w-16 h-16 mx-auto text-ink-fainter" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <div class="p-6">
                    <h1 class="text-xl font-bold text-ink">{{ file.name }}</h1>
                    <p class="text-sm text-ink-soft mt-1">
                        {{ file.formatted_size }} · {{ file.extension.toUpperCase() }} · Uploaded {{ file.created_at }} by {{ file.uploaded_by }}
                    </p>
                    <p class="text-sm text-ink-soft mt-1">
                        {{ file.views.toLocaleString() }} views · {{ file.downloads.toLocaleString() }} downloads
                    </p>

                    <div class="flex flex-wrap gap-3 mt-6">
                        <a :href="file.download_url" download
                            class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm">
                            <ArrowDownTrayIcon class="w-4 h-4" /> Download
                        </a>
                        <button type="button" @click="copyText(file.download_url, 'Link copied!')"
                            class="inline-flex items-center gap-2 bg-surface-soft hover:bg-line-soft px-5 py-2.5 rounded-lg font-medium text-sm">
                            <LinkIcon class="w-4 h-4" /> Copy Link
                        </button>
                        <button type="button" @click="copyText(file.embed_code, 'Embed code copied!')"
                            class="inline-flex items-center gap-2 bg-surface-soft hover:bg-line-soft px-5 py-2.5 rounded-lg font-medium text-sm">
                            <CodeBracketIcon class="w-4 h-4" /> Copy Embed
                        </button>
                    </div>

                    <div v-if="file.is_video" class="mt-6">
                        <p class="text-xs uppercase tracking-wider text-ink-faint font-medium mb-2">Embed Code</p>
                        <textarea readonly rows="2" class="w-full text-xs bg-surface-muted border rounded-lg px-3 py-2 font-mono">{{ file.embed_code }}</textarea>
                    </div>
                </div>
            </div>

        <Footer />
        </div>
    </div>
</template>
