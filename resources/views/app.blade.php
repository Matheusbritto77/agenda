<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $tenant = app()->bound('bookingTenant') ? app('bookingTenant') : null;
            if (! $tenant && auth()->check()) {
                $user = auth()->user();
                $tenantId = $user->parent_id ?: $user->id;
                $tenant = \App\Models\User::find($tenantId);
            }
            $brandingOwnerId = $tenant ? ($tenant->parent_id ?: $tenant->id) : null;
            $tenantBranding = $brandingOwnerId
                ? \App\Models\BrandingSetting::where('user_id', $brandingOwnerId)->first()
                : null;
            $tenantFaviconUrl = $tenantBranding?->favicon_url;
        @endphp

        <!-- Favicon -->
        @if ($tenantFaviconUrl)
            <link rel="icon" id="dynamic-favicon" href="{{ $tenantFaviconUrl }}">
            <link rel="apple-touch-icon" id="dynamic-apple-touch-icon" href="{{ $tenantFaviconUrl }}">
        @else
            <link rel="icon" id="dynamic-favicon" type="image/svg+xml" href="/favicon.svg">
            <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
            <link rel="alternate icon" href="/favicon.ico">
            <link rel="apple-touch-icon" id="dynamic-apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Dark / Light Theme Immediate Init to prevent FOUC (Default: Light Mode) -->
        <script>
            try {
                const savedTheme = localStorage.getItem('agendae_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (_) {}
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
