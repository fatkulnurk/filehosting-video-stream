<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { MagnifyingGlassIcon, ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    headers: { type: Array, required: true }, // [{ key, label, align, sortable, sortKey, sticky }]
    rows: { type: Array, required: true },
    paginator: { type: Object, default: null }, // { links, meta, ... }
    filters: { type: Object, default: () => ({ search: '', sort: null, direction: 'asc', per_page: 10 }) },
    routeName: { type: String, default: '' },
    routeParams: { type: Object, default: () => ({}) },
    showSearch: { type: Boolean, default: true },
    showCount: { type: Boolean, default: true },
    filterOptions: { type: Array, default: () => [] }, // [{ key, label, options: [{value,label}] }]
    clientSort: { type: Boolean, default: false },
});

const params = () => ({
    ...props.routeParams,
    search: props.filters?.search || '',
    sort: props.filters?.sort || null,
    direction: props.filters?.direction || 'asc',
    per_page: props.filters?.per_page || 10,
});

const go = (overrides) => {
    if (props.clientSort) return;
    router.get(route(props.routeName), stripEmpty({ ...params(), ...overrides }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const stripEmpty = (obj) =>
    Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== '' && v !== null && v !== undefined && v !== 'undefined'));

// Search input is initialized from the current URL search param so the box reflects server state.
const searchInput = ref(new URLSearchParams(window.location.search).get('search') || '');
let debounceTimer = null;
watch(searchInput, () => {
    if (props.clientSort) return;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => go({ search: searchInput.value, page: 1 }), 400);
});

// Client-side sort state
const localSortKey = ref(null);
const localDirection = ref('asc');

const sortedRows = computed(() => {
    if (!props.clientSort || !localSortKey.value) return props.rows;
    const key = localSortKey.value;
    const header = props.headers.find((h) => (h.sortKey || h.key) === key);
    const extract = header?.sortValue || ((r) => r[key]);
    const dir = localDirection.value === 'asc' ? 1 : -1;
    return [...props.rows].sort((a, b) => {
        const av = extract(a);
        const bv = extract(b);
        if (av == null && bv == null) return 0;
        if (av == null) return dir;
        if (bv == null) return -dir;
        if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
        return String(av).localeCompare(String(bv)) * dir;
    });
});

const sortBy = (header) => {
    if (!header.sortable) return;
    const key = header.sortKey || header.key;
    if (props.clientSort) {
        if (localSortKey.value === key) {
            localDirection.value = localDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            localSortKey.value = key;
            localDirection.value = 'asc';
        }
        return;
    }
    if (props.filters?.sort === key) {
        const next = props.filters.direction === 'asc' ? 'desc' : 'asc';
        go({ sort: key, direction: next, page: 1 });
    } else {
        go({ sort: key, direction: 'asc', page: 1 });
    }
};

const changePerPage = (event) => {
    go({ per_page: Number(event.target.value), page: 1 });
};

const changeFilter = (key, event) => {
    go({ [key]: event.target.value || null, page: 1 });
};

const columnIsSticky = (h, i) => h.sticky === true || (h.sticky !== false && i === props.headers.length - 1);

const sortIndicator = (header) => {
    if (!header.sortable) return '';
    const key = header.sortKey || header.key;
    if (props.clientSort) {
        if (localSortKey.value !== key) return '';
        return localDirection.value === 'asc' ? 'asc' : 'desc';
    }
    if (props.filters?.sort !== key) return '';
    return props.filters?.direction === 'asc' ? 'asc' : 'desc';
};
</script>

<template>
    <div class="bg-surface rounded-xl shadow">
        <!-- Toolbar: filters + search -->
        <div v-if="showSearch || filterOptions.length" class="p-3 border-b border-line-soft flex flex-wrap items-center justify-end gap-2">
            <select
                v-for="f in filterOptions"
                :key="f.key"
                :value="filters?.[f.key] || ''"
                @change="changeFilter(f.key, $event)"
                class="text-sm rounded-lg border border-line px-3 py-2"
            >
                <option value="">{{ f.label }}</option>
                <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <div v-if="showSearch" class="relative w-56">
                <input
                    v-model="searchInput"
                    type="text"
                    placeholder="Search..."
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-line focus:ring-2 focus:ring-brand-500"
                />
                <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-2.5 text-ink-faint" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs uppercase text-ink-soft">
                    <tr>
                        <th
                            v-for="(h, i) in headers"
                            :key="h.key"
                            class="px-4 py-3 font-medium whitespace-nowrap"
                            :class="[
                                h.align === 'right' ? 'text-right' : 'text-left',
                                h.sortable ? 'cursor-pointer select-none hover:text-brand-700' : '',
                                columnIsSticky(h, i) ? 'sticky right-0 bg-surface-muted' : '',
                            ]"
                            @click="sortBy(h)"
                        >
                            <slot :name="'header-' + h.key">
                                <span class="inline-flex items-center gap-1">
                                    {{ h.label }}
                                    <span v-if="h.sortable" class="inline-flex flex-col leading-none">
                                        <ChevronUpIcon v-if="sortIndicator(h) === 'asc'" class="w-3 h-3 text-brand-700" />
                                        <ChevronUpIcon v-else class="w-3 h-3 text-ink-faint" />
                                        <ChevronDownIcon v-if="sortIndicator(h) === 'desc'" class="w-3 h-3 text-brand-700" />
                                        <ChevronDownIcon v-else class="w-3 h-3 text-ink-faint" />
                                    </span>
                                </span>
                            </slot>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, ri) in (clientSort ? sortedRows : rows)"
                        :key="row.key || row.id || ri"
                        class="border-t hover:bg-surface-muted/50"
                    >
                        <td
                            v-for="(h, i) in headers"
                            :key="h.key"
                            class="px-4 py-3"
                            :class="[
                                h.align === 'right' ? 'text-right' : 'text-left',
                                columnIsSticky(h, i) ? 'sticky right-0 bg-surface border-l border-line-soft' : '',
                            ]"
                        >
                            <slot :name="'cell-' + h.key" :row="row">
                                {{ row[h.key] }}
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td :colspan="headers.length" class="px-4 py-16 text-center">
                            <slot name="empty" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer: per-page selector + pagination links -->
        <div v-if="paginator" class="px-4 py-3 border-t border-line-soft flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-ink-soft">
                <span v-if="showCount">Rows per page:</span>
                <select
                    v-if="showCount"
                    :value="filters?.per_page || 10"
                    @change="changePerPage"
                    class="rounded-lg border border-line px-2 py-1 text-sm"
                >
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
                <span v-if="paginator.meta">
                    {{ paginator.meta.from || 0 }}–{{ paginator.meta.to || 0 }} of {{ paginator.meta.total || 0 }}
                </span>
            </div>
            <Pagination :links="paginator.links" :meta="paginator.meta" />
        </div>
    </div>
</template>