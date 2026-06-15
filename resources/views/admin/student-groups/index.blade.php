<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Student Groups
            </h2>
            <a href="{{ route('admin.student-groups.create') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                Create Group
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

            <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.student-groups.index') }}" class="grid gap-4 md:grid-cols-3">
                    <div>
                        <x-input-label for="search" value="Search" />
                        <x-text-input
                            id="search"
                            name="search"
                            type="search"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Group name or description"
                        />
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>Filter</x-primary-button>
                        <a href="{{ route('admin.student-groups.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                @forelse ($groups as $group)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="grid gap-6 lg:grid-cols-[1fr,1.3fr]">
                            <div>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $group->name }}</h3>
                                        @if ($group->description)
                                            <p class="mt-1 text-sm text-gray-500">{{ $group->description }}</p>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $group->students_count }} student(s)
                                        </span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $group->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $group->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-3 text-sm font-medium">
                                    <a href="{{ route('admin.student-groups.edit', $group) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                                    @if ($group->is_active)
                                        <form method="POST" action="{{ route('admin.student-groups.destroy', $group) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this group?')">
                                                Deactivate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.student-groups.activate', $group) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:text-green-900">
                                                Reactivate
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.student-groups.permanent-destroy', $group) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-700 hover:text-red-950" onclick="return confirm('Permanently delete this group? This only works when it has no students or exam assignments.')">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                <div class="mt-5 space-y-3">
                                    @forelse ($group->examAssignments->sortByDesc('available_from') as $assignment)
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
                                        <p class="text-sm text-gray-500">No group exams assigned yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.student-groups.assignments.store', $group) }}" class="grid gap-4 rounded-md bg-gray-50 p-4">
                                @csrf

                                <div>
                                    <x-input-label for="group_exam_id_{{ $group->id }}" value="Assign Exam to Group" />
                                    <select id="group_exam_id_{{ $group->id }}" name="exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Choose exam</option>
                                        @foreach ($exams as $exam)
                                            <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <x-input-label for="group_available_from_{{ $group->id }}" value="Available From" />
                                        <x-text-input id="group_available_from_{{ $group->id }}" name="available_from" type="datetime-local" class="mt-1 block w-full" />
                                    </div>

                                    <div>
                                        <x-input-label for="group_available_until_{{ $group->id }}" value="Available Until" />
                                        <x-text-input id="group_available_until_{{ $group->id }}" name="available_until" type="datetime-local" class="mt-1 block w-full" />
                                    </div>
                                </div>

                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                                    Save Group Assignment
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-10 text-center shadow-sm sm:rounded-lg">
                        <p class="text-sm text-gray-500">No student groups have been created yet.</p>
                    </div>
                @endforelse
            </div>

            @if ($groups->hasPages())
                <div class="bg-white px-6 py-4 shadow-sm sm:rounded-lg">
                    {{ $groups->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
