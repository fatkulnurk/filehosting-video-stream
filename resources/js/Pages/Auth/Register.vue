<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('register.store'), form, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-ink mb-1">Create account</h2>
        <p class="text-sm text-ink-soft mb-6">Start hosting your files and videos</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                <input v-model="form.name" type="text" placeholder="Your name" required autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Password</label>
                <input v-model="form.password" type="password" placeholder="Create a password" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" placeholder="Repeat your password" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                {{ processing ? 'Registering...' : 'Register' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            Already have an account?
            <Link :href="route('login')" class="text-brand-700 font-medium hover:underline">Login</Link>
        </p>
    </div>
</template>
