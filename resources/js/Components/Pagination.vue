<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: Array,
    meta: Object,
});

const isNumeric = (label) => /^\d+$/.test(String(label ?? '').trim());

// Numbered page links only (excludes prev/next/ellipsis)
const pageLinks = computed(() => (props.links ?? []).filter((l) => isNumeric(l.label)));

const firstPage = computed(() => pageLinks.value[0] || null);
const lastPage = computed(() => pageLinks.value[pageLinks.value.length - 1] || null);

const prev = computed(() => props.links?.[0] || null);
const next = computed(() => props.links?.[props.links.length - 1] || null);

const currentPage = computed(() => {
    const active = (props.links ?? []).find((l) => l.active);
    if (active && isNumeric(active.label)) return Number(active.label);
    return props.meta?.current_page ?? null;
});

const firstNum = computed(() => (firstPage.value && isNumeric(firstPage.value.label) ? Number(firstPage.value.label) : null));
const lastNum = computed(() => (lastPage.value && isNumeric(lastPage.value.label) ? Number(lastPage.value.label) : null));

const btnBase = 'inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm';
const btnEnabled = 'bg-surface border hover:bg-brand-50 text-ink-body';
const btnDisabled = 'bg-surface-soft text-ink-faint pointer-events-none';
const btnActive = 'bg-brand-600 border-brand-600 text-white';
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center gap-2 mt-6">
        <!-- Mobile (< sm): Prev/Next + first + current + last with ellipses -->
        <div class="flex items-center gap-1.5 sm:hidden">
            <Link v-if="prev?.url" :href="prev.url" :class="[btnBase, btnEnabled]">&lsaquo;</Link>
            <span v-else :class="[btnBase, btnDisabled]">&lsaquo;</span>

            <template v-if="firstNum">
                <Link :href="firstPage.url" :class="[btnBase, btnEnabled]">{{ firstNum }}</Link>
                <span v-if="currentPage && currentPage > firstNum + 1" class="px-1 text-ink-faint">&hellip;</span>
            </template>

            <span v-if="currentPage && currentPage !== firstNum && currentPage !== lastNum" :class="[btnBase, btnActive]">
                {{ currentPage }}
            </span>

            <template v-if="lastNum">
                <span v-if="currentPage && lastNum - currentPage > 1" class="px-1 text-ink-faint">&hellip;</span>
                <Link v-if="currentPage !== lastNum" :href="lastPage.url" :class="[btnBase, btnEnabled]">{{ lastNum }}</Link>
            </template>

            <Link v-if="next?.url" :href="next.url" :class="[btnBase, btnEnabled]">&rsaquo;</Link>
            <span v-else :class="[btnBase, btnDisabled]">&rsaquo;</span>
        </div>

        <!-- Desktop: full numbered list -->
        <template v-for="(link, i) in links" :key="String(link.label) + i">
            <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[btnBase, link.active ? btnActive : btnEnabled]"
                class="hidden sm:inline-flex"
            />
            <span
                v-else
                v-html="link.label"
                :class="[btnBase, link.active ? btnActive : btnDisabled]"
                class="hidden sm:inline-flex"
            />
        </template>
    </div>
</template>