<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sui Control</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts / Style -->
        <script>
            if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a', 950: '#172554',
                            }
                        }
                    }
                }
            }
        </script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        @if (file_exists(public_path('logo.png')))
            <link rel="icon" href="/logo.png" type="image/png">
        @else
            <link rel="icon" href="/favicon.ico" type="image/x-icon">
        @endif
        <style>
            input[type="text"],
            input[type="password"],
            input[type="email"],
            input[type="number"],
            select,
            textarea {
                padding: 0.75rem 1rem;
                font-size: 1.0625rem;
                border-radius: 0.75rem;
                min-height: 2.75rem;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
            <!-- Wallpaper Background -->
            <div class="absolute inset-0 z-0">
                <img src="/login.png" alt="Background" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/20"></div>
            </div>
            
            <!-- Content Overlay -->
            <div class="relative z-10 w-full sm:max-w-md mt-6 px-6 py-4">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
