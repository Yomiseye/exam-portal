<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Question Preview
                </h2>
                <p class="mt-1 text-sm text-white/80">
                    {{ $question->category->fullName() }} · {{ $question->typeLabel() }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.questions.edit', $question) }}" class="text-sm font-medium text-white/90 hover:text-white">
                    Edit
                </a>
                <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-white/90 hover:text-white">
                    Back to questions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Question</div>
                        <div class="mt-2 rich-content text-base leading-7 text-gray-950">{!! $question->question_text !!}</div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                            {{ ucfirst($question->difficulty) }}
                        </span>
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide {{ $question->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $question->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                @if ($question->image_path)
                    <div class="mt-5">
                        <img
                            src="{{ $question->imageUrl() }}"
                            alt="Question image"
                            class="max-h-96 w-full rounded-md border border-gray-200 object-contain"
                        >
                    </div>
                @endif

                @if ($question->tags->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($question->tags as $tag)
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Answers</h3>
                    <p class="text-sm text-gray-500">
                        Correct answer{{ $question->options->where('is_correct', true)->count() === 1 ? '' : 's' }} highlighted
                    </p>
                </div>

                <div class="mt-4 grid gap-4">
                    @foreach ($question->options as $option)
                        <div class="rounded-md border p-4 {{ $option->is_correct ? 'border-emerald-300 bg-emerald-50' : 'border-gray-200 bg-white' }}">
                            <div class="flex items-start gap-3">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $option->is_correct ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                    {{ chr(64 + $loop->iteration) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="rich-content text-sm leading-6 text-gray-900">{!! $option->option_text !!}</div>

                                    @if ($option->image_path)
                                        <img
                                            src="{{ $option->imageUrl() }}"
                                            alt="Option image"
                                            class="mt-3 max-h-56 rounded-md border border-gray-200 bg-white object-contain"
                                        >
                                    @endif

                                    @if ($question->question_type === \App\Models\Question::TYPE_MATCHING)
                                        <div class="mt-3 rounded-md bg-white/80 p-3 text-sm text-gray-700">
                                            <span class="font-medium text-gray-500">Match:</span>
                                            {{ $option->match_text }}
                                        </div>
                                    @endif
                                </div>

                                @if ($option->is_correct)
                                    <span class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white">
                                        Correct
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Explanation</h3>

                @if ($question->explanation)
                    <div class="rich-content mt-3 rounded-md bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                        {!! $question->explanation !!}
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-500">No explanation has been added for this question.</p>
                @endif

                @if ($question->explanation_image_path)
                    <div class="mt-4">
                        <img
                            src="{{ $question->explanationImageUrl() }}"
                            alt="Explanation image"
                            class="max-h-80 w-full rounded-md border border-gray-200 object-contain"
                        >
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
