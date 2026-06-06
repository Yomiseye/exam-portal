<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Attempt Details
            </h2>
            <a href="{{ route('admin.results.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                Back to Results
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Student</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $attempt->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $attempt->user->email }}</div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-gray-500">Exam</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">{{ $attempt->exam->title }}</div>
                        <div class="text-sm text-gray-500">Pass mark: {{ $attempt->exam->pass_mark }}%</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="text-sm text-gray-500">Score</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $attempt->score }} / {{ $attempt->total_questions }}</div>
                    </div>

                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="text-sm text-gray-500">Percentage</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $attempt->percentage }}%</div>
                    </div>

                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="mt-1 text-2xl font-semibold capitalize text-gray-900">{{ str_replace('_', ' ', $attempt->status) }}</div>
                    </div>

                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="text-sm text-gray-500">Submitted</div>
                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</div>
                    </div>
                </div>
            </div>

            @php
                $unusedRetakePermission = $attempt->user->retakePermissions
                    ->first(fn ($permission) => $permission->exam_id === $attempt->exam_id && $permission->used_at === null);
            @endphp

            @if ($attempt->status !== 'in_progress')
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Retake Permission</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Grant this student one more attempt for {{ $attempt->exam->title }}.
                            </p>

                            @if ($unusedRetakePermission)
                                <p class="mt-3 text-sm text-green-700">
                                    An unused retake was granted by {{ $unusedRetakePermission->grantedBy?->name ?? 'an admin' }}
                                    on {{ $unusedRetakePermission->created_at->format('M j, Y g:i A') }}.
                                </p>
                            @endif
                        </div>

                        @if (! $unusedRetakePermission)
                            <form method="POST" action="{{ route('admin.results.retake', $attempt) }}" class="grid gap-3 md:min-w-80">
                                @csrf
                                <textarea
                                    name="reason"
                                    rows="3"
                                    placeholder="Reason, e.g. network interruption"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >{{ old('reason') }}</textarea>
                                <x-input-error :messages="$errors->get('reason')" />

                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                    Grant Retake
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($attempt->answers as $answer)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Question {{ $loop->iteration }}</div>
                                <div class="mt-2 text-gray-900">{{ $answer->question->question_text }}</div>
                            </div>

                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                            <div class="rounded-md bg-gray-50 p-3">
                                <div class="font-medium text-gray-500">Selected Answer</div>
                                <div class="mt-1 text-gray-900">{{ $answer->selectedOption?->option_text ?? 'No answer selected' }}</div>
                            </div>

                            <div class="rounded-md bg-gray-50 p-3">
                                <div class="font-medium text-gray-500">Correct Answer</div>
                                <div class="mt-1 text-gray-900">{{ $answer->question->options->firstWhere('is_correct', true)?->option_text }}</div>
                            </div>
                        </div>

                        @if ($answer->question->explanation)
                            <div class="mt-3 rounded-md bg-blue-50 p-3 text-sm text-blue-800">
                                {{ $answer->question->explanation }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
