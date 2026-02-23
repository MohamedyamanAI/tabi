<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useTheme } from '@/utils/theme';

defineProps({
    terms: String,
});

onMounted(async () => {
    useTheme();
});
</script>

<template>
    <Head title="Terms of Service" />

    <div class="font-sans text-text-secondary antialiased">
        <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-10 pb-10 bg-default-background">
            <Link href="/" class="text-2xl font-bold text-text-primary">
                Tabi
            </Link>

            <div
                class="w-full sm:max-w-2xl mt-6 p-6 bg-card-background border border-card-border shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert"
                v-html="terms" />
        </div>
    </div>
</template>
