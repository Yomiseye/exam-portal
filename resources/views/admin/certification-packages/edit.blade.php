<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Package
            </h2>
            <a href="{{ route('admin.certifications.show', $certification) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Back to {{ $certification->name }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.certification-packages.update', $package) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.certification-packages.partials.form')

                    <div class="flex justify-end">
                        <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                            <x-icon name="save" />
                            Save Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
