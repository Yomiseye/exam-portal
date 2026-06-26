<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Dashboard
            </h2>
            <p class="text-sm text-white/80">Manage students, exam content, assignments, and results from one workspace.</p>
        </div>
    </x-slot>

    <div class="portal-page">
        <div class="portal-container">
            <div class="mb-6 grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Students</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $studentCount }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Assignments</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $assignmentCount }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Categories</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $categoryCount }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Questions</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $questionCount }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Exams</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $examCount }}</div>
                </div>

                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Submitted</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $submittedAttemptCount }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.25fr,0.75fr]">
                <section class="portal-panel p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-950">Operations</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-600">
                                Build the test bank, publish exams, assign candidates, and review outcomes.
                            </p>
                        </div>
                        <a href="{{ route('admin.results.index') }}" class="portal-button-primary shrink-0">
                            <x-icon name="chart-bar" />
                            View Results
                        </a>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <a href="{{ route('admin.students.index') }}" class="rounded-md border border-gray-200 bg-white p-4 transition hover:border-teal-200 hover:bg-slate-50">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-gray-950">
                                <x-icon name="users" />
                                Students
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Register, import, group, activate, and assign exams.</p>
                        </a>

                        <a href="{{ route('admin.questions.index') }}" class="rounded-md border border-gray-200 bg-white p-4 transition hover:border-teal-200 hover:bg-slate-50">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-gray-950">
                                <x-icon name="circle-help" />
                                Question Bank
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Create, import, tag, filter, and maintain questions.</p>
                        </a>

                        <a href="{{ route('admin.exams.index') }}" class="rounded-md border border-gray-200 bg-white p-4 transition hover:border-teal-200 hover:bg-slate-50">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-gray-950">
                                <x-icon name="clipboard-list" />
                                Exams
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Configure timing, pass marks, corrections, and pause rules.</p>
                        </a>

                        <a href="{{ route('admin.categories.index') }}" class="rounded-md border border-gray-200 bg-white p-4 transition hover:border-teal-200 hover:bg-slate-50">
                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-gray-950">
                                <x-icon name="tag" />
                                Categories
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Organize subjects and topic hierarchies.</p>
                        </a>
                    </div>
                </section>

                <aside class="portal-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-950">Quick Actions</h3>
                    <p class="mt-1 text-sm text-gray-500">Common admin tasks.</p>

                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('admin.students.create') }}" class="portal-button-primary w-full">
                            <x-icon name="user-plus" />
                            Register Student
                        </a>
                        <a href="{{ route('admin.students.import') }}" class="portal-button-secondary w-full">
                            <x-icon name="upload" />
                            Import Students
                        </a>
                        <a href="{{ route('admin.questions.create') }}" class="portal-button-primary w-full">
                            <x-icon name="plus" />
                            Create Question
                        </a>
                        <a href="{{ route('admin.questions.import') }}" class="portal-button-secondary w-full">
                            <x-icon name="upload" />
                            Import Questions
                        </a>
                        <a href="{{ route('admin.exams.create') }}" class="portal-button-primary w-full">
                            <x-icon name="plus" />
                            Create Exam
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
