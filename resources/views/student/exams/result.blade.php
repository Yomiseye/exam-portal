<x-app-layout>
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

            @if ($attempt->exam->show_corrections)
                <div class="mt-6 space-y-4">
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

                            <div class="mt-4 text-sm text-gray-700">
                                Your answer: {{ $answer->selectedOption?->option_text ?? 'No answer' }}
                            </div>

                            @if (! $answer->is_correct)
                                <div class="mt-2 text-sm text-gray-700">
                                    Correct answer: {{ $answer->question->options->firstWhere('is_correct', true)?->option_text }}
                                </div>
                            @endif

                            @if ($answer->question->explanation)
                                <div class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-600">
                                    {{ $answer->question->explanation }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
