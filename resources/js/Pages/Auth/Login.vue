<script setup>
import { reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const page = usePage();
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
        <h2 class="page-title mb-1">Welcome back</h2>
        <p class="text-sm text-soft mb-6">Login to your account</p>

        <div v-if="page.props.errors?.email || page.props.errors?.password" class="alert alert-danger-soft mb-4">
            These credentials do not match our records.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required autofocus class="input" />
            </div>
            <div>
                <label class="label">Password</label>
                <input v-model="form.password" type="password" placeholder="Your password" required class="input" />
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm text-muted">
                    <input v-model="form.remember" type="checkbox" class="rounded border-line mr-2" />
                    Remember me
                </label>
                <Link :href="route('password.request')" class="link text-sm">Forgot password?</Link>
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                {{ processing ? 'Logging in...' : 'Login' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Don't have an account?
            <Link :href="route('register')" class="link font-medium">Register</Link>
        </p>
    </div>
</template>
