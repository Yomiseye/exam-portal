<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Results
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('admin.results.index') }}" class="mb-6 grid gap-4 rounded-md bg-white p-4 shadow-sm md:grid-cols-[1fr,1fr,auto]">
                <div>
                    <x-input-label for="exam_id" value="Exam" />
                    <select id="exam_id" name="exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All exams</option>
                        @foreach ($exams as $exam)
                            <option value="{{ $exam->id }}" @selected((string) request('exam_id') === (string) $exam->id)>
                                {{ $exam->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All statuses</option>
                        @foreach (['in_progress' => 'In Progress', 'passed' => 'Passed', 'failed' => 'Failed'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Filter
                    </button>

                    <a href="{{ route('admin.results.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Exam</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Score</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Started</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Submitted</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ $attempt->user->name }}</div>
                                        <div class="text-gray-500">{{ $attempt->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $attempt->exam->title }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        @if ($attempt->status === 'in_progress')
                                            -
                                        @else
                                            {{ $attempt->score }} / {{ $attempt->total_questions }} · {{ $attempt->percentage }}%
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{
                                            match ($attempt->status) {
                                                'passed' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                default => 'bg-yellow-100 text-yellow-800',
                                            }
                                        }}">
                                            {{ str_replace('_', ' ', ucfirst($attempt->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attempt->started_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @php
                                            $hasUnusedRetake = $attempt->user->retakePermissions
                                                ->contains(fn ($permission) => $permission->exam_id === $attempt->exam_id && $permission->used_at === null);
                                        @endphp

                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('admin.results.show', $attempt) }}" class="text-indigo-600 hover:text-indigo-900">View</a>

                                            @if ($attempt->status !== 'in_progress' && ! $hasUnusedRetake)
                                                <form method="POST" action="{{ route('admin.results.retake', $attempt) }}">
                                                    @csrf
                                                    <button type="submit" class="text-green-700 hover:text-green-900">
                                                        Grant retake
                                                    </button>
                                                </form>
                                            @elseif ($hasUnusedRetake)
                                                <span class="text-gray-400">Retake granted</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No attempts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($attempts->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $attempts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
