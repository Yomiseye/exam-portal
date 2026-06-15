<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Result
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ $attempt->exam->title }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Submitted {{ $attempt->submitted_at->diffForHumans() }}</p>
                        </div>

                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-medium {{ $attempt->status === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($attempt->status) }}
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Score</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $attempt->score }} / {{ $attempt->total_questions }}</div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Percentage</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $attempt->percentage }}%</div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Pass Mark</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $attempt->exam->pass_mark }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($attempt->exam->show_corrections)
                <div
                    class="mt-6 space-y-4"
                    x-data="{
                        current: 0,
                        filter: 'all',
                        answers: @js($attempt->answers->values()->map(fn ($answer) => ['correct' => (bool) $answer->is_correct])->all()),
                        visibleIndexes() {
                            return this.answers
                                .map((answer, index) => ({ answer, index }))
                                .filter(({ answer }) => this.filter === 'all' || (this.filter === 'incorrect' ? ! answer.correct : answer.correct))
                                .map(({ index }) => index);
                        },
                        visibleCount() {
                            return this.visibleIndexes().length;
                        },
                        visiblePosition() {
                            const position = this.visibleIndexes().indexOf(this.current);
                            return position === -1 ? 0 : position;
                        },
                        setFilter(value) {
                            this.filter = value;
                            this.current = this.visibleIndexes()[0] ?? 0;
                        },
                        previous() {
                            const indexes = this.visibleIndexes();
                            const position = indexes.indexOf(this.current);

                            if (position > 0) {
                                this.current = indexes[position - 1];
                            }
                        },
                        next() {
                            const indexes = this.visibleIndexes();
                            const position = indexes.indexOf(this.current);

                            if (position < indexes.length - 1) {
                                this.current = indexes[position + 1];
                            }
                        },
                    }"
                >
                    <div class="rounded-md bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-gray-600">
                                Review <span x-text="visibleCount() ? visiblePosition() + 1 : 0"></span> of <span x-text="visibleCount()"></span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-md border px-3 py-2 text-xs font-semibold uppercase tracking-widest"
                                    :class="filter === 'all' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                    @click="setFilter('all')"
                                >
                                    All
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border px-3 py-2 text-xs font-semibold uppercase tracking-widest"
                                    :class="filter === 'incorrect' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                    @click="setFilter('incorrect')"
                                >
                                    Incorrect
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border px-3 py-2 text-xs font-semibold uppercase tracking-widest"
                                    :class="filter === 'correct' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                    @click="setFilter('correct')"
                                >
                                    Correct
                                </button>
                            </div>
                        </div>
                    </div>

                    @foreach ($attempt->answers as $answer)
                        @php
                            $questionType = $answer->question->question_type;
                            $selectedOptionIds = collect($answer->selected_option_ids ?? [])->map(fn ($id) => (int) $id)->all();
                            $selectedOptions = $answer->question->options->whereIn('id', $selectedOptionIds);
                            $correctOptions = $answer->question->options->where('is_correct', true);
                            $matchingAnswer = $answer->matching_answer ?? [];
                        @endphp

                        <div
                            class="bg-white p-6 shadow-sm sm:rounded-lg"
                            x-show="current === {{ $loop->index }} && visibleIndexes().includes({{ $loop->index }})"
                            x-cloak
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Question {{ $loop->iteration }}</div>
                                    <div class="mt-2 text-gray-900">{{ $answer->question->question_text }}</div>

                                    @if ($answer->question->image_path)
                                        <div class="mt-4">
                                            <img
                                                src="{{ $answer->question->imageUrl() }}"
                                                alt="Question image"
                                                class="max-h-80 w-full rounded-md border border-gray-200 object-contain"
                                            >
                                        </div>
                                    @endif
                                </div>

                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                                </span>
                            </div>

                            <div class="mt-4 text-sm text-gray-700">
                                <div class="font-medium text-gray-500">Your answer</div>

                                @if ($questionType === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
                                    <div class="mt-1">{{ $selectedOptions->pluck('option_text')->implode(', ') ?: 'No answer' }}</div>
                                @elseif ($questionType === \App\Models\Question::TYPE_MATCHING)
                                    <div class="mt-2 space-y-1">
                                        @foreach ($answer->question->options as $option)
                                            <div>{{ $option->option_text }}: {{ $matchingAnswer[$option->id] ?? 'No answer' }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-1">{{ $answer->selectedOption?->option_text ?? 'No answer' }}</div>
                                @endif
                            </div>

                            @if (! $answer->is_correct)
                                <div class="mt-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-500">Correct answer</div>

                                    @if ($questionType === \App\Models\Question::TYPE_MATCHING)
                                        <div class="mt-2 space-y-1">
                                            @foreach ($answer->question->options as $option)
                                                <div>{{ $option->option_text }}: {{ $option->match_text }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="mt-1">{{ $correctOptions->pluck('option_text')->implode(', ') }}</div>
                                    @endif
                                </div>
                            @endif

                            @if ($answer->question->explanation)
                                <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-600">
                                    {{ $answer->question->explanation }}
                                </div>
                            @endif

                            @if ($answer->question->explanation_image_path)
                                <div class="mt-3">
                                    <img
                                        src="{{ $answer->question->explanationImageUrl() }}"
                                        alt="Explanation image"
                                        class="max-h-80 w-full rounded-md border border-gray-200 object-contain"
                                    >
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="visiblePosition() === 0"
                            @click="previous"
                        >
                            Previous
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                            :disabled="visiblePosition() >= visibleCount() - 1"
                            @click="next"
                        >
                            Next
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
