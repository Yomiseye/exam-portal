<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Available Exams</h3>

                        <div class="mt-6 space-y-4">
                            @forelse ($exams as $exam)
                                <div class="rounded-md border border-gray-200 p-4">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ $exam->title }}</h4>

                                            @if ($exam->description)
                                                <p class="mt-1 text-sm text-gray-600">{{ $exam->description }}</p>
                                            @endif

                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1">{{ $exam->duration_minutes }} min</span>
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1">{{ $exam->total_questions }} questions</span>
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1">{{ $exam->pass_mark }}% pass mark</span>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-500">
                                                {{ $exam->categories->map(fn ($category) => $category->fullName())->join(', ') }}
                                            </div>

                                            <div class="mt-3">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{
                                                    match ($exam->student_status) {
                                                        'available' => 'bg-green-100 text-green-800',
                                                        'retake_granted' => 'bg-blue-100 text-blue-800',
                                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                                        'scheduled' => 'bg-blue-100 text-blue-800',
                                                        'closed' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    }
                                                }}">
                                                    {{
                                                        match ($exam->student_status) {
                                                            'available' => 'Available',
                                                            'retake_granted' => 'Retake granted',
                                                            'in_progress' => 'In progress',
                                                            'scheduled' => 'Scheduled',
                                                            'closed' => 'Closed',
                                                            default => 'Completed',
                                                        }
                                                    }}
                                                </span>
                                            </div>

                                            @if ($exam->assignment)
                                                <div class="mt-2 text-xs text-gray-500">
                                                    Available {{ $exam->assignment->available_from->format('M j, Y g:i A') }}
                                                    to
                                                    {{ $exam->assignment->available_until->format('M j, Y g:i A') }}
                                                </div>
                                            @endif
                                        </div>

                                        @if ($exam->student_status === 'in_progress')
                                            <a href="{{ route('student.attempts.show', $exam->latest_attempt) }}" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                                Continue
                                            </a>
                                        @elseif ($exam->student_status === 'completed')
                                            <a href="{{ route('student.attempts.result', $exam->latest_attempt) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                                View Result
                                            </a>
                                        @elseif (in_array($exam->student_status, ['scheduled', 'closed'], true))
                                            <span class="inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-400">
                                                Not Available
                                            </span>
                                        @else
                                            <a href="{{ route('student.exams.show', $exam) }}" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                                {{ $exam->student_status === 'retake_granted' ? 'Retake Exam' : 'View Exam' }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No active exams are available.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Recent Results</h3>

                        <div class="mt-6 space-y-3">
                            @forelse ($attempts as $attempt)
                                <a href="{{ $attempt->status === 'in_progress' ? route('student.attempts.show', $attempt) : route('student.attempts.result', $attempt) }}" class="block rounded-md border border-gray-200 p-4 hover:border-gray-300">
                                    <div class="font-medium text-gray-900">{{ $attempt->exam->title }}</div>
                                    <div class="mt-1 text-sm text-gray-500">
                                        @if ($attempt->status === 'in_progress')
                                            In progress
                                        @else
                                            {{ $attempt->score }} / {{ $attempt->total_questions }} · {{ $attempt->percentage }}%
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-gray-500">No attempts yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
