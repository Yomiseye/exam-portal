<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'categoryCount' => Category::count(),
            'questionCount' => Question::count(),
            'examCount' => Exam::count(),
            'submittedAttemptCount' => Attempt::query()
                ->whereIn('status', ['passed', 'failed'])
                ->count(),
        ]);
    }
}
