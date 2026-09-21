<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $exam->title }}
                </h2>
                <p class="text-sm text-white/80">Assignment visibility from the test perspective.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.exams.edit', $exam) }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="pencil" />
                    Edit Exam
                </a>
                <a href="{{ route('admin.exams.index') }}" class="portal-button-secondary text-xs uppercase tracking-widest">
                    <x-icon name="arrow-left" />
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-4">
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Direct Students</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $directStudentCount }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Assigned Groups</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $exam->groupAssignments->count() }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Inherited Students</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $inheritedStudentCount }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Total Visible Students</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $totalVisibleStudentCount }}</div>
                </div>
            </section>

            <section class="portal-panel p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Exam Summary</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $exam->duration_minutes }} minutes - {{ $exam->total_questions }} questions - {{ $exam->pass_mark }}% pass mark
                        </p>
                        @if ($exam->description)
                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $exam->description }}</p>
                        @endif
                    </div>

                    <span class="portal-badge {{ $exam->is_active ? 'portal-badge-success' : 'portal-badge-neutral' }}">
                        <x-icon name="{{ $exam->is_active ? 'check-circle' : 'x-circle' }}" class="h-3 w-3" />
                        {{ $exam->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($exam->categories as $category)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $category->fullName() }}</span>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[0.9fr,1.1fr]">
                <div class="portal-panel p-5">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Direct Student Assignments</h3>
                        <p class="mt-1 text-sm text-gray-500">Students assigned to this test individually.</p>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($exam->assignments->sortByDesc('available_from') as $assignment)
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-950">{{ $assignment->user?->name ?? 'Unknown student' }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ $assignment->user?->email }}</div>
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ $assignment->available_from->format('M j, Y g:i A') }}
                                            to
                                            {{ $assignment->available_until->format('M j, Y g:i A') }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Group: {{ $assignment->user?->studentGroup?->name ?? 'No group' }} - Assigned by {{ $assignment->assignedBy?->name ?? 'Admin' }}
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
                                icon="users"
                                title="No direct student assignments"
                                message="Direct assignments are created from the Students page."
                            />
                        @endforelse
                    </div>
                </div>

                <div class="portal-panel p-5">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950">Group Assignments</h3>
                        <p class="mt-1 text-sm text-gray-500">Groups assigned to this test and students inheriting access.</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($exam->groupAssignments->sortByDesc('available_from') as $assignment)
                            <div class="rounded-md border border-teal-100 bg-teal-50/50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-950">{{ $assignment->studentGroup?->name ?? 'Unknown group' }}</div>
                                        <div class="mt-1 text-sm text-gray-600">
                                            {{ $assignment->available_from->format('M j, Y g:i A') }}
                                            to
                                            {{ $assignment->available_until->format('M j, Y g:i A') }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $assignment->studentGroup?->students?->count() ?? 0 }} student(s) inherit this test - Assigned by {{ $assignment->assignedBy?->name ?? 'Admin' }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        @if ($assignment->studentGroup)
                                            <a href="{{ route('admin.student-groups.show', $assignment->studentGroup) }}" class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-200 hover:bg-teal-50">
                                                <x-icon name="eye" class="h-3 w-3" />
                                                View group
                                            </a>
                                        @endif
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

                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    @forelse ($assignment->studentGroup?->students ?? collect() as $student)
                                        <div class="rounded-md bg-white px-3 py-2 text-sm ring-1 ring-teal-100">
                                            <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $student->email }}</div>
                                        </div>
                                    @empty
                                        <div class="rounded-md border border-dashed border-teal-200 bg-white/70 px-3 py-4 text-sm text-gray-500 sm:col-span-2">
                                            No students currently belong to this group.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <x-empty-state
                                class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-8"
                                icon="users-round"
                                title="No group assignments"
                                message="Group assignments are created from the Student Groups page."
                            />
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
