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
        <h2 class="page-title mb-6">Set New Password</h2>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="label">Email</label>
                <input v-model="form.email" type="email" required readonly class="input bg-surface-muted" />
            </div>
            <div>
                <label class="label">New Password</label>
                <input v-model="form.password" type="password" placeholder="New password" required class="input" />
            </div>
            <div>
                <label class="label">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" placeholder="Repeat new password" required class="input" />
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                {{ processing ? 'Resetting...' : 'Reset Password' }}
            </button>
        </form>
    </div>
</template>
