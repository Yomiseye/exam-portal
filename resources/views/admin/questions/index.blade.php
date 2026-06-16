<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Questions
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.questions.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Import Excel
                </a>
                <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Create Question
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.questions.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="search" value="Search" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="search"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Question, category, type"
                        />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Category" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->fullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="difficulty" value="Difficulty" />
                        <select id="difficulty" name="difficulty" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All difficulties</option>
                            @foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>Filter</x-primary-button>
                        <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>
            </div>

            <form id="bulk-question-form" method="POST" action="{{ route('admin.questions.bulk-action') }}" class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                @csrf

                <div class="grid gap-4 md:grid-cols-[1fr,auto] md:items-end">
                    <div>
                        <x-input-label for="bulk_action" value="Bulk Action" />
                        <select id="bulk_action" name="action" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose action</option>
                            <option value="activate">Activate selected</option>
                            <option value="deactivate">Deactivate selected</option>
                            <option value="delete">Permanently delete selected</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('action')" />
                        <x-input-error class="mt-2" :messages="$errors->get('question_ids')" />
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700" onclick="return confirm('Apply this bulk action to the selected questions? Permanent delete will skip questions with exam history.')">
                        Apply to Selected
                    </button>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        onclick="document.querySelectorAll('[data-question-select]').forEach((checkbox) => checkbox.checked = this.checked)"
                                    >
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difficulty</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($questions as $question)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input
                                            type="checkbox"
                                            name="question_ids[]"
                                            value="{{ $question->id }}"
                                            form="bulk-question-form"
                                            data-question-select
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-md">
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 100) }}
                                        @if ($question->image_path)
                                            <div class="mt-1 text-xs font-normal text-gray-500">Has image</div>
                                        @endif
                                        @if ($question->explanation_image_path)
                                            <div class="mt-1 text-xs font-normal text-gray-500">Has explanation image</div>
                                        @endif
                                        @if ($question->tags->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach ($question->tags as $tag)
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-600">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $question->category->fullName() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $question->typeLabel() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {{ $question->difficulty }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $question->options_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" action="{{ route('admin.questions.status.update', $question) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $question->is_active ? '0' : '1' }}">
                                            <button
                                                type="submit"
                                                class="group inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-xs font-semibold transition {{ $question->is_active ? 'border-green-200 bg-green-50 text-green-800 hover:bg-green-100' : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100' }}"
                                                title="Toggle question status"
                                                onclick="return confirm('Mark this question as {{ $question->is_active ? 'inactive' : 'active' }}?')"
                                            >
                                                <span class="relative inline-flex h-4 w-7 items-center rounded-full {{ $question->is_active ? 'bg-green-600' : 'bg-gray-300' }}">
                                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $question->is_active ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                                </span>
                                                {{ $question->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('admin.questions.edit', $question) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                                            <form method="POST" action="{{ route('admin.questions.permanent-destroy', $question) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this question? This only works when it has not been used in an exam attempt.')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No questions have been created yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($questions->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
