<?php

namespace Tests\Feature\Admin;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_results_index(): void
    {
        $admin = User::factory()->admin()->create();
        $attempt = $this->submittedAttempt();

        $this->actingAs($admin)
            ->get(route('admin.results.index'))
            ->assertOk()
            ->assertSee($attempt->user->name)
            ->assertSee($attempt->exam->title)
            ->assertSee('0%');
    }

    public function test_student_cannot_view_admin_results(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.results.index'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_results_by_exam_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $shownAttempt = $this->submittedAttempt(status: 'passed');
        $hiddenAttempt = $this->submittedAttempt(title: 'Hidden Exam', status: 'failed');

        $this->actingAs($admin)
            ->get(route('admin.results.index', [
                'exam_id' => $shownAttempt->exam_id,
                'status' => 'passed',
            ]))
            ->assertOk()
            ->assertSee($shownAttempt->exam->title)
            ->assertDontSee('<td class="px-6 py-4 text-sm text-gray-700">'.$hiddenAttempt->exam->title.'</td>', false);
    }

    public function test_admin_can_view_attempt_details(): void
    {
        $admin = User::factory()->admin()->create();
        $attempt = $this->submittedAttempt();

        $this->actingAs($admin)
            ->get(route('admin.results.show', $attempt))
            ->assertOk()
            ->assertSee($attempt->user->email)
            ->assertSee('Selected Answer')
            ->assertSee('Correct Answer')
            ->assertSee('Incorrect');
    }

    public function test_admin_dashboard_shows_submitted_attempt_count(): void
    {
        $admin = User::factory()->admin()->create();

        $this->submittedAttempt();
        $this->inProgressAttempt();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Submitted Attempts')
            ->assertSee('1');
    }

    private function submittedAttempt(string $title = 'Aptitude Test', string $status = 'failed'): Attempt
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestion($title);
        $question = Question::firstOrFail();
        $wrongOption = $question->options()->where('is_correct', false)->firstOrFail();

        $attempt = Attempt::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => $status === 'passed' ? 1 : 0,
            'total_questions' => 1,
            'percentage' => $status === 'passed' ? 100 : 0,
            'status' => $status,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'submitted_at' => now(),
        ]);

        $attempt->answers()->create([
            'question_id' => $question->id,
            'selected_option_id' => $status === 'passed'
                ? $question->options()->where('is_correct', true)->firstOrFail()->id
                : $wrongOption->id,
            'is_correct' => $status === 'passed',
        ]);

        return $attempt->load('exam', 'user');
    }

    private function inProgressAttempt(): Attempt
    {
        $student = User::factory()->student()->create();
        $exam = $this->examWithQuestion('Practice Test');

        return Attempt::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'total_questions' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    private function examWithQuestion(string $title): Exam
    {
        $category = Category::create([
            'name' => $title.' Category',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => $title,
            'duration_minutes' => 30,
            'total_questions' => 1,
            'pass_mark' => 50,
            'is_randomized' => false,
            'show_corrections' => true,
            'is_active' => true,
        ]);

        $exam->categories()->sync([$category->id]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => $title.' question?',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $question->options()->createMany([
            ['option_text' => 'Correct answer', 'is_correct' => true],
            ['option_text' => 'Wrong answer', 'is_correct' => false],
        ]);

        return $exam;
    }
}
