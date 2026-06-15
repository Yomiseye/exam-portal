<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\GroupExamAssignment;
use App\Models\StudentGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentGroupController extends Controller
{
    /**
     * Display student groups and group exam assignments.
     */
    public function index(Request $request): View
    {
        $groups = StudentGroup::query()
            ->withCount('students')
            ->with(['examAssignments.exam'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $exams = Exam::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('admin.student-groups.index', compact('groups', 'exams'));
    }

    /**
     * Show the form for creating a group.
     */
    public function create(): View
    {
        return view('admin.student-groups.create', [
            'group' => null,
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request): RedirectResponse
    {
        StudentGroup::create($this->validatedGroup($request));

        return redirect()
            ->route('admin.student-groups.index')
            ->with('status', 'Student group created successfully.');
    }

    /**
     * Show the form for editing a group.
     */
    public function edit(StudentGroup $studentGroup): View
    {
        return view('admin.student-groups.edit', [
            'group' => $studentGroup,
        ]);
    }

    /**
     * Update a student group.
     */
    public function update(Request $request, StudentGroup $studentGroup): RedirectResponse
    {
        $studentGroup->update($this->validatedGroup($request, $studentGroup));

        return redirect()
            ->route('admin.student-groups.index')
            ->with('status', 'Student group updated successfully.');
    }

    /**
     * Deactivate a group without removing students or history.
     */
    public function destroy(StudentGroup $studentGroup): RedirectResponse
    {
        $studentGroup->update(['is_active' => false]);

        return redirect()
            ->route('admin.student-groups.index')
            ->with('status', 'Student group deactivated successfully.');
    }

    /**
     * Permanently delete a group only when no students or assignments depend on it.
     */
    public function permanentDestroy(StudentGroup $studentGroup): RedirectResponse
    {
        $studentGroup->loadCount(['students', 'examAssignments']);

        if ($studentGroup->students_count > 0 || $studentGroup->exam_assignments_count > 0) {
            return back()->withErrors([
                'student_group' => 'This group cannot be permanently deleted while it has students or exam assignments. Remove those first or deactivate it.',
            ]);
        }

        $studentGroup->delete();

        return redirect()
            ->route('admin.student-groups.index')
            ->with('status', 'Student group permanently deleted successfully.');
    }

    /**
     * Reactivate a group.
     */
    public function activate(StudentGroup $studentGroup): RedirectResponse
    {
        $studentGroup->update(['is_active' => true]);

        return redirect()
            ->route('admin.student-groups.index')
            ->with('status', 'Student group reactivated successfully.');
    }

    /**
     * Assign or update an exam availability window for a group.
     */
    public function assignExam(Request $request, StudentGroup $studentGroup): RedirectResponse
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'available_from' => ['required', 'date'],
            'available_until' => ['required', 'date', 'after:available_from'],
        ]);

        $exam = Exam::query()
            ->where('is_active', true)
            ->findOrFail($validated['exam_id']);

        GroupExamAssignment::updateOrCreate(
            [
                'student_group_id' => $studentGroup->id,
                'exam_id' => $exam->id,
            ],
            [
                'assigned_by' => $request->user()->id,
                'available_from' => $validated['available_from'],
                'available_until' => $validated['available_until'],
            ],
        );

        return back()->with('status', 'Group exam assignment saved successfully.');
    }

    /**
     * @return array{name: string, description?: string|null, is_active: bool}
     */
    private function validatedGroup(Request $request, ?StudentGroup $group = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('student_groups', 'name')->ignore($group),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
