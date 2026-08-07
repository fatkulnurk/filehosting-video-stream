<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TwoFactorSettings from '@/Components/TwoFactorSettings.vue';
import FileInput from '@/Components/FileInput.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ user: Object, twoFactorEnabled: Boolean });

const profileForm = reactive({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.profile?.phone || '',
    bio: props.user.profile?.bio || '',
});

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });

const submitProfile = () => {
    router.patch(route('user-profile-information.update'), {
        name: profileForm.name,
        email: profileForm.email,
    }, { preserveScroll: true });
};

const submitProfileExtra = () => {
    router.patch(route('profile.update'), {
        phone: profileForm.phone,
        bio: profileForm.bio,
    }, { preserveScroll: true });
};

const submitPassword = () => {
    router.put(route('user-password.update'), passwordForm, {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.current_password = '';
            passwordForm.password = '';
            passwordForm.password_confirmation = '';
        },
    });
};

const avatarFile = ref(null);
const uploadAvatar = () => {
    if (!avatarFile.value) return;
    const form = new FormData();
    form.append('avatar', avatarFile.value);
    router.post(route('profile.avatar'), form, { forceFormData: true, preserveScroll: true });
    avatarFile.value = null;
};

watch(avatarFile, (val) => {
    if (val) uploadAvatar();
});
</script>

<template>
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold text-ink">Profile Settings</h1>

        <!-- Profile info -->
        <div class="bg-surface rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Profile Information</h2>

            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-brand-600 text-white flex items-center justify-center text-2xl font-bold">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <FileInput v-model="avatarFile" accept="image/*" compact hint="PNG or JPG" />
                </div>
            </div>

            <form @submit.prevent="submitProfile" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Name</label>
                        <input v-model="profileForm.name" type="text" placeholder="Your name" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Email</label>
                        <input v-model="profileForm.email" type="email" placeholder="you@example.com" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Phone</label>
                        <input v-model="profileForm.phone" type="text" placeholder="+1 (555) 000-0000" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-1">Bio</label>
                        <input v-model="profileForm.bio" type="text" placeholder="Tell us a little about yourself" class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Info</button>
                    <button type="button" @click="submitProfileExtra" class="bg-surface-soft hover:bg-line-soft text-ink-body px-4 py-2 rounded-lg text-sm font-medium">Save Phone & Bio</button>
                </div>
            </form>
        </div>

        <!-- Password -->
        <div class="bg-surface rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Update Password</h2>
            <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Current Password</label>
                    <input v-model="passwordForm.current_password" type="password" placeholder="Enter current password" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">New Password</label>
                    <input v-model="passwordForm.password" type="password" placeholder="New password" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-body mb-1">Confirm New Password</label>
                    <input v-model="passwordForm.password_confirmation" type="password" placeholder="Repeat new password" required class="w-full rounded-lg border-line border px-3 py-2 text-sm" />
                </div>
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Update Password</button>
            </form>
        </div>

        <!-- 2FA -->
        <TwoFactorSettings :enabled="twoFactorEnabled" />
    </div>
</template>
