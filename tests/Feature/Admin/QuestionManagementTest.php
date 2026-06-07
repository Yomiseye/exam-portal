<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_questions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.questions.index'))
            ->assertOk();
    }

    public function test_student_cannot_view_questions(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.questions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_question_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        Category::create(['name' => 'Mathematics']);

        $this->actingAs($admin)
            ->get(route('admin.questions.create'))
            ->assertOk()
            ->assertSee('Create Question');
    }

    public function test_admin_can_view_question_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'English']);
        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Choose the noun.',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['option_text' => 'Quickly', 'is_correct' => false],
            ['option_text' => 'Lagos', 'is_correct' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertSee('Edit Question');
    }

    public function test_admin_can_create_question_with_options(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Mathematics']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'What is 2 + 2?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'explanation' => '2 plus 2 equals 4.',
                'difficulty' => 'easy',
                'is_active' => '1',
                'correct_option' => '2',
                'options' => [
                    ['text' => '2'],
                    ['text' => '3'],
                    ['text' => '4'],
                    ['text' => '5'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $this->assertDatabaseHas('questions', [
            'category_id' => $category->id,
            'question_text' => 'What is 2 + 2?',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $question = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();

        $this->assertDatabaseHas('options', [
            'question_id' => $question->id,
            'option_text' => '4',
            'is_correct' => true,
        ]);

        $this->assertSame(4, $question->options()->count());
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_admin_can_update_question_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'English']);
        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Old question?',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['option_text' => 'Old A', 'is_correct' => true],
            ['option_text' => 'Old B', 'is_correct' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'category_id' => $category->id,
                'question_text' => 'Choose the noun.',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'explanation' => 'A noun names a person, place, or thing.',
                'difficulty' => 'medium',
                'correct_option' => '1',
                'options' => [
                    ['text' => 'Quickly'],
                    ['text' => 'Lagos'],
                    ['text' => 'Run'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question->refresh();

        $this->assertSame('Choose the noun.', $question->question_text);
        $this->assertSame('medium', $question->difficulty);
        $this->assertFalse($question->is_active);
        $this->assertSame(3, $question->options()->count());
        $this->assertTrue($question->options()->where('option_text', 'Lagos')->firstOrFail()->is_correct);
    }

    public function test_admin_can_deactivate_question(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Biology']);
        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'What is a cell?',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.questions.destroy', $question))
            ->assertRedirect(route('admin.questions.index'));

        $this->assertFalse($question->fresh()->is_active);
    }

    public function test_question_requires_at_least_two_options(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Programming']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'What does PHP stand for?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'PHP: Hypertext Preprocessor'],
                ],
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_question_requires_correct_option_to_match_an_option(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'General Knowledge']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Which planet is known as the red planet?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '9',
                'options' => [
                    ['text' => 'Earth'],
                    ['text' => 'Mars'],
                ],
            ])
            ->assertSessionHasErrors('correct_option');
    }

    public function test_question_requires_active_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Should fail?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'Yes'],
                    ['text' => 'No'],
                ],
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_admin_can_create_multiple_correct_question(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Science']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Which are states of matter?',
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'difficulty' => 'easy',
                'correct_options' => ['0', '2'],
                'options' => [
                    ['text' => 'Solid'],
                    ['text' => 'Stone'],
                    ['text' => 'Liquid'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('question_text', 'Which are states of matter?')->firstOrFail();

        $this->assertSame(Question::TYPE_MULTIPLE_CHOICE, $question->question_type);
        $this->assertSame(2, $question->options()->where('is_correct', true)->count());
    }

    public function test_admin_can_create_true_false_question(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Civics']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Lagos is in Nigeria.',
                'question_type' => Question::TYPE_TRUE_FALSE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'True'],
                    ['text' => 'False'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('question_text', 'Lagos is in Nigeria.')->firstOrFail();

        $this->assertSame(Question::TYPE_TRUE_FALSE, $question->question_type);
        $this->assertTrue($question->options()->where('option_text', 'True')->firstOrFail()->is_correct);
    }

    public function test_admin_can_create_matching_question(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Geography']);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Match the countries to capitals.',
                'question_type' => Question::TYPE_MATCHING,
                'difficulty' => 'medium',
                'options' => [
                    ['text' => 'Nigeria', 'match_text' => 'Abuja'],
                    ['text' => 'Ghana', 'match_text' => 'Accra'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('question_text', 'Match the countries to capitals.')->firstOrFail();

        $this->assertSame(Question::TYPE_MATCHING, $question->question_type);
        $this->assertSame('Abuja', $question->options()->where('option_text', 'Nigeria')->firstOrFail()->match_text);
    }
}
