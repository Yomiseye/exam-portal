<?php

namespace Tests\Feature\Student;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamRetakePermission;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTakingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_active_exams_on_dashboard(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($exam->title);
    }

    public function test_admin_cannot_start_student_exam(): void
    {
        $admin = User::factory()->admin()->create();
        $exam = $this->examWithQuestions();

        $this->actingAs($admin)
            ->post(route('student.exams.start', $exam))
            ->assertForbidden();
    }

    public function test_student_can_start_exam_attempt(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertRedirect();

        $attempt = Attempt::firstOrFail();

        $this->assertSame($student->id, $attempt->user_id);
        $this->assertSame($exam->id, $attempt->exam_id);
        $this->assertSame('in_progress', $attempt->status);
        $this->assertNotNull($attempt->expires_at);
        $this->assertSame(2, $attempt->answers()->count());
    }

    public function test_attempt_page_uses_single_question_navigation(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::firstOrFail();

        $this->actingAs($student)
            ->get(route('student.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('Question 1 of 2')
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Submit Exam');
    }

    public function test_student_resumes_existing_in_progress_attempt(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::firstOrFail();

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.attempts.show', $attempt));

        $this->assertDatabaseCount('attempts', 1);
    }

    public function test_student_cannot_start_exam_without_enough_questions(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 1, totalQuestions: 2);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->from(route('student.exams.show', $exam))
            ->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.show', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('attempts', 0);
    }

    public function test_student_can_submit_exam_and_view_score(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2, passMark: 50);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();

        $answers = [];

        foreach ($attempt->answers as $index => $answer) {
            $answers[$answer->question_id] = $answer->question
                ->options
                ->firstWhere('is_correct', $index === 0)
                ->id;
        }

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => $answers,
            ])
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame(50, $attempt->percentage);
        $this->assertSame('passed', $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_student_cannot_start_completed_exam_without_retake_permission(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        $attempt = Attempt::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => 1,
            'total_questions' => 1,
            'percentage' => 100,
            'status' => 'passed',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'submitted_at' => now(),
        ]);

        $attempt->answers()->create([
            'question_id' => Question::firstOrFail()->id,
            'selected_option_id' => Question::firstOrFail()->options()->where('is_correct', true)->firstOrFail()->id,
            'is_correct' => true,
        ]);

        $this->actingAs($student)
            ->from(route('student.exams.show', $exam))
            ->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.show', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('attempts', 1);
    }

    public function test_student_can_start_retake_with_unused_permission_and_permission_is_consumed(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        Attempt::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => 0,
            'total_questions' => 1,
            'percentage' => 0,
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'submitted_at' => now(),
        ]);

        $permission = ExamRetakePermission::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'granted_by' => $admin->id,
            'reason' => 'Network interruption.',
        ]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertRedirect();

        $permission->refresh();

        $this->assertNotNull($permission->used_at);
        $this->assertNotNull($permission->used_attempt_id);
        $this->assertSame(2, Attempt::count());
        $this->assertSame('in_progress', Attempt::latest('id')->firstOrFail()->status);
    }

    public function test_student_answer_is_saved_before_final_submit(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();
        $option = $answer->question->options->firstWhere('is_correct', true);

        $this->actingAs($student)
            ->patchJson(route('student.attempts.answers.save', $attempt), [
                'question_id' => $answer->question_id,
                'option_id' => $option->id,
            ])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertSame($option->id, $answer->fresh()->selected_option_id);
    }

    public function test_student_can_submit_exam_using_previously_saved_answers(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2, passMark: 50);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();

        foreach ($attempt->answers as $index => $answer) {
            $option = $answer->question
                ->options
                ->firstWhere('is_correct', $index === 0);

            $this->actingAs($student)
                ->patchJson(route('student.attempts.answers.save', $attempt), [
                    'question_id' => $answer->question_id,
                    'option_id' => $option->id,
                ])
                ->assertOk();
        }

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt))
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame(50, $attempt->percentage);
        $this->assertSame('passed', $attempt->status);
    }

    public function test_student_cannot_save_answer_for_another_students_attempt(): void
    {
        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($owner, $exam);

        $this->actingAs($owner)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();
        $option = $answer->question->options->first();

        $this->actingAs($otherStudent)
            ->patchJson(route('student.attempts.answers.save', $attempt), [
                'question_id' => $answer->question_id,
                'option_id' => $option->id,
            ])
            ->assertForbidden();

        $this->assertNull($answer->fresh()->selected_option_id);
    }

    public function test_student_must_answer_all_questions_before_time_expires(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();

        $this->actingAs($student)
            ->from(route('student.attempts.show', $attempt))
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [
                    $answer->question_id => $answer->question->options->firstWhere('is_correct', true)->id,
                ],
            ])
            ->assertRedirect(route('student.attempts.show', $attempt))
            ->assertSessionHasErrors("answers.{$attempt->answers->last()->question_id}");

        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_expired_attempt_can_submit_unanswered_questions_as_incorrect(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(questionCount: 2, totalQuestions: 2, passMark: 60);
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();

        $this->travel(31)->minutes();

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [
                    $answer->question_id => $answer->question->options->firstWhere('is_correct', true)->id,
                ],
            ])
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame(50, $attempt->percentage);
        $this->assertSame('failed', $attempt->status);
    }

    public function test_student_can_submit_multiple_correct_question(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(passMark: 100);
        $this->assignExam($student, $exam);

        $question = Question::firstOrFail();
        $question->update(['question_type' => Question::TYPE_MULTIPLE_CHOICE]);
        $question->options()->delete();
        $question->options()->createMany([
            ['option_text' => 'Solid', 'is_correct' => true],
            ['option_text' => 'Stone', 'is_correct' => false],
            ['option_text' => 'Liquid', 'is_correct' => true],
        ]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();
        $correctOptionIds = $answer->question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->values()
            ->all();

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [
                    $answer->question_id => $correctOptionIds,
                ],
            ])
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();
        $answer->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame('passed', $attempt->status);
        $this->assertEqualsCanonicalizing($correctOptionIds, $answer->selected_option_ids);
    }

    public function test_student_can_submit_true_false_question(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(passMark: 100);
        $this->assignExam($student, $exam);

        $question = Question::firstOrFail();
        $question->update(['question_type' => Question::TYPE_TRUE_FALSE]);
        $question->options()->delete();
        $true = $question->options()->create(['option_text' => 'True', 'is_correct' => true]);
        $question->options()->create(['option_text' => 'False', 'is_correct' => false]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [
                    $answer->question_id => $true->id,
                ],
            ])
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame('passed', $attempt->status);
    }

    public function test_student_can_submit_matching_question(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions(passMark: 100);
        $this->assignExam($student, $exam);

        $question = Question::firstOrFail();
        $question->update(['question_type' => Question::TYPE_MATCHING]);
        $question->options()->delete();
        $nigeria = $question->options()->create(['option_text' => 'Nigeria', 'match_text' => 'Abuja', 'is_correct' => true]);
        $ghana = $question->options()->create(['option_text' => 'Ghana', 'match_text' => 'Accra', 'is_correct' => true]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::with('answers.question.options')->firstOrFail();
        $answer = $attempt->answers->first();

        $this->actingAs($student)
            ->post(route('student.attempts.submit', $attempt), [
                'answers' => [
                    $answer->question_id => [
                        $nigeria->id => 'Abuja',
                        $ghana->id => 'Accra',
                    ],
                ],
            ])
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();
        $answer->refresh();

        $this->assertSame(1, $attempt->score);
        $this->assertSame('passed', $attempt->status);
        $this->assertSame('Abuja', $answer->matching_answer[$nigeria->id]);
    }

    public function test_expired_attempt_is_finalized_when_opened(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();
        $this->assignExam($student, $exam);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam));

        $attempt = Attempt::firstOrFail();

        $this->travel(31)->minutes();

        $this->actingAs($student)
            ->get(route('student.attempts.show', $attempt))
            ->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();

        $this->assertSame(0, $attempt->score);
        $this->assertSame('failed', $attempt->status);
    }

    public function test_student_cannot_view_another_students_attempt(): void
    {
        $owner = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $exam = $this->examWithQuestions();

        $attempt = Attempt::create([
            'user_id' => $owner->id,
            'exam_id' => $exam->id,
            'total_questions' => 1,
            'started_at' => now(),
        ]);

        $this->actingAs($otherStudent)
            ->get(route('student.attempts.show', $attempt))
            ->assertForbidden();
    }

    public function test_student_only_sees_assigned_exams_on_dashboard(): void
    {
        $student = User::factory()->student()->create();
        $assignedExam = $this->examWithQuestions();
        $unassignedExam = $this->examWithQuestions();

        $this->assignExam($student, $assignedExam);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($assignedExam->title)
            ->assertDontSee($unassignedExam->title);
    }

    public function test_student_cannot_start_exam_outside_assignment_window(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();

        $this->assignExam(
            student: $student,
            exam: $exam,
            availableFrom: now()->addDay(),
            availableUntil: now()->addDays(2),
        );

        $this->actingAs($student)
            ->from(route('student.exams.show', $exam))
            ->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.show', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('attempts', 0);
    }

    private function examWithQuestions(int $questionCount = 1, int $totalQuestions = 1, int $passMark = 50): Exam
    {
        $suffix = (string) str()->uuid();

        $category = Category::create([
            'name' => 'Mathematics '.$suffix,
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Aptitude Test '.$suffix,
            'duration_minutes' => 30,
            'total_questions' => $totalQuestions,
            'pass_mark' => $passMark,
            'is_randomized' => false,
            'show_corrections' => true,
            'is_active' => true,
        ]);

        $exam->categories()->sync([$category->id]);

        foreach (range(1, $questionCount) as $number) {
            $question = Question::create([
                'category_id' => $category->id,
                'question_text' => "Question {$number}?",
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'is_active' => true,
            ]);

            $question->options()->createMany([
                ['option_text' => 'Correct answer', 'is_correct' => true],
                ['option_text' => 'Wrong answer', 'is_correct' => false],
            ]);
        }

        return $exam;
    }

    private function assignExam(
        User $student,
        Exam $exam,
        $availableFrom = null,
        $availableUntil = null,
    ): ExamAssignment {
        $admin = User::factory()->admin()->create();

        return ExamAssignment::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'assigned_by' => $admin->id,
            'available_from' => $availableFrom ?? now()->subMinute(),
            'available_until' => $availableUntil ?? now()->addDay(),
        ]);
    }
}
