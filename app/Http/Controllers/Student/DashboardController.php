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
        $exams = Exam::query()
            ->with([
                'categories',
                'assignments' => fn ($query) => $query->where('user_id', request()->user()->id),
            ])
            ->where('is_active', true)
            ->whereHas('assignments', fn ($query) => $query->where('user_id', request()->user()->id))
            ->latest()
            ->get();

        $attempts = request()->user()
            ->attempts()
            ->with('exam')
            ->latest()
            ->get();

        $latestAttemptsByExam = $attempts->unique('exam_id')->keyBy('exam_id');
        $unusedRetakesByExam = request()->user()
            ->retakePermissions()
            ->whereNull('used_at')
            ->get()
            ->keyBy('exam_id');

        $exams->each(function (Exam $exam) use ($latestAttemptsByExam, $unusedRetakesByExam): void {
            $latestAttempt = $latestAttemptsByExam->get($exam->id);
            $unusedRetake = $unusedRetakesByExam->get($exam->id);
            $assignment = $exam->assignments->first();

            $status = match (true) {
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

        $recentAttempts = $attempts->take(5);

        return view('student.dashboard', [
            'exams' => $exams,
            'attempts' => $recentAttempts,
        ]);
    }
}
