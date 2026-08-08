<script setup>
import { Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
import PublicHeader from '@/Components/PublicHeader.vue';

defineProps({
    appName: { type: String, default: 'FHV Stream' },
    updatedAt: { type: String, default: '' },
});

const sections = [
    {
        title: '1. Information We Collect',
        body: 'We collect account information (name, email), file metadata, usage data (views, downloads), and technical data such as IP addresses and user agents to operate and secure the Service.',
    },
    {
        title: '2. How We Use Your Information',
        body: 'We use your information to provide and maintain the Service, enforce storage limits, display analytics, improve performance, and ensure security and compliance.',
    },
    {
        title: '3. Content You Store',
        body: 'Your files and videos remain yours. We do not sell your content. Files you mark public are accessible via public links; private files are only accessible to you and those you share with.',
    },
    {
        title: '4. Cloud Downloads & Torrents',
        body: 'When you use cloud downloads or torrents, we process the URLs and torrent metadata you provide solely to perform the download on your behalf. Downloaded content is stored according to your privacy settings.',
    },
    {
        title: '5. Cookies & Local Storage',
        body: 'We use cookies and local storage for authentication, session management, and remembering preferences.',
    },
    {
        title: '6. Data Retention',
        body: 'We retain your data while your account is active. Deleted files are moved to trash and permanently removed according to our retention schedule. Download tasks and logs may be pruned periodically.',
    },
    {
        title: '7. Third-Party Services',
        body: 'We may use third-party storage providers, analytics, and infrastructure services to deliver the Service. These providers process data only on our behalf.',
    },
    {
        title: '8. Security',
        body: 'We implement reasonable technical and organizational measures to protect your data, including encryption in transit and access controls.',
    },
    {
        title: '9. Your Rights',
        body: 'You may access, correct, export, or delete your personal data and content. Contact us to exercise these rights.',
    },
    {
        title: '10. Changes to This Policy',
        body: 'We may update this Privacy Policy from time to time. Continued use of the Service after changes constitutes acceptance.',
    },
];
</script>

<template>
    <div class="min-h-screen bg-surface-muted">
        <PublicHeader :app-name="appName" />

        <main class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold text-ink mb-1">Privacy Policy</h1>
            <p v-if="updatedAt" class="text-sm text-ink-soft mb-8">Last updated: {{ updatedAt }}</p>

            <div class="bg-surface rounded-2xl shadow border p-8 space-y-6">
                <section v-for="section in sections" :key="section.title">
                    <h2 class="text-lg font-semibold text-ink">{{ section.title }}</h2>
                    <p class="mt-1 text-sm text-ink-muted leading-relaxed">{{ section.body }}</p>
                </section>
            </div>

            <p class="mt-8 text-sm text-ink-soft">
                Questions about privacy? Contact us or review our
                <Link :href="route('terms')" class="text-brand-700 hover:underline">Terms of Service</Link>.
            </p>
        </main>

        <Footer dark />
    </div>
</template>
