<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Exam
            </h2>
            <a href="{{ route('admin.exams.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to exams
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.exams.store') }}" class="p-6 space-y-6">
                    @csrf

                    @include('admin.exams.partials.form', [
                        'exam' => null,
                        'categories' => $categories,
                    ])

                    <div class="flex justify-end">
                        <x-primary-button>Create Exam</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
