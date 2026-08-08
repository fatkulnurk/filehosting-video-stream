<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = reactive({ password: '' });
const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('password.confirm.store'), form, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="page-title mb-1">Confirm Password</h2>
        <p class="text-sm text-soft mb-6">Enter your password to continue</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="label">Password</label>
                <input v-model="form.password" type="password" placeholder="Your password" required autofocus class="input" />
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                Confirm
            </button>
        </form>
    </div>
</template>
