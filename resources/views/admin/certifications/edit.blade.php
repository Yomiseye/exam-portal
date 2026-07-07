<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Certification
            </h2>
            <a href="{{ route('admin.certifications.show', $certification) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Back to Packages
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.certifications.update', $certification) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.certifications.partials.form', ['certification' => $certification])

                    <div class="flex justify-end">
                        <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                            <x-icon name="save" />
                            Save Certification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
