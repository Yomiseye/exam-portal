<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
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
        ]);
    }
}
