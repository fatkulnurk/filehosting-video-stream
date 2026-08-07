<script setup>
import { Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: GuestLayout });

const page = usePage();
const processing = ref(false);

const sendVerification = () => {
    processing.value = true;
    router.post(route('verification.send'), {}, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8 text-center">
        <h2 class="text-2xl font-bold text-ink mb-2">Verify Your Email</h2>
        <p class="text-sm text-ink-muted mb-6">
            We sent a verification link to <strong>{{ page.props.auth?.user?.email }}</strong>.
            Please check your inbox.
        </p>

        <button type="button" @click="sendVerification" :disabled="processing"
            class="bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 px-6 rounded-lg disabled:opacity-50">
            {{ processing ? 'Sending...' : 'Resend Verification Email' }}
        </button>

        <div class="mt-6">
            <button type="button" @click="router.post(route('logout'))" class="text-sm text-ink-soft hover:underline">
                Logout
            </button>
        </div>
    </div>
</template>
