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

            .portal-page {
                padding-top: 2rem;
                padding-bottom: 3rem;
            }

            .portal-container {
                width: 100%;
                max-width: 80rem;
                margin-inline: auto;
                padding-inline: 1rem;
            }

            @media (min-width: 640px) {
                .portal-container {
                    padding-inline: 1.5rem;
                }
            }

            @media (min-width: 1024px) {
                .portal-container {
                    padding-inline: 2rem;
                }
            }

            .portal-panel {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.96);
                box-shadow: var(--portal-shadow);
            }

            .portal-panel-muted {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 8px;
                background: #f8fafc;
            }

            .portal-kpi {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 8px;
                background: #ffffff;
                padding: 1rem;
                box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            }

            .portal-button-primary,
            .portal-button-secondary,
            .portal-button-danger,
            .portal-button-muted {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 2.5rem;
                border-radius: 8px;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                font-weight: 650;
                line-height: 1;
                transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease, box-shadow 140ms ease;
            }

            .portal-button-primary {
                border: 1px solid transparent;
                background: var(--portal-primary);
                color: #ffffff;
                box-shadow: 0 10px 22px rgba(15, 118, 110, 0.18);
            }

            .portal-button-primary:hover {
                background: var(--portal-primary-dark);
            }

            .portal-button-secondary {
                border: 1px solid var(--portal-border);
                background: #ffffff;
                color: #334155;
            }

            .portal-button-secondary:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
            }

            .portal-button-danger {
                border: 1px solid transparent;
                background: var(--portal-danger);
                color: #ffffff;
            }

            .portal-button-danger:hover {
                background: #991b1b;
            }

            .portal-button-muted {
                border: 1px solid #e2e8f0;
                background: #f1f5f9;
                color: #64748b;
            }

            .portal-badge {
                display: inline-flex;
                align-items: center;
                width: fit-content;
                border-radius: 999px;
                padding: 0.25rem 0.625rem;
                font-size: 0.75rem;
                font-weight: 650;
                line-height: 1.2;
            }

            .portal-badge-success {
                background: #dcfce7;
                color: #166534;
            }

            .portal-badge-info {
                background: #dbeafe;
                color: #1e40af;
            }

            .portal-badge-warning {
                background: #fef3c7;
                color: #92400e;
            }

            .portal-badge-danger {
                background: #fee2e2;
                color: #991b1b;
            }

            .portal-badge-neutral {
                background: #f1f5f9;
                color: #475569;
            }

            .portal-alert {
                border-radius: 8px;
                border: 1px solid transparent;
                padding: 1rem;
                font-size: 0.875rem;
            }

            .portal-alert-info {
                border-color: #bfdbfe;
                background: #eff6ff;
                color: #1d4ed8;
            }

            .portal-alert-success {
                border-color: #bbf7d0;
                background: #f0fdf4;
                color: #166534;
            }

            .portal-alert-warning {
                border-color: #fde68a;
                background: #fffbeb;
                color: #92400e;
            }

            .portal-alert-danger {
                border-color: #fecaca;
                background: #fef2f2;
                color: #991b1b;
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
