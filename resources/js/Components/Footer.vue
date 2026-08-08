<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    appName: { type: String, default: 'FHV Stream' },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <footer :class="['py-8', dark ? 'bg-ink text-ink-faint' : 'bg-surface border-t border-line text-ink-soft']">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-sm">{{ appName }} — Open Source File Hosting</p>
            <nav class="flex items-center gap-4 text-sm">
                <Link :href="route('terms')" class="hover:text-brand-700" :class="dark ? 'hover:text-brand-300' : ''">Terms</Link>
                <Link :href="route('privacy')" class="hover:text-brand-700" :class="dark ? 'hover:text-brand-300' : ''">Privacy Policy</Link>
            </nav>
        </div>
    </footer>
</template>
