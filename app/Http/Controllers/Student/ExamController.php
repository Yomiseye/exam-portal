<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamRetakePermission;
use App\Models\GroupExamAssignment;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Show exam instructions before the student starts.
     */
    public function show(Exam $exam): View
    {
        abort_unless($exam->is_active, 404);

        $exam->load('categories.parent');

        $assignment = $this->assignment(request(), $exam);
        abort_unless($assignment, 404);

        $activeAttempt = $this->activeAttempt(request(), $exam);
        $latestCompletedAttempt = $this->latestCompletedAttempt(request(), $exam);
        $unusedRetakePermission = $this->unusedRetakePermission(request(), $exam);

        return view('student.exams.show', compact(
            'exam',
            'assignment',
            'activeAttempt',
            'latestCompletedAttempt',
            'unusedRetakePermission',
        ));
    }

    /**
     * Create an in-progress attempt and choose the exam questions.
     */
    public function start(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless($exam->is_active, 404);

        $assignment = $this->assignment($request, $exam);
        abort_unless($assignment, 404);

        $activeAttempt = $this->activeAttempt($request, $exam);

        if ($activeAttempt && ! $activeAttempt->isExpired()) {
            return redirect()->route('student.attempts.show', $activeAttempt);
        }

        if ($activeAttempt && $activeAttempt->isExpired()) {
            $this->finalizeAttempt($activeAttempt);

            return redirect()->route('student.attempts.result', $activeAttempt);
        }

        if (! $assignment->isAvailable()) {
            return back()->withErrors([
                'exam' => 'This exam is not available for your account at this time.',
            ]);
        }

        $latestCompletedAttempt = $this->latestCompletedAttempt($request, $exam);
        $unusedRetakePermission = $this->unusedRetakePermission($request, $exam);

        if ($latestCompletedAttempt && ! $unusedRetakePermission) {
            return back()->withErrors([
                'exam' => 'You have already completed this exam. Ask an admin to grant a retake if you need another attempt.',
            ]);
        }

        $questions = $this->availableQuestions($exam);

        if ($questions->count() < $exam->total_questions) {
            return back()->withErrors([
                'exam' => 'This exam does not have enough active questions yet.',
            ]);
        }

        $selectedQuestions = $questions->take($exam->total_questions);

        $attempt = DB::transaction(function () use ($request, $exam, $selectedQuestions, $unusedRetakePermission): Attempt {
            $attempt = Attempt::create([
                'user_id' => $request->user()->id,
                'exam_id' => $exam->id,
                'total_questions' => $selectedQuestions->count(),
                'started_at' => now(),
                'expires_at' => now()->addMinutes($exam->duration_minutes),
            ]);

            $attempt->answers()->createMany(
                $selectedQuestions
                    ->map(fn (Question $question) => ['question_id' => $question->id])
                    ->all(),
            );

            if ($unusedRetakePermission) {
                $unusedRetakePermission->update([
                    'used_attempt_id' => $attempt->id,
                    'used_at' => now(),
                ]);
            }

            return $attempt;
        });

        return redirect()->route('student.attempts.show', $attempt);
    }

    /**
     * Show the active attempt answer form.
     */
    public function attempt(Attempt $attempt): View|RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.attempts.result', $attempt);
        }

        if ($attempt->isExpired()) {
            $this->finalizeAttempt($attempt);

            return redirect()->route('student.attempts.result', $attempt);
        }

        if ($attempt->isPaused()) {
            $attempt->resume();
            $attempt->refresh();
        }

        $attempt->load([
            'exam',
            'answers.question.options' => fn ($query) => $query->inRandomOrder(),
        ]);

        return view('student.exams.attempt', compact('attempt'));
    }

    /**
     * Pause an active attempt when the exam permits it.
     */
    public function pause(Attempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        $attempt->load('exam');

        if (! $attempt->exam->allow_pause) {
            return back()->withErrors([
                'exam' => 'This exam cannot be paused.',
            ]);
        }

        if ($attempt->isExpired()) {
            $this->finalizeAttempt($attempt);

            return redirect()->route('student.attempts.result', $attempt);
        }

        $attempt->pause();

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Exam paused. You can resume it from your dashboard.');
    }

    /**
     * Persist one selected answer while the attempt is still in progress.
     */
    public function saveAnswer(Request $request, Attempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        if ($attempt->isPaused()) {
            return response()->json([
                'message' => 'This attempt is paused.',
                'redirect' => route('student.dashboard'),
            ], 409);
        }

        if ($attempt->isExpired()) {
            $this->finalizeAttempt($attempt);

            return response()->json([
                'message' => 'This attempt has expired and was submitted.',
                'redirect' => route('student.attempts.result', $attempt),
            ], 409);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
        ]);

        $answer = $attempt->answers()
            ->with('question.options')
            ->where('question_id', $validated['question_id'])
            ->firstOrFail();

        $value = $request->has('answer')
            ? $request->input('answer')
            : $request->input('option_id');

        if (! $this->answerValueIsValid($answer, $value, false)) {
            throw ValidationException::withMessages([
                'answer' => 'Choose a valid answer.',
            ]);
        }

        $this->persistAnswer($answer, $value);

        return response()->json(['saved' => true]);
    }

    /**
     * Persist the question the student is currently viewing.
     */
    public function saveProgress(Request $request, Attempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        if ($attempt->isPaused()) {
            return response()->json([
                'message' => 'This attempt is paused.',
                'redirect' => route('student.dashboard'),
            ], 409);
        }

        $validated = $request->validate([
            'current_question_index' => ['required', 'integer', 'min:0'],
        ]);

        $answerCount = $attempt->answers()->count();
        $lastIndex = max(0, $answerCount - 1);

        $attempt->update([
            'current_question_index' => min($validated['current_question_index'], $lastIndex),
        ]);

        return response()->json(['saved' => true]);
    }

    /**
     * Submit answers and calculate the score on the backend.
     */
    public function submit(Request $request, Attempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        if ($attempt->isPaused()) {
            $attempt->resume();
            $attempt->refresh();
        }

        $attempt->load('answers.question.options', 'exam');

        $isExpired = $attempt->isExpired();

        $validator = Validator::make($request->all(), [
            'answers' => ['nullable', 'array'],
            'submit_unanswered' => ['nullable', 'boolean'],
        ]);

        $submitUnanswered = $request->input('submit_unanswered') === '1'
            || $request->boolean('submit_unanswered');

        $validator->after(function ($validator) use ($request, $attempt, $isExpired, $submitUnanswered): void {
            foreach ($attempt->answers as $answer) {
                $value = $this->submittedOrSavedAnswer($request, $answer);

                if (! $this->answerIsComplete($answer, $value)) {
                    if (! $isExpired && ! $submitUnanswered) {
                        $validator->errors()->add("answers.{$answer->question_id}", 'Choose an answer.');
                    }

                    continue;
                }

                if (! $this->answerValueIsValid($answer, $value, true)) {
                    $validator->errors()->add("answers.{$answer->question_id}", 'Choose a valid answer.');
                }
            }
        });

        $validator->validate();

        $this->finalizeAttempt($attempt, $request->input('answers', []));

        return redirect()->route('student.attempts.result', $attempt);
    }

    /**
     * Show the submitted attempt result.
     */
    public function result(Attempt $attempt): View
    {
        $this->authorizeAttempt($attempt);

        abort_if($attempt->status === 'in_progress', 404);

        $attempt->load([
            'exam',
            'answers.selectedOption',
            'answers.question.options',
        ]);

        return view('student.exams.result', compact('attempt'));
    }

    private function authorizeAttempt(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === request()->user()->id, 403);
    }

    /**
     * Persist submitted answers and close the attempt.
     *
     * @param  array<int|string, mixed>  $submittedAnswers
     */
    private function finalizeAttempt(Attempt $attempt, array $submittedAnswers = []): void
    {
        $attempt->loadMissing('answers.question.options', 'exam');

        DB::transaction(function () use ($attempt, $submittedAnswers): void {
            $score = 0;

            foreach ($attempt->answers as $answer) {
                $value = array_key_exists($answer->question_id, $submittedAnswers)
                    ? $submittedAnswers[$answer->question_id]
                    : $this->storedAnswerValue($answer);

                $this->persistAnswer($answer, $value);

                $answer->refresh();

                $isCorrect = $this->answerIsCorrect($answer);
                $score += $isCorrect ? 1 : 0;

                $answer->update([
                    'is_correct' => $isCorrect,
                ]);
            }

            $percentage = (int) round(($score / $attempt->total_questions) * 100);

            $attempt->update([
                'score' => $score,
                'percentage' => $percentage,
                'status' => $percentage >= $attempt->exam->pass_mark ? 'passed' : 'failed',
                'submitted_at' => now(),
            ]);
        });
    }

    private function availableQuestions(Exam $exam)
    {
        $categoryIds = $exam->categories()
            ->with('subcategories')
            ->get()
            ->flatMap(fn ($category) => array_merge(
                [$category->id],
                $category->subcategories->pluck('id')->all(),
            ))
            ->unique()
            ->values();

        $query = Question::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->whereHas('options', fn ($query) => $query->where('is_correct', true))
            ->with('options');

        if ($exam->is_randomized) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    private function submittedOrSavedAnswer(Request $request, AttemptAnswer $answer): mixed
    {
        return $request->has("answers.{$answer->question_id}")
            ? $request->input("answers.{$answer->question_id}")
            : $this->storedAnswerValue($answer);
    }

    private function storedAnswerValue(AttemptAnswer $answer): mixed
    {
        return match ($answer->question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => $answer->selected_option_ids ?? [],
            Question::TYPE_MATCHING => $answer->matching_answer ?? [],
            default => $answer->selected_option_id,
        };
    }

    private function answerIsComplete(AttemptAnswer $answer, mixed $value): bool
    {
        return match ($answer->question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => is_array($value) && $this->optionIds($value) !== [],
            Question::TYPE_MATCHING => $this->matchingAnswerIsComplete($answer, $value),
            default => is_numeric($value),
        };
    }

    private function matchingAnswerIsComplete(AttemptAnswer $answer, mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($answer->question->options as $option) {
            if (blank($value[$option->id] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function answerValueIsValid(AttemptAnswer $answer, mixed $value, bool $requireComplete): bool
    {
        $optionIds = $answer->question->options->pluck('id')->map(fn ($id) => (int) $id)->all();

        return match ($answer->question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => $this->selectedOptionIdsAreValid($value, $optionIds, $requireComplete),
            Question::TYPE_MATCHING => $this->matchingAnswerIsValid($value, $optionIds, $requireComplete),
            default => is_numeric($value) && in_array((int) $value, $optionIds, true),
        };
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function selectedOptionIdsAreValid(mixed $value, array $optionIds, bool $requireComplete): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $selectedIds = $this->optionIds($value);

        if ($requireComplete && $selectedIds === []) {
            return false;
        }

        return collect($selectedIds)->every(fn (int $id) => in_array($id, $optionIds, true));
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function matchingAnswerIsValid(mixed $value, array $optionIds, bool $requireComplete): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $optionId => $matchText) {
            if (! in_array((int) $optionId, $optionIds, true)) {
                return false;
            }

            if ($requireComplete && blank($matchText)) {
                return false;
            }
        }

        return ! $requireComplete || count($value) >= count($optionIds);
    }

    private function persistAnswer(AttemptAnswer $answer, mixed $value): void
    {
        match ($answer->question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => $answer->update([
                'selected_option_id' => null,
                'selected_option_ids' => $this->optionIds(is_array($value) ? $value : []),
                'matching_answer' => null,
            ]),
            Question::TYPE_MATCHING => $answer->update([
                'selected_option_id' => null,
                'selected_option_ids' => null,
                'matching_answer' => $this->matchingAnswer($answer, is_array($value) ? $value : []),
            ]),
            default => $answer->update([
                'selected_option_id' => is_numeric($value) ? (int) $value : null,
                'selected_option_ids' => null,
                'matching_answer' => null,
            ]),
        };
    }

    private function answerIsCorrect(AttemptAnswer $answer): bool
    {
        return match ($answer->question->question_type) {
            Question::TYPE_MULTIPLE_CHOICE => $this->multipleChoiceAnswerIsCorrect($answer),
            Question::TYPE_MATCHING => $this->matchingAnswerIsCorrect($answer),
            default => (bool) $answer->question->options->firstWhere('id', $answer->selected_option_id)?->is_correct,
        };
    }

    private function multipleChoiceAnswerIsCorrect(AttemptAnswer $answer): bool
    {
        $selectedIds = $this->optionIds($answer->selected_option_ids ?? []);
        $correctIds = $answer->question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        sort($selectedIds);

        return $selectedIds === $correctIds;
    }

    private function matchingAnswerIsCorrect(AttemptAnswer $answer): bool
    {
        $submitted = $answer->matching_answer ?? [];

        foreach ($answer->question->options as $option) {
            if ($this->normalizedMatchText($submitted[$option->id] ?? null) !== $this->normalizedMatchText($option->match_text)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, int>
     */
    private function optionIds(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private function matchingAnswer(AttemptAnswer $answer, array $value): array
    {
        return $answer->question->options
            ->mapWithKeys(fn (Option $option) => [
                $option->id => trim((string) ($value[$option->id] ?? '')),
            ])
            ->all();
    }

    private function normalizedMatchText(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function activeAttempt(Request $request, Exam $exam): ?Attempt
    {
        return $request->user()
            ->attempts()
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();
    }

    private function latestCompletedAttempt(Request $request, Exam $exam): ?Attempt
    {
        return $request->user()
            ->attempts()
            ->where('exam_id', $exam->id)
            ->whereIn('status', ['passed', 'failed'])
            ->latest()
            ->first();
    }

    private function unusedRetakePermission(Request $request, Exam $exam): ?ExamRetakePermission
    {
        return $request->user()
            ->retakePermissions()
            ->where('exam_id', $exam->id)
            ->whereNull('used_at')
            ->oldest()
            ->first();
    }

    private function assignment(Request $request, Exam $exam): ExamAssignment|GroupExamAssignment|null
    {
        $directAssignment = $request->user()
            ->examAssignments()
            ->where('exam_id', $exam->id)
            ->first();

        $group = $request->user()->studentGroup;
        $groupAssignment = null;

        if ($group?->is_active) {
            $groupAssignment = $group
                ->examAssignments()
                ->where('exam_id', $exam->id)
                ->first();
        }

        return match (true) {
            $directAssignment?->isAvailable() => $directAssignment,
            $groupAssignment?->isAvailable() => $groupAssignment,
            default => $directAssignment ?? $groupAssignment,
        };
    }
}
