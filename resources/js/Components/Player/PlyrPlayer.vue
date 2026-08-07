<script setup>
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import Plyr from 'plyr';
import Hls from 'hls.js';
import 'plyr/dist/plyr.css';

const props = defineProps({
    src: { type: String, required: true },
    type: { type: String, default: '' },
    poster: { type: String, default: '' },
    subtitles: { type: Array, default: () => [] },
});

const videoEl = ref(null);
let player = null;
let hls = null;

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

const initHls = () => {
    if (!videoEl.value || !isHls.value) return;

    if (Hls.isSupported()) {
        hls = new Hls({
            enableWorker: true,
            lowLatencyMode: false,
        });
        hls.loadSource(props.src);
        hls.attachMedia(videoEl.value);

        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            if (player) {
                const qualityLevels = hls.levels.map((level, i) => ({
                    value: i,
                    label: level.height ? `${level.height}p` : 'Auto',
                    badge: level.height ? null : 'Auto',
                }));
                qualityLevels.unshift({ value: -1, label: 'Auto', badge: 'Auto' });
                player.options.quality = {
                    default: -1,
                    options: qualityLevels.map((q) => q.value),
                    forced: true,
                    onChange: (newQuality) => {
                        hls.currentLevel = newQuality;
                    },
                };
                player.quality = player.options.quality;
            }
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hls.recoverMediaError();
                        break;
                    default:
                        hls.destroy();
                        break;
                }
            }
        });
    } else if (videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoEl.value.src = props.src;
    }
};

onMounted(() => {
    if (!videoEl.value) return;

    document.addEventListener('keydown', blockKeys, true);

    player = new Plyr(videoEl.value, {
        controls: ['play-large', 'restart', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'settings', 'captions', 'fullscreen'],
        settings: ['captions', 'speed', 'quality'],
        captions: { active: true, language: 'en', update: true },
        disableContextMenu: false,
        keyboard: { focused: true, global: false },
        tooltips: { controls: true, seek: true },
        quality: {
            default: -1,
            options: [-1],
            forced: true,
        },
    });

    if (isHls.value) {
        initHls();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', blockKeys, true);
    if (hls) {
        hls.destroy();
        hls = null;
    }
    if (player) {
        player.destroy();
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
            class="plyr-react w-full rounded-lg bg-black"
            preload="metadata"
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