<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-surface-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to ensure dark class is never applied --}}
        <script>
            (function() {
                document.documentElement.classList.remove('dark');
            })();
        </script>

        <title inertia>{{ config('app.name', 'Fibermade') }}</title>

        <link rel="icon" href="/icon.png" type="image/png">
        <link rel="apple-touch-icon" href="/icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="min-h-full font-sans antialiased">
        @inertia
    </body>
</html>
