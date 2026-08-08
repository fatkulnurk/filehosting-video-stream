<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';

const page = usePage();
const user = () => page.props.auth?.user;

defineProps({
    appName: { type: String, default: 'FHV Stream' },
    updatedAt: { type: String, default: '' },
});

const sections = [
    {
        title: '1. Acceptance of Terms',
        body: 'By accessing or using the Service, you agree to be bound by these Terms. If you do not agree, do not use the Service.',
    },
    {
        title: '2. Accounts',
        body: 'You are responsible for safeguarding your account credentials and for all activity that occurs under your account. Notify us immediately of any unauthorized use.',
    },
    {
        title: '3. Acceptable Use',
        body: 'You agree not to upload, share, or distribute content that is unlawful, infringes intellectual property rights, or violates applicable laws. You must comply with all copyright and licensing obligations for any content you host.',
    },
    {
        title: '4. Cloud Downloads & Torrents',
        body: 'The cloud download feature fetches content on your behalf from URLs or torrents you provide. You are solely responsible for ensuring you have the right to download and store such content.',
    },
    {
        title: '5. Storage Quota & Plans',
        body: 'Storage limits are determined by your subscription plan. We may change plan features or limits with reasonable notice.',
    },
    {
        title: '6. Termination',
        body: 'We may suspend or terminate accounts that violate these Terms or abuse the Service, with or without notice.',
    },
    {
        title: '7. Disclaimer & Limitation of Liability',
        body: 'The Service is provided "as is" without warranties of any kind. To the maximum extent permitted by law, we are not liable for indirect, incidental, or consequential damages.',
    },
    {
        title: '8. Changes to These Terms',
        body: 'We may update these Terms from time to time. Continued use of the Service after changes constitutes acceptance of the revised Terms.',
    },
];
</script>

<template>
    <div class="min-h-screen bg-surface-muted">
        <header class="bg-brand-700">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <Link href="/" class="text-white text-xl font-bold">{{ appName }}</Link>
                <div class="flex items-center gap-3">
                    <template v-if="user()">
                        <Link :href="route('dashboard')" class="text-white hover:text-brand-200 text-sm">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-white hover:text-brand-200 text-sm">Login</Link>
                        <Link :href="route('register')" class="bg-surface text-brand-700 px-4 py-1.5 rounded-lg text-sm font-medium">Register</Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold text-ink mb-1">Terms of Service</h1>
            <p v-if="updatedAt" class="text-sm text-ink-soft mb-8">Last updated: {{ updatedAt }}</p>

            <div class="bg-surface rounded-2xl shadow border p-8 space-y-6">
                <section v-for="section in sections" :key="section.title">
                    <h2 class="text-lg font-semibold text-ink">{{ section.title }}</h2>
                    <p class="mt-1 text-sm text-ink-muted leading-relaxed">{{ section.body }}</p>
                </section>
            </div>

            <p class="mt-8 text-sm text-ink-soft">
                Questions? Contact us or review our
                <Link :href="route('privacy')" class="text-brand-700 hover:underline">Privacy Policy</Link>.
            </p>
        </main>

        <Footer dark />
    </div>
</template>
