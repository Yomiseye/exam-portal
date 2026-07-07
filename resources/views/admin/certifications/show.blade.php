<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $certification->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">Manage packages and the exams included in each package.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.certifications.index') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="arrow-left" />
                    Certifications
                </a>
                <a href="{{ route('admin.certifications.edit', $certification) }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="pencil" />
                    Edit
                </a>
                <a href="{{ route('admin.certifications.packages.create', $certification) }}" class="portal-button-primary text-xs uppercase tracking-widest">
                    <x-icon name="plus" />
                    Add Package
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-4 md:flex-row md:items-start">
                    @if ($certification->imageUrl())
                        <img src="{{ $certification->imageUrl() }}" alt="" class="h-28 w-28 rounded-md object-cover">
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-md bg-gray-100 text-gray-500">
                            <x-icon name="tag" class="h-8 w-8" />
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $certification->name }}</h3>
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $certification->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                <x-icon name="{{ $certification->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                {{ $certification->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if ($certification->description)
                            <p class="mt-2 max-w-4xl text-sm leading-6 text-gray-600">{{ $certification->description }}</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                @forelse ($certification->packages as $package)
                    <article class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                                    @if ($package->badge)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">{{ $package->badge }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-2xl font-semibold text-gray-950">NGN {{ number_format((float) $package->price, 2) }}</div>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $package->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                <x-icon name="{{ $package->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                {{ $package->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if ($package->description)
                            <p class="mt-3 text-sm leading-6 text-gray-600">{{ $package->description }}</p>
                        @endif

                        <dl class="mt-5 grid gap-3 text-sm">
                            <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                                <dt class="inline-flex items-center gap-1.5 text-gray-500">
                                    <x-icon name="calendar-days" class="h-3.5 w-3.5" />
                                    Access Duration
                                </dt>
                                <dd class="font-semibold text-gray-900">{{ $package->duration_days }} days</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                                <dt class="inline-flex items-center gap-1.5 text-gray-500">
                                    <x-icon name="clipboard-list" class="h-3.5 w-3.5" />
                                    Mock Exams
                                </dt>
                                <dd class="font-semibold text-gray-900">{{ $package->exams->count() }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Included Exams</div>
                            <div class="mt-2 space-y-2">
                                @forelse ($package->exams as $exam)
                                    <div class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ $exam->title }}</div>
                                @empty
                                    <x-empty-state
                                        class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-5"
                                        icon="clipboard-list"
                                        title="No exams assigned"
                                        message="Edit this package to include exams."
                                    />
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3 text-xs font-medium">
                            <a href="{{ route('admin.certification-packages.edit', $package) }}" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-900">
                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                Edit
                            </a>

                            @if ($package->is_active)
                                <form method="POST" action="{{ route('admin.certification-packages.destroy', $package) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this package?')">
                                        <x-icon name="x-circle" class="h-3.5 w-3.5" />
                                        Deactivate
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.certification-packages.permanent-destroy', $package) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this package?')">
                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                    Delete
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg lg:col-span-3">
                        <x-empty-state
                            icon="clipboard-list"
                            title="No packages yet"
                            message="Add the three commercial package options for this certification."
                        >
                            <a href="{{ route('admin.certifications.packages.create', $certification) }}" class="portal-button-primary text-xs uppercase tracking-widest">
                                <x-icon name="plus" />
                                Add Package
                            </a>
                        </x-empty-state>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
