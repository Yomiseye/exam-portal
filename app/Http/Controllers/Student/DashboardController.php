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
            ->limit(5)
            ->get();

        return view('student.dashboard', compact('exams', 'attempts'));
    }
}
