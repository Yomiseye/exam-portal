<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Question
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.questions.preview', $question) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-900">
                    <x-icon name="eye" class="h-3.5 w-3.5" />
                    Preview
                </a>
                <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900">
                    <x-icon name="circle-help" class="h-3.5 w-3.5" />
                    Back to questions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.questions.update', $question) }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.questions.partials.form', [
                        'question' => $question,
                        'categories' => $categories,
                    ])

                    <div class="flex justify-end">
                        <x-primary-button>
                            <x-icon name="save" />
                            Save Changes
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
