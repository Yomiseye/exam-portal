<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Certification
            </h2>
            <a href="{{ route('admin.certifications.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Back to Certifications
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.certifications.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @include('admin.certifications.partials.form', ['certification' => null])

                    <div class="flex justify-end">
                        <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                            <x-icon name="save" />
                            Create Certification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
