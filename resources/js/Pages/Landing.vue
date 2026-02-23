<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useTheme, theme, themeSetting } from '@/utils/theme';
import { SunIcon, MoonIcon, ComputerDesktopIcon } from '@heroicons/vue/20/solid';

onMounted(async () => {
    useTheme();
});

function cycleTheme() {
    const order = ['system', 'light', 'dark'] as const;
    const idx = order.indexOf(themeSetting.value);
    themeSetting.value = order[(idx + 1) % order.length];
}
</script>

<template>
    <Head title="Tabi" />

    <div class="min-h-screen flex flex-col bg-default-background">
        <!-- Navbar -->
        <nav
            class="flex items-center justify-between px-6 sm:px-10 py-4 border-b border-card-border">
            <span class="text-xl font-bold text-text-primary">Tabi</span>

            <div class="flex items-center gap-3">
                <button
                    @click="cycleTheme"
                    class="p-2 rounded-lg text-text-secondary hover:text-text-primary hover:bg-card-background transition-colors"
                    :title="`Theme: ${themeSetting}`">
                    <SunIcon v-if="theme === 'light'" class="w-5 h-5" />
                    <MoonIcon v-else-if="theme === 'dark'" class="w-5 h-5" />
                    <ComputerDesktopIcon v-else class="w-5 h-5" />
                </button>
                <Link
                    href="/login"
                    class="px-5 py-2 rounded-lg border border-card-border text-sm text-text-primary hover:bg-card-background transition-colors">
                    Log in
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 sm:px-10">
            <h1
                class="text-5xl sm:text-6xl lg:text-7xl font-bold text-text-primary tracking-tight text-center max-w-4xl leading-tight">
                Powerful time tracking. Honest pricing.
            </h1>

            <p
                class="mt-6 text-lg sm:text-xl text-text-secondary text-center max-w-2xl leading-relaxed">
                Everything Hubstaff offers &mdash; screenshots, idle detection, activity
                tracking &mdash; open source and starting at $3/user/month.
            </p>

            <div class="mt-10 flex gap-4">
                <Link
                    href="/register"
                    class="px-8 py-3 text-lg rounded-lg bg-accent-300/80 text-white hover:bg-accent-300 transition-colors">
                    Get Started
                </Link>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="flex items-center justify-center gap-4 px-6 sm:px-10 py-4 border-t border-card-border text-sm text-text-secondary">
            <span>&copy; {{ new Date().getFullYear() }} Tabi</span>
            <Link
                href="/privacy-policy"
                class="hover:text-text-primary transition-colors">
                Privacy Policy
            </Link>
            <Link
                href="/terms-of-service"
                class="hover:text-text-primary transition-colors">
                Terms of Service
            </Link>
        </footer>
    </div>
</template>
