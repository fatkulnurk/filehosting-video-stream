<script setup>
defineProps({
    padding: { type: String, default: 'p-5' },
    fluid: { type: Boolean, default: false },
    gradient: { type: Boolean, default: false },
    icon: { type: Object, default: null },
    iconColor: { type: String, default: 'text-brand-600' },
    iconBg: { type: String, default: 'bg-brand-100' },
});
</script>

<template>
    <div
        :class="[
            'rounded-xl shadow',
            gradient ? 'bg-gradient-to-br from-surface to-brand-50' : 'bg-surface',
            fluid ? '' : padding,
        ]"
    >
        <div v-if="icon" class="flex items-center gap-4">
            <div :class="['shrink-0 p-3 rounded-xl', iconBg]">
                <component :is="icon" :class="['w-6 h-6', iconColor]" />
            </div>
            <div class="flex-1 min-w-0">
                <slot />
            </div>
        </div>
        <div v-else>
            <slot />
        </div>
    </div>
</template>