<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = reactive({ email: '' });
const processing = ref(false);
const sent = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('password.email'), form, {
        preserveScroll: true,
        onSuccess: () => (sent.value = true),
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="page-title mb-1">Reset Password</h2>
        <p class="text-sm text-soft mb-6">Enter your email and we'll send you a reset link</p>

        <div v-if="sent" class="alert alert-success-soft mb-4">
            Reset link sent! Check your email.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required autofocus class="input" />
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                {{ processing ? 'Sending...' : 'Send Reset Link' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            <Link :href="route('login')" class="link font-medium">Back to login</Link>
        </p>
    </div>
</template>
