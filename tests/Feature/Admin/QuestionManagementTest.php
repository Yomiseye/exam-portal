<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use App\Services\QuestionImportSpreadsheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

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

        $this->categoryPair('Mathematics', 'Algebra');

        $this->actingAs($admin)
            ->get(route('admin.questions.create'))
            ->assertOk()
            ->assertSee('Create Question');
    }

    public function test_admin_can_view_question_import_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->categoryPair('Mathematics', 'Algebra');

        $this->actingAs($admin)
            ->get(route('admin.questions.import'))
            ->assertOk()
            ->assertSee('Import Questions')
            ->assertSee('Excel Format');
    }

    public function test_admin_can_view_question_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('English', 'Grammar');
        $question = Question::create([
            'category_id' => $subcategory->id,
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
        [$category, $subcategory] = $this->categoryPair('Mathematics', 'Algebra');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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
            'category_id' => $subcategory->id,
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

    public function test_admin_can_create_question_with_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Science', 'Diagrams');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'What does the diagram show?',
                'image' => UploadedFile::fake()->image('diagram.png')->size(512),
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'A cell'],
                    ['text' => 'A planet'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('question_text', 'What does the diagram show?')->firstOrFail();

        $this->assertNotNull($question->image_path);
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_admin_can_create_question_with_explanation_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Science', 'Explanations');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'What does the explanation diagram show?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'explanation' => 'Use the diagram to review the answer.',
                'explanation_image' => UploadedFile::fake()->image('explanation.png')->size(512),
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'A cell'],
                    ['text' => 'A planet'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question = Question::where('question_text', 'What does the explanation diagram show?')->firstOrFail();

        $this->assertNotNull($question->explanation_image_path);
        Storage::disk('public')->assertExists($question->explanation_image_path);
    }

    public function test_admin_can_remove_question_explanation_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Biology', 'Explanations');
        Storage::disk('public')->put('question-images/explanation-remove.png', 'image');

        $question = Question::create([
            'category_id' => $subcategory->id,
            'question_text' => 'Remove explanation image question?',
            'question_type' => Question::TYPE_SINGLE_CHOICE,
            'explanation' => 'Explanation with image.',
            'explanation_image_path' => 'question-images/explanation-remove.png',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'Remove explanation image question?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'explanation' => 'Explanation with image.',
                'remove_explanation_image' => '1',
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'A'],
                    ['text' => 'B'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $this->assertNull($question->fresh()->explanation_image_path);
        Storage::disk('public')->assertMissing('question-images/explanation-remove.png');
    }

    public function test_admin_can_create_question_under_subcategory(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::create(['name' => 'Project Management', 'is_active' => true]);
        $subcategory = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Scope Performance Domain',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $parent->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'What is scope baseline?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'medium',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'Approved scope statement, WBS, and WBS dictionary'],
                    ['text' => 'Only the project budget'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $this->assertDatabaseHas('questions', [
            'category_id' => $subcategory->id,
            'question_text' => 'What is scope baseline?',
        ]);
    }

    public function test_admin_can_create_question_with_new_category_and_new_subcategory(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'new_category_name' => 'Project Management',
                'new_subcategory_name' => 'Stakeholder Performance Domain',
                'question_text' => 'Who should be engaged throughout the project?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'medium',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'Stakeholders'],
                    ['text' => 'Only the project sponsor'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $category = Category::where('name', 'Project Management')->firstOrFail();
        $subcategory = Category::where('name', 'Stakeholder Performance Domain')->firstOrFail();

        $this->assertSame($category->id, $subcategory->parent_id);
        $this->assertDatabaseHas('questions', [
            'category_id' => $subcategory->id,
            'question_text' => 'Who should be engaged throughout the project?',
        ]);
    }

    public function test_admin_can_create_question_with_existing_category_and_new_subcategory(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Project Management',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'new_subcategory_name' => 'Risk Performance Domain',
                'question_text' => 'What does risk management focus on?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'medium',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'Uncertainty and response planning'],
                    ['text' => 'Only procurement paperwork'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $subcategory = Category::where('name', 'Risk Performance Domain')->firstOrFail();

        $this->assertSame($category->id, $subcategory->parent_id);
        $this->assertDatabaseHas('questions', [
            'category_id' => $subcategory->id,
            'question_text' => 'What does risk management focus on?',
        ]);
    }

    public function test_admin_can_update_question_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('English', 'Parts of Speech');
        $question = Question::create([
            'category_id' => $subcategory->id,
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
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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

    public function test_admin_can_replace_question_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Biology', 'Cells');
        Storage::disk('public')->put('question-images/old.png', 'old-image');

        $question = Question::create([
            'category_id' => $subcategory->id,
            'question_text' => 'Old image question?',
            'image_path' => 'question-images/old.png',
            'question_type' => Question::TYPE_SINGLE_CHOICE,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'Updated image question?',
                'image' => UploadedFile::fake()->image('new-image.jpg')->size(256),
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'medium',
                'correct_option' => '1',
                'options' => [
                    ['text' => 'A'],
                    ['text' => 'B'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $question->refresh();

        $this->assertNotSame('question-images/old.png', $question->image_path);
        Storage::disk('public')->assertMissing('question-images/old.png');
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_admin_can_remove_question_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Biology', 'Cells');
        Storage::disk('public')->put('question-images/remove.png', 'image');

        $question = Question::create([
            'category_id' => $subcategory->id,
            'question_text' => 'Remove image question?',
            'image_path' => 'question-images/remove.png',
            'question_type' => Question::TYPE_SINGLE_CHOICE,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'Remove image question?',
                'remove_image' => '1',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'A'],
                    ['text' => 'B'],
                ],
            ])
            ->assertRedirect(route('admin.questions.index'));

        $this->assertNull($question->fresh()->image_path);
        Storage::disk('public')->assertMissing('question-images/remove.png');
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
        [$category, $subcategory] = $this->categoryPair('Programming', 'PHP');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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
        [$category, $subcategory] = $this->categoryPair('General Knowledge', 'Planets');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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
        $subcategory = Category::create([
            'parent_id' => $category->id,
            'name' => 'Inactive Subcategory',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'question_text' => 'Should fail?',
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => 'easy',
                'correct_option' => '0',
                'options' => [
                    ['text' => 'Yes'],
                    ['text' => 'No'],
                ],
            ])
            ->assertSessionHasErrors('parent_category_id');
    }

    public function test_admin_can_create_multiple_correct_question(): void
    {
        $admin = User::factory()->admin()->create();
        [$category, $subcategory] = $this->categoryPair('Science', 'Matter');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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
        [$category, $subcategory] = $this->categoryPair('Civics', 'Nigeria');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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
        [$category, $subcategory] = $this->categoryPair('Geography', 'Capitals');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'parent_category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
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

    public function test_admin_can_import_questions_from_excel(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelUpload([
            ['category', 'subcategory', 'question_type', 'question', 'difficulty', 'option_1', 'option_2', 'option_3', 'correct_answers', 'match_1', 'match_2', 'match_3'],
            ['Mathematics', 'Number Theory', 'multiple_choice', 'Select even numbers.', 'easy', '2', '3', '4', '1;3', '', '', ''],
            ['Geography', 'Capitals', 'matching', 'Match countries to capitals.', 'medium', 'Nigeria', 'Ghana', '', '', 'Abuja', 'Accra', ''],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.import.store'), [
                'questions_file' => $file,
            ])
            ->assertRedirect(route('admin.questions.index'))
            ->assertSessionHas('status', '2 question(s) imported successfully.');

        $this->assertDatabaseHas('questions', [
            'question_text' => 'Select even numbers.',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
        ]);
        $this->assertDatabaseHas('questions', [
            'question_text' => 'Match countries to capitals.',
            'question_type' => Question::TYPE_MATCHING,
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'Mathematics',
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('categories', [
            'name' => 'Number Theory',
        ]);

        $multipleChoice = Question::where('question_text', 'Select even numbers.')->firstOrFail();
        $matching = Question::where('question_text', 'Match countries to capitals.')->firstOrFail();

        $this->assertSame(2, $multipleChoice->options()->where('is_correct', true)->count());
        $this->assertSame('Abuja', $matching->options()->where('option_text', 'Nigeria')->firstOrFail()->match_text);
    }

    public function test_question_import_rejects_invalid_rows_without_creating_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $file = $this->excelUpload([
            ['category', 'subcategory', 'question_type', 'question', 'difficulty', 'option_1', 'option_2', 'correct_answers'],
            ['', 'General', 'single_choice', 'Should fail?', 'easy', 'Yes', 'No', '1'],
        ]);

        $this->actingAs($admin)
            ->from(route('admin.questions.import'))
            ->post(route('admin.questions.import.store'), [
                'questions_file' => $file,
            ])
            ->assertRedirect(route('admin.questions.import'))
            ->assertSessionHasErrors('questions_file');

        $this->assertDatabaseMissing('questions', [
            'question_text' => 'Should fail?',
        ]);
    }

    public function test_spreadsheet_reader_uses_selected_sheet_index(): void
    {
        $file = $this->multiSheetExcelUpload(
            [
                ['category', 'subcategory', 'question_type', 'question', 'difficulty', 'option_1', 'option_2', 'correct_answers'],
                ['First Category', 'First Topic', 'single_choice', 'Question from first sheet?', 'easy', 'Yes', 'No', '1'],
            ],
            [
                ['category', 'subcategory', 'question_type', 'question', 'difficulty', 'option_1', 'option_2', 'correct_answers'],
                ['Second Category', 'Second Topic', 'single_choice', 'Question from second sheet?', 'easy', 'Yes', 'No', '1'],
            ],
        );

        $spreadsheet = app(QuestionImportSpreadsheet::class);

        $firstSheetRows = $spreadsheet->rows($file->getRealPath(), 0);
        $secondSheetRows = $spreadsheet->rows($file->getRealPath(), 1);

        $this->assertSame('Question from first sheet?', $firstSheetRows[0]['question']);
        $this->assertSame('Question from second sheet?', $secondSheetRows[0]['question']);
    }

    /**
     * @return array{0: Category, 1: Category}
     */
    private function categoryPair(string $categoryName, string $subcategoryName): array
    {
        $category = Category::create([
            'name' => $categoryName,
            'is_active' => true,
        ]);

        $subcategory = Category::create([
            'parent_id' => $category->id,
            'name' => $subcategoryName,
            'is_active' => true,
        ]);

        return [$category, $subcategory];
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function excelUpload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'questions-import-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows));
        $zip->close();

        return new UploadedFile(
            $path,
            'questions.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    /**
     * @param  array<int, array<int, string>>  $firstSheetRows
     * @param  array<int, array<int, string>>  $secondSheetRows
     */
    private function multiSheetExcelUpload(array $firstSheetRows, array $secondSheetRows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'questions-import-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="First" sheetId="1" r:id="rId1"/><sheet name="Second" sheetId="2" r:id="rId2"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($firstSheetRows));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->worksheetXml($secondSheetRows));
        $zip->close();

        return new UploadedFile(
            $path,
            'questions.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function worksheetXml(array $rows): string
    {
        $xmlRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cellXml = '';

            foreach ($row as $columnIndex => $value) {
                $reference = $this->excelColumn($columnIndex + 1).($rowIndex + 1);
                $cellXml .= '<c r="'.$reference.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            }

            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cellXml.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>';
    }

    private function excelColumn(int $index): string
    {
        $column = '';

        while ($index > 0) {
            $index--;
            $column = chr(65 + ($index % 26)).$column;
            $index = intdiv($index, 26);
        }

        return $column;
    }
}
