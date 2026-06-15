<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\StudentGroup;
use App\Models\User;
use App\Services\QuestionImportSpreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display registered students and their assigned exams.
     */
    public function index(Request $request): View
    {
        $students = User::query()
            ->where('role', 'student')
            ->with(['examAssignments.exam', 'studentGroup'])
            ->withCount('attempts')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhereHas('studentGroup', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->when($request->filled('student_group_id'), fn ($query) => $query->where('student_group_id', $request->integer('student_group_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $exams = Exam::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $groups = StudentGroup::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.students.index', compact('students', 'exams', 'groups'));
    }

    /**
     * Show the form for registering a student.
     */
    public function create(): View
    {
        return view('admin.students.create', [
            'groups' => StudentGroup::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the student spreadsheet import form.
     */
    public function import(): View
    {
        return view('admin.students.import', [
            'sheets' => [],
            'importPath' => null,
            'importFileName' => null,
            'sheetError' => null,
        ]);
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
            'student_group_id' => [
                'nullable',
                'integer',
                Rule::exists('student_groups', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'new_group_name' => ['nullable', 'string', 'max:255', 'unique:student_groups,name'],
        ]);

        if (filled($validated['student_group_id'] ?? null) && filled($validated['new_group_name'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['new_group_name' => 'Choose an existing group or add a new one, not both.']);
        }

        $groupId = $validated['student_group_id'] ?? null;

        if (filled($validated['new_group_name'] ?? null)) {
            $groupId = StudentGroup::create([
                'name' => trim($validated['new_group_name']),
                'is_active' => true,
            ])->id;
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'student_group_id' => $groupId,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Student registered successfully.');
    }

    /**
     * Import students from an uploaded Excel workbook.
     */
    public function storeImport(Request $request, QuestionImportSpreadsheet $spreadsheet): RedirectResponse|View
    {
        $validated = $request->validate([
            'students_file' => ['nullable', 'required_without:import_path', 'file', 'mimes:xlsx', 'max:5120'],
            'import_path' => ['nullable', 'string'],
            'sheet_index' => ['nullable', 'integer', 'min:0'],
        ]);

        [$path, $temporaryImportPath, $sheetIndex, $sheetSelectionView] = $this->resolveImportSheet(
            $request,
            $spreadsheet,
            'students_file',
            'admin.students.import',
        );

        if ($sheetSelectionView) {
            return $sheetSelectionView;
        }

        $rows = $spreadsheet->rows($path, $sheetIndex);

        if ($temporaryImportPath) {
            Storage::disk('local')->delete($temporaryImportPath);
        }

        if ($rows === []) {
            return back()
                ->withInput()
                ->withErrors(['students_file' => 'The spreadsheet does not contain any student rows.']);
        }

        $students = [];
        $errors = [];
        $emailsInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->studentDataFromImportRow($row, $rowNumber, $emailsInFile);

            if ($data['errors'] !== []) {
                $errors = array_merge($errors, $data['errors']);

                continue;
            }

            $emailsInFile[] = $data['student']['email'];
            $students[] = $data['student'];
        }

        if ($errors !== []) {
            return back()
                ->withInput()
                ->withErrors(['students_file' => implode("\n", $errors)]);
        }

        DB::transaction(function () use ($students): void {
            foreach ($students as $studentData) {
                $groupId = null;

                if (filled($studentData['group'])) {
                    $group = StudentGroup::firstOrCreate(
                        ['name' => $studentData['group']],
                        ['is_active' => true],
                    );

                    if (! $group->is_active) {
                        $group->update(['is_active' => true]);
                    }

                    $groupId = $group->id;
                }

                User::create([
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                    'password' => Hash::make($studentData['password']),
                    'role' => 'student',
                    'student_group_id' => $groupId,
                    'is_active' => $studentData['is_active'],
                ]);
            }
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', count($students).' student(s) imported successfully.');
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

    public function removeFromGroup(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $student->update(['student_group_id' => null]);

        return back()->with('status', 'Student removed from group successfully.');
    }

    public function updateGroup(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'student_group_id' => [
                'nullable',
                'integer',
                Rule::exists('student_groups', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $student->update([
            'student_group_id' => $validated['student_group_id'] ?? null,
        ]);

        return back()->with('status', 'Student group updated successfully.');
    }

    public function deactivate(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $student->update(['is_active' => false]);

        return back()->with('status', 'Student deactivated successfully.');
    }

    public function activate(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $student->update(['is_active' => true]);

        return back()->with('status', 'Student reactivated successfully.');
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        if ($student->attempts()->exists()) {
            return back()->withErrors([
                'student' => 'This student has exam history and cannot be permanently deleted. Deactivate the student instead.',
            ]);
        }

        $student->delete();

        return back()->with('status', 'Student permanently deleted successfully.');
    }

    public function clearHistory(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        DB::transaction(function () use ($student): void {
            $student->attempts()->delete();
            $student->retakePermissions()->delete();
        });

        return back()->with('status', 'Student exam history cleared successfully.');
    }

    public function destroyWithHistory(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        DB::transaction(function () use ($student): void {
            $student->attempts()->delete();
            $student->retakePermissions()->delete();
            $student->delete();
        });

        return back()->with('status', 'Student exam history was cleared and the student was permanently deleted.');
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int, string>  $emailsInFile
     * @return array{errors: array<int, string>, student?: array{name: string, email: string, password: string, group: string|null, is_active: bool}}
     */
    private function studentDataFromImportRow(array $row, int $rowNumber, array $emailsInFile): array
    {
        $errors = [];
        $name = trim($row['name'] ?? '');
        $email = strtolower(trim($row['email'] ?? ''));
        $password = trim($row['password'] ?? '');
        $group = trim($row['group'] ?? '');

        if ($name === '') {
            $errors[] = "Row {$rowNumber}: name is required.";
        }

        if ($email === '') {
            $errors[] = "Row {$rowNumber}: email is required.";
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$rowNumber}: email must be a valid email address.";
        } elseif (User::query()->where('email', $email)->exists()) {
            $errors[] = "Row {$rowNumber}: email already exists.";
        } elseif (in_array($email, $emailsInFile, true)) {
            $errors[] = "Row {$rowNumber}: email is duplicated in this spreadsheet.";
        }

        if ($password === '') {
            $errors[] = "Row {$rowNumber}: password is required.";
        } elseif (mb_strlen($password) < 8) {
            $errors[] = "Row {$rowNumber}: password must be at least 8 characters.";
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        return [
            'errors' => [],
            'student' => [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'group' => $group === '' ? null : $group,
                'is_active' => $this->booleanFromImport($row['is_active'] ?? 'yes'),
            ],
        ];
    }

    private function booleanFromImport(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'active'], true);
    }

    /**
     * @return array{0: string, 1: string|null, 2: int, 3: View|null}
     */
    private function resolveImportSheet(
        Request $request,
        QuestionImportSpreadsheet $spreadsheet,
        string $fileInput,
        string $view,
    ): array {
        if ($request->filled('import_path')) {
            $temporaryImportPath = $request->string('import_path')->toString();

            if (! str_starts_with($temporaryImportPath, 'imports/') || ! Storage::disk('local')->exists($temporaryImportPath)) {
                abort(422, 'The uploaded spreadsheet is no longer available. Please upload it again.');
            }

            $path = Storage::disk('local')->path($temporaryImportPath);
            $sheets = $spreadsheet->sheets($path);
            $sheetIndex = $request->integer('sheet_index', -1);

            if (! collect($sheets)->pluck('index')->contains($sheetIndex)) {
                return [
                    $path,
                    $temporaryImportPath,
                    0,
                    view($view, [
                        'sheets' => $sheets,
                        'importPath' => $temporaryImportPath,
                        'importFileName' => basename($temporaryImportPath),
                        'sheetError' => 'Choose the worksheet to import.',
                    ]),
                ];
            }

            return [$path, $temporaryImportPath, $sheetIndex, null];
        }

        $uploadedFile = $request->file($fileInput);
        $path = $uploadedFile->getRealPath();
        $sheets = $spreadsheet->sheets($path);

        if (count($sheets) > 1 && ! $request->filled('sheet_index')) {
            $temporaryImportPath = $uploadedFile->store('imports', 'local');

            return [
                Storage::disk('local')->path($temporaryImportPath),
                $temporaryImportPath,
                0,
                view($view, [
                    'sheets' => $sheets,
                    'importPath' => $temporaryImportPath,
                    'importFileName' => $uploadedFile->getClientOriginalName(),
                    'sheetError' => null,
                ]),
            ];
        }

        return [$path, null, (int) ($sheets[0]['index'] ?? 0), null];
    }
}
