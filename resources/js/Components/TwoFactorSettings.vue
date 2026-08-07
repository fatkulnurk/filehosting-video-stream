<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from '@/composables/useConfirm';

const { confirm: confirmAction } = useConfirm();

const props = defineProps({ enabled: Boolean });

const qrCode = ref('');
const secretKey = ref('');
const recoveryCodes = ref([]);
const showConfirm = ref(false);
const confirmCode = ref('');
const showCodes = ref(false);
const message = ref('');
const isError = ref(false);
const processing = ref(false);

const enable = async () => {
    processing.value = true;
    message.value = '';
    try {
        await axios.post(route('two-factor.enable'));
        const qr = await axios.get(route('two-factor.qr-code'));
        qrCode.value = qr.data.svg;
        const secret = await axios.get(route('two-factor.secret-key'));
        secretKey.value = secret.data.secretKey;
        showConfirm.value = true;
    } catch (e) {
        isError.value = true;
        message.value = e.response?.data?.message || 'Failed to enable 2FA';
    } finally {
        processing.value = false;
    }
};

const confirm = async () => {
    processing.value = true;
    try {
        await axios.post(route('two-factor.confirm'), { code: confirmCode.value });
        showConfirm.value = false;
        message.value = 'Two-factor authentication enabled!';
        isError.value = false;
        router.reload();
    } catch (e) {
        isError.value = true;
        message.value = e.response?.data?.errors?.code?.[0] || 'Invalid code';
    } finally {
        processing.value = false;
    }
};

const getRecoveryCodes = async () => {
    const res = await axios.get(route('two-factor.recovery-codes'));
    recoveryCodes.value = res.data;
    showCodes.value = true;
};

const regenerate = async () => {
    await axios.post(route('two-factor.regenerate-recovery-codes'));
    getRecoveryCodes();
};

const disable = async () => {
    const ok = await confirmAction({ message: 'Disable two-factor authentication?' });
    if (!ok) return;
    await axios.delete(route('two-factor.disable'));
    message.value = 'Two-factor authentication disabled';
    isError.value = false;
    qrCode.value = '';
    showCodes.value = false;
    router.reload();
};
</script>

<template>
    <div class="bg-surface rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-ink mb-2">Two-Factor Authentication</h2>
        <p class="text-sm text-ink-soft mb-4">
            Add an extra layer of security to your account using a TOTP authenticator app (Google Authenticator, Authy, etc).
        </p>

        <div v-if="message" :class="isError ? 'bg-danger-50 text-danger-600 border-danger-200' : 'bg-success-50 text-success-600 border-success-200'" class="border px-4 py-3 rounded-lg text-sm mb-4">
            {{ message }}
        </div>

        <div v-if="!props.enabled">
            <button type="button" @click="enable" :disabled="processing"
                class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium disabled:opacity-50">
                {{ processing ? 'Enabling...' : 'Enable 2FA' }}
            </button>
        </div>

        <div v-else>
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-success-50 text-success-600 text-sm px-3 py-1 rounded-full">2FA is enabled</span>
                <button type="button" @click="disable" class="text-sm text-danger-600 hover:underline">Disable</button>
            </div>
            <button type="button" @click="showCodes ? (showCodes = false) : getRecoveryCodes()"
                class="text-sm text-brand-700 hover:underline">
                {{ showCodes ? 'Hide recovery codes' : 'Show recovery codes' }}
            </button>
            <button v-if="showCodes" type="button" @click="regenerate" class="text-sm text-ink-soft hover:underline ml-3">
                Regenerate
            </button>
        </div>

        <!-- Confirm step -->
        <div v-if="showConfirm" class="mt-6">
            <h3 class="font-semibold text-ink-strong mb-3">Scan the QR code with your authenticator app</h3>
            <div class="bg-surface-muted rounded-lg p-4 inline-block" v-html="qrCode"></div>
            <p class="text-xs text-ink-soft mt-2">Secret key: <code class="bg-surface-soft px-1 rounded">{{ secretKey }}</code></p>
            <div class="mt-4 flex gap-2">
                <input v-model="confirmCode" type="text" inputmode="numeric" placeholder="6-digit code"
                    class="w-32 rounded-lg border-line border px-3 py-2 text-sm tracking-widest" />
                <button type="button" @click="confirm" :disabled="processing"
                    class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50">Confirm</button>
            </div>
        </div>

        <div v-if="showCodes" class="mt-6">
            <h3 class="font-semibold text-ink-strong mb-2">Recovery Codes</h3>
            <p class="text-xs text-ink-soft mb-2">Store these codes somewhere safe. Each code can be used once.</p>
            <div class="grid grid-cols-1 gap-1 bg-surface-muted rounded-lg p-4">
                <code v-for="code in recoveryCodes" :key="code" class="text-sm text-ink-body">{{ code }}</code>
            </div>
        </div>
    </div>
</template>
