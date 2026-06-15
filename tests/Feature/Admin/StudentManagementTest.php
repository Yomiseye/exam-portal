<?php

namespace Tests\Feature\Admin;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Exam;
use App\Models\ExamRetakePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_student(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.students.store'), [
                'name' => 'Jane Student',
                'email' => 'jane@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
            'role' => 'student',
        ]);
    }

    public function test_student_cannot_access_admin_student_management(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_exam_to_student_with_availability_period(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $exam = $this->exam();

        $this->actingAs($admin)
            ->post(route('admin.students.assignments.store', $student), [
                'exam_id' => $exam->id,
                'available_from' => now()->addHour()->format('Y-m-d H:i:s'),
                'available_until' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_assignments', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'assigned_by' => $admin->id,
        ]);
    }

    public function test_assignment_requires_end_after_start(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $exam = $this->exam();

        $this->actingAs($admin)
            ->post(route('admin.students.assignments.store', $student), [
                'exam_id' => $exam->id,
                'available_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'available_until' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('available_until');
    }

    public function test_admin_can_clear_student_history_without_deleting_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $exam = $this->exam();

        $attempt = Attempt::create([
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

        ExamRetakePermission::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'granted_by' => $admin->id,
            'used_attempt_id' => $attempt->id,
            'used_at' => now(),
            'reason' => 'Reset history.',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.students.clear-history', $student))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email' => $student->email,
            'role' => 'student',
        ]);
        $this->assertDatabaseMissing('attempts', [
            'id' => $attempt->id,
        ]);
        $this->assertDatabaseMissing('exam_retake_permissions', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    private function exam(): Exam
    {
        $category = Category::create([
            'name' => 'Mathematics',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Assigned Exam',
            'duration_minutes' => 30,
            'total_questions' => 1,
            'pass_mark' => 50,
            'is_randomized' => false,
            'show_corrections' => true,
            'is_active' => true,
        ]);

        $exam->categories()->sync([$category->id]);

        return $exam;
    }
}
