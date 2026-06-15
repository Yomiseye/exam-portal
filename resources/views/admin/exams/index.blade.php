<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Exams
            </h2>
            <a href="{{ route('admin.exams.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Create Exam
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass Mark</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rules</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($exams as $exam)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-xs">
                                        <div>{{ $exam->title }}</div>
                                        @if ($exam->description)
                                            <div class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($exam->description, 80) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $exam->categories->map(fn ($category) => $category->fullName())->join(', ') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $exam->duration_minutes }} min
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $exam->total_questions }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $exam->pass_mark }}%
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div>{{ $exam->is_randomized ? 'Randomized' : 'Fixed order' }}</div>
                                        <div>{{ $exam->show_corrections ? 'Corrections shown' : 'Corrections hidden' }}</div>
                                        <div>{{ $exam->allow_pause ? 'Pause allowed' : 'Pause disabled' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $exam->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $exam->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.exams.edit', $exam) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                                        @if ($exam->is_active)
                                            <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" class="inline ms-4">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this exam?')">
                                                    Deactivate
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.exams.permanent-destroy', $exam) }}" class="inline ms-4">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this exam? This only works when it has no attempt history.')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No exams have been created yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exams->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
