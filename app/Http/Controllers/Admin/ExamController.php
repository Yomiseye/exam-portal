<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(): View
    {
        $exams = Exam::query()
            ->with('categories')
            ->latest()
            ->paginate(10);

        return view('admin.exams.index', compact('exams'));
    }

    /**
     * Show the form for creating an exam.
     */
    public function create(): View
    {
        return view('admin.exams.create', [
            'categories' => $this->activeCategories(),
        ]);
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedExamData($request);

        DB::transaction(function () use ($data): void {
            $exam = Exam::create($data['exam']);

            $exam->categories()->sync($data['category_ids']);
        });

        return redirect()
            ->route('admin.exams.index')
            ->with('status', 'Exam created successfully.');
    }

    /**
     * Show the form for editing an exam.
     */
    public function edit(Exam $exam): View
    {
        $exam->load('categories');

        return view('admin.exams.edit', [
            'exam' => $exam,
            'categories' => $this->activeCategories($exam),
        ]);
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $this->validatedExamData($request);

        DB::transaction(function () use ($exam, $data): void {
            $exam->update($data['exam']);

            $exam->categories()->sync($data['category_ids']);
        });

        return redirect()
            ->route('admin.exams.index')
            ->with('status', 'Exam updated successfully.');
    }

    /**
     * Deactivate the specified exam.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->update(['is_active' => false]);

        return redirect()
            ->route('admin.exams.index')
            ->with('status', 'Exam deactivated successfully.');
    }

    /**
     * Validate exam form data.
     *
     * @return array{exam: array<string, mixed>, category_ids: array<int, int>}
     */
    private function validatedExamData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'total_questions' => ['required', 'integer', 'min:1'],
            'pass_mark' => ['required', 'integer', 'min:0', 'max:100'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'is_randomized' => ['sometimes', 'boolean'],
            'show_corrections' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'exam' => [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'total_questions' => $validated['total_questions'],
                'pass_mark' => $validated['pass_mark'],
                'is_randomized' => $request->boolean('is_randomized'),
                'show_corrections' => $request->boolean('show_corrections'),
                'is_active' => $request->boolean('is_active'),
            ],
            'category_ids' => array_values(array_unique(array_map('intval', $validated['category_ids']))),
        ];
    }

    /**
     * Get active categories for exam forms.
     */
    private function activeCategories(?Exam $exam = null)
    {
        return Category::query()
            ->where(function ($query) use ($exam): void {
                $query->where('is_active', true);

                if ($exam) {
                    $query->orWhereIn('id', $exam->categories->pluck('id'));
                }
            })
            ->orderBy('name')
            ->get();
    }
}
