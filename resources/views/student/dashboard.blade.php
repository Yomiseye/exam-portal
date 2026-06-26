<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Student Dashboard
            </h2>
            <p class="text-sm text-white/80">Track assigned exams, resume attempts, and review recent results.</p>
        </div>
    </x-slot>

    <div class="portal-page">
        <div class="portal-container">
            @if (session('status'))
                <div class="portal-alert portal-alert-success mb-6">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="portal-alert portal-alert-danger mb-6">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                $availableCount = $exams->whereIn('student_status', ['available', 'retake_granted'])->count();
                $activeCount = $exams->whereIn('student_status', ['in_progress', 'paused'])->count();
                $completedCount = $exams->where('student_status', 'completed')->count();
                $scheduledCount = $exams->where('student_status', 'scheduled')->count();
            @endphp

            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Available Now</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $availableCount }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">In Progress</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $activeCount }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Scheduled</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $scheduledCount }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Completed</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $completedCount }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <div class="portal-panel overflow-hidden">
                    <div class="p-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-950">Assigned Exams</h3>
                            <p class="mt-1 text-sm text-gray-500">Start what is available, or resume work already in progress.</p>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse ($exams as $exam)
                                <div class="rounded-md border border-gray-200 bg-white p-4 transition hover:border-teal-200 hover:bg-slate-50">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-semibold text-gray-950">{{ $exam->title }}</h4>
                                                <span class="portal-badge {{
                                                    match ($exam->student_status) {
                                                        'available' => 'portal-badge-success',
                                                        'retake_granted' => 'portal-badge-info',
                                                        'paused' => 'portal-badge-warning',
                                                        'in_progress' => 'portal-badge-warning',
                                                        'scheduled' => 'portal-badge-info',
                                                        'closed' => 'portal-badge-danger',
                                                        default => 'portal-badge-neutral',
                                                    }
                                                }}">
                                                    <x-icon
                                                        :name="match ($exam->student_status) {
                                                            'available', 'retake_granted', 'completed' => 'check-circle',
                                                            'paused', 'in_progress', 'scheduled' => 'clock',
                                                            'closed' => 'x-circle',
                                                            default => 'chart-bar',
                                                        }"
                                                        class="h-3 w-3"
                                                    />
                                                    {{
                                                        match ($exam->student_status) {
                                                            'available' => 'Available',
                                                            'retake_granted' => 'Retake granted',
                                                            'paused' => 'Paused',
                                                            'in_progress' => 'In progress',
                                                            'scheduled' => 'Scheduled',
                                                            'closed' => 'Closed',
                                                            default => 'Completed',
                                                        }
                                                    }}
                                                </span>
                                            </div>

                                            @if ($exam->description)
                                                <p class="mt-1 text-sm text-gray-600">{{ $exam->description }}</p>
                                            @endif

                                            <div class="mt-3 grid gap-2 text-xs text-gray-600 sm:grid-cols-3">
                                                <span class="rounded-md bg-gray-100 px-2.5 py-2">{{ $exam->duration_minutes }} min</span>
                                                <span class="rounded-md bg-gray-100 px-2.5 py-2">{{ $exam->total_questions }} questions</span>
                                                <span class="rounded-md bg-gray-100 px-2.5 py-2">{{ $exam->pass_mark }}% pass mark</span>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-500">
                                                {{ $exam->categories->map(fn ($category) => $category->fullName())->join(', ') }}
                                            </div>

                                            @if ($exam->assignment)
                                                <div class="mt-2 text-xs text-gray-500">
                                                    Available {{ $exam->assignment->available_from->format('M j, Y g:i A') }}
                                                    to
                                                    {{ $exam->assignment->available_until->format('M j, Y g:i A') }}
                                                </div>
                                            @endif
                                        </div>

                                        @if (in_array($exam->student_status, ['in_progress', 'paused'], true))
                                            <a href="{{ route('student.attempts.show', $exam->latest_attempt) }}" class="portal-button-primary shrink-0">
                                                <x-icon name="arrow-right" />
                                                {{ $exam->student_status === 'paused' ? 'Resume' : 'Continue' }}
                                            </a>
                                        @elseif ($exam->student_status === 'completed')
                                            <a href="{{ route('student.attempts.result', $exam->latest_attempt) }}" class="portal-button-secondary shrink-0">
                                                <x-icon name="chart-bar" />
                                                View Result
                                            </a>
                                        @elseif (in_array($exam->student_status, ['scheduled', 'closed'], true))
                                            <span class="portal-button-muted shrink-0">
                                                <x-icon name="x-circle" />
                                                Not Available
                                            </span>
                                        @else
                                            <a href="{{ route('student.exams.show', $exam) }}" class="portal-button-primary shrink-0">
                                                <x-icon name="clipboard-list" />
                                                {{ $exam->student_status === 'retake_granted' ? 'Retake Exam' : 'View Exam' }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <x-empty-state
                                    class="rounded-md border border-dashed border-gray-300 bg-white px-4 py-8"
                                    icon="clipboard-list"
                                    title="No active exams"
                                    message="Available exams will appear here when your admin assigns one."
                                />
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="portal-panel overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-950">Recent Activity</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest submitted or ongoing attempts.</p>

                        <div class="mt-6 space-y-3">
                            @forelse ($attempts as $attempt)
                                <a href="{{ $attempt->status === 'in_progress' ? route('student.attempts.show', $attempt) : route('student.attempts.result', $attempt) }}" class="block rounded-md border border-gray-200 p-4 transition hover:border-teal-200 hover:bg-slate-50">
                                    <div class="font-medium text-gray-900">{{ $attempt->exam->title }}</div>
                                    <div class="mt-1 text-sm text-gray-500">
                                        @if ($attempt->status === 'in_progress')
                                            {{ $attempt->isPaused() ? 'Paused' : 'In progress' }}
                                        @else
                                            {{ $attempt->score }} / {{ $attempt->total_questions }} &middot; {{ $attempt->percentage }}%
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <x-empty-state
                                    class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6"
                                    icon="chart-bar"
                                    title="No attempts yet"
                                    message="Your exam activity will appear here after you start or submit an exam."
                                />
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
