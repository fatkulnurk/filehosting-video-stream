<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Squares2X2Icon,
    FolderIcon,
    TrashIcon,
    StarIcon,
    ArrowLeftOnRectangleIcon,
    UserCircleIcon,
    KeyIcon,
    BellIcon,
    ShoppingBagIcon,
    ClockIcon,
    ArrowPathIcon,
    ComputerDesktopIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import FlashMessages from '@/Components/FlashMessages.vue';
import NavLink from '@/Components/NavLink.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Footer from '@/Components/Footer.vue';
import { formatBytes } from '@/utils/format';

defineProps({ title: { type: String, default: '' } });

const page = usePage();
const user = () => page.props.auth?.user;
const mobileMenu = ref(null);
</script>

<template>
    <div class="min-h-screen bg-surface-soft">
        <FlashMessages />
        <ConfirmationModal />

        <!-- Mobile top bar -->
        <div class="relative z-50 lg:hidden bg-brand-600 text-white px-4 py-3 flex items-center justify-between">
            <Link href="/" class="font-bold text-lg">FHV Stream</Link>
            <details ref="mobileMenu">
                <summary class="cursor-pointer list-none text-white">
                    <Squares2X2Icon class="w-6 h-6" />
                </summary>
                <nav class="absolute left-0 right-0 top-full bg-brand-700 px-4 py-3 space-y-1 shadow-lg">
                    <Link :href="route('dashboard')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Dashboard</Link>
                    <Link :href="route('files.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">My Files</Link>
                    <Link :href="route('trash.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Trash</Link>
                    <Link :href="route('cloud-downloads.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Cloud Downloads</Link>
                    <Link :href="route('subscriptions.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Plan & Billing</Link>
                    <template v-if="user()?.is_admin">
                        <div class="pt-3 pb-1 text-xs uppercase tracking-wider text-brand-300">Admin</div>
                        <Link :href="route('admin.dashboard')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Admin Dashboard</Link>
                        <Link :href="route('admin.users.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Users</Link>
                        <Link :href="route('admin.files.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Files</Link>
                        <Link :href="route('admin.directories.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Directories</Link>
                        <Link :href="route('admin.subscriptions.index')" @click="mobileMenu.open = false" class="block px-3 py-2 text-sm text-brand-100 hover:bg-brand-800 rounded">Subscriptions</Link>
                    </template>
                </nav>
            </details>
        </div>

        <div class="lg:flex">
            <!-- Sidebar -->
            <aside class="hidden lg:flex bg-brand-700 text-white w-64 h-screen sticky top-0 flex-shrink-0 flex-col">
                <div class="p-6">
                    <Link href="/" class="text-2xl font-bold">FHV Stream</Link>
                </div>

                <nav class="px-3 space-y-1 flex-1 overflow-y-auto">
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <Squares2X2Icon class="w-5 h-5" /> Dashboard
                    </NavLink>
                    <NavLink :href="route('files.index')" :active="route().current('files.index') || route().current('files.show')">
                        <FolderIcon class="w-5 h-5" /> My Files
                    </NavLink>
                    <NavLink :href="route('files.favorites')" :active="route().current('files.favorites')">
                        <StarIcon class="w-5 h-5" /> Favorites
                    </NavLink>
                    <NavLink :href="route('cloud-downloads.index')" :active="route().current('cloud-downloads.*')">
                        <ArrowDownTrayIcon class="w-5 h-5" /> Cloud Downloads
                    </NavLink>
                    <NavLink :href="route('files.recently-accessed')" :active="route().current('files.recently-accessed')">
                        <ClockIcon class="w-5 h-5" /> Recently Accessed
                    </NavLink>
                    <NavLink :href="route('files.shared-with-me')" :active="route().current('files.shared-with-me')">
                        <ArrowPathIcon class="w-5 h-5" /> Shared With Me
                    </NavLink>
                    <NavLink :href="route('trash.index')" :active="route().current('trash.index')">
                        <TrashIcon class="w-5 h-5" /> Trash
                    </NavLink>

                    <div class="pt-4 mt-4 border-t border-brand-800 space-y-1">
                        <NavLink :href="route('subscriptions.index')" :active="route().current('subscriptions.index')">
                            <ShoppingBagIcon class="w-5 h-5" /> Plan & Billing
                        </NavLink>
                        <NavLink :href="route('api-keys.index')" :active="route().current('api-keys.index')">
                            <KeyIcon class="w-5 h-5" /> API Keys
                        </NavLink>
                        <NavLink :href="route('webhooks.index')" :active="route().current('webhooks.index')">
                            <BellIcon class="w-5 h-5" /> Webhooks
                        </NavLink>
                        <NavLink :href="route('activity-logs.index')" :active="route().current('activity-logs.index')">
                            <ArrowPathIcon class="w-5 h-5" /> Activity Logs
                        </NavLink>
                    </div>

                    <div v-if="user()?.is_admin" class="pt-4 mt-4 border-t border-brand-800 space-y-1">
                        <div class="px-4 py-2 text-xs uppercase tracking-wider text-brand-300">Admin</div>
                        <NavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                            <Squares2X2Icon class="w-5 h-5" /> Dashboard
                        </NavLink>
                        <div class="px-4 pt-3 pb-1 text-xs uppercase tracking-wider text-brand-400/70">Users</div>
                        <NavLink :href="route('admin.users.index')" :active="route().current('admin.users.*')">
                            <UserCircleIcon class="w-5 h-5" /> Users
                        </NavLink>
                        <div class="px-4 pt-3 pb-1 text-xs uppercase tracking-wider text-brand-400/70">Content</div>
                        <NavLink :href="route('admin.files.index')" :active="route().current('admin.files.*')">
                            <FolderIcon class="w-5 h-5" /> Files
                        </NavLink>
                        <NavLink :href="route('admin.directories.index')" :active="route().current('admin.directories.*')">
                            <FolderIcon class="w-5 h-5" /> Directories
                        </NavLink>
                        <div class="px-4 pt-3 pb-1 text-xs uppercase tracking-wider text-brand-400/70">Storage</div>
                        <NavLink :href="route('admin.storage.index')" :active="route().current('admin.storage.*')">
                            <ComputerDesktopIcon class="w-5 h-5" /> Servers
                        </NavLink>
                        <NavLink :href="route('admin.packages.index')" :active="route().current('admin.packages.*')">
                            <ShoppingBagIcon class="w-5 h-5" /> Packages
                        </NavLink>
                        <div class="px-4 pt-3 pb-1 text-xs uppercase tracking-wider text-brand-400/70">Billing</div>
                        <NavLink :href="route('admin.subscriptions.index')" :active="route().current('admin.subscriptions.*')">
                            <ShoppingBagIcon class="w-5 h-5" /> Subscriptions
                        </NavLink>
                        <div class="px-4 pt-3 pb-1 text-xs uppercase tracking-wider text-brand-400/70">System</div>
                        <NavLink :href="route('admin.downloaders.index')" :active="route().current('admin.downloaders.*')">
                            <ArrowDownTrayIcon class="w-5 h-5" /> Downloaders
                        </NavLink>
                        <NavLink :href="route('admin.logs.index')" :active="route().current('admin.logs.*')">
                            <ArrowPathIcon class="w-5 h-5" /> Logs
                        </NavLink>
                    </div>
                </nav>

                <div class="p-4 border-t border-brand-800 text-xs text-brand-300 mt-auto shrink-0">
                    <div class="text-[11px] uppercase tracking-wider text-brand-400 mb-1">Storage</div>
                    <div class="font-medium text-brand-200">
                        {{ formatBytes(user()?.storage_used || 0) }} / {{ user()?.storage_limit ? formatBytes(user()?.storage_limit) : '∞' }}
                    </div>
                    <div class="w-full bg-brand-900 rounded-full h-1.5 mt-2">
                        <div class="bg-success-400 h-1.5 rounded-full" :style="{ width: Math.min(100, user()?.usage_percentage || 0) + '%' }"></div>
                    </div>
                </div>
            </aside>

            <!-- Content -->
            <div class="flex-1 flex flex-col min-h-screen">
                <header class="bg-surface shadow-sm px-4 lg:px-8 py-3 flex items-center justify-between">
                    <div class="text-lg font-semibold text-ink-strong">{{ title }}</div>
                    <div class="flex items-center gap-3">
                        <span class="hidden sm:block text-sm text-ink-soft">{{ user()?.name }}</span>
                        <details class="relative">
                            <summary class="cursor-pointer list-none">
                                <div class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                                    {{ (user()?.name || '?').charAt(0).toUpperCase() }}
                                </div>
                            </summary>
                            <ul class="absolute right-0 mt-2 w-48 bg-surface rounded-lg shadow-lg border py-1 z-50">
                                <li>
                                    <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-ink-body hover:bg-surface-soft flex items-center gap-2">
                                        <UserCircleIcon class="w-4 h-4" /> Profile
                                    </Link>
                                </li>
                                <li>
                                    <button type="button" @click="router.post(route('logout'))" class="w-full text-left px-4 py-2 text-sm text-ink-body hover:bg-surface-soft flex items-center gap-2">
                                        <ArrowLeftOnRectangleIcon class="w-4 h-4" /> Logout
                                    </button>
                                </li>
                            </ul>
                        </details>
                    </div>
                </header>

                <main class="flex-1 p-4 lg:p-8">
                    <slot />
                </main>
                <Footer />
            </div>
        </div>
    </div>
</template>
