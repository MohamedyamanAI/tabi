<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useTheme, theme, themeSetting } from '@/utils/theme';
import {
    SunIcon,
    MoonIcon,
    ComputerDesktopIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    CameraIcon,
    ComputerDesktopIcon as AppTrackingIcon,
    DocumentTextIcon,
    ClockIcon,
    FolderIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    PauseCircleIcon,
    ShieldCheckIcon,
    UsersIcon,
    ChevronDownIcon,
} from '@heroicons/vue/20/solid';
import { CheckCircleIcon as CheckCircleOutlineIcon } from '@heroicons/vue/24/outline';
import { handleDesktopDownload } from '@/utils/download';

interface FaqItem {
    q: string;
    a: string;
}

const props = withDefaults(
    defineProps<{
        title?: string;
        headline?: string;
        subtitle?: string;
        metaDescription?: string;
        ogTitle?: string;
        ogDescription?: string;
        jsonLdDescription?: string;
        comparisonHeadline?: string;
        comparisonSubtitle?: string;
        extraFaqs?: FaqItem[];
    }>(),
    {
        title: 'Tabi',
        headline: 'Powerful time tracking. Honest pricing.',
        subtitle: 'Everything you need for your team: screenshots, idle detection, activity tracking. Open source and starting at $1/user/month.',
        metaDescription: 'Powerful time tracking with screenshots, idle detection, and activity tracking. Open source and starting at $1/user/month.',
        ogTitle: 'Tabi - Powerful time tracking. Honest pricing.',
        ogDescription: 'Everything you need for your team: screenshots, idle detection, activity tracking. Open source and starting at $1/user/month.',
        jsonLdDescription: 'Powerful time tracking with screenshots, idle detection, and activity tracking.',
        comparisonHeadline: 'How Tabi compares',
        comparisonSubtitle: "The popular tools don't monitor. The monitoring tool is expensive. Tabi gives you both.",
        extraFaqs: () => [],
    }
);

onMounted(async () => {
    useTheme();
    document.documentElement.style.scrollBehavior = 'smooth';
});

const billingCycle = ref<'monthly' | 'annual'>('annual');
const comparisonSection = ref<HTMLElement | null>(null);
const navHidden = ref(false);
const heroRef = ref<HTMLElement | null>(null);
const particleCanvas = ref<HTMLCanvasElement | null>(null);

// Particle system
interface Particle {
    originX: number;
    originY: number;
    x: number;
    y: number;
    vx: number;
    vy: number;
    radius: number;
}

const mouse = { x: -9999, y: -9999 };
let particles: Particle[] = [];
let animFrame = 0;

function initParticles(canvas: HTMLCanvasElement) {
    const spacing = 40;
    const cols = Math.ceil(canvas.width / spacing);
    const rows = Math.ceil(canvas.height / spacing);
    particles = [];
    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const x = c * spacing + spacing / 2;
            const y = r * spacing + spacing / 2;
            particles.push({ originX: x, originY: y, x, y, vx: 0, vy: 0, radius: 3 });
        }
    }
}

function animateParticles(canvas: HTMLCanvasElement) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const context = ctx;

    const pushRadius = 120;
    const pushForce = 8;
    const returnSpeed = 0.04;
    const friction = 0.85;
    const isDark = theme.value === 'dark';
    const dotColor = isDark ? 'rgba(200, 200, 200, 0.3)' : 'rgba(120, 120, 120, 0.25)';

    function frame() {
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = dotColor;

        for (const p of particles) {
            context.globalAlpha = Math.max(0, 1 - (p.y / canvas.height) * 1.2);

            const dx = p.x - mouse.x;
            const dy = p.y - mouse.y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < pushRadius && dist > 0) {
                const force = (1 - dist / pushRadius) * pushForce;
                p.vx += (dx / dist) * force;
                p.vy += (dy / dist) * force;
            }

            p.vx += (p.originX - p.x) * returnSpeed;
            p.vy += (p.originY - p.y) * returnSpeed;
            p.vx *= friction;
            p.vy *= friction;
            p.x += p.vx;
            p.y += p.vy;

            context.beginPath();
            context.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            context.fill();
        }
        context.globalAlpha = 1;

        animFrame = requestAnimationFrame(frame);
    }

    frame();
}

function onMouseMove(e: MouseEvent) {
    if (!particleCanvas.value) return;
    const rect = particleCanvas.value.getBoundingClientRect();
    mouse.x = (e.clientX - rect.left) * (particleCanvas.value.width / rect.width);
    mouse.y = (e.clientY - rect.top) * (particleCanvas.value.height / rect.height);
}

function onMouseLeave() {
    mouse.x = -9999;
    mouse.y = -9999;
}

function setupCanvas() {
    const canvas = particleCanvas.value;
    const hero = heroRef.value;
    if (!canvas || !hero) return;
    canvas.width = hero.offsetWidth * window.devicePixelRatio;
    canvas.height = hero.offsetHeight * window.devicePixelRatio;
    initParticles(canvas);
    cancelAnimationFrame(animFrame);
    animateParticles(canvas);
}

let observer: IntersectionObserver | null = null;
let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    setupCanvas();

    if (heroRef.value) {
        heroRef.value.addEventListener('mouseleave', onMouseLeave);
        resizeObserver = new ResizeObserver(() => setupCanvas());
        resizeObserver.observe(heroRef.value);
    }

    if (comparisonSection.value) {
        observer = new IntersectionObserver(
            (entries) => {
                const entry = entries[0];
                if (!entry) return;
                navHidden.value = entry.intersectionRatio > 0.3;
            },
            { threshold: [0, 0.3, 1] }
        );
        observer.observe(comparisonSection.value);
    }
});

onUnmounted(() => {
    cancelAnimationFrame(animFrame);
    observer?.disconnect();
    resizeObserver?.disconnect();
    heroRef.value?.removeEventListener('mouseleave', onMouseLeave);
    document.documentElement.style.scrollBehavior = '';
});

watch(theme, () => {
    // Restart animation to pick up new dot color
    cancelAnimationFrame(animFrame);
    if (particleCanvas.value) animateParticles(particleCanvas.value);
});

const baseFaqs: FaqItem[] = [
    {
        q: 'Is my data secure? Where are screenshots stored?',
        a: "Screenshots are stored on encrypted cloud infrastructure with encryption at rest. All data is transmitted over HTTPS. We don't sell, share, or provide third-party access to your data. Screenshots are automatically deleted after 3 months to keep storage lean and your data footprint minimal.",
    },
    {
        q: 'How often are screenshots taken?',
        a: "Screenshots are taken at configurable intervals while the timer is running. You can adjust the frequency to fit your team's needs. Screenshots are compressed as WebP images to keep them lightweight, and they're retained for 3 months before being automatically cleaned up.",
    },
    {
        q: 'What platforms does Tabi support?',
        a: 'Tabi has desktop apps for macOS, Windows, and Linux. The web dashboard works in any modern browser. Mobile apps are on the roadmap.',
    },
    {
        q: 'How long does it take to set up Tabi for my team?',
        a: 'Most teams are up and running in under 10 minutes. Create your account, invite your team via email, and have everyone install the desktop app. No onboarding calls, no configuration consultants, no minimum seats.',
    },
    {
        q: 'Do you offer a free trial?',
        a: 'Yes. Tabi offers a 7-day free trial with full access to every feature.',
    },
];

const faqs = computed(() => [...props.extraFaqs, ...baseFaqs]);

function cycleTheme() {
    if (theme.value === 'dark') {
        themeSetting.value = 'light';
    } else {
        themeSetting.value = 'dark';
    }
}
</script>

<template>
    <Head :title="props.title">
        <meta
            name="description"
            :content="props.metaDescription" />
        <meta property="og:title" :content="props.ogTitle" />
        <meta
            property="og:description"
            :content="props.ogDescription" />
        <meta name="twitter:title" :content="props.ogTitle" />
        <meta
            name="twitter:description"
            :content="props.ogDescription" />
        <component
            is="script"
            type="application/ld+json"
            v-text="
                JSON.stringify({
                    '@context': 'https://schema.org',
                    '@type': 'SoftwareApplication',
                    name: 'Tabi',
                    applicationCategory: 'BusinessApplication',
                    description: props.jsonLdDescription,
                    offers: {
                        '@type': 'Offer',
                        price: '1.00',
                        priceCurrency: 'USD',
                        description: 'Per user per month',
                    },
                })
            " />
    </Head>

    <div class="min-h-screen flex flex-col bg-default-background">
        <!-- Navbar -->
        <nav
            class="fixed top-4 left-0 right-0 z-50 mx-auto w-full max-w-5xl flex items-center justify-between px-5 py-2 rounded-full border border-card-border bg-default-background/80 backdrop-blur-md transition-all duration-300"
            :class="navHidden ? 'opacity-0 -translate-y-full pointer-events-none' : 'opacity-100 translate-y-0'">
            <div class="flex items-center gap-2">
                <img
                    v-if="theme === 'dark'"
                    src="/images/logo_transparent_white.png"
                    alt="Tabi"
                    class="h-8" />
                <img
                    v-else
                    src="/images/logo_transparent_black.png"
                    alt="Tabi"
                    class="h-8" />
                <span class="text-xl font-bold text-text-primary">Tabi</span>
            </div>

            <div class="hidden sm:flex items-center gap-6 text-sm text-text-secondary">
                <a href="#features" class="hover:text-text-primary transition-colors">Features</a>
                <a href="#pricing" class="hover:text-text-primary transition-colors">Pricing</a>
            </div>

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

        <!-- Hero wrapper (particles cover hero area) -->
        <div ref="heroRef" class="relative" @mousemove="onMouseMove">
            <!-- Particle field -->
            <canvas
                ref="particleCanvas"
                class="pointer-events-none absolute inset-0 z-0 w-full h-full"></canvas>

        <!-- Hero -->
        <main
            class="relative flex-1 flex flex-col items-center px-6 sm:px-10 pt-24 sm:pt-32 lg:pt-40">
            <h1
                class="text-5xl sm:text-6xl lg:text-7xl font-bold text-text-primary tracking-tight text-center max-w-4xl leading-tight">
                {{ props.headline }}
            </h1>

            <p
                class="mt-6 text-lg sm:text-xl text-text-secondary text-center max-w-2xl leading-relaxed">
                {{ props.subtitle }}
            </p>

            <div class="mt-10 flex gap-4">
                <Link
                    href="/register"
                    class="px-8 py-3 text-lg rounded-lg bg-accent-400 text-white hover:bg-accent-500 transition-colors">
                    Get Started
                </Link>
                <button
                    @click="handleDesktopDownload"
                    class="px-8 py-3 text-lg rounded-lg border border-card-border text-text-primary hover:bg-card-background transition-colors">
                    Download App
                </button>
            </div>

            <div class="mt-6 flex items-center gap-6 text-sm text-text-secondary">
                <span class="flex items-center gap-1.5">
                    <CheckCircleOutlineIcon class="w-4 h-4 text-accent-400" />
                    Free 7-day trial
                </span>
                <span class="flex items-center gap-1.5">
                    <CheckCircleOutlineIcon class="w-4 h-4 text-accent-400" />
                    1 minute setup
                </span>
                <span class="flex items-center gap-1.5">
                    <CheckCircleOutlineIcon class="w-4 h-4 text-accent-400" />
                    Cancel anytime
                </span>
            </div>

            <img
                v-if="theme === 'dark'"
                src="/images/hero-dark.png"
                alt="Tabi time tracking dashboard"
                loading="lazy"
                class="relative z-10 mt-16 w-full max-w-5xl rounded-xl border border-card-border" />
            <img
                v-else
                src="/images/hero-light.png"
                alt="Tabi time tracking dashboard"
                loading="lazy"
                class="relative z-10 mt-16 w-full max-w-5xl rounded-xl border border-card-border" />
        </main>
        </div>

        <!-- Comparison -->
        <section id="features" ref="comparisonSection" class="px-6 sm:px-10 py-24 scroll-mt-20">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-12">
                    <h2
                        class="text-3xl sm:text-4xl font-bold text-text-primary tracking-tight">
                        {{ props.comparisonHeadline }}
                    </h2>
                    <p class="mt-4 text-lg text-text-secondary max-w-2xl mx-auto">
                        {{ props.comparisonSubtitle }}
                    </p>
                    <p class="mt-2 text-sm text-text-tertiary">
                        All prices are annual billing.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-lg text-left border-separate border-spacing-0">
                        <thead class="sticky top-0 z-20">
                            <tr>
                                <th class="sticky left-0 z-30 bg-default-background px-6 py-5"></th>
                                <th class="bg-default-background px-6 py-5 text-center">
                                    <div class="inline-flex items-center rounded-xl bg-accent-300/15 px-6 py-3 ring-2 ring-accent-300/30">
                                        <span class="text-xl font-bold text-accent-300">Tabi</span>
                                    </div>
                                </th>
                                <th class="bg-default-background px-6 py-5 text-center">
                                    <span class="text-base font-semibold text-text-tertiary">Toggl Track</span>
                                </th>
                                <th class="bg-default-background px-6 py-5 text-center">
                                    <span class="text-base font-semibold text-text-tertiary">Clockify</span>
                                </th>
                                <th class="bg-default-background px-6 py-5 text-center">
                                    <span class="text-base font-semibold text-text-tertiary">Hubstaff</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <CameraIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Screenshots</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Unlimited</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-red-400">
                                        <XCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Never offered</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Pro only <span class="text-sm">($7.99/mo)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Capped <span class="text-sm">($10/mo)</span></span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <AppTrackingIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>App tracking</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-red-400">
                                        <XCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>No</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Not in reports</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Capped <span class="text-sm">($10/mo)</span></span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <DocumentTextIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Invoicing</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Beta <span class="text-sm">($9/mo)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Standard <span class="text-sm">($5.49/mo)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <ClockIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Time tracking</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <FolderIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Clients, projects &amp; tasks</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Starter <span class="text-sm">($9/mo)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <CurrencyDollarIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Billable hours &amp; rates</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Starter <span class="text-sm">($9/mo)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <ChartBarIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Reports &amp; exports</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <PauseCircleIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>Idle time detection</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>Included</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>Grow <span class="text-sm">($7.50/mo)</span></span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center gap-3 font-medium text-text-primary">
                                        <ShieldCheckIcon class="w-5 h-5 shrink-0 text-text-tertiary" />
                                        <span>No add-on fees</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 bg-accent-300/[0.08] px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-accent-400 font-medium">
                                        <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                        <span>One plan</span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>3 tiers <span class="text-sm">($9+)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>5 tiers <span class="text-sm">($0–$12)</span></span>
                                    </div>
                                </td>
                                <td class="border-t border-card-border/40 px-6 py-5">
                                    <div class="flex items-center justify-center gap-2 text-amber-500">
                                        <ExclamationTriangleIcon class="w-6 h-6 shrink-0" />
                                        <span>4 tiers + add-ons</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="sticky left-0 z-10 bg-default-background border-t-2 border-card-border px-6 py-6 text-lg font-bold text-text-primary">Price for all features</td>
                                <td class="border-t-2 border-card-border bg-accent-300/[0.15] px-6 py-6 text-center">
                                    <span class="text-2xl font-bold text-accent-300">$3/user/mo</span>
                                </td>
                                <td class="border-t-2 border-card-border px-6 py-6 text-center text-lg font-semibold text-text-tertiary">$9+/user/mo</td>
                                <td class="border-t-2 border-card-border px-6 py-6 text-center text-lg font-semibold text-text-tertiary">$7.99+/user/mo</td>
                                <td class="border-t-2 border-card-border px-6 py-6 text-center text-lg font-semibold text-text-tertiary">$10+/user/mo</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Plans -->
        <section id="pricing" class="px-6 sm:px-10 py-24 scroll-mt-20">
            <div class="mx-auto max-w-5xl">
                <div class="text-center mb-12">
                    <h2
                        class="text-3xl sm:text-4xl font-bold text-text-primary tracking-tight">
                        You've seen the comparison.
                    </h2>
                    <p class="mt-4 text-lg text-text-secondary">
                        The rest is just picking a plan.
                    </p>
                    <div class="mt-6 inline-flex items-center gap-3">
                        <div
                            class="inline-flex items-center gap-1 rounded-full border border-card-border bg-card-background p-1 text-sm">
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5 font-semibold transition-colors"
                                :class="
                                    billingCycle === 'monthly'
                                        ? 'bg-accent-400 text-white shadow-sm'
                                        : 'text-text-primary hover:bg-default-background/80'
                                "
                                @click="billingCycle = 'monthly'">
                                Monthly
                            </button>
                            <button
                                type="button"
                                class="rounded-full px-4 py-1.5 font-semibold transition-colors"
                                :class="
                                    billingCycle === 'annual'
                                        ? 'bg-accent-400 text-white shadow-sm'
                                        : 'text-text-primary hover:bg-default-background/80'
                                "
                                @click="billingCycle = 'annual'">
                                Annual
                            </button>
                        </div>
                        <span
                            v-if="billingCycle === 'annual'"
                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            Save up to 67%
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-stretch max-w-3xl mx-auto">
                    <!-- Track -->
                    <div
                        class="flex flex-col justify-between rounded-2xl border border-card-border bg-card-background p-6">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-text-tertiary">
                                Track
                            </div>
                            <div class="mt-3 flex items-baseline gap-1">
                                <span class="text-4xl font-bold tracking-tight text-text-primary">{{
                                    billingCycle === 'monthly' ? '$3' : '$1'
                                }}</span>
                                <span class="text-sm text-text-secondary">/user/month</span>
                            </div>
                            <p v-if="billingCycle === 'annual'" class="mt-1 text-xs text-text-tertiary">
                                Billed yearly
                            </p>
                            <p class="mt-3 text-sm text-text-secondary">
                                Full productivity tracking for growing teams.
                            </p>
                            <ul class="mt-6 space-y-3 text-sm text-text-secondary">
                                <li class="flex items-center gap-2.5">
                                    <UsersIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Unlimited members</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <ClockIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Time tracking &amp; projects</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <FolderIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Clients, tags &amp; tasks</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CurrencyDollarIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Billable hours &amp; rates</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <ChartBarIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Reports &amp; exports</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <DocumentTextIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Invoicing</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <PauseCircleIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Idle time detection</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <AppTrackingIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>App tracking</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            href="/register"
                            class="mt-8 block w-full rounded-lg border border-card-border px-4 py-2.5 text-center text-sm font-semibold text-text-primary hover:bg-default-background transition-colors">
                            Start Free Trial
                        </Link>
                    </div>

                    <!-- Monitor -->
                    <div
                        class="relative flex flex-col justify-between rounded-2xl border border-accent-300/40 bg-gradient-to-b from-accent-300/5 to-card-background p-6">
                        <div
                            class="absolute right-4 top-4 rounded-full bg-accent-400 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                            Recommended
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-accent-300">
                                Monitor
                            </div>
                            <div class="mt-3 flex items-baseline gap-1.5">
                                <span class="text-5xl font-bold tracking-tight text-text-primary">{{
                                    billingCycle === 'monthly' ? '$5' : '$3'
                                }}</span>
                                <span class="text-base text-text-secondary">/user/month</span>
                            </div>
                            <p v-if="billingCycle === 'annual'" class="mt-1 text-xs text-text-tertiary">
                                Billed yearly
                            </p>
                            <p class="mt-3 text-sm text-text-secondary">
                                Everything in Track plus screenshot monitoring.
                            </p>
                            <ul class="mt-6 space-y-3 text-sm text-text-secondary">
                                <li class="flex items-center gap-2.5">
                                    <UsersIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Unlimited members</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <ClockIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Time tracking &amp; projects</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <FolderIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Clients, tags &amp; tasks</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CurrencyDollarIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Billable hours &amp; rates</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <ChartBarIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Reports &amp; exports</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <DocumentTextIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Invoicing</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <PauseCircleIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Idle time detection</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <AppTrackingIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>App tracking</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <CameraIcon class="w-4 h-4 shrink-0 text-accent-400" />
                                    <span>Screenshot capture &amp; management</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            href="/register"
                            class="mt-8 block w-full rounded-lg bg-accent-400 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-accent-500 transition-colors">
                            Start Free Trial
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="px-6 sm:px-10 py-24">
            <div class="mx-auto max-w-3xl">
                <h2
                    class="text-3xl sm:text-4xl font-bold text-text-primary tracking-tight text-center mb-12">
                    Frequently asked questions
                </h2>

                <div class="space-y-4">
                    <details
                        v-for="(faq, i) in faqs"
                        :key="i"
                        class="group rounded-xl border border-card-border bg-card-background">
                        <summary
                            class="flex cursor-pointer items-center justify-between px-6 py-5 text-lg font-medium text-text-primary select-none [&::-webkit-details-marker]:hidden">
                            <span>{{ faq.q }}</span>
                            <ChevronDownIcon
                                class="w-5 h-5 shrink-0 text-text-tertiary transition-transform duration-200 group-open:rotate-180" />
                        </summary>
                        <div class="px-6 pb-5 text-base leading-relaxed text-text-secondary">
                            {{ faq.a }}
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-card-border px-6 sm:px-10 pt-16 pb-8">
            <div class="mx-auto max-w-5xl">
                <div class="grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-6">
                    <!-- Brand -->
                    <div class="lg:col-span-1">
                        <div class="flex items-center gap-2">
                            <img
                                v-if="theme === 'dark'"
                                src="/images/logo_transparent_white.png"
                                alt="Tabi"
                                class="h-7" />
                            <img
                                v-else
                                src="/images/logo_transparent_black.png"
                                alt="Tabi"
                                class="h-7" />
                            <span class="text-lg font-bold text-text-primary">Tabi</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-text-secondary">
                            Powerful time tracking with screenshots, idle detection, and activity tracking.
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-text-tertiary">
                            Product
                        </h4>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li>
                                <Link href="/register" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Get Started
                                </Link>
                            </li>
                            <li>
                                <Link href="/login" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Log in
                                </Link>
                            </li>
                            <li>
                                <button @click="handleDesktopDownload" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Download App
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Built for -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-text-tertiary">
                            Built for
                        </h4>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li>
                                <Link href="/time-tracking-software-for-agencies" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Agencies
                                </Link>
                            </li>
                            <li>
                                <Link href="/time-tracking-software-for-startups" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Startups
                                </Link>
                            </li>
                            <li>
                                <Link href="/time-tracking-software-for-remote-teams" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Remote Teams
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Open Source -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-text-tertiary">
                            Open Source
                        </h4>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li>
                                <a href="https://github.com/MohamedyamanAI/tabi" target="_blank" rel="noopener noreferrer" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Web App
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/MohamedyamanAI/tabi-desktop" target="_blank" rel="noopener noreferrer" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Desktop App
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-text-tertiary">
                            Legal
                        </h4>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li>
                                <Link href="/privacy-policy" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <Link href="/terms-of-service" class="text-text-secondary hover:text-text-primary transition-colors">
                                    Terms of Service
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- CTA -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-text-tertiary">
                            Ready to start?
                        </h4>
                        <p class="mt-4 text-sm text-text-secondary">
                            7-day free trial.
                        </p>
                        <Link
                            href="/register"
                            class="mt-4 inline-block rounded-lg bg-accent-300/80 px-5 py-2.5 text-sm font-semibold text-white hover:bg-accent-300 transition-colors">
                            Get Started
                        </Link>
                    </div>
                </div>

                <div class="mt-12 border-t border-card-border/50 pt-6 text-center text-xs text-text-tertiary">
                    &copy; {{ new Date().getFullYear() }} Tabi. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>

