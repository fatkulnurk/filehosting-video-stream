<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: Number, default: 500 },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-surface-muted flex items-center justify-center p-4">
        <div class="text-center max-w-md">
            <div class="text-7xl font-bold text-brand-700 mb-4">{{ status }}</div>
            <h1 class="text-2xl font-bold text-ink mb-2">
                {{ status === 404 ? 'Page Not Found' : status === 403 ? 'Access Denied' : 'Something Went Wrong' }}
            </h1>
            <p class="text-ink-muted mb-6">{{ message }}</p>
            <Link href="/" class="inline-block bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-lg font-medium">
                Back to Home
            </Link>
        </div>
    </div>
</template>
