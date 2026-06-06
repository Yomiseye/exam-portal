<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Show exam instructions before the student starts.
     */
    public function show(Exam $exam): View
    {
        abort_unless($exam->is_active, 404);

        $exam->load('categories');

        return view('student.exams.show', compact('exam'));
    }

    /**
     * Create an in-progress attempt and choose the exam questions.
     */
    public function start(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless($exam->is_active, 404);

        $activeAttempt = $request->user()
            ->attempts()
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($activeAttempt && ! $activeAttempt->isExpired()) {
            return redirect()->route('student.attempts.show', $activeAttempt);
        }

        if ($activeAttempt && $activeAttempt->isExpired()) {
            $this->finalizeAttempt($activeAttempt);

            return redirect()->route('student.attempts.result', $activeAttempt);
        }

        $questions = $this->availableQuestions($exam);

        if ($questions->count() < $exam->total_questions) {
            return back()->withErrors([
                'exam' => 'This exam does not have enough active questions yet.',
            ]);
        }

        $selectedQuestions = $questions->take($exam->total_questions);

        $attempt = DB::transaction(function () use ($request, $exam, $selectedQuestions): Attempt {
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

        $attempt->load([
            'exam',
            'answers.question.options' => fn ($query) => $query->inRandomOrder(),
        ]);

        return view('student.exams.attempt', compact('attempt'));
    }

    /**
     * Persist one selected answer while the attempt is still in progress.
     */
    public function saveAnswer(Request $request, Attempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        if ($attempt->isExpired()) {
            $this->finalizeAttempt($attempt);

            return response()->json([
                'message' => 'This attempt has expired and was submitted.',
                'redirect' => route('student.attempts.result', $attempt),
            ], 409);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['required', 'integer'],
        ]);

        $answer = $attempt->answers()
            ->with('question.options')
            ->where('question_id', $validated['question_id'])
            ->firstOrFail();

        abort_unless(
            $answer->question->options->contains(fn (Option $option) => $option->id === (int) $validated['option_id']),
            422,
        );

        $answer->update([
            'selected_option_id' => (int) $validated['option_id'],
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

        $attempt->load('answers.question.options', 'exam');

        $isExpired = $attempt->isExpired();

        $validator = Validator::make($request->all(), [
            'answers' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($request, $attempt, $isExpired): void {
            foreach ($attempt->answers as $answer) {
                $selectedOptionId = $this->submittedOrSavedOptionId($request, $answer);

                if (! $selectedOptionId) {
                    if (! $isExpired) {
                        $validator->errors()->add("answers.{$answer->question_id}", 'Choose an answer.');
                    }

                    continue;
                }

                $belongsToQuestion = $answer->question
                    ->options
                    ->contains(fn (Option $option) => $option->id === (int) $selectedOptionId);

                if (! $belongsToQuestion) {
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
     * @param  array<int|string, int|string>  $submittedAnswers
     */
    private function finalizeAttempt(Attempt $attempt, array $submittedAnswers = []): void
    {
        $attempt->loadMissing('answers.question.options', 'exam');

        DB::transaction(function () use ($attempt, $submittedAnswers): void {
            $score = 0;

            foreach ($attempt->answers as $answer) {
                $selectedOptionId = $submittedAnswers[$answer->question_id] ?? $answer->selected_option_id;
                $selectedOption = $answer->question
                    ->options
                    ->firstWhere('id', (int) $selectedOptionId);

                $isCorrect = (bool) $selectedOption?->is_correct;
                $score += $isCorrect ? 1 : 0;

                $answer->update([
                    'selected_option_id' => $selectedOption?->id,
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
        $categoryIds = $exam->categories()->pluck('categories.id');

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

    private function submittedOrSavedOptionId(Request $request, AttemptAnswer $answer): mixed
    {
        return $request->input("answers.{$answer->question_id}", $answer->selected_option_id);
    }
}
