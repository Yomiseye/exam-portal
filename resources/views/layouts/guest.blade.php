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
            body.portal-guest {
                min-height: 100vh;
                background:
                    linear-gradient(135deg, rgba(15, 118, 110, 0.12), transparent 32%),
                    linear-gradient(315deg, rgba(180, 83, 9, 0.12), transparent 30%),
                    #f8fafc;
            }

            .guest-brand {
                display: grid;
                place-items: center;
                width: 72px;
                height: 72px;
                border-radius: 18px;
                background: #123c3a;
                color: white;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            }

            .guest-card {
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.96);
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            }

            .guest-card input {
                border-radius: 8px !important;
            }

            .guest-card button {
                border-radius: 8px !important;
                background: #0f766e !important;
            }
        </style>
    </head>
    <body class="portal-guest font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-6 sm:pt-0">
            <div class="guest-brand">
                <a href="/">
                    <x-application-logo class="w-10 h-10 fill-current text-white" />
                </a>
            </div>

            <div class="guest-card w-full sm:max-w-md mt-6 px-6 py-5 overflow-hidden">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
