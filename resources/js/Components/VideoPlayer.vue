<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    src: { type: String, required: true },
    type: { type: String, default: '' },
    poster: { type: String, default: '' },
    subtitles: { type: Array, default: () => [] },
    driver: { type: String, default: '' },
    theaterMode: { type: Boolean, default: false },
});

const emit = defineEmits(['update:theaterMode']);

const page = usePage();

const PlyrPlayer = defineAsyncComponent(() => import('@/Components/Player/PlyrPlayer.vue'));
const VideoJsPlayer = defineAsyncComponent(() => import('@/Components/Player/VideoJsPlayer.vue'));
const DPlayerPlayer = defineAsyncComponent(() => import('@/Components/Player/DPlayerPlayer.vue'));

const activeDriver = computed(() => props.driver || page.props.player?.driver || 'plyr');
const isTheater = ref(props.theaterMode);

const toggleTheater = () => {
    isTheater.value = !isTheater.value;
    emit('update:theaterMode', isTheater.value);
};
</script>

<template>
    <div :class="['relative', isTheater ? 'theater-mode' : '']">
        <button
            type="button"
            @click="toggleTheater"
            class="absolute top-2 right-2 z-20 bg-black/60 hover:bg-black/80 text-white p-2 rounded-lg transition-colors"
            :title="isTheater ? 'Exit Theater Mode' : 'Theater Mode'"
        >
            <svg v-if="!isTheater" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
            </svg>
        </button>
        <PlyrPlayer v-if="activeDriver === 'plyr'" :src="src" :type="type" :poster="poster" :subtitles="subtitles" />
        <VideoJsPlayer v-else-if="activeDriver === 'videojs'" :src="src" :type="type" :poster="poster" :subtitles="subtitles" />
        <DPlayerPlayer v-else-if="activeDriver === 'dplayer'" :src="src" :type="type" :poster="poster" :subtitles="subtitles" />
        <PlyrPlayer v-else :src="src" :type="type" :poster="poster" :subtitles="subtitles" />
    </div>
</template>

<style scoped>
.theater-mode {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.theater-mode :deep(.plyr-react),
.theater-mode :deep(.video-js),
.theater-mode :deep(.dplayer) {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
}

.theater-mode :deep(video) {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>