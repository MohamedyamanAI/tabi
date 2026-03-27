import posthog from 'posthog-js';

if (import.meta.env.PROD && import.meta.env.VITE_POSTHOG_PROJECT_TOKEN) {
    posthog.init(import.meta.env.VITE_POSTHOG_PROJECT_TOKEN, {
        api_host:
            import.meta.env.VITE_POSTHOG_HOST || 'https://eu.i.posthog.com',
        autocapture: true,
        capture_pageview: true,
        capture_pageleave: true,
        enable_recording_console_log: true,
        session_recording: {
            maskInputFn: (text, element) => {
                const type = element?.getAttribute('type');
                if (type === 'password') return '*'.repeat(text.length);
                return text;
            },
            collectFonts: true,
            recordCrossOriginIframes: true,
            recordHeaders: true,
            recordBody: true,
        },
    });
}
