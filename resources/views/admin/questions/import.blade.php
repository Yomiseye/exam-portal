<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Questions
            </h2>
            <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to Questions
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Excel Format</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Use the first worksheet and keep the first row as headers. Missing categories and subcategories will be created automatically.
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Column</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Required</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Example</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">category</td>
                                <td class="px-3 py-2 text-gray-600">Yes</td>
                                <td class="px-3 py-2 text-gray-600">Project Management</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">subcategory</td>
                                <td class="px-3 py-2 text-gray-600">Yes</td>
                                <td class="px-3 py-2 text-gray-600">Scope Performance Domain</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">question_type</td>
                                <td class="px-3 py-2 text-gray-600">Yes</td>
                                <td class="px-3 py-2 text-gray-600">single_choice, multiple_choice, true_false, matching</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">question</td>
                                <td class="px-3 py-2 text-gray-600">Yes</td>
                                <td class="px-3 py-2 text-gray-600">What is 2 + 2?</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">difficulty</td>
                                <td class="px-3 py-2 text-gray-600">No</td>
                                <td class="px-3 py-2 text-gray-600">easy, medium, hard</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">option_1, option_2...</td>
                                <td class="px-3 py-2 text-gray-600">Yes</td>
                                <td class="px-3 py-2 text-gray-600">4, 5</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">correct_answers</td>
                                <td class="px-3 py-2 text-gray-600">Except matching</td>
                                <td class="px-3 py-2 text-gray-600">1 or 1;3</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">match_1, match_2...</td>
                                <td class="px-3 py-2 text-gray-600">Matching only</td>
                                <td class="px-3 py-2 text-gray-600">Abuja, Accra</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">explanation, is_active</td>
                                <td class="px-3 py-2 text-gray-600">No</td>
                                <td class="px-3 py-2 text-gray-600">Optional note, yes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.questions.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="questions_file" value="Excel File (.xlsx)" />
                        <input
                            id="questions_file"
                            name="questions_file"
                            type="file"
                            accept=".xlsx"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 text-sm shadow-sm file:me-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                        >

                        @if ($errors->has('questions_file'))
                            <div class="mt-3 whitespace-pre-line rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('questions_file') }}</div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Import Questions</x-primary-button>
                        <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
