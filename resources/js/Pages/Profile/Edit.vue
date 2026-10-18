<script setup>
import { computed, reactive, ref, watch } from 'vue';
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
    router.patch(route('profile.update'), {
        name: profileForm.name,
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
const avatarUrl = computed(() =>
    props.user.profile?.avatar ? `/storage/${props.user.profile.avatar}` : null,
);
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
        <h1 class="page-title">Profile Settings</h1>

        <!-- Profile info -->
        <div class="panel p-6">
            <h2 class="section-title mb-4">Profile Information</h2>

            <div class="flex items-center gap-4 mb-6">
                <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    :alt="user.name"
                    class="w-16 h-16 rounded-full object-cover"
                />
                <div v-else class="w-16 h-16 rounded-full bg-brand-600 text-white flex items-center justify-center text-2xl font-bold">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <FileInput v-model="avatarFile" accept="image/*" compact hint="PNG or JPG" />
                </div>
            </div>

            <form @submit.prevent="submitProfile" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Name</label>
                        <input v-model="profileForm.name" type="text" placeholder="Your name" class="input" />
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input v-model="profileForm.email" type="email" placeholder="you@example.com" class="input bg-surface-soft opacity-70" readonly />
                    </div>
                    <div>
                        <label class="label">Phone</label>
                        <input v-model="profileForm.phone" type="text" placeholder="+1 (555) 000-0000" class="input" />
                    </div>
                    <div>
                        <label class="label">Bio</label>
                        <input v-model="profileForm.bio" type="text" placeholder="Tell us a little about yourself" class="input" />
                    </div>
                </div>
                <div class="flex">
                    <button type="submit" class="btn btn-primary btn-md">Save</button>
                </div>
            </form>
        </div>

        <!-- Password -->
        <div class="panel p-6">
            <h2 class="section-title mb-4">Update Password</h2>
            <form @submit.prevent="submitPassword" class="space-y-4 max-w-md">
                <div>
                    <label class="label">Current Password</label>
                    <input v-model="passwordForm.current_password" type="password" placeholder="Enter current password" required class="input" />
                </div>
                <div>
                    <label class="label">New Password</label>
                    <input v-model="passwordForm.password" type="password" placeholder="New password" required class="input" />
                </div>
                <div>
                    <label class="label">Confirm New Password</label>
                    <input v-model="passwordForm.password_confirmation" type="password" placeholder="Repeat new password" required class="input" />
                </div>
                <button type="submit" class="btn btn-primary btn-md">Update Password</button>
            </form>
        </div>

        <!-- 2FA -->
        <TwoFactorSettings :enabled="twoFactorEnabled" />
    </div>
</template>
