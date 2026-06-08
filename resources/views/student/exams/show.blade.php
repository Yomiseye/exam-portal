<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $exam->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->has('exam'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first('exam') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($activeAttempt)
                        <div class="mb-6 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                            You already have this exam in progress. Continue your attempt before starting anything new.
                        </div>
                    @elseif (! $assignment->isAvailable())
                        <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                            This exam is assigned to you, but it is only available from
                            {{ $assignment->available_from->format('M j, Y g:i A') }}
                            to
                            {{ $assignment->available_until->format('M j, Y g:i A') }}.
                        </div>
                    @elseif ($latestCompletedAttempt && $unusedRetakePermission)
                        <div class="mb-6 rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                            A retake has been granted for this exam. Starting now will use that retake permission.
                        </div>
                    @elseif ($latestCompletedAttempt)
                        <div class="mb-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                            You have already completed this exam. You can retake it only after an admin grants permission.
                        </div>
                    @endif

                    @if ($exam->description)
                        <p class="text-gray-700">{{ $exam->description }}</p>
                    @endif

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Duration</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ $exam->duration_minutes }} min</div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Questions</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ $exam->total_questions }}</div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-sm text-gray-500">Pass Mark</div>
                            <div class="mt-1 text-xl font-semibold text-gray-900">{{ $exam->pass_mark }}%</div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-md bg-gray-50 p-4">
                        <div class="text-sm font-medium text-gray-700">Availability</div>
                        <div class="mt-1 text-sm text-gray-600">
                            {{ $assignment->available_from->format('M j, Y g:i A') }}
                            to
                            {{ $assignment->available_until->format('M j, Y g:i A') }}
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm font-medium text-gray-700">Categories</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($exam->categories as $category)
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $category->fullName() }}</span>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="mt-8">
                        @csrf
                        @if ($activeAttempt)
                            <a href="{{ route('student.attempts.show', $activeAttempt) }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                Continue Attempt
                            </a>
                        @elseif (! $assignment->isAvailable())
                            <button type="button" disabled class="inline-flex items-center rounded-md bg-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">
                                Not Available
                            </button>
                        @elseif ($latestCompletedAttempt && ! $unusedRetakePermission)
                            <a href="{{ route('student.attempts.result', $latestCompletedAttempt) }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                                View Result
                            </a>
                        @else
                            <button type="submit" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                {{ $unusedRetakePermission ? 'Start Retake' : 'Start Exam' }}
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
