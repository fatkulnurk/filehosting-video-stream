<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = reactive({ code: '', recovery_code: '' });
const processing = ref(false);
const mode = ref('code');

const submit = () => {
    processing.value = true;
    router.post(route('two-factor.login.store'), form, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="bg-surface rounded-2xl shadow-xl p-8">
        <h2 class="page-title mb-1">Two-Factor Authentication</h2>
        <p class="text-sm text-soft mb-6">Enter your authentication code to continue</p>

        <div class="flex gap-2 mb-4">
            <button type="button" @click="mode = 'code'"
                :class="mode === 'code' ? 'bg-brand-600 text-white' : 'bg-surface-soft text-ink-body'"
                class="px-4 py-1.5 rounded-full text-sm font-medium">Use Code</button>
            <button type="button" @click="mode = 'recovery_code'"
                :class="mode === 'recovery_code' ? 'bg-brand-600 text-white' : 'bg-surface-soft text-ink-body'"
                class="px-4 py-1.5 rounded-full text-sm font-medium">Recovery Code</button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div v-if="mode === 'code'">
                <label class="label">Code</label>
                <input v-model="form.code" type="text" inputmode="numeric" autofocus placeholder="123456" class="input tracking-widest" />
            </div>
            <div v-else>
                <label class="label">Recovery Code</label>
                <input v-model="form.recovery_code" type="text" placeholder="Recovery code" autofocus class="input" />
            </div>
            <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                {{ processing ? 'Verifying...' : 'Verify' }}
            </button>
        </form>
    </div>
</template>
