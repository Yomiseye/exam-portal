<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Questions
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.questions.import') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="upload" />
                    Import Excel
                </a>
                <a href="{{ route('admin.questions.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                    <x-icon name="plus" />
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
                        <x-input-label for="search" value="Search" icon="search" />
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
                        <x-input-label for="category_id" value="Category" icon="tag" />
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
                        <x-input-label for="difficulty" value="Difficulty" icon="filter" />
                        <select id="difficulty" name="difficulty" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All difficulties</option>
                            @foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="lifecycle" value="Lifecycle" icon="filter" />
                        <select id="lifecycle" name="lifecycle" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All lifecycles</option>
                            @foreach (\App\Models\Question::LIFECYCLES as $value => $label)
                                <option value="{{ $value }}" @selected(request('lifecycle') === $value)>{{ $label }}</option>
                            @endforeach
                            <option value="unclassified" @selected(request('lifecycle') === 'unclassified')>Unclassified</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="eco_domain" value="Eco_Domain" icon="tag" />
                        <select id="eco_domain" name="eco_domain" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All eco domains</option>
                            @foreach (\App\Models\Question::ECO_DOMAINS as $value => $label)
                                <option value="{{ $value }}" @selected(request('eco_domain') === $value)>{{ $label }}</option>
                            @endforeach
                            <option value="unclassified" @selected(request('eco_domain') === 'unclassified')>Unclassified</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="domain" value="Performance_Domain" icon="tag" />
                        <select id="domain" name="domain" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All performance domains</option>
                            @foreach (\App\Models\Question::PERFORMANCE_DOMAINS as $value => $label)
                                <option value="{{ $value }}" @selected(request('domain') === $value)>{{ $label }}</option>
                            @endforeach
                            <option value="unclassified" @selected(request('domain') === 'unclassified')>Unclassified</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="focus_area" value="Focus Area" icon="clipboard-list" />
                        <select id="focus_area" name="focus_area" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All focus areas</option>
                            @foreach (\App\Models\Question::FOCUS_AREAS as $value => $label)
                                <option value="{{ $value }}" @selected(request('focus_area') === $value)>{{ $label }}</option>
                            @endforeach
                            <option value="unclassified" @selected(request('focus_area') === 'unclassified')>Unclassified</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            <x-icon name="filter" />
                            Filter
                        </x-primary-button>
                        <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>
            </div>

            <form id="bulk-question-form" method="POST" action="{{ route('admin.questions.bulk-action') }}" class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                @csrf

                <div class="grid gap-4 md:grid-cols-[1fr,auto] md:items-end">
                    <div>
                        <x-input-label for="bulk_action" value="Bulk Action" icon="check-circle" />
                        <select id="bulk_action" name="action" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose action</option>
                            <option value="activate">Activate selected</option>
                            <option value="deactivate">Deactivate selected</option>
                            <option value="delete">Permanently delete selected</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('action')" />
                        <x-input-error class="mt-2" :messages="$errors->get('question_ids')" />
                    </div>

                    <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest" onclick="return confirm('Apply this bulk action to the selected questions? Permanent delete will skip questions with exam history.')">
                        <x-icon name="check-circle" />
                        Apply to Selected
                    </button>
                </div>
            </form>

            <div class="space-y-4">
                <div class="flex flex-col gap-3 rounded-md border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Question Bank</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $questions->total() }} question(s) found.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-page-size-selector />

                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                onclick="document.querySelectorAll('[data-question-select]').forEach((checkbox) => checkbox.checked = this.checked)"
                            >
                            Select visible questions
                        </label>
                    </div>
                </div>

                @forelse ($questions as $question)
                    <article class="rounded-md border border-gray-200 bg-white p-4 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                        <div class="grid gap-4 lg:grid-cols-[auto,1fr,18rem] lg:items-start">
                            <div class="flex items-start gap-3 lg:block">
                                <input
                                    type="checkbox"
                                    name="question_ids[]"
                                    value="{{ $question->id }}"
                                    form="bulk-question-form"
                                    data-question-select
                                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                >

                                <div class="lg:mt-3 lg:text-center">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">ID</div>
                                    <div class="mt-1 text-sm font-semibold text-gray-950">#{{ $question->id }}</div>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        <x-icon name="circle-help" class="h-3 w-3" />
                                        {{ $question->typeLabel() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold capitalize text-gray-700">
                                        <x-icon name="filter" class="h-3 w-3" />
                                        {{ $question->difficulty }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        <x-icon name="clipboard-list" class="h-3 w-3" />
                                        {{ $question->options_count }} option(s)
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                        <x-icon name="filter" class="h-3 w-3" />
                                        {{ $question->lifecycleLabel() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        <x-icon name="tag" class="h-3 w-3" />
                                        {{ $question->ecoDomainLabel() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                        <x-icon name="tag" class="h-3 w-3" />
                                        {{ $question->performanceDomainLabel() }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        <x-icon name="clipboard-list" class="h-3 w-3" />
                                        {{ $question->focusAreaLabel() }}
                                    </span>
                                </div>

                                <div class="mt-3 text-sm font-medium leading-6 text-gray-950">
                                    {{ \Illuminate\Support\Str::limit(\App\Support\RichText::plainText($question->question_text), 220) }}
                                </div>

                                <div class="mt-3 text-sm text-gray-500">
                                    <span class="font-medium text-gray-700">Category:</span>
                                    {{ $question->category->fullName() }}
                                </div>

                                @if ($question->image_path || $question->explanation_image_path)
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @if ($question->image_path)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700">
                                                <x-icon name="image" class="h-3 w-3" />
                                                Question image
                                            </span>
                                        @endif
                                        @if ($question->explanation_image_path)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                <x-icon name="image" class="h-3 w-3" />
                                                Explanation image
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                @if ($question->tags->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1">
                                        @foreach ($question->tags as $tag)
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-600">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-col gap-3 lg:items-end">
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
                                        <x-icon name="{{ $question->is_active ? 'check-circle' : 'x-circle' }}" class="h-3.5 w-3.5" />
                                        {{ $question->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>

                                <div class="flex flex-wrap gap-3 text-xs lg:justify-end">
                                    <a href="{{ route('admin.questions.preview', $question) }}" class="inline-flex items-center gap-1.5 font-semibold text-teal-700 hover:text-teal-900">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                        Preview
                                    </a>
                                    <a href="{{ route('admin.questions.edit', $question) }}" class="inline-flex items-center gap-1.5 font-semibold text-indigo-600 hover:text-indigo-900">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('admin.questions.permanent-destroy', $question) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 font-semibold text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this question? This only works when it has not been used in an exam attempt.')">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-md border border-gray-200 bg-white shadow-sm">
                        <x-empty-state
                            icon="circle-help"
                            title="No questions yet"
                            message="Create questions manually or import them from Excel."
                        >
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="{{ route('admin.questions.import') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                                    <x-icon name="upload" />
                                    Import Excel
                                </a>
                                <a href="{{ route('admin.questions.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                                    <x-icon name="plus" />
                                    Create Question
                                </a>
                            </div>
                        </x-empty-state>
                    </div>
                @endforelse

                @if ($questions->hasPages())
                    <div class="rounded-md border border-gray-200 bg-white px-6 py-4 shadow-sm">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
