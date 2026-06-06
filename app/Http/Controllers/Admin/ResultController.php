<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
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
            ->with(['exam', 'user'])
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
            'user',
            'answers.selectedOption',
            'answers.question.options',
        ]);

        return view('admin.results.show', compact('attempt'));
    }
}
