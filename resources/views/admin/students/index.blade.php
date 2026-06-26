<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Students
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.students.import') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="upload" />
                    Import Excel
                </a>
                <a href="{{ route('admin.students.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                    <x-icon name="user-plus" />
                    Register Student
                </a>
            </div>
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

            <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.students.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="search" value="Search" icon="search" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="search"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Name, email, group"
                        />
                    </div>

                    <div>
                        <x-input-label for="student_group_id" value="Group" icon="users-round" />
                        <select id="student_group_id" name="student_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All groups</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected((string) request('student_group_id') === (string) $group->id)>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" icon="filter" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            <x-icon name="filter" />
                            Filter
                        </x-primary-button>
                        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                @forelse ($students as $student)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="grid gap-6 lg:grid-cols-[1fr,1.4fr]">
                            <div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $student->name }}</h3>
                                        <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                {{ $student->studentGroup?->name ?? 'No group' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {{ $student->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                <x-icon name="{{ $student->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $student->examAssignments->count() }} assigned
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $student->attempts_count }} attempt(s)
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                                    @if ($student->student_group_id)
                                        <form method="POST" action="{{ route('admin.students.remove-group', $student) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-900">
                                                <x-icon name="users-round" class="h-3.5 w-3.5" />
                                                Remove from group
                                            </button>
                                        </form>
                                    @endif

                                    @if ($student->is_active)
                                        <form method="POST" action="{{ route('admin.students.deactivate', $student) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this student?')">
                                                <x-icon name="x-circle" class="h-3.5 w-3.5" />
                                                Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.students.activate', $student) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-green-700 hover:text-green-900">
                                                <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                                Reactivate
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this student? This is only allowed if the student has no exam history.')">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                            Permanent delete
                                        </button>
                                    </form>

                                    @if ($student->attempts_count > 0)
                                        <form method="POST" action="{{ route('admin.students.clear-history', $student) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-amber-700 hover:text-amber-900" onclick="return confirm('Clear this student exam history? The student account will remain, but attempts and retake permissions will be removed.')">
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                                Clear history
                                            </button>
                                        </form>
                                    @endif
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
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {{
                                                    $assignment->isAvailable()
                                                        ? 'bg-green-100 text-green-800'
                                                        : ($assignment->available_from->isFuture() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')
                                                }}">
                                                    <x-icon
                                                        :name="$assignment->isAvailable()
                                                            ? 'check-circle'
                                                            : ($assignment->available_from->isFuture() ? 'clock' : 'x-circle')"
                                                        class="h-3 w-3"
                                                    />
                                                    {{
                                                        $assignment->isAvailable()
                                                            ? 'Available now'
                                                            : ($assignment->available_from->isFuture() ? 'Scheduled' : 'Closed')
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <x-empty-state
                                            class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6"
                                            icon="clipboard-list"
                                            title="No exams assigned"
                                            message="Assign an exam using the form on this student card."
                                        />
                                    @endforelse
                                </div>
                            </div>

                            <div class="space-y-4">
                                <form method="POST" action="{{ route('admin.students.group.update', $student) }}" class="grid gap-4 rounded-md bg-gray-50 p-4">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <x-input-label for="student_group_{{ $student->id }}" value="Update Group" icon="users-round" />
                                        <select id="student_group_{{ $student->id }}" name="student_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">No group</option>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->id }}" @selected($student->student_group_id === $group->id)>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="submit" class="portal-button-secondary text-xs uppercase tracking-widest">
                                        <x-icon name="save" />
                                        Save Group
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.students.assignments.store', $student) }}" class="grid gap-4 rounded-md bg-gray-50 p-4">
                                    @csrf

                                    <div>
                                        <x-input-label for="exam_id_{{ $student->id }}" value="Assign Exam" icon="clipboard-list" />
                                        <select id="exam_id_{{ $student->id }}" name="exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Choose exam</option>
                                            @foreach ($exams as $exam)
                                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <x-input-label for="available_from_{{ $student->id }}" value="Available From" icon="calendar-days" />
                                            <x-text-input id="available_from_{{ $student->id }}" name="available_from" type="datetime-local" class="mt-1 block w-full" />
                                        </div>

                                        <div>
                                            <x-input-label for="available_until_{{ $student->id }}" value="Available Until" icon="calendar-days" />
                                            <x-text-input id="available_until_{{ $student->id }}" name="available_until" type="datetime-local" class="mt-1 block w-full" />
                                        </div>
                                    </div>

                                    <button type="submit" class="portal-button-primary text-xs uppercase tracking-widest">
                                        <x-icon name="clipboard-list" />
                                        Save Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <x-empty-state
                            icon="users"
                            title="No students yet"
                            message="Register students manually or import an Excel sheet."
                        >
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="{{ route('admin.students.import') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                                    <x-icon name="upload" />
                                    Import Excel
                                </a>
                                <a href="{{ route('admin.students.create') }}" class="portal-button-primary text-xs uppercase tracking-widest">
                                    <x-icon name="user-plus" />
                                    Register Student
                                </a>
                            </div>
                        </x-empty-state>
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
