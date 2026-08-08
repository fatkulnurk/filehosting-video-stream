<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Footer from '@/Components/Footer.vue';
import {
    DocumentIcon,
    FolderIcon,
    ServerIcon,
    PlayIcon,
    ArrowDownTrayIcon,
    BoltIcon,
    LinkIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import { formatBytes } from '@/utils/format';

const page = usePage();
const user = () => page.props.auth?.user;
defineProps({ packages: Array });

const featureStats = [
    { value: '100 MB', label: 'Max File Size', icon: DocumentIcon },
    { value: '∞', label: 'Nested Folders', icon: FolderIcon },
    { value: '10', label: 'Storage Drivers', icon: ServerIcon },
    { value: '24/7', label: 'Video Streaming', icon: PlayIcon },
];

const cloudFeatures = [
    {
        title: 'Download from URLs',
        description: 'Paste any direct file URL and the server downloads it into your storage in the background — no need to re-upload.',
        icon: LinkIcon,
    },
    {
        title: 'Torrent support',
        description: 'Add magnet links or .torrent files. A dedicated torrent client downloads them automatically with live progress.',
        icon: ArrowDownTrayIcon,
    },
    {
        title: 'Instant dedup',
        description: 'Already downloaded the same torrent? Files are reused instantly without re-downloading — saving your quota.',
        icon: BoltIcon,
    },
];

const steps = [
    { title: 'Add a source', description: 'Paste a URL or a magnet/.torrent link.' },
    { title: 'Track progress live', description: 'Watch status, progress and errors from the Cloud Downloads page.' },
    { title: 'Access your files', description: 'Automatically moved into your storage when complete.' },
];
</script>

<template>
    <div class="min-h-screen bg-surface-muted">
        <header class="bg-brand-700">
            <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="text-white text-xl font-bold">FHV Stream</div>
                <div class="flex items-center gap-3">
                    <template v-if="user()">
                        <Link :href="route('dashboard')" class="text-white hover:text-brand-200 text-sm">Dashboard</Link>
                        <button type="button" @click="router.post(route('logout'))" class="text-white hover:text-brand-200 text-sm">Logout</button>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-white hover:text-brand-200 text-sm">Login</Link>
                        <Link :href="route('register')" class="bg-surface text-brand-700 px-4 py-1.5 rounded-lg text-sm font-medium">Register</Link>
                    </template>
                </div>
            </div>
        </header>

        <section class="bg-brand-700 pb-20">
            <div class="max-w-6xl mx-auto px-4 text-center pt-16">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Host, Share & Embed Your Videos
                </h1>
                <p class="text-brand-200 text-lg mb-8 max-w-2xl mx-auto">
                    An open-source file hosting platform focused on video sharing.
                    Multi-storage, embed player, link expiry, full analytics, and
                    cloud downloads from URLs or torrents.
                </p>
                <Link :href="user() ? route('files.index') : route('register')"
                    class="inline-block bg-surface text-brand-700 px-8 py-3 rounded-xl font-semibold text-lg hover:bg-brand-50">
                    Get Started Free
                </Link>
            </div>
        </section>

        <section class="max-w-6xl mx-auto px-4 -mt-10">
            <div class="grid md:grid-cols-4 gap-6">
                <Card
                    v-for="(stat, i) in featureStats"
                    :key="i"
                    gradient
                    :icon="stat.icon"
                >
                    <div class="text-2xl font-bold text-brand-700">{{ stat.value }}</div>
                    <div class="text-sm text-ink-soft mt-0.5">{{ stat.label }}</div>
                </Card>
            </div>
        </section>

        <section class="max-w-6xl mx-auto px-4 py-16">
            <h2 class="text-3xl font-bold text-center mb-10">Cloud Downloads</h2>
            <p class="text-center text-ink-soft text-lg max-w-2xl mx-auto mb-10">
                Fetch files straight from the internet into your storage — with built-in
                status tracking and torrent support.
            </p>
            <div class="grid md:grid-cols-3 gap-6">
                <div v-for="feat in cloudFeatures" :key="feat.title"
                    class="bg-surface rounded-2xl shadow border p-6">
                    <component :is="feat.icon" class="w-9 h-9 text-brand-600" />
                    <h3 class="mt-4 text-lg font-semibold text-ink">{{ feat.title }}</h3>
                    <p class="mt-2 text-sm text-ink-muted leading-relaxed">{{ feat.description }}</p>
                </div>
            </div>
            <div class="mt-10 text-center">
                <Link :href="user() ? route('cloud-downloads.index') : route('register')"
                    class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-lg font-medium text-sm">
                    <ArrowDownTrayIcon class="w-4 h-4" /> Open Cloud Downloads
                </Link>
            </div>
        </section>

        <section class="bg-surface">
            <div class="max-w-6xl mx-auto px-4 py-16">
                <h2 class="text-3xl font-bold text-center mb-10">How It Works</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(step, i) in steps" :key="i" class="text-center">
                        <div class="w-12 h-12 mx-auto bg-brand-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                            {{ i + 1 }}
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-ink">{{ step.title }}</h3>
                        <p class="mt-2 text-sm text-ink-muted max-w-xs mx-auto">{{ step.description }}</p>
                    </div>
                </div>
                <div class="mt-10 flex items-center justify-center gap-2 text-sm text-ink-soft">
                    <CheckIcon class="w-4 h-4 text-success-600" /> No re-upload needed — the server does the downloading.
                </div>
            </div>
        </section>

        <section class="max-w-6xl mx-auto px-4 py-16">
            <h2 class="text-3xl font-bold text-center mb-10">Choose Your Plan</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="pkg in packages" :key="pkg.id"
                    class="bg-surface rounded-2xl shadow border p-6 flex flex-col">
                    <div class="text-lg font-semibold text-ink">{{ pkg.name }}</div>
                    <div class="mt-3 text-3xl font-bold text-brand-700">
                        ${{ pkg.price_monthly }}
                        <span class="text-sm text-ink-faint font-normal">/mo</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-ink-muted">
                        <p>{{ formatBytes(pkg.storage_limit) }} storage</p>
                        <p>Max file {{ pkg.formatted_max_file_size }}</p>
                        <p v-if="pkg.features.length === 0">Basic features</p>
                        <p v-for="f in pkg.features" :key="f">{{ f.replace(/_/g, ' ') }}</p>
                    </div>
                    <div class="mt-6">
                        <Link :href="user() ? route('subscriptions.index') : route('register')"
                            class="block text-center bg-brand-600 hover:bg-brand-700 text-white rounded-lg py-2 font-medium text-sm">
                            {{ pkg.price_monthly > 0 ? 'Choose Plan' : 'Start Free' }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <Footer dark />
    </div>
</template>