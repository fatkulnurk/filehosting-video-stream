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
        <h2 class="text-2xl font-bold text-ink mb-1">Confirm Password</h2>
        <p class="text-sm text-ink-soft mb-6">Enter your password to continue</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Password</label>
                <input v-model="form.password" type="password" placeholder="Your password" required autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                Confirm
            </button>
        </form>
    </div>
</template>
