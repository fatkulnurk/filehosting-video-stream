<script setup>
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import DPlayer from 'dplayer';

const props = defineProps({
    src: { type: String, required: true },
    type: { type: String, default: '' },
    poster: { type: String, default: '' },
    subtitles: { type: Array, default: () => [] },
});

const containerEl = ref(null);
let player = null;

const isHls = computed(() => {
    const src = props.src.toLowerCase();
    return src.includes('.m3u8') || props.type === 'hls' || props.type === 'application/x-mpegurl' || props.type === 'application/vnd.apple.mpegurl';
});

const blockKeys = (e) => {
    const k = e.key.toLowerCase();
    if (
        (e.ctrlKey || e.metaKey) && ['s', 'u', 'j', 'p'].includes(k) ||
        e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && ['s', 'i', 'j', 'c'].includes(k))
    ) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
};

const defaultTrack = () => props.subtitles.find((t) => t.default) || props.subtitles[0] || null;

onMounted(() => {
    if (!containerEl.value) return;

    document.addEventListener('keydown', blockKeys, true);

    const track = defaultTrack();

    const videoConfig = {
        url: props.src,
        type: props.type || 'auto',
        pic: props.poster || undefined,
    };

    if (isHls.value) {
        videoConfig.type = 'hls';
    }

    player = new DPlayer({
        container: containerEl.value,
        video: videoConfig,
        subtitle: track
            ? {
                  url: track.url,
                  type: track.url.includes('.vtt') ? 'webvtt' : 'srt',
              }
            : undefined,
        screenshot: false,
        hotkey: true,
        preload: 'metadata',
        volume: 0.7,
        playbackSpeed: [0.5, 0.75, 1, 1.25, 1.5, 2],
        contextmenu: [],
        logo: '',
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', blockKeys, true);
    if (player) {
        player.destroy();
        player = null;
    }
});
</script>

<template>
    <div class="relative" @contextmenu.prevent>
        <div ref="containerEl" class="rounded-lg overflow-hidden" @dragstart.prevent></div>
    </div>
</template>