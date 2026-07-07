<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Certifications - {{ config('app.name', 'Exam Portal') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#eef3f6] font-sans text-gray-950 antialiased">
        <div class="min-h-screen">
            <header class="border-b border-white/10 bg-[#082f36] text-white">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-base font-bold">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-white text-[#082f36] shadow-sm">
                            <x-application-logo class="h-6 w-6 fill-current" />
                        </span>
                        <span>{{ config('app.name', 'Exam Portal') }}</span>
                    </a>

                    <nav class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('certifications.index') }}" class="inline-flex items-center gap-2 rounded-md border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/15">
                            <x-icon name="award" class="h-4 w-4" />
                            Certifications
                        </a>
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
                    <div class="mx-auto max-w-7xl px-4 pb-12 pt-10 sm:px-6 lg:px-8">
                        <div class="mx-auto max-w-4xl text-center">
                            <div class="inline-flex items-center gap-2 rounded-full border border-teal-200/30 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-50">
                                <x-icon name="sparkles" class="h-3.5 w-3.5 text-amber-300" />
                                Premium Certification Marketplace
                            </div>
                            <h1 class="mt-5 text-4xl font-semibold tracking-normal sm:text-5xl">
                                Select your certification preparation path.
                            </h1>
                            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-teal-50/85">
                                Compare certification tracks, preview package options, and choose the preparation route that fits your exam goal.
                            </p>
                        </div>

                        <div class="mx-auto mt-8 grid max-w-4xl gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-white/10 bg-white/10 p-4 text-center">
                                <div class="text-3xl font-semibold">{{ $certifications->count() }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-teal-50/70">Published tracks</div>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/10 p-4 text-center">
                                <div class="text-3xl font-semibold">{{ $certifications->sum('packages_count') }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-teal-50/70">Packages</div>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/10 p-4 text-center">
                                <div class="text-3xl font-semibold">{{ $certifications->sum(fn ($certification) => $certification->activePackages->sum('exams_count')) }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-teal-50/70">Exam inclusions</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-950">Available Certification Paths</h2>
                            <p class="mt-1 text-sm text-gray-600">Pick a track to compare its package options in detail.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        @forelse ($certifications as $certification)
                            @php
                                $packages = $certification->activePackages;
                                $previewPackages = $packages->take(3);
                                $durationMin = $packages->min('duration_days');
                                $durationMax = $packages->max('duration_days');
                                $examCount = $packages->sum('exams_count');
                            @endphp

                            <article class="group flex h-full flex-col overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-1 hover:border-teal-300 hover:shadow-lg">
                                <a href="{{ route('certifications.show', $certification) }}" class="relative block bg-[#dcefed]">
                                    @if ($certification->imageUrl())
                                        <img src="{{ $certification->imageUrl() }}" alt="" class="h-52 w-full object-cover">
                                    @else
                                        <div class="flex h-52 items-center justify-center text-teal-900">
                                            <x-icon name="award" class="h-16 w-16" />
                                        </div>
                                    @endif
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#082f36]/90 to-transparent p-4">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-300 px-3 py-1 text-xs font-semibold text-[#082f36]">
                                            <x-icon name="clipboard-list" class="h-3 w-3" />
                                            {{ $certification->packages_count }} package(s)
                                        </span>
                                    </div>
                                </a>

                                <div class="flex flex-1 flex-col p-5">
                                    <h3 class="text-xl font-semibold text-gray-950 group-hover:text-teal-800">{{ $certification->name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600">
                                        {{ \Illuminate\Support\Str::limit($certification->description ?: 'View package options and included mock exams for this certification track.', 150) }}
                                    </p>

                                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                                        <div class="rounded-md bg-[#f4f7f8] px-2 py-3">
                                            <div class="text-lg font-semibold text-gray-950">{{ $certification->packages_count }}</div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Packages</div>
                                        </div>
                                        <div class="rounded-md bg-[#f4f7f8] px-2 py-3">
                                            <div class="text-lg font-semibold text-gray-950">{{ $examCount }}</div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Exams</div>
                                        </div>
                                        <div class="rounded-md bg-[#f4f7f8] px-2 py-3">
                                            <div class="text-lg font-semibold text-gray-950">
                                                @if ($durationMin && $durationMax)
                                                    {{ $durationMin === $durationMax ? $durationMax : $durationMin.'-'.$durationMax }}
                                                @else
                                                    --
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Days</div>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex-1">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Package preview</div>
                                        <div class="mt-2 space-y-2">
                                            @forelse ($previewPackages as $package)
                                                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                                                    <div class="min-w-0">
                                                        <div class="truncate font-semibold text-gray-950">{{ $package->name }}</div>
                                                        @if ($package->badge)
                                                            <div class="text-xs font-medium text-teal-700">{{ $package->badge }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="shrink-0 text-right text-xs font-semibold text-gray-600">
                                                        {{ $package->duration_days }} days<br>
                                                        {{ $package->exams_count }} exam(s)
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="rounded-md border border-dashed border-gray-200 px-3 py-4 text-sm text-gray-500">
                                                    Package details will appear soon.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <a href="{{ route('certifications.show', $certification) }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-md bg-[#0f766e] px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-[#115e59]">
                                        <x-icon name="arrow-right" class="h-4 w-4" />
                                        View Packages
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-md border border-gray-200 bg-white shadow-sm lg:col-span-2 xl:col-span-3">
                                <x-empty-state
                                    icon="tag"
                                    title="No certifications available"
                                    message="Certification packages will appear here once they are published."
                                />
                            </div>
                        @endforelse
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
