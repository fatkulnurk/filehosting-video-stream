<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    appName: { type: String, default: 'FHV Stream' },
});

const page = usePage();
const user = () => page.props.auth?.user;
</script>

<template>
    <header class="bg-brand-700">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <Link href="/" class="text-white text-xl font-bold">{{ props.appName }}</Link>
            <div class="flex items-center gap-3">
                <template v-if="user()">
                    <Link :href="route('dashboard')" class="text-white hover:text-brand-200 text-sm">Dashboard</Link>
                    <button
                        type="button"
                        @click="router.post(route('logout'))"
                        class="text-white hover:text-brand-200 text-sm"
                    >
                        Logout
                    </button>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="text-white hover:text-brand-200 text-sm">Login</Link>
                    <Link :href="route('register')" class="bg-surface text-brand-700 px-4 py-1.5 rounded-lg text-sm font-medium">
                        Register
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
