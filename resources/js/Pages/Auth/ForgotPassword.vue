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
        <h2 class="text-2xl font-bold text-ink mb-1">Reset Password</h2>
        <p class="text-sm text-ink-soft mb-6">Enter your email and we'll send you a reset link</p>

        <div v-if="sent" class="bg-success-50 border border-success-200 text-success-700 px-4 py-3 rounded-lg text-sm mb-4">
            Reset link sent! Check your email.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                {{ processing ? 'Sending...' : 'Send Reset Link' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            <Link :href="route('login')" class="text-brand-700 font-medium hover:underline">Back to login</Link>
        </p>
    </div>
</template>
