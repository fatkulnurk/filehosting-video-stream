<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const props = defineProps({ token: String, email: String });

const form = reactive({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('password.update'), form, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold text-ink mb-6">Set New Password</h2>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                <input v-model="form.email" type="email" required readonly
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm bg-surface-muted" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">New Password</label>
                <input v-model="form.password" type="password" placeholder="New password" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-ink-body mb-1">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" placeholder="Repeat new password" required
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                {{ processing ? 'Resetting...' : 'Reset Password' }}
            </button>
        </form>
    </div>
</template>
