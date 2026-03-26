<script setup lang="ts">
import LandingLayout from '@/Components/LandingLayout.vue';

const extraFaqs = [
    {
        q: 'Is Tabi suitable for a team of 2-3 people?',
        a: 'Yes. Tabi has no minimum seat requirements. Start with 2 people and scale to 200 without changing plans or hitting feature gates.',
    },
    {
        q: 'Can we start free and upgrade later?',
        a: 'Absolutely. Tabi is free for solo use. When your team grows, upgrade to a paid plan and get a 7-day free trial with full access to every feature.',
    },
    {
        q: 'How does Tabi compare to building our own tracking?',
        a: "Tabi is open source, so you get full transparency. But you don't need to self-host — our cloud plan starts at $1/user/month with zero setup. Most startups are up and running in under 10 minutes.",
    },
];
</script>

<template>
    <LandingLayout
        title="Tabi - Time Tracking for Startups"
        headline="Time tracking that scales with your startup."
        subtitle="Screenshots, idle detection, and activity tracking from day one. No per-feature upsells, no enterprise-only gates. Starting at $1/user/month."
        meta-description="Time tracking software for startups. Screenshots, idle detection, and activity tracking. No hidden fees. Starting at $1/user/month."
        og-title="Tabi - Time Tracking Software for Startups"
        og-description="Screenshots, idle detection, and activity tracking from day one. No per-feature upsells. Just honest pricing that grows with your startup."
        json-ld-description="Time tracking software for startups with screenshots, idle detection, and activity tracking."
        comparison-headline="Why startups choose Tabi"
        comparison-subtitle="Other tools lock features behind $9+/user tiers. Tabi gives you everything from day one at a price that won't kill your runway."
        :extra-faqs="extraFaqs" />
</template>
