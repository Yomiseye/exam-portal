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

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="grid gap-6 lg:grid-cols-2">
                    @if ($attempt->status !== 'in_progress')
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

                            @if (! $unusedRetakePermission)
                                <form method="POST" action="{{ route('admin.results.retake', $attempt) }}" class="mt-4 grid gap-3">
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
                    @else
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Retake Permission</h3>
                            <p class="mt-1 text-sm text-gray-600">Retakes can be granted after the attempt is submitted.</p>
                        </div>
                    @endif

                    <div class="border-t border-gray-200 pt-6 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        <h3 class="text-lg font-medium text-gray-900">Attempt Management</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Delete this attempt and its saved answers from the result history.
                        </p>

                        <form method="POST" action="{{ route('admin.results.destroy', $attempt) }}" class="mt-4">
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-500"
                                onclick="return confirm('Delete this attempt and its saved answers? This cannot be undone.')"
                            >
                                Delete Attempt
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @foreach ($attempt->answers as $answer)
                    @php
                        $questionType = $answer->question->question_type;
                        $selectedOptionIds = collect($answer->selected_option_ids ?? [])->map(fn ($id) => (int) $id)->all();
                        $selectedOptions = $answer->question->options->whereIn('id', $selectedOptionIds);
                        $correctOptions = $answer->question->options->where('is_correct', true);
                        $matchingAnswer = $answer->matching_answer ?? [];
                    @endphp

                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Question {{ $loop->iteration }}</div>
                                <div class="rich-content mt-2 text-gray-900">{!! $answer->question->question_text !!}</div>
                            </div>

                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                            <div class="rounded-md bg-gray-50 p-3">
                                <div class="font-medium text-gray-500">Selected Answer</div>
                                <div class="mt-1 text-gray-900">
                                    @if ($questionType === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
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
                                            No answer selected
                                        @endforelse
                                    @elseif ($questionType === \App\Models\Question::TYPE_MATCHING)
                                        <div class="space-y-1">
                                            @foreach ($answer->question->options as $option)
                                                <div class="mb-3">
                                                    <span class="rich-content inline-block">{!! $option->option_text !!}</span>: {{ $matchingAnswer[$option->id] ?? 'No answer selected' }}
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
                                        @if ($answer->selectedOption)
                                            <div class="rich-content">{!! $answer->selectedOption->option_text !!}</div>
                                            @if ($answer->selectedOption->image_path)
                                                <img
                                                    src="{{ $answer->selectedOption->imageUrl() }}"
                                                    alt="Selected option image"
                                                    class="mt-2 max-h-36 rounded-md border border-gray-200 object-contain"
                                                >
                                            @endif
                                        @else
                                            No answer selected
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-md bg-gray-50 p-3">
                                <div class="font-medium text-gray-500">Correct Answer</div>
                                <div class="mt-1 text-gray-900">
                                    @if ($questionType === \App\Models\Question::TYPE_MATCHING)
                                        <div class="space-y-1">
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
                                        @foreach ($correctOptions as $correctOption)
                                            <div class="rich-content mb-3">
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
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($answer->question->explanation)
                            <div class="mt-3 rounded-md bg-blue-50 p-3 text-sm text-blue-800">
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
            </div>
        </div>
    </div>
</x-app-layout>
