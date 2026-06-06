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

                    <div class="mt-6">
                        <div class="text-sm font-medium text-gray-700">Categories</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($exam->categories as $category)
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="mt-8">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                            Start Exam
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
