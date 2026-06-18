@php
    $firstErrorIndex = $attempt->answers
        ->values()
        ->search(fn ($answer) => $errors->has('answers.'.$answer->question_id));

    $savedQuestionIndex = min(
        max((int) ($attempt->current_question_index ?? 0), 0),
        max($attempt->answers->count() - 1, 0),
    );
    $initialQuestionIndex = $firstErrorIndex === false ? $savedQuestionIndex : $firstErrorIndex;
    $answerStates = $attempt->answers->values()->mapWithKeys(function ($answer, $index) {
        return [
            $index => filled($answer->selected_option_id)
                || filled($answer->selected_option_ids)
                || filled($answer->matching_answer),
        ];
    });
@endphp

<x-app-layout>
    <style>
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

        .attempt-shell {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .attempt-shell {
                grid-template-columns: minmax(0, 1fr) 18rem;
                align-items: start;
            }
        }

        .attempt-topbar {
            position: sticky;
            top: 0.75rem;
            z-index: 20;
        }

        .question-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.1);
        }

        .question-option {
            cursor: pointer;
            transition: border-color 140ms ease, background-color 140ms ease, box-shadow 140ms ease;
        }

        .question-option:hover {
            border-color: rgba(15, 118, 110, 0.28);
            background: #f8fafc;
        }

        .exam-secure {
            -webkit-user-select: none;
            user-select: none;
        }

        .exam-secure input,
        .exam-secure select,
        .exam-secure textarea {
            -webkit-user-select: auto;
            user-select: auto;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $attempt->exam->title }}
            </h2>
            <div class="text-sm text-white/85">
                {{ $attempt->total_questions }} questions
            </div>
        </div>
    </x-slot>

    <div class="portal-page">
        <div class="portal-container">
            <form
                x-data="{
                    submitted: false,
                    submitConfirm: false,
                    current: {{ $initialQuestionIndex }},
                    total: {{ $attempt->answers->count() }},
                    answered: @js($answerStates),
                    flagged: {},
                    saving: false,
                    savedAt: '',
                    expiresAt: new Date('{{ $attempt->expires_at->toIso8601String() }}').getTime(),
                    remaining: '',
                    remainingSeconds: 0,
                    securityWarnings: 0,
                    lastSecurityWarning: '',
                    fullscreenSupported: false,
                    fullscreenActive: false,
                    updateTimer() {
                        const diff = Math.max(0, this.expiresAt - Date.now());
                        this.remainingSeconds = Math.floor(diff / 1000);
                        const minutes = Math.floor(diff / 60000);
                        const seconds = Math.floor((diff % 60000) / 1000);
                        this.remaining = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                        if (diff <= 0 && ! this.submitted) {
                            this.submitted = true;
                            document.getElementById('attempt-form')?.submit();
                        }
                    },
                    init() {
                        this.updateTimer();
                        setInterval(() => this.updateTimer(), 1000);
                        this.initSecurityControls();
                    },
                    initSecurityControls() {
                        this.fullscreenSupported = Boolean(document.documentElement.requestFullscreen);
                        this.fullscreenActive = Boolean(document.fullscreenElement);

                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden && ! this.submitted) {
                                this.addSecurityWarning('Tab or window switch detected.');
                            }
                        });

                        window.addEventListener('blur', () => {
                            if (! this.submitted) {
                                this.addSecurityWarning('Exam window lost focus.');
                            }
                        });

                        document.addEventListener('fullscreenchange', () => {
                            this.fullscreenActive = Boolean(document.fullscreenElement);

                            if (! this.fullscreenActive && ! this.submitted) {
                                this.addSecurityWarning('Fullscreen mode exited.');
                            }
                        });
                    },
                    addSecurityWarning(message) {
                        this.securityWarnings++;
                        this.lastSecurityWarning = message;
                    },
                    blockSecurityAction(message) {
                        this.addSecurityWarning(message);
                    },
                    blockExamShortcut(event) {
                        const key = event.key.toLowerCase();
                        const blockedWithModifier = ['a', 'c', 'p', 's', 'u', 'v', 'x'].includes(key) && (event.ctrlKey || event.metaKey);
                        const blockedFunctionKey = ['printscreen'].includes(key);

                        if (blockedWithModifier || blockedFunctionKey) {
                            event.preventDefault();
                            this.blockSecurityAction('Restricted shortcut blocked.');
                        }
                    },
                    async requestSecureFullscreen() {
                        if (! document.documentElement.requestFullscreen) {
                            this.addSecurityWarning('Fullscreen is not supported by this browser.');
                            return;
                        }

                        try {
                            await document.documentElement.requestFullscreen();
                            this.fullscreenActive = true;
                        } catch (error) {
                            this.addSecurityWarning('Fullscreen request was not completed.');
                        }
                    },
                    answeredCount() {
                        return Object.values(this.answered).filter(Boolean).length;
                    },
                    unansweredCount() {
                        return this.total - this.answeredCount();
                    },
                    async saveAnswer(questionId, answer) {
                        this.saving = true;
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

                        this.answered[this.current] = Array.isArray(answer)
                            ? answer.length > 0
                            : (answer && typeof answer === 'object' ? Object.values(answer).some(Boolean) : Boolean(answer));
                        this.saving = false;
                        this.savedAt = 'Saved';
                    },
                    async saveProgress() {
                        const response = await fetch('{{ route('student.attempts.progress.save', $attempt) }}', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                current_question_index: this.current,
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
                            this.saveProgress();
                        }
                    },
                    previous() {
                        if (this.current > 0) {
                            this.current--;
                            this.saveProgress();
                        }
                    },
                    goTo(index) {
                        this.current = Math.min(Math.max(index, 0), this.total - 1);
                        this.saveProgress();
                    },
                    toggleFlag(index) {
                        this.flagged[index] = ! this.flagged[index];
                    },
                    confirmSubmit() {
                        this.submitConfirm = true;
                    },
                }"
                id="attempt-form"
                method="POST"
                action="{{ route('student.attempts.submit', $attempt) }}"
                class="space-y-4"
                :class="{ 'exam-secure': true }"
                @submit="submitted = true"
                @contextmenu.prevent="blockSecurityAction('Right-click blocked during exam.')"
                @copy.prevent="blockSecurityAction('Copy blocked during exam.')"
                @cut.prevent="blockSecurityAction('Cut blocked during exam.')"
                @paste.prevent="blockSecurityAction('Paste blocked during exam.')"
                @keydown.window="blockExamShortcut($event)"
            >
                @csrf

                <div class="attempt-topbar portal-panel p-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="grid grid-cols-3 gap-2 text-center sm:flex sm:text-left">
                            <div class="rounded-md bg-slate-50 px-3 py-2">
                                <div class="text-xs font-medium text-slate-500">Progress</div>
                                <div class="text-sm font-semibold text-slate-900"><span x-text="answeredCount()"></span>/<span x-text="total"></span></div>
                            </div>
                            <div class="rounded-md px-3 py-2" :class="remainingSeconds <= 300 ? 'bg-red-50' : 'bg-emerald-50'">
                                <div class="text-xs font-medium" :class="remainingSeconds <= 300 ? 'text-red-600' : 'text-emerald-700'">Time Left</div>
                                <div class="text-sm font-semibold" :class="remainingSeconds <= 300 ? 'text-red-700' : 'text-emerald-900'" x-text="remaining"></div>
                            </div>
                            <div class="rounded-md bg-slate-50 px-3 py-2">
                                <div class="text-xs font-medium text-slate-500">Status</div>
                                <div class="text-sm font-semibold text-slate-900">
                                    <span x-show="saving">Saving...</span>
                                    <span x-show="! saving" x-text="savedAt || 'Ready'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($attempt->exam->allow_pause)
                                <button
                                    type="submit"
                                    form="pause-form"
                                    class="portal-button-secondary"
                                    onclick="return confirm('Pause this exam and return to your dashboard?')"
                                >
                                    Pause
                                </button>
                            @endif

                            <button
                                type="button"
                                class="portal-button-danger"
                                @click="confirmSubmit"
                            >
                                Submit Exam
                            </button>
                        </div>
                    </div>
                </div>

                <div class="portal-alert portal-alert-info">
                    This exam will submit automatically when the timer reaches zero.
                    @if ($attempt->exam->allow_pause)
                        You can pause and resume later from your dashboard.
                    @endif
                </div>

                <div class="portal-panel p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Exam Security</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Copying, pasting, right-clicking, and switching away from this exam are monitored during the attempt.
                            </p>
                            <p class="mt-2 text-sm font-medium" :class="securityWarnings > 0 ? 'text-amber-700' : 'text-emerald-700'">
                                <span x-text="securityWarnings"></span> warning(s)
                                <span x-show="lastSecurityWarning">- <span x-text="lastSecurityWarning"></span></span>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="portal-button-secondary"
                            x-show="fullscreenSupported && ! fullscreenActive"
                            @click="requestSecureFullscreen"
                        >
                            Enter Fullscreen
                        </button>

                        <span
                            x-show="fullscreenActive"
                            class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700"
                        >
                            Fullscreen Active
                        </span>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="portal-alert portal-alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="attempt-shell">
                    <section class="space-y-4">
                        <div class="portal-panel p-4">
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
                                class="question-card p-5 sm:p-6"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-500">
                                            Question {{ $loop->iteration }} of {{ $attempt->answers->count() }}
                                        </div>
                                        <div class="mt-1 text-xs font-medium uppercase text-gray-400">
                                            {{ $answer->question->typeLabel() }}
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="portal-button-secondary min-h-0 px-3 py-2 text-xs"
                                        :class="flagged[{{ $loop->index }}] ? 'border-amber-300 bg-amber-50 text-amber-800' : ''"
                                        @click="toggleFlag({{ $loop->index }})"
                                    >
                                        <span x-text="flagged[{{ $loop->index }}] ? 'Flagged' : 'Flag for Review'"></span>
                                    </button>
                                </div>

                                <div class="rich-content mt-5 text-base leading-7 text-gray-950">{!! $answer->question->question_text !!}</div>

                                @if ($answer->question->image_path)
                                    <div class="mt-4">
                                        <img
                                            src="{{ $answer->question->imageUrl() }}"
                                            alt="Question image"
                                            class="max-h-96 w-full rounded-md border border-gray-200 object-contain"
                                        >
                                    </div>
                                @endif

                                <div class="mt-6 space-y-3">
                                    @if (in_array($answer->question->question_type, [\App\Models\Question::TYPE_SINGLE_CHOICE, \App\Models\Question::TYPE_TRUE_FALSE], true))
                                        @foreach ($answer->question->options as $option)
                                            <label class="question-option flex items-start rounded-md border border-gray-200 p-4">
                                                <input
                                                    type="radio"
                                                    name="answers[{{ $answer->question_id }}]"
                                                    value="{{ $option->id }}"
                                                    @checked((string) old("answers.{$answer->question_id}", $answer->selected_option_id) === (string) $option->id)
                                                    @change="saveAnswer({{ $answer->question_id }}, {{ $option->id }})"
                                                    class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                >
                                                <span class="ms-3 block">
                                                    <span class="rich-content text-sm leading-6 text-gray-800">{!! $option->option_text !!}</span>
                                                    @if ($option->image_path)
                                                        <img
                                                            src="{{ $option->imageUrl() }}"
                                                            alt="Option image"
                                                            class="mt-3 max-h-44 rounded-md border border-gray-200 object-contain"
                                                        >
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    @elseif ($answer->question->question_type === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
                                        @php
                                            $selectedOptionIds = collect(old("answers.{$answer->question_id}", $answer->selected_option_ids ?? []))
                                                ->map(fn ($id) => (string) $id)
                                                ->all();
                                        @endphp

                                        @foreach ($answer->question->options as $option)
                                            <label class="question-option flex items-start rounded-md border border-gray-200 p-4">
                                                <input
                                                    type="checkbox"
                                                    name="answers[{{ $answer->question_id }}][]"
                                                    value="{{ $option->id }}"
                                                    data-answer-group="{{ $answer->question_id }}"
                                                    @checked(in_array((string) $option->id, $selectedOptionIds, true))
                                                    @change="saveAnswer({{ $answer->question_id }}, checkboxValues({{ $answer->question_id }}))"
                                                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                >
                                                <span class="ms-3 block">
                                                    <span class="rich-content text-sm leading-6 text-gray-800">{!! $option->option_text !!}</span>
                                                    @if ($option->image_path)
                                                        <img
                                                            src="{{ $option->imageUrl() }}"
                                                            alt="Option image"
                                                            class="mt-3 max-h-44 rounded-md border border-gray-200 object-contain"
                                                        >
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    @else
                                        @php
                                            $matchingAnswer = old("answers.{$answer->question_id}", $answer->matching_answer ?? []);
                                            $matchChoices = $answer->question->options->pluck('match_text')->filter()->values();
                                        @endphp

                                        @foreach ($answer->question->options as $option)
                                            <div class="rounded-md border border-gray-200 p-4">
                                                <div class="rich-content text-sm font-medium text-gray-700">{!! $option->option_text !!}</div>
                                                @if ($option->image_path)
                                                    <img
                                                        src="{{ $option->imageUrl() }}"
                                                        alt="Option image"
                                                        class="mt-3 max-h-44 rounded-md border border-gray-200 object-contain"
                                                    >
                                                @endif
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

                        <div class="portal-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                class="portal-button-secondary"
                                :disabled="current === 0"
                                :class="current === 0 ? 'opacity-40' : ''"
                                @click="previous"
                            >
                                Previous
                            </button>

                            <div class="text-sm text-gray-500">
                                <span x-text="unansweredCount()"></span> unanswered
                            </div>

                            <button
                                type="button"
                                class="portal-button-primary"
                                x-show="current < total - 1"
                                @click="next"
                            >
                                Next Question
                            </button>

                            <button
                                type="button"
                                class="portal-button-danger"
                                x-show="current === total - 1"
                                @click="confirmSubmit"
                            >
                                Finish Exam
                            </button>
                        </div>
                    </section>

                    <aside class="portal-panel p-4 lg:sticky lg:top-28">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Question Map</h3>
                            <span class="text-xs text-gray-500"><span x-text="answeredCount()"></span> answered</span>
                        </div>

                        <div class="mt-4 grid grid-cols-5 gap-2 lg:grid-cols-4" aria-label="Question navigation">
                            @foreach ($attempt->answers as $navAnswer)
                                <button
                                    type="button"
                                    class="relative flex h-10 w-10 items-center justify-center rounded-md border text-sm font-semibold"
                                    :class="current === {{ $loop->index }}
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : (answered[{{ $loop->index }}] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50')"
                                    @click="goTo({{ $loop->index }})"
                                    aria-label="Go to question {{ $loop->iteration }}"
                                >
                                    {{ $loop->iteration }}
                                    <span
                                        x-show="flagged[{{ $loop->index }}]"
                                        class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-amber-500"
                                    ></span>
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-5 space-y-2 text-xs text-gray-600">
                            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-slate-900"></span> Current</div>
                            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-emerald-100 ring-1 ring-emerald-200"></span> Answered</div>
                            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-white ring-1 ring-slate-200"></span> Unanswered</div>
                            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-amber-500"></span> Flagged</div>
                        </div>
                    </aside>
                </div>

                <div
                    x-show="submitConfirm"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
                >
                    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl">
                        <h3 class="text-lg font-semibold text-gray-950">Submit this exam?</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            You have <span class="font-semibold text-gray-900" x-text="unansweredCount()"></span> unanswered question(s).
                            Once submitted, you cannot edit your answers.
                        </p>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" class="portal-button-secondary" @click="submitConfirm = false">
                                Keep Reviewing
                            </button>
                            <button type="submit" class="portal-button-danger" :disabled="submitted">
                                <span x-show="! submitted">Submit Now</span>
                                <span x-show="submitted">Submitting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            @if ($attempt->exam->allow_pause)
                <form id="pause-form" method="POST" action="{{ route('student.attempts.pause', $attempt) }}" class="hidden">
                    @csrf
                    @method('PATCH')
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
