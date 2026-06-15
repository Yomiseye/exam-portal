<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $exam->title }}
            </h2>
            <p class="text-sm text-white/80">Review the instructions before starting your timed attempt.</p>
        </div>
    </x-slot>

    <div class="portal-page">
        <div class="portal-container max-w-5xl">
            @if ($errors->has('exam'))
                <div class="portal-alert portal-alert-danger mb-6">
                    {{ $errors->first('exam') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1fr,22rem]">
                <section class="portal-panel overflow-hidden">
                    <div class="border-b border-gray-100 p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-2xl font-semibold text-gray-950">{{ $exam->title }}</h3>
                                @if ($exam->description)
                                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $exam->description }}</p>
                                @endif
                            </div>

                            @if ($activeAttempt)
                                <span class="portal-badge portal-badge-warning">In progress</span>
                            @elseif (! $assignment->isAvailable())
                                <span class="portal-badge portal-badge-danger">Not available</span>
                            @elseif ($latestCompletedAttempt && $unusedRetakePermission)
                                <span class="portal-badge portal-badge-info">Retake granted</span>
                            @elseif ($latestCompletedAttempt)
                                <span class="portal-badge portal-badge-neutral">Completed</span>
                            @else
                                <span class="portal-badge portal-badge-success">Ready</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($activeAttempt)
                            <div class="portal-alert portal-alert-warning mb-6">
                                You already have this exam in progress. Continue your attempt before starting anything new.
                            </div>
                        @elseif (! $assignment->isAvailable())
                            <div class="portal-alert portal-alert-danger mb-6">
                                This exam is assigned to you, but it is only available from
                                {{ $assignment->available_from->format('M j, Y g:i A') }}
                                to
                                {{ $assignment->available_until->format('M j, Y g:i A') }}.
                            </div>
                        @elseif ($latestCompletedAttempt && $unusedRetakePermission)
                            <div class="portal-alert portal-alert-info mb-6">
                                A retake has been granted for this exam. Starting now will use that retake permission.
                            </div>
                        @elseif ($latestCompletedAttempt)
                            <div class="portal-alert portal-alert-info mb-6">
                                You have already completed this exam. You can retake it only after an admin grants permission.
                            </div>
                        @endif

                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="portal-kpi">
                                <div class="text-sm text-gray-500">Duration</div>
                                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $exam->duration_minutes }} min</div>
                            </div>

                            <div class="portal-kpi">
                                <div class="text-sm text-gray-500">Questions</div>
                                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $exam->total_questions }}</div>
                            </div>

                            <div class="portal-kpi">
                                <div class="text-sm text-gray-500">Pass Mark</div>
                                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $exam->pass_mark }}%</div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div class="portal-panel-muted p-4">
                                <div class="text-sm font-semibold text-gray-800">Availability</div>
                                <div class="mt-2 text-sm leading-6 text-gray-600">
                                    {{ $assignment->available_from->format('M j, Y g:i A') }}
                                    to
                                    {{ $assignment->available_until->format('M j, Y g:i A') }}
                                </div>
                            </div>

                            <div class="portal-panel-muted p-4">
                                <div class="text-sm font-semibold text-gray-800">Categories</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($exam->categories as $category)
                                        <span class="portal-badge portal-badge-neutral">{{ $category->fullName() }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-sm font-semibold text-gray-950">Before you start</h4>
                            <div class="mt-3 grid gap-3 text-sm text-gray-700">
                                <div class="rounded-md border border-gray-200 bg-white p-3">The timer starts immediately after you begin.</div>
                                <div class="rounded-md border border-gray-200 bg-white p-3">The exam auto-submits when time runs out.</div>
                                <div class="rounded-md border border-gray-200 bg-white p-3">
                                    {{ $exam->allow_pause ? 'Pause and resume is allowed for this exam.' : 'Pause and resume is disabled for this exam.' }}
                                </div>
                                <div class="rounded-md border border-gray-200 bg-white p-3">Retakes require admin permission unless one has already been granted.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="portal-panel p-6 lg:sticky lg:top-24">
                    <h3 class="text-lg font-semibold text-gray-950">Ready to proceed?</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        Make sure you have enough uninterrupted time and a stable connection before starting.
                    </p>

                    <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="mt-6">
                        @csrf
                        @if ($activeAttempt)
                            <a href="{{ route('student.attempts.show', $activeAttempt) }}" class="portal-button-primary w-full">
                                Continue Attempt
                            </a>
                        @elseif (! $assignment->isAvailable())
                            <button type="button" disabled class="portal-button-muted w-full">
                                Not Available
                            </button>
                        @elseif ($latestCompletedAttempt && ! $unusedRetakePermission)
                            <a href="{{ route('student.attempts.result', $latestCompletedAttempt) }}" class="portal-button-secondary w-full">
                                View Result
                            </a>
                        @else
                            <button type="submit" class="portal-button-primary w-full" onclick="return confirm('Start this timed exam now?')">
                                {{ $unusedRetakePermission ? 'Start Retake' : 'Start Exam' }}
                            </button>
                        @endif
                    </form>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
