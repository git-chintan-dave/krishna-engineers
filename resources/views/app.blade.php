<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])
>
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    {{-- PWA --}}
    <link rel="icon" href="/favicon.ico">

    <link
        rel="icon"
        href="/icons/icon-192x192.png"
        type="image/png"
        sizes="192x192"
    >

    <link
        rel="icon"
        href="/icons/icon-512x512.png"
        type="image/png"
        sizes="512x512"
    >

    <link
        rel="apple-touch-icon"
        href="/icons/icon-180x180.png"
    >

    <link rel="manifest" href="/manifest.json">

    <meta
        name="theme-color"
        content="#171717"
    >

    <meta
        name="mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="default"
    >

    <meta
        name="apple-mobile-web-app-title"
        content="Krishna Engineers"
    >

    {{-- iPhone splash screens --}}
    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-750x1334.png"
        media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1179x2556.png"
        media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1206x2622.png"
        media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1290x2796.png"
        media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1320x2868.png"
        media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)"
    >

    {{-- iPhone splash screens - landscape --}}
    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1334x750.png"
        media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2556x1179.png"
        media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2622x1206.png"
        media="(device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2796x1290.png"
        media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2868x1320.png"
        media="(device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)"
    >

    {{-- iPad splash screens --}}
    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1488x2266.png"
        media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1536x2048.png"
        media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1640x2360.png"
        media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-1668x2388.png"
        media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2048x2732.png"
        media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)"
    >

    {{-- iPad splash screens - landscape --}}
    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2266x1488.png"
        media="(device-width: 744px) and (device-height: 1133px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2048x1536.png"
        media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2360x1640.png"
        media="(device-width: 820px) and (device-height: 1180px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2388x1668.png"
        media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    <link
        rel="apple-touch-startup-image"
        href="/splash/splash-2732x2048.png"
        media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)"
    >

    @fonts

    @vite([
        'resources/css/app.css',
        'resources/js/app.ts',
        "resources/js/pages/{$page['component']}.vue"
    ])

    <x-inertia::head>
        <title>{{ config('app.name', 'Laravel') }}</title>
    </x-inertia::head>
</head>

<body class="font-sans antialiased">
<x-inertia::app />
</body>
</html>
