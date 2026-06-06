<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Categories</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $categoryCount }}</div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Questions</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $questionCount }}</div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Exams</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $examCount }}</div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Submitted Attempts</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $submittedAttemptCount }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p>Welcome, admin. This area will manage categories, questions, exams, results, and retake permissions.</p>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Manage Categories
                        </a>

                        <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Manage Questions
                        </a>

                        <a href="{{ route('admin.exams.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Manage Exams
                        </a>

                        <a href="{{ route('admin.results.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
