<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = reactive({ email: '', password: '', remember: false });
const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('login.store'), form, {
        preserveScroll: true,
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-ink mb-1">Welcome back</h2>
        <p class="text-sm text-ink-soft mb-6">Login to your account</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Password</label>
                <input v-model="form.password" type="password" placeholder="Your password" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" />
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm text-ink-muted">
                    <input v-model="form.remember" type="checkbox" class="rounded border-line mr-2" />
                    Remember me
                </label>
                <Link :href="route('password.request')" class="text-sm text-brand-700 hover:underline">Forgot password?</Link>
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                {{ processing ? 'Logging in...' : 'Login' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            Don't have an account?
            <Link :href="route('register')" class="text-brand-700 font-medium hover:underline">Register</Link>
        </p>
    </div>
</template>
