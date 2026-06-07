@php
    $firstErrorIndex = $attempt->answers
        ->values()
        ->search(fn ($answer) => $errors->has('answers.'.$answer->question_id));

    $initialQuestionIndex = $firstErrorIndex === false ? 0 : $firstErrorIndex;
@endphp

<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .attempt-stage {
            display: grid;
            gap: 1rem;
        }

        .attempt-progress {
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .attempt-progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0f766e, #b45309);
            transition: width 180ms ease;
        }

        .question-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.1);
        }

        .question-option {
            cursor: pointer;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $attempt->exam->title }}
            </h2>
            <div
                x-data="{
                    expiresAt: new Date('{{ $attempt->expires_at->toIso8601String() }}').getTime(),
                    remaining: '',
                    submitted: false,
                    update() {
                        const diff = Math.max(0, this.expiresAt - Date.now());
                        const minutes = Math.floor(diff / 60000);
                        const seconds = Math.floor((diff % 60000) / 1000);
                        this.remaining = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                        if (diff <= 0 && ! this.submitted) {
                            this.submitted = true;
                            document.getElementById('attempt-form')?.submit();
                        }
                    },
                    init() {
                        this.update();
                        setInterval(() => this.update(), 1000);
                    },
                }"
                class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700"
            >
                Time left: <span x-text="remaining"></span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form
                x-data="{
                    submitted: false,
                    current: {{ $initialQuestionIndex }},
                    total: {{ $attempt->answers->count() }},
                    async saveAnswer(questionId, answer) {
                        const response = await fetch('{{ route('student.attempts.answers.save', $attempt) }}', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                question_id: questionId,
                                answer,
                            }),
                        });

                        if (response.status === 409) {
                            const data = await response.json();

                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        }
                    },
                    checkboxValues(questionId) {
                        return Array.from(document.querySelectorAll(`[data-answer-group='${questionId}']:checked`))
                            .map((input) => input.value);
                    },
                    matchingValues(questionId) {
                        return Array.from(document.querySelectorAll(`[data-matching-group='${questionId}']`))
                            .reduce((values, input) => {
                                values[input.dataset.optionId] = input.value;
                                return values;
                            }, {});
                    },
                    next() {
                        if (this.current < this.total - 1) {
                            this.current++;
                        }
                    },
                    previous() {
                        if (this.current > 0) {
                            this.current--;
                        }
                    },
                }"
                id="attempt-form"
                method="POST"
                action="{{ route('student.attempts.submit', $attempt) }}"
                class="attempt-stage"
                @submit="submitted = true"
            >
                @csrf

                <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-700">
                    This exam will submit automatically when the timer reaches zero.
                </div>

                <div class="rounded-md bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span>Question <span x-text="current + 1"></span> of <span x-text="total"></span></span>
                        <span x-text="Math.round(((current + 1) / total) * 100) + '%'"></span>
                    </div>
                    <div class="attempt-progress mt-3">
                        <span :style="'width: ' + (((current + 1) / total) * 100) + '%'"></span>
                    </div>
                </div>

                @foreach ($attempt->answers as $answer)
                    <div
                        x-show="current === {{ $loop->index }}"
                        x-cloak
                        class="question-card p-6"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm font-medium text-gray-500">
                                Question {{ $loop->iteration }} of {{ $attempt->answers->count() }}
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ $attempt->total_questions }} total questions
                            </div>
                        </div>

                        <div class="mt-2 text-gray-900">{{ $answer->question->question_text }}</div>

                        <div class="mt-5 space-y-3">
                            @if (in_array($answer->question->question_type, [\App\Models\Question::TYPE_SINGLE_CHOICE, \App\Models\Question::TYPE_TRUE_FALSE], true))
                                @foreach ($answer->question->options as $option)
                                    <label class="question-option flex items-start rounded-md border border-gray-200 p-3">
                                        <input
                                            type="radio"
                                            name="answers[{{ $answer->question_id }}]"
                                            value="{{ $option->id }}"
                                            @checked((string) old("answers.{$answer->question_id}", $answer->selected_option_id) === (string) $option->id)
                                            @change="saveAnswer({{ $answer->question_id }}, {{ $option->id }})"
                                            class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                        <span class="ms-3 text-sm text-gray-700">{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                            @elseif ($answer->question->question_type === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
                                @php
                                    $selectedOptionIds = collect(old("answers.{$answer->question_id}", $answer->selected_option_ids ?? []))
                                        ->map(fn ($id) => (string) $id)
                                        ->all();
                                @endphp

                                @foreach ($answer->question->options as $option)
                                    <label class="question-option flex items-start rounded-md border border-gray-200 p-3">
                                        <input
                                            type="checkbox"
                                            name="answers[{{ $answer->question_id }}][]"
                                            value="{{ $option->id }}"
                                            data-answer-group="{{ $answer->question_id }}"
                                            @checked(in_array((string) $option->id, $selectedOptionIds, true))
                                            @change="saveAnswer({{ $answer->question_id }}, checkboxValues({{ $answer->question_id }}))"
                                            class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                        <span class="ms-3 text-sm text-gray-700">{{ $option->option_text }}</span>
                                    </label>
                                @endforeach
                            @else
                                @php
                                    $matchingAnswer = old("answers.{$answer->question_id}", $answer->matching_answer ?? []);
                                    $matchChoices = $answer->question->options->pluck('match_text')->filter()->values();
                                @endphp

                                @foreach ($answer->question->options as $option)
                                    <div class="rounded-md border border-gray-200 p-3">
                                        <div class="text-sm font-medium text-gray-700">{{ $option->option_text }}</div>
                                        <select
                                            name="answers[{{ $answer->question_id }}][{{ $option->id }}]"
                                            data-matching-group="{{ $answer->question_id }}"
                                            data-option-id="{{ $option->id }}"
                                            @change="saveAnswer({{ $answer->question_id }}, matchingValues({{ $answer->question_id }}))"
                                            class="mt-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        >
                                            <option value="">Choose match</option>
                                            @foreach ($matchChoices as $matchChoice)
                                                <option value="{{ $matchChoice }}" @selected(($matchingAnswer[$option->id] ?? '') === $matchChoice)>
                                                    {{ $matchChoice }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <x-input-error class="mt-3" :messages="$errors->get('answers.'.$answer->question_id)" />
                    </div>
                @endforeach

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        x-show="current > 0"
                        @click="previous"
                    >
                        Previous
                    </button>

                    <div class="hidden text-sm text-gray-500 sm:block">
                        Question <span x-text="current + 1"></span> of <span x-text="total"></span>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                        x-show="current < total - 1"
                        @click="next"
                    >
                        Next
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                        :disabled="submitted"
                        x-show="current === total - 1"
                    >
                        <span x-show="! submitted">Submit Exam</span>
                        <span x-show="submitted">Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
