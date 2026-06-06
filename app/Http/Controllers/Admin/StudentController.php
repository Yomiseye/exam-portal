<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display registered students and their assigned exams.
     */
    public function index(): View
    {
        $students = User::query()
            ->where('role', 'student')
            ->with(['examAssignments.exam'])
            ->latest()
            ->paginate(10);

        $exams = Exam::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('admin.students.index', compact('students', 'exams'));
    }

    /**
     * Show the form for registering a student.
     */
    public function create(): View
    {
        return view('admin.students.create');
    }

    /**
     * Register a new student without logging out the admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
        ]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Student registered successfully.');
    }

    /**
     * Assign or update an exam availability window for a student.
     */
    public function assignExam(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'available_from' => ['required', 'date'],
            'available_until' => ['required', 'date', 'after:available_from'],
        ]);

        $exam = Exam::query()
            ->where('is_active', true)
            ->findOrFail($validated['exam_id']);

        ExamAssignment::updateOrCreate(
            [
                'user_id' => $student->id,
                'exam_id' => $exam->id,
            ],
            [
                'assigned_by' => $request->user()->id,
                'available_from' => $validated['available_from'],
                'available_until' => $validated['available_until'],
            ],
        );

        return back()->with('status', 'Exam assignment saved successfully.');
    }
}
