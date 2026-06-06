<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamRetakePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * Display submitted and in-progress student attempts.
     */
    public function index(Request $request): View
    {
        $attempts = Attempt::query()
            ->with(['exam', 'user.retakePermissions'])
            ->when($request->filled('exam_id'), fn ($query) => $query->where('exam_id', $request->integer('exam_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $exams = Exam::query()
            ->orderBy('title')
            ->get();

        return view('admin.results.index', compact('attempts', 'exams'));
    }

    /**
     * Display one attempt with its selected answers.
     */
    public function show(Attempt $attempt): View
    {
        $attempt->load([
            'exam',
            'user.retakePermissions.grantedBy',
            'answers.selectedOption',
            'answers.question.options',
        ]);

        return view('admin.results.show', compact('attempt'));
    }

    /**
     * Grant a student one more attempt for the selected exam.
     */
    public function grantRetake(Request $request, Attempt $attempt): RedirectResponse
    {
        abort_if($attempt->status === 'in_progress', 422);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $hasUnusedPermission = ExamRetakePermission::query()
            ->where('user_id', $attempt->user_id)
            ->where('exam_id', $attempt->exam_id)
            ->whereNull('used_at')
            ->exists();

        if ($hasUnusedPermission) {
            return back()->with('status', 'This student already has an unused retake permission for this exam.');
        }

        ExamRetakePermission::create([
            'user_id' => $attempt->user_id,
            'exam_id' => $attempt->exam_id,
            'granted_by' => $request->user()->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('status', 'Retake permission granted successfully.');
    }
}
