<?php

namespace Tests\Feature\Student;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Exam;
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

    public function test_student_answer_is_saved_before_final_submit(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();

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

    public function test_expired_attempt_is_finalized_when_opened(): void
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestions();

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

    private function examWithQuestions(int $questionCount = 1, int $totalQuestions = 1, int $passMark = 50): Exam
    {
        $category = Category::create([
            'name' => 'Mathematics',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Aptitude Test',
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
}
