<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --portal-ink: #111827;
                --portal-muted: #64748b;
                --portal-border: #dbe3ee;
                --portal-soft: #f6f8fb;
                --portal-panel: #ffffff;
                --portal-primary: #0f766e;
                --portal-primary-dark: #115e59;
                --portal-accent: #b45309;
                --portal-danger: #b91c1c;
                --portal-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            }

            [x-cloak] {
                display: none !important;
            }

            body.portal-app {
                background:
                    linear-gradient(180deg, #eef7f5 0%, #f7fafc 28%, #f8fafc 100%);
                color: var(--portal-ink);
            }

            .portal-shell {
                min-height: 100vh;
                background:
                    linear-gradient(180deg, rgba(15, 118, 110, 0.08), transparent 240px),
                    var(--portal-soft);
            }

            .portal-app nav {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
                background: rgba(255, 255, 255, 0.92);
                backdrop-filter: blur(14px);
            }

            .portal-header {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
                background: linear-gradient(135deg, #123c3a 0%, #0f766e 58%, #b45309 100%);
                color: white;
                box-shadow: none;
            }

            .portal-header h1,
            .portal-header h2,
            .portal-header h3,
            .portal-header a,
            .portal-header .text-gray-800,
            .portal-header .text-gray-500,
            .portal-header .text-indigo-600 {
                color: white !important;
            }

            .portal-app main > .py-12 {
                padding-top: 2rem;
                padding-bottom: 3rem;
            }

            .portal-app .bg-white {
                border: 1px solid rgba(15, 23, 42, 0.08);
                background-color: rgba(255, 255, 255, 0.96) !important;
            }

            .portal-app .shadow-sm,
            .portal-app .shadow-md {
                box-shadow: var(--portal-shadow) !important;
            }

            .portal-app .sm\:rounded-lg,
            .portal-app .rounded-lg {
                border-radius: 8px !important;
            }

            .portal-app table {
                font-size: 0.92rem;
            }

            .portal-app thead,
            .portal-app .bg-gray-50 {
                background-color: #f1f5f9 !important;
            }

            .portal-app th {
                color: #475569 !important;
                letter-spacing: 0;
            }

            .portal-app tbody tr {
                transition: background-color 140ms ease;
            }

            .portal-app tbody tr:hover {
                background-color: #f8fafc;
            }

            .portal-app input[type="text"],
            .portal-app input[type="email"],
            .portal-app input[type="password"],
            .portal-app input[type="number"],
            .portal-app select,
            .portal-app textarea {
                border-color: var(--portal-border) !important;
                border-radius: 8px !important;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
            }

            .portal-app input:focus,
            .portal-app select:focus,
            .portal-app textarea:focus {
                border-color: var(--portal-primary) !important;
                box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.16) !important;
            }

            .portal-app button[class*="bg-gray-800"],
            .portal-app a[class*="bg-gray-800"] {
                border-radius: 8px !important;
                background: var(--portal-primary) !important;
                box-shadow: 0 10px 22px rgba(15, 118, 110, 0.18);
                letter-spacing: 0;
            }

            .portal-app button[class*="bg-gray-800"]:hover,
            .portal-app a[class*="bg-gray-800"]:hover {
                background: var(--portal-primary-dark) !important;
            }

            .portal-app a.text-indigo-600,
            .portal-app .text-indigo-600 {
                color: var(--portal-primary) !important;
            }

            .portal-app .rounded-full {
                letter-spacing: 0;
            }

            .portal-app input[type="radio"],
            .portal-app input[type="checkbox"] {
                color: var(--portal-primary) !important;
            }

            .portal-app label:has(input[type="radio"]) {
                transition: border-color 140ms ease, background-color 140ms ease;
            }

            .portal-app label:has(input[type="radio"]:checked) {
                border-color: rgba(15, 118, 110, 0.45) !important;
                background: #ecfdf5;
            }

            .portal-app .bg-blue-50 {
                border: 1px solid #bfdbfe;
            }
        </style>
    </head>
    <body class="portal-app font-sans antialiased">
        <div class="portal-shell">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="portal-header">
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
