<script setup>
import { reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const page = usePage();
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
        <h2 class="page-title mb-1">Create account</h2>
        <p class="text-sm text-soft mb-6">Start hosting your files and videos</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="label">Name</label>
                <input v-model="form.name" type="text" placeholder="Your name" required autofocus class="input"
                    :class="{ 'border-danger-500': page.props.errors?.name }" />
                <p v-if="page.props.errors?.name" class="form-error mt-1">{{ page.props.errors.name }}</p>
            </div>
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" placeholder="you@example.com" required class="input"
                    :class="{ 'border-danger-500': page.props.errors?.email }" />
                <p v-if="page.props.errors?.email" class="form-error mt-1">{{ page.props.errors.email }}</p>
            </div>
            <div>
                <label class="label">Password</label>
                <input v-model="form.password" type="password" placeholder="Create a password" required class="input"
                    :class="{ 'border-danger-500': page.props.errors?.password }" />
                <p v-if="page.props.errors?.password" class="form-error mt-1">{{ page.props.errors.password }}</p>
            </div>
            <div>
                <label class="label">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" placeholder="Repeat your password" required class="input" />
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                {{ processing ? 'Registering...' : 'Register' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Already have an account?
            <Link :href="route('login')" class="link font-medium">Login</Link>
        </p>
    </div>
</template>
