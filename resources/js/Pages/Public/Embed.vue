<script setup>
import VideoPlayer from '@/Components/VideoPlayer.vue';

defineProps({ file: Object, subtitles: Array });
</script>

<template>
    <div class="bg-black min-h-screen flex items-center justify-center">
        <div class="w-full max-w-4xl">
            <VideoPlayer v-if="file.is_video" :src="file.stream_url" :subtitles="subtitles || []" />
            <img v-else-if="file.is_image" :src="file.stream_url" :alt="file.name" class="w-full" />
            <div v-else class="text-white text-center py-20">
                <p class="text-lg">{{ file.name }}</p>
                <p class="text-ink-faint text-sm mt-2">This file type cannot be previewed.</p>
            </div>
        </div>
    </div>
</template>
