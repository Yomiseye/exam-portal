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
            ->with('categories')
            ->where('is_active', true)
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

            $status = match (true) {
                $latestAttempt?->status === 'in_progress' => 'in_progress',
                $latestAttempt !== null && $unusedRetake !== null => 'retake_granted',
                $latestAttempt !== null => 'completed',
                default => 'available',
            };

            $exam->setAttribute('student_status', $status);
            $exam->setAttribute('latest_attempt', $latestAttempt);
        });

        $recentAttempts = $attempts->take(5);

        return view('student.dashboard', [
            'exams' => $exams,
            'attempts' => $recentAttempts,
        ]);
    }
}
