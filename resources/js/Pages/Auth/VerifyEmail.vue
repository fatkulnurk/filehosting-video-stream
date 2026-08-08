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
        <h2 class="page-title mb-2">Verify Your Email</h2>
        <p class="text-sm text-muted mb-6">
            We sent a verification link to <strong>{{ page.props.auth?.user?.email }}</strong>.
            Please check your inbox.
        </p>

        <button type="button" @click="sendVerification" :disabled="processing" class="btn btn-primary btn-md">
            {{ processing ? 'Sending...' : 'Resend Verification Email' }}
        </button>

        <div class="mt-6">
            <button type="button" @click="router.post(route('logout'))" class="text-sm text-soft hover:underline">
                Logout
            </button>
        </div>
    </div>
</template>
