<script setup>
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';

const props = defineProps({
    src: { type: String, required: true },
    type: { type: String, default: '' },
    poster: { type: String, default: '' },
    subtitles: { type: Array, default: () => [] },
});

const videoEl = ref(null);
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

onMounted(() => {
    if (!videoEl.value) return;

    document.addEventListener('keydown', blockKeys, true);

    const options = {
        autoplay: false,
        controls: true,
        preload: 'metadata',
        poster: props.poster || undefined,
        controlBar: {
            pictureInPictureToggle: false,
            children: ['playToggle', 'currentTimeDisplay', 'timeDivider', 'durationDisplay', 'progressControl', 'remainingTimeDisplay', 'volumePanel', 'captionsButton', 'settingsMenuButton', 'fullscreenToggle'],
        },
        userActions: { hotkeys: false },
        html5: {
            nativeControlsForTouch: false,
            vhs: {
                overrideNative: !window.MediaSource,
            },
        },
    };

    if (isHls.value) {
        options.sources = [{
            src: props.src,
            type: props.type || 'application/x-mpegurl',
        }];
    }

    player = videojs(videoEl.value, options);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', blockKeys, true);
    if (player) {
        player.dispose();
        player = null;
    }
});
</script>

<template>
    <div class="relative" @contextmenu.prevent>
        <video
            ref="videoEl"
            :src="!isHls ? src : undefined"
            :type="!isHls ? type : undefined"
            :poster="poster"
            class="video-js vjs-default-skin vjs-big-play-centered w-full rounded-lg bg-black"
            playsinline
            controlslist="nodownload noremoteplayback"
            disablepictureinpicture
            @dragstart.prevent
        >
            <track
                v-for="(track, i) in subtitles"
                :key="track.id ?? i"
                kind="captions"
                :src="track.url"
                :srclang="track.srclang"
                :label="track.label"
                :default="track.default"
            />
        </video>
    </div>
</template>