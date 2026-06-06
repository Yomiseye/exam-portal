<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_exams(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.exams.index'))
            ->assertOk();
    }

    public function test_student_cannot_view_exams_admin_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.exams.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_exam_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        Category::create(['name' => 'Mathematics']);

        $this->actingAs($admin)
            ->get(route('admin.exams.create'))
            ->assertOk()
            ->assertSee('Create Exam');
    }

    public function test_admin_can_create_exam_with_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $math = Category::create(['name' => 'Mathematics']);
        $english = Category::create(['name' => 'English']);

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'title' => 'General Aptitude Test',
                'description' => 'Mixed category exam.',
                'duration_minutes' => 30,
                'total_questions' => 20,
                'pass_mark' => 50,
                'is_randomized' => '1',
                'show_corrections' => '1',
                'is_active' => '1',
                'category_ids' => [$math->id, $english->id],
            ])
            ->assertRedirect(route('admin.exams.index'));

        $this->assertDatabaseHas('exams', [
            'title' => 'General Aptitude Test',
            'duration_minutes' => 30,
            'total_questions' => 20,
            'pass_mark' => 50,
            'is_randomized' => true,
            'show_corrections' => true,
            'is_active' => true,
        ]);

        $exam = Exam::where('title', 'General Aptitude Test')->firstOrFail();

        $this->assertSame(
            [$math->id, $english->id],
            $exam->categories()->orderBy('categories.id')->pluck('categories.id')->all(),
        );
    }

    public function test_admin_can_update_exam_and_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $math = Category::create(['name' => 'Mathematics']);
        $english = Category::create(['name' => 'English']);

        $exam = Exam::create([
            'title' => 'Old Exam',
            'duration_minutes' => 20,
            'total_questions' => 10,
            'pass_mark' => 40,
            'is_randomized' => true,
            'show_corrections' => false,
            'is_active' => true,
        ]);
        $exam->categories()->sync([$math->id]);

        $this->actingAs($admin)
            ->put(route('admin.exams.update', $exam), [
                'title' => 'English Test',
                'description' => 'Updated exam.',
                'duration_minutes' => 45,
                'total_questions' => 30,
                'pass_mark' => 60,
                'show_corrections' => '1',
                'category_ids' => [$english->id],
            ])
            ->assertRedirect(route('admin.exams.index'));

        $exam->refresh();

        $this->assertSame('English Test', $exam->title);
        $this->assertSame(45, $exam->duration_minutes);
        $this->assertSame(30, $exam->total_questions);
        $this->assertSame(60, $exam->pass_mark);
        $this->assertFalse($exam->is_randomized);
        $this->assertTrue($exam->show_corrections);
        $this->assertFalse($exam->is_active);
        $this->assertSame([$english->id], $exam->categories()->pluck('categories.id')->all());
    }

    public function test_admin_can_deactivate_exam(): void
    {
        $admin = User::factory()->admin()->create();
        $exam = Exam::create([
            'title' => 'Biology Test',
            'duration_minutes' => 15,
            'total_questions' => 10,
            'pass_mark' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.exams.destroy', $exam))
            ->assertRedirect(route('admin.exams.index'));

        $this->assertFalse($exam->fresh()->is_active);
    }

    public function test_exam_requires_at_least_one_active_category(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = Category::create([
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'title' => 'Invalid Exam',
                'duration_minutes' => 30,
                'total_questions' => 20,
                'pass_mark' => 50,
                'category_ids' => [$inactive->id],
            ])
            ->assertSessionHasErrors('category_ids.0');
    }

    public function test_exam_requires_timing_and_valid_pass_mark(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Programming']);

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'title' => 'Invalid Rules Exam',
                'duration_minutes' => 0,
                'total_questions' => 0,
                'pass_mark' => 101,
                'category_ids' => [$category->id],
            ])
            ->assertSessionHasErrors([
                'duration_minutes',
                'total_questions',
                'pass_mark',
            ]);
    }
}
