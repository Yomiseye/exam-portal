<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Students
            </h2>
            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                Register Student
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($students as $student)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="grid gap-6 lg:grid-cols-[1fr,1.4fr]">
                            <div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $student->name }}</h3>
                                        <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                    </div>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $student->examAssignments->count() }} assigned
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @forelse ($student->examAssignments->sortByDesc('available_from') as $assignment)
                                        <div class="rounded-md border border-gray-200 p-3">
                                            <div class="font-medium text-gray-900">{{ $assignment->exam->title }}</div>
                                            <div class="mt-1 text-sm text-gray-500">
                                                {{ $assignment->available_from->format('M j, Y g:i A') }}
                                                to
                                                {{ $assignment->available_until->format('M j, Y g:i A') }}
                                            </div>
                                            <div class="mt-2">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{
                                                    $assignment->isAvailable()
                                                        ? 'bg-green-100 text-green-800'
                                                        : ($assignment->available_from->isFuture() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')
                                                }}">
                                                    {{
                                                        $assignment->isAvailable()
                                                            ? 'Available now'
                                                            : ($assignment->available_from->isFuture() ? 'Scheduled' : 'Closed')
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">No exams assigned yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.students.assignments.store', $student) }}" class="grid gap-4 rounded-md bg-gray-50 p-4">
                                @csrf

                                <div>
                                    <x-input-label for="exam_id_{{ $student->id }}" value="Assign Exam" />
                                    <select id="exam_id_{{ $student->id }}" name="exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Choose exam</option>
                                        @foreach ($exams as $exam)
                                            <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <x-input-label for="available_from_{{ $student->id }}" value="Available From" />
                                        <x-text-input id="available_from_{{ $student->id }}" name="available_from" type="datetime-local" class="mt-1 block w-full" />
                                    </div>

                                    <div>
                                        <x-input-label for="available_until_{{ $student->id }}" value="Available Until" />
                                        <x-text-input id="available_until_{{ $student->id }}" name="available_until" type="datetime-local" class="mt-1 block w-full" />
                                    </div>
                                </div>

                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                    Save Assignment
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-10 text-center shadow-sm sm:rounded-lg">
                        <p class="text-sm text-gray-500">No students have been registered yet.</p>
                    </div>
                @endforelse
            </div>

            @if ($students->hasPages())
                <div class="bg-white px-6 py-4 shadow-sm sm:rounded-lg">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
