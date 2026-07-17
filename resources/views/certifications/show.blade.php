<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $certification->name }} Packages - {{ config('app.name', 'Exam Portal') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#eef3f6] font-sans text-gray-950 antialiased">
        @php
            $packageCount = $certification->activePackages->count();
            $durationMin = $certification->activePackages->min('duration_days');
            $durationMax = $certification->activePackages->max('duration_days');
        @endphp

        <div class="min-h-screen">
            <header class="border-b border-white/10 bg-[#082f36] text-white">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <a href="{{ route('certifications.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-50 hover:text-amber-200">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        Back to Certifications
                    </a>

                    <nav class="flex flex-wrap gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-md bg-amber-400 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#082f36] shadow-sm transition hover:bg-amber-300">
                                <x-icon name="layout-dashboard" class="h-4 w-4" />
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-md bg-amber-400 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#082f36] shadow-sm transition hover:bg-amber-300">
                                <x-icon name="log-out" class="h-4 w-4" />
                                Log in
                            </a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main>
                <section class="bg-[#082f36] text-white">
                    <div class="mx-auto grid max-w-7xl gap-8 px-4 pb-12 pt-10 sm:px-6 lg:grid-cols-[0.9fr,1.1fr] lg:items-center lg:px-8">
                        <div class="overflow-hidden rounded-md border border-white/10 bg-white/10 shadow-lg">
                            @if ($certification->imageUrl())
                                <img src="{{ $certification->imageUrl() }}" alt="" class="h-72 w-full object-cover lg:h-96">
                            @else
                                <div class="flex h-72 items-center justify-center bg-white/10 text-amber-200 lg:h-96">
                                    <x-icon name="award" class="h-20 w-20" />
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-teal-200/30 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-50">
                                <x-icon name="award" class="h-3.5 w-3.5 text-amber-300" />
                                Certification Track
                            </div>
                            <h1 class="mt-5 text-4xl font-semibold tracking-normal sm:text-5xl">{{ $certification->name }}</h1>
                            <p class="mt-4 max-w-3xl text-base leading-7 text-teal-50/85">
                                {{ $certification->description ?: 'Compare available preparation packages and choose the access level that fits your exam timeline.' }}
                            </p>

                            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-md border border-white/10 bg-white/10 p-4">
                                    <div class="text-3xl font-semibold">{{ $packageCount }}</div>
                                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-teal-50/70">Packages</div>
                                </div>
                                <div class="rounded-md border border-white/10 bg-white/10 p-4">
                                    <div class="text-3xl font-semibold">
                                        @if ($durationMin && $durationMax)
                                            {{ $durationMin === $durationMax ? $durationMax : $durationMin.'-'.$durationMax }}
                                        @else
                                            0
                                        @endif
                                    </div>
                                    <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-teal-50/70">Access days</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-950">Compare Package Options</h2>
                            <p class="mt-1 text-sm text-gray-600">Choose based on preparation intensity, access duration, and package fit.</p>
                        </div>
                    </div>

                    <div class="mt-7 grid gap-6 lg:grid-cols-3">
                        @forelse ($certification->activePackages as $package)
                            @php
                                $badge = strtolower((string) $package->badge);
                                $isRecommended = str_contains($badge, 'recommended');
                                $isBestValue = str_contains($badge, 'best');
                                $isStarter = str_contains($badge, 'starter') || str_contains(strtolower($package->name), 'practice');
                                $price = (float) $package->price;
                                $priceLabel = $price > 0 ? 'NGN '.number_format($price, 2) : 'Pricing pending';
                                $accentClass = $isRecommended
                                    ? 'border-teal-500 ring-2 ring-teal-100'
                                    : ($isBestValue ? 'border-amber-400 ring-2 ring-amber-100' : 'border-gray-200');
                                $badgeLabel = $isRecommended ? 'Recommended' : ($isBestValue ? 'Best Value' : ($package->badge ?: ($isStarter ? 'Starter' : 'Package')));
                            @endphp

                            <article class="relative flex h-full flex-col overflow-hidden rounded-md border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg {{ $accentClass }}">
                                <div class="{{ $isRecommended ? 'bg-teal-700' : ($isBestValue ? 'bg-amber-500' : 'bg-[#082f36]') }} px-5 py-4 text-white">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide">
                                                <x-icon name="{{ $isBestValue ? 'award' : ($isRecommended ? 'sparkles' : 'clipboard-list') }}" class="h-3 w-3" />
                                                {{ $badgeLabel }}
                                            </div>
                                            <h3 class="mt-3 text-xl font-semibold">{{ $package->name }}</h3>
                                        </div>
                                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-md bg-white/15">
                                            <x-icon name="{{ $isBestValue ? 'award' : ($isRecommended ? 'sparkles' : 'clipboard-list') }}" class="h-5 w-5" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    <p class="min-h-20 text-sm leading-6 text-gray-600">
                                        {{ $package->description ?: ($isRecommended ? 'Best fit for candidates actively preparing for an exam date.' : ($isBestValue ? 'Best fit for candidates who want the broadest preparation access.' : 'Best fit for candidates starting with focused practice.')) }}
                                    </p>

                                    <div class="mt-5 rounded-md border border-gray-200 bg-[#f8fafc] p-4">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Package price</div>
                                        <div class="mt-1 text-3xl font-semibold text-gray-950">{{ $priceLabel }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ $package->duration_days }} days of preparation access</div>
                                    </div>

                                    <dl class="mt-4 grid gap-3 text-sm">
                                        <div class="flex items-center justify-between rounded-md bg-white px-3 py-2 ring-1 ring-gray-200">
                                            <dt class="inline-flex items-center gap-1.5 text-gray-500">
                                                <x-icon name="calendar-days" class="h-3.5 w-3.5" />
                                                Duration
                                            </dt>
                                            <dd class="font-semibold text-gray-900">{{ $package->duration_days }} days</dd>
                                        </div>
                                    </dl>

                                    <a href="{{ route('login') }}" class="{{ $isRecommended ? 'bg-[#0f766e] text-white hover:bg-[#115e59]' : 'bg-[#082f36] text-white hover:bg-[#0f3f47]' }} mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md px-4 py-3 text-xs font-semibold uppercase tracking-widest shadow-sm transition">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                        Get Started
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-md border border-gray-200 bg-white shadow-sm lg:col-span-3">
                                <x-empty-state
                                    icon="clipboard-list"
                                    title="No packages available"
                                    message="Packages for this certification will appear here once they are published."
                                />
                            </div>
                        @endforelse
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
