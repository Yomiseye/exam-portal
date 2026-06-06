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
                                                {{ $exam->categories->pluck('name')->join(', ') }}
                                            </div>
                                        </div>

                                        <a href="{{ route('student.exams.show', $exam) }}" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                            View Exam
                                        </a>
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
