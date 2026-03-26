<script setup lang="ts">
import LandingLayout from '@/Components/LandingLayout.vue';

const extraFaqs = [
    {
        q: 'How do screenshots work with remote teams across time zones?',
        a: "Screenshots are captured automatically while the timer runs, regardless of time zone. Managers can review activity asynchronously from the dashboard — no need to be online at the same time.",
    },
    {
        q: 'Can remote employees see their own screenshots?',
        a: 'Yes. Team members can view their own screenshots and activity data. Transparency builds trust and helps people self-correct without manager intervention.',
    },
    {
        q: 'Does Tabi work with contractors and freelancers?',
        a: 'Yes. Invite contractors as team members, assign them to specific projects, and track their hours and activity just like full-time employees. No minimum seats required.',
    },
];
</script>

<template>
    <LandingLayout
        title="Tabi - Time Tracking for Remote Teams"
        headline="See what your remote team is working on."
        subtitle="Screenshots, idle detection, and app tracking so you know where time goes. No micromanagement, just clarity. Open source and starting at $1/user/month."
        meta-description="Time tracking software for remote teams. Screenshots, idle detection, and app tracking for distributed teams. Starting at $1/user/month."
        og-title="Tabi - Time Tracking Software for Remote Teams"
        og-description="Screenshots, idle detection, and app tracking so you know where time goes. No micromanagement, just clarity for remote teams."
        json-ld-description="Time tracking software for remote teams with screenshots, idle detection, and app tracking."
        comparison-headline="Why remote teams choose Tabi"
        comparison-subtitle="Remote visibility shouldn't cost $10+/user. Tabi gives you screenshots, idle detection, and app tracking in one affordable plan."
        :extra-faqs="extraFaqs" />
</template>
