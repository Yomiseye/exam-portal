<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .secure-corrections {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
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

            <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Performance Analytics</h3>
                        <p class="mt-1 text-sm text-gray-500">Breakdown of your score by question classification.</p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-700">
                        {{ $attempt->score }} correct
                    </span>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-4">
                    @foreach ($performanceAnalytics as $section)
                        <div class="rounded-md border border-gray-200 p-4">
                            <h4 class="text-sm font-semibold text-gray-950">{{ $section['title'] }}</h4>

                            <div class="mt-4 space-y-3">
                                @forelse ($section['rows'] as $row)
                                    @php
                                        $statusClass = match ($row['status']) {
                                            'Strength' => 'bg-green-50 text-green-700',
                                            'Developing' => 'bg-amber-50 text-amber-800',
                                            default => 'bg-red-50 text-red-700',
                                        };
                                    @endphp

                                    <div>
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900">{{ $row['label'] }}</div>
                                                <div class="mt-0.5 text-xs text-gray-500">{{ $row['correct'] }} of {{ $row['total'] }} correct</div>
                                            </div>

                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-950">{{ $row['percentage'] }}%</div>
                                                <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                                                    {{ $row['status'] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-2 h-2 rounded-full bg-gray-100">
                                            <div
                                                class="h-2 rounded-full {{ $row['percentage'] >= 80 ? 'bg-green-500' : ($row['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                style="width: {{ $row['percentage'] }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No classified questions in this attempt.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($attempt->exam->show_corrections)
                <div
                    class="secure-corrections mt-6 space-y-4"
                    oncontextmenu="return false"
                    oncopy="return false"
                    oncut="return false"
                    onpaste="return false"
                    onselectstart="return false"
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
                        jumpTo(index) {
                            if (this.visibleIndexes().includes(index)) {
                                this.current = index;
                            }
                        },
                        blockShortcut(event) {
                            const key = event.key.toLowerCase();
                            const blockedWithModifier = ['a', 'c', 'p', 's', 'u', 'x'].includes(key);

                            if ((event.ctrlKey || event.metaKey) && blockedWithModifier) {
                                event.preventDefault();
                                return false;
                            }

                            if (event.key === 'PrintScreen') {
                                event.preventDefault();
                                return false;
                            }
                        },
                    }"
                    @keydown.window="blockShortcut($event)"
                >
                    <div class="rounded-md bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-950">Corrections Navigation</h3>
                                    <div class="mt-1 text-sm text-gray-600">
                                        Review <span x-text="visibleCount() ? visiblePosition() + 1 : 0"></span> of <span x-text="visibleCount()"></span>
                                    </div>
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

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Jump to question</div>
                                <div class="mt-2 grid grid-cols-5 gap-2 sm:grid-cols-8 md:grid-cols-10">
                                    @foreach ($attempt->answers as $navAnswer)
                                        <button
                                            type="button"
                                            class="relative inline-flex h-10 items-center justify-center rounded-md border text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-40"
                                            :class="[
                                                current === {{ $loop->index }} ? 'border-gray-900 bg-gray-900 text-white' : '{{ $navAnswer->is_correct ? 'border-green-200 bg-green-50 text-green-800 hover:bg-green-100' : 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' }}',
                                                ! visibleIndexes().includes({{ $loop->index }}) ? 'hidden' : ''
                                            ]"
                                            @click="jumpTo({{ $loop->index }})"
                                            title="Question {{ $loop->iteration }} - {{ $navAnswer->is_correct ? 'Correct' : 'Incorrect' }}"
                                        >
                                            {{ $loop->iteration }}
                                        </button>
                                    @endforeach
                                </div>

                                <div x-show="visibleCount() === 0" x-cloak class="mt-3 rounded-md border border-dashed border-gray-200 px-3 py-4 text-sm text-gray-500">
                                    No questions match this filter.
                                </div>
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
                                    <div class="rich-content mt-2 text-gray-900">{!! $answer->question->question_text !!}</div>

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
                                    <div class="rich-content mt-1">
                                        @forelse ($selectedOptions as $selectedOption)
                                            <div class="mb-3">
                                                <div>{!! $selectedOption->option_text !!}</div>
                                                @if ($selectedOption->image_path)
                                                    <img
                                                        src="{{ $selectedOption->imageUrl() }}"
                                                        alt="Selected option image"
                                                        class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                    >
                                                @endif
                                            </div>
                                        @empty
                                            No answer
                                        @endforelse
                                    </div>
                                @elseif (in_array($questionType, [\App\Models\Question::TYPE_MATCHING, \App\Models\Question::TYPE_DRAG_DROP], true))
                                    <div class="mt-2 space-y-1">
                                        @foreach ($answer->question->options as $option)
                                            <div class="mb-3">
                                                <span class="rich-content inline-block">{!! $option->option_text !!}</span>: {{ $matchingAnswer[$option->id] ?? 'No answer' }}
                                                @if ($option->image_path)
                                                    <img
                                                        src="{{ $option->imageUrl() }}"
                                                        alt="Option image"
                                                        class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                    >
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rich-content mt-1">
                                        @if ($answer->selectedOption)
                                            {!! $answer->selectedOption->option_text !!}
                                            @if ($answer->selectedOption->image_path)
                                                <img
                                                    src="{{ $answer->selectedOption->imageUrl() }}"
                                                    alt="Selected option image"
                                                    class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                >
                                            @endif
                                        @else
                                            No answer
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if (! $answer->is_correct)
                                <div class="mt-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-500">Correct answer</div>

                                    @if (in_array($questionType, [\App\Models\Question::TYPE_MATCHING, \App\Models\Question::TYPE_DRAG_DROP], true))
                                        <div class="mt-2 space-y-1">
                                            @foreach ($answer->question->options as $option)
                                                <div class="mb-3">
                                                    <span class="rich-content inline-block">{!! $option->option_text !!}</span>: {{ $option->match_text }}
                                                    @if ($option->image_path)
                                                        <img
                                                            src="{{ $option->imageUrl() }}"
                                                            alt="Correct option image"
                                                            class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                        >
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rich-content mt-1">
                                            @foreach ($correctOptions as $correctOption)
                                                <div class="mb-3">
                                                    <div>{!! $correctOption->option_text !!}</div>
                                                    @if ($correctOption->image_path)
                                                        <img
                                                            src="{{ $correctOption->imageUrl() }}"
                                                            alt="Correct option image"
                                                            class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                        >
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($answer->question->explanation)
                                <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-600">
                                    <div class="rich-content">{!! $answer->question->explanation !!}</div>
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
