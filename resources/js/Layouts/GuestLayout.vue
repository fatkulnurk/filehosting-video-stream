<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-brand-700 flex flex-col">
        <div class="flex items-center justify-center pt-8">
            <Link href="/" class="text-white text-3xl font-bold">FHV Stream</Link>
        </div>
        <main class="flex-1 flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>
        <footer class="text-center text-brand-300 text-xs py-6">
            FileHosting Video Stream — Open Source File Hosting
        </footer>
    </div>
</template>
