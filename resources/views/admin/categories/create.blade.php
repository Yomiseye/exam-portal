<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Category
            </h2>
            <a href="{{ route('admin.categories.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to categories
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.categories.store') }}" class="p-6 space-y-6">
                    @csrf

                    @include('admin.categories.partials.form', ['category' => null])

                    <div class="flex justify-end">
                        <x-primary-button>Create Category</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
