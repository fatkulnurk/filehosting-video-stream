<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div v-if="page.props.flash?.success" class="fixed top-4 right-4 z-[100]">
        <div class="alert alert-success">
            {{ page.props.flash.success }}
        </div>
    </div>
    <div v-if="page.props.flash?.error" class="fixed top-4 right-4 z-[100]">
        <div class="alert alert-danger">
            {{ page.props.flash.error }}
        </div>
    </div>
</template>
