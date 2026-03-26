<script setup lang="ts">
import LandingLayout from '@/Components/LandingLayout.vue';

const extraFaqs = [
    {
        q: 'Can I track billable hours per client?',
        a: 'Yes. Tabi lets you assign time entries to specific clients and projects, set billable rates, and generate reports broken down by client. Export or invoice directly from your dashboard.',
    },
    {
        q: 'Can different team members have different hourly rates?',
        a: 'Absolutely. You can set individual billable rates per member and per project, so your reports and invoices always reflect the right numbers.',
    },
    {
        q: 'How do screenshots help agencies?',
        a: 'Screenshots give you proof of work for client reporting, help identify workflow bottlenecks, and keep remote contractors accountable without constant check-ins.',
    },
];
</script>

<template>
    <LandingLayout
        title="Tabi - Time Tracking for Agencies"
        headline="Time tracking built for agencies."
        subtitle="Track billable hours, capture screenshots, and monitor your team's productivity. All for $1/user/month instead of $10."
        meta-description="Time tracking software for agencies. Track billable hours, capture screenshots, and monitor team productivity. Starting at $1/user/month."
        og-title="Tabi - Time Tracking Software for Agencies"
        og-description="Track billable hours, capture screenshots, and monitor your team's productivity. Built for agencies that need visibility without the enterprise price tag."
        json-ld-description="Time tracking software for agencies with screenshots, idle detection, and billable hour tracking."
        comparison-headline="Why agencies choose Tabi"
        comparison-subtitle="Most agency tools charge $10+/user for screenshots and billable tracking. Tabi includes everything in one plan."
        :extra-faqs="extraFaqs" />
</template>
