<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the student dashboard.
     */
    public function __invoke(): View
    {
        $student = request()->user();
        $groupId = $student->studentGroup?->is_active ? $student->student_group_id : null;

        $exams = Exam::query()
            ->with([
                'categories.parent',
                'assignments' => fn ($query) => $query->where('user_id', $student->id),
                'groupAssignments' => fn ($query) => $groupId
                    ? $query->where('student_group_id', $groupId)
                    : $query->whereRaw('1 = 0'),
            ])
            ->where('is_active', true)
            ->where(function ($query) use ($student, $groupId): void {
                $query->whereHas('assignments', fn ($query) => $query->where('user_id', $student->id));

                if ($groupId) {
                    $query->orWhereHas('groupAssignments', fn ($query) => $query->where('student_group_id', $groupId));
                }
            })
            ->latest()
            ->get();

        $attempts = $student
            ->attempts()
            ->with('exam')
            ->latest()
            ->get();

        $attempts
            ->groupBy('exam_id')
            ->each(function ($examAttempts): void {
                $examAttempts
                    ->sortBy('started_at')
                    ->values()
                    ->each(fn ($attempt, int $index) => $attempt->setAttribute('attempt_number', $index + 1));
            });

        $latestAttemptsByExam = $attempts->unique('exam_id')->keyBy('exam_id');
        $unusedRetakesByExam = $student
            ->retakePermissions()
            ->whereNull('used_at')
            ->get()
            ->keyBy('exam_id');

        $exams->each(function (Exam $exam) use ($latestAttemptsByExam, $unusedRetakesByExam): void {
            $latestAttempt = $latestAttemptsByExam->get($exam->id);
            $unusedRetake = $unusedRetakesByExam->get($exam->id);
            $directAssignment = $exam->assignments->first();
            $groupAssignment = $exam->groupAssignments->first();
            $assignment = match (true) {
                $directAssignment?->isAvailable() => $directAssignment,
                $groupAssignment?->isAvailable() => $groupAssignment,
                default => $directAssignment ?? $groupAssignment,
            };

            $status = match (true) {
                $latestAttempt?->status === 'in_progress' && $latestAttempt->isPaused() => 'paused',
                $latestAttempt?->status === 'in_progress' => 'in_progress',
                $assignment?->available_from->isFuture() => 'scheduled',
                $assignment?->available_until->isPast() => 'closed',
                $latestAttempt !== null && $unusedRetake !== null => 'retake_granted',
                $latestAttempt !== null => 'completed',
                default => 'available',
            };

            $exam->setAttribute('student_status', $status);
            $exam->setAttribute('latest_attempt', $latestAttempt);
            $exam->setAttribute('assignment', $assignment);
        });

        return view('student.dashboard', [
            'exams' => $exams,
            'attempts' => $attempts,
            'performanceTrends' => $this->performanceTrends($attempts),
        ]);
    }

    /**
     * @param  Collection<int, Attempt>  $attempts
     * @return array{summary: array{completed: int, average: int, best: int, latest: int|null, delta: int|null, direction: string}, exams: array<int, array{exam: string, attempts: int, average: int, latest: int, previous: int|null, delta: int|null, direction: string, recent: array<int, array{attempt_number: int, percentage: int, status: string, date: string}>}>}
     */
    private function performanceTrends(Collection $attempts): array
    {
        $completed = $attempts
            ->whereIn('status', ['passed', 'failed'])
            ->sortBy('started_at')
            ->values();

        $latest = $completed->last();
        $first = $completed->first();
        $best = (int) ($completed->max('percentage') ?? 0);
        $average = $completed->isNotEmpty() ? (int) round($completed->avg('percentage')) : 0;
        $delta = $first && $latest && $completed->count() > 1
            ? (int) $latest->percentage - (int) $first->percentage
            : null;

        return [
            'summary' => [
                'completed' => $completed->count(),
                'average' => $average,
                'best' => $best,
                'latest' => $latest?->percentage,
                'delta' => $delta,
                'direction' => $this->trendDirection($delta),
            ],
            'exams' => $completed
                ->groupBy('exam_id')
                ->map(function (Collection $examAttempts): array {
                    $ordered = $examAttempts->sortBy('started_at')->values();
                    $latest = $ordered->last();
                    $previous = $ordered->count() > 1 ? $ordered->get($ordered->count() - 2) : null;
                    $delta = $previous && $latest
                        ? (int) $latest->percentage - (int) $previous->percentage
                        : null;

                    return [
                        'exam' => $latest->exam?->title ?? 'Unknown exam',
                        'attempts' => $ordered->count(),
                        'average' => (int) round($ordered->avg('percentage')),
                        'latest' => (int) $latest->percentage,
                        'previous' => $previous?->percentage,
                        'delta' => $delta,
                        'direction' => $this->trendDirection($delta),
                        'recent' => $ordered
                            ->take(-6)
                            ->map(fn (Attempt $attempt) => [
                                'attempt_number' => (int) $attempt->attempt_number,
                                'percentage' => (int) $attempt->percentage,
                                'status' => $attempt->status,
                                'date' => $attempt->started_at?->format('M j'),
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->sortByDesc(fn (array $row) => $row['attempts'])
                ->values()
                ->all(),
        ];
    }

    private function trendDirection(?int $delta): string
    {
        return match (true) {
            $delta === null => 'steady',
            $delta > 0 => 'up',
            $delta < 0 => 'down',
            default => 'steady',
        };
    }
}
