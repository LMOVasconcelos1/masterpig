<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <meta name="description" content="Sistema de gerenciamento de granja suína Sui Control - MasterPig">
        <meta name="theme-color" content="#f97316" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#78350f" media="(prefers-color-scheme: dark)">

        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/png" href="/logo.png">
        <link rel="apple-touch-icon" href="/logoSemPalavra.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/logoSemPalavra.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/logoSemPalavra.png">
        <link rel="apple-touch-icon" sizes="167x167" href="/logoSemFundo.png">

        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Sui Control">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="format-detection" content="telephone=no">
        <meta name="msapplication-TileColor" content="#f97316">
        <meta name="msapplication-tap-highlight" content="no">

        <style>
            :root {
                --sat: env(safe-area-inset-top);
                --sar: env(safe-area-inset-right);
                --sab: env(safe-area-inset-bottom);
                --sal: env(safe-area-inset-left);
            }
            html, body { height: 100%; }
            body { -webkit-tap-highlight-color: transparent; overscroll-behavior-y: none; -webkit-overflow-scrolling: touch; }
            .h-screen { min-height: 100vh; min-height: 100dvh; }
            .min-h-screen { min-height: 100vh; min-height: 100dvh; }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="bg-gray-100"
             style="min-height: 100vh; min-height: 100dvh; padding-top: var(--sat); padding-right: var(--sar); padding-bottom: var(--sab); padding-left: var(--sal);">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
