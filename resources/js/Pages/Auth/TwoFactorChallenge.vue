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
        <h2 class="text-2xl font-bold text-ink mb-1">Two-Factor Authentication</h2>
        <p class="text-sm text-ink-soft mb-6">Enter your authentication code to continue</p>

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
                <label class="block text-sm font-medium text-ink-body mb-1">Code</label>
                <input v-model="form.code" type="text" inputmode="numeric" autofocus placeholder="123456"
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm tracking-widest focus:ring-2 focus:ring-brand-500" />
            </div>
            <div v-else>
                <label class="block text-sm font-medium text-ink-body mb-1">Recovery Code</label>
                <input v-model="form.recovery_code" type="text" placeholder="Recovery code" autofocus
                    class="w-full rounded-lg border-line border px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500" />
            </div>
            <button type="submit" :disabled="processing"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg disabled:opacity-50">
                {{ processing ? 'Verifying...' : 'Verify' }}
            </button>
        </form>
    </div>
</template>
