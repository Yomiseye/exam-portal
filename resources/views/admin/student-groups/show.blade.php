<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $group->name }}
                </h2>
                <p class="text-sm text-white/80">Group students, assigned tests, and membership controls.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.student-groups.edit', $group) }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="pencil" />
                    Edit Group
                </a>
                <a href="{{ route('admin.student-groups.index') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="arrow-left" />
                    Back
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

            <section class="grid gap-4 md:grid-cols-3">
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Students</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $group->students->count() }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Group Tests</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $group->examAssignments->count() }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Status</div>
                    <div class="mt-2">
                        <span class="portal-badge {{ $group->is_active ? 'portal-badge-success' : 'portal-badge-neutral' }}">
                            <x-icon name="{{ $group->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                            {{ $group->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </section>

            @if ($group->description)
                <section class="portal-panel p-5">
                    <h3 class="text-base font-semibold text-gray-950">Description</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $group->description }}</p>
                </section>
            @endif

            <section class="grid gap-6 lg:grid-cols-[0.9fr,1.1fr]">
                <div class="portal-panel p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-950">Group Assigned Tests</h3>
                            <p class="mt-1 text-sm text-gray-500">Tests inherited by every active student in this group.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($group->examAssignments->sortByDesc('available_from') as $assignment)
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-950">{{ $assignment->exam->title }}</div>
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ $assignment->available_from->format('M j, Y g:i A') }}
                                            to
                                            {{ $assignment->available_until->format('M j, Y g:i A') }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Assigned by {{ $assignment->assignedBy?->name ?? 'Admin' }}
                                        </div>
                                    </div>

                                    <span class="portal-badge {{
                                        $assignment->isAvailable()
                                            ? 'portal-badge-success'
                                            : ($assignment->available_from->isFuture() ? 'portal-badge-info' : 'portal-badge-neutral')
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
                                class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-8"
                                icon="clipboard-list"
                                title="No group tests"
                                message="Assign tests from the Student Groups page."
                            />
                        @endforelse
                    </div>
                </div>

                <div class="portal-panel p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-950">Students in Group</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $group->students->count() }} student(s) currently assigned.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($group->students as $student)
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-950">{{ $student->name }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ $student->email }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="portal-badge {{ $student->is_active ? 'portal-badge-success' : 'portal-badge-neutral' }}">
                                                <x-icon name="{{ $student->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <span class="portal-badge portal-badge-neutral">
                                                <x-icon name="chart-bar" class="h-3 w-3" />
                                                {{ $student->attempts_count }} attempt(s)
                                            </span>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('admin.students.remove-group', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-950" onclick="return confirm('Remove this student from {{ $group->name }}?')">
                                            <x-icon name="x-circle" class="h-3.5 w-3.5" />
                                            Remove from group
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <x-empty-state
                                class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-8"
                                icon="users"
                                title="No students assigned"
                                message="Assign students to this group from the Students page."
                            />
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
