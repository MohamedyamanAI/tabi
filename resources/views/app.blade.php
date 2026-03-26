<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <meta name="description" content="Powerful time tracking with screenshots, idle detection, and activity tracking. Open source and starting at $1/user/month.">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Tabi') }}">
        <meta property="og:description" content="Powerful time tracking with screenshots, idle detection, and activity tracking. Open source and starting at $1/user/month.">
        <meta property="og:site_name" content="{{ config('app.name', 'Tabi') }}">
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
        <meta name="twitter:title" content="{{ config('app.name', 'Tabi') }}">
        <meta name="twitter:description" content="Powerful time tracking with screenshots, idle detection, and activity tracking. Open source and starting at $1/user/month.">

        <title inertia>{{ config('app.name', 'Tabi') }}</title>

        <!-- Favicons -->
        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        <link rel="manifest" href="/favicons/site.webmanifest">
        <meta name="apple-mobile-web-app-title" content="Tabi">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#000000">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="#000000">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#000000">

        <!-- Scripts -->
        @routes
        @vite(array_filter(\Nwidart\Modules\Module::getAssets(), fn($asset) => $asset !== 'resources/css/filament/admin/theme.css'))
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
