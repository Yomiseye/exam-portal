<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Analytics
            </h2>
            <p class="text-sm text-white/80">Performance insights across attempts, exams, groups, metadata, and question quality.</p>
        </div>
    </x-slot>

    <div class="portal-page">
        <div class="portal-container space-y-6">
            <section class="portal-panel p-5">
                <form method="GET" action="{{ route('admin.analytics.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="exam_id" value="Exam" icon="clipboard-list" />
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
                        <x-input-label for="date_from" value="From" icon="calendar-days" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
                    </div>

                    <div>
                        <x-input-label for="date_to" value="To" icon="calendar-days" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            <x-icon name="filter" />
                            Filter
                        </x-primary-button>
                        <a href="{{ route('admin.analytics.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Reset</a>
                    </div>
                </form>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Submitted Attempts</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $summary['attempts'] }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Passed</div>
                    <div class="mt-2 text-3xl font-semibold text-emerald-700">{{ $summary['passed'] }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Failed</div>
                    <div class="mt-2 text-3xl font-semibold text-red-700">{{ $summary['failed'] }}</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Pass Rate</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $summary['pass_rate'] }}%</div>
                </div>
                <div class="portal-kpi">
                    <div class="text-sm font-medium text-gray-500">Average Score</div>
                    <div class="mt-2 text-3xl font-semibold text-gray-950">{{ $summary['average_score'] }}%</div>
                </div>
            </section>

            @if ($summary['attempts'] === 0)
                <x-empty-state
                    icon="chart-bar"
                    title="No analytics yet"
                    message="Submitted attempts will appear here once students complete exams within the selected filter."
                />
            @else
                <section class="grid gap-6 lg:grid-cols-[0.8fr,1.2fr]">
                    <div class="portal-panel p-5">
                        <h3 class="text-lg font-semibold text-gray-950">Pass / Fail Trend</h3>
                        <div class="mt-5 space-y-4">
                            @foreach ($statusRows as $row)
                                <div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="font-medium text-gray-700">{{ $row['label'] }}</span>
                                        <span class="font-semibold text-gray-950">{{ $row['count'] }} / {{ $summary['attempts'] }} ({{ $row['percentage'] }}%)</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-gray-100">
                                        <div
                                            class="h-2 rounded-full {{ $row['label'] === 'Passed' ? 'bg-emerald-500' : 'bg-red-500' }}"
                                            style="width: {{ $row['percentage'] }}%"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="portal-panel p-5">
                        <h3 class="text-lg font-semibold text-gray-950">Exam Performance</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Exam</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Attempts</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Average</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Pass Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($examRows as $row)
                                        <tr>
                                            <td class="px-3 py-3 font-medium text-gray-900">{{ $row['label'] }}</td>
                                            <td class="px-3 py-3 text-gray-600">{{ $row['attempts'] }}</td>
                                            <td class="px-3 py-3 text-gray-600">{{ $row['average'] }}%</td>
                                            <td class="px-3 py-3 text-gray-600">{{ $row['pass_rate'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-6 text-center text-gray-500">No exam data for this filter.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-4">
                    @foreach ($metadataSections as $section)
                        <div class="portal-panel p-5">
                            <h3 class="text-base font-semibold text-gray-950">{{ $section['title'] }}</h3>

                            <div class="mt-4 space-y-4">
                                @forelse ($section['rows'] as $row)
                                    @php
                                        $statusClass = match ($row['status']) {
                                            'Strength' => 'bg-emerald-50 text-emerald-700',
                                            'Developing' => 'bg-amber-50 text-amber-800',
                                            default => 'bg-red-50 text-red-700',
                                        };
                                    @endphp

                                    <div>
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900">{{ $row['label'] }}</div>
                                                <div class="mt-0.5 text-xs text-gray-500">{{ $row['correct'] }} / {{ $row['total'] }} correct</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-950">{{ $row['percentage'] }}%</div>
                                                <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                                                    {{ $row['status'] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-gray-100">
                                            <div
                                                class="h-2 rounded-full {{ $row['percentage'] >= 80 ? 'bg-emerald-500' : ($row['percentage'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                style="width: {{ $row['percentage'] }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No classified answers.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </section>

                <section class="grid gap-6 lg:grid-cols-[0.9fr,1.1fr]">
                    <div class="portal-panel p-5">
                        <h3 class="text-lg font-semibold text-gray-950">Group Performance</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($groupRows as $row)
                                <div class="rounded-md border border-gray-200 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $row['label'] }}</div>
                                            <div class="mt-0.5 text-xs text-gray-500">{{ $row['attempts'] }} attempt(s)</div>
                                        </div>
                                        <div class="text-right text-sm">
                                            <div class="font-semibold text-gray-950">{{ $row['average'] }}%</div>
                                            <div class="text-xs text-gray-500">{{ $row['pass_rate'] }}% pass rate</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No group data for this filter.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="portal-panel p-5">
                        <h3 class="text-lg font-semibold text-gray-950">Commonly Missed Questions</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($missedQuestions as $row)
                                <div class="rounded-md border border-gray-200 p-3">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Question #{{ $row['question_id'] }}</div>
                                            <p class="mt-1 text-sm leading-6 text-gray-600">{{ $row['question'] }}</p>
                                        </div>
                                        <div class="shrink-0 text-left sm:text-right">
                                            <div class="text-sm font-semibold text-red-700">{{ $row['miss_rate'] }}% missed</div>
                                            <div class="text-xs text-gray-500">{{ $row['missed'] }} of {{ $row['attempts'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No missed questions for this filter.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
