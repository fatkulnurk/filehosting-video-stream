<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ file: Object, error: String });

const form = reactive({ password: '' });
const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.post(route('public.unlock', props.file.id), form, {
        onFinish: () => (processing.value = false),
    });
};
</script>

<template>
    <div class="min-h-screen bg-surface-soft flex items-center justify-center p-4">
        <div class="max-w-sm w-full">
            <div class="bg-surface rounded-2xl shadow-lg p-8">
                <div class="text-center mb-6">
                    <LockClosedIcon class="w-12 h-12 mx-auto text-brand-700" />
                    <h1 class="text-xl font-bold text-ink mt-4">This file is password protected</h1>
                    <p class="text-sm text-soft mt-1">{{ file.name }} ({{ file.formatted_size }})</p>
                </div>

                <div v-if="error" class="alert alert-danger-soft mb-4">
                    {{ error }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <input v-model="form.password" type="password" placeholder="Enter password" required autofocus
                        class="input py-2.5" />
                    <button type="submit" :disabled="processing" class="btn btn-primary btn-md w-full">
                        {{ processing ? 'Checking...' : 'Unlock File' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
