<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuestionTag;
use App\Services\QuestionImportSpreadsheet;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions.
     */
    public function index(Request $request): View
    {
        $questions = Question::query()
            ->with('category.parent', 'tags')
            ->withCount('options')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('question_text', 'like', $search)
                        ->orWhere('question_type', 'like', $search)
                        ->orWhere('difficulty', 'like', $search)
                        ->orWhereHas('category', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('category.parent', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('tags', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->string('difficulty')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()
            ->with('parent')
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.questions.index', compact('questions', 'categories'));
    }

    /**
     * Show the form for creating a question.
     */
    public function create(): View
    {
        return view('admin.questions.create', [
            'categories' => $this->parentCategories(),
        ]);
    }

    /**
     * Show the question spreadsheet import form.
     */
    public function import(): View
    {
        return view('admin.questions.import', [
            'categories' => $this->parentCategories(),
            'sheets' => [],
            'importPath' => null,
            'importFileName' => null,
            'sheetError' => null,
        ]);
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedQuestionData($request);

        DB::transaction(function () use ($request, $data): void {
            $subcategory = $this->resolveSubcategory($data['category']);
            $questionData = $data['question'];
            $questionData['category_id'] = $subcategory->id;

            $question = Question::create($questionData);
            $this->syncQuestionImage($request, $question);
            $this->syncExplanationImage($request, $question);
            $this->syncTags($question, $data['tags']);

            $this->syncOptions($request, $question, $data['options'], $data['correct_options']);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Question created successfully.');
    }

    /**
     * Import questions from an uploaded Excel workbook.
     */
    public function storeImport(Request $request, QuestionImportSpreadsheet $spreadsheet): RedirectResponse|View
    {
        $validated = $request->validate([
            'questions_file' => ['nullable', 'required_without:import_path', 'file', 'mimes:xlsx', 'max:5120'],
            'import_path' => ['nullable', 'string'],
            'sheet_index' => ['nullable', 'required_with:import_path', 'integer', 'min:0'],
        ]);

        [$path, $temporaryImportPath, $sheetIndex, $sheetSelectionView, $sheets] = $this->resolveImportSheet(
            $request,
            $spreadsheet,
            'questions_file',
            'admin.questions.import',
        );

        if ($sheetSelectionView) {
            return $sheetSelectionView;
        }

        $rows = $spreadsheet->rows($path, $sheetIndex);

        if ($rows === []) {
            return $this->importSheetErrorView(
                'The selected worksheet does not contain any question rows.',
                $temporaryImportPath,
                $sheets,
                $view = 'admin.questions.import',
            );
        }

        $importRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->questionDataFromImportRow($row, $rowNumber);

            if ($data['errors'] !== []) {
                $errors = array_merge($errors, $data['errors']);

                continue;
            }

            $importRows[] = $data['question'];
        }

        if ($errors !== []) {
            return $this->importSheetErrorView(
                implode("\n", $errors),
                $temporaryImportPath,
                $sheets,
                'admin.questions.import',
            );
        }

        DB::transaction(function () use ($importRows): void {
            foreach ($importRows as $data) {
                $subcategory = $this->resolveImportSubcategory($data['category']);
                $questionData = $data['question'];
                $questionData['category_id'] = $subcategory->id;

                $question = Question::create($questionData);
                $this->syncTags($question, $data['tags']);

                $this->syncOptions(null, $question, $data['options'], $data['correct_options']);
            }
        });

        if ($temporaryImportPath) {
            Storage::disk('local')->delete($temporaryImportPath);
        }

        return redirect()
            ->route('admin.questions.index')
            ->with('status', count($importRows).' question(s) imported successfully.');
    }

    /**
     * Apply a bulk action to selected questions.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->get();

        if ($validated['action'] === 'deactivate') {
            Question::query()
                ->whereIn('id', $questions->pluck('id'))
                ->update(['is_active' => false]);

            return back()->with('status', $questions->count().' question(s) deactivated successfully.');
        }

        if ($validated['action'] === 'activate') {
            Question::query()
                ->whereIn('id', $questions->pluck('id'))
                ->update(['is_active' => true]);

            return back()->with('status', $questions->count().' question(s) activated successfully.');
        }

        $deleted = 0;
        $blocked = 0;

        DB::transaction(function () use ($questions, &$deleted, &$blocked): void {
            foreach ($questions as $question) {
                if ($question->attemptAnswers()->exists()) {
                    $blocked++;

                    continue;
                }

                $this->deleteQuestionImage($question);
                $this->deleteExplanationImage($question);
                $this->deleteOptionImages($question);
                $question->tags()->detach();
                $question->delete();
                $deleted++;
            }
        });

        $message = "{$deleted} question(s) permanently deleted.";

        if ($blocked > 0) {
            $message .= " {$blocked} question(s) were skipped because they have exam history.";
        }

        return back()->with('status', $message);
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Question $question): View
    {
        $question->load('options', 'tags');

        return view('admin.questions.edit', [
            'question' => $question,
            'categories' => $this->parentCategories(),
        ]);
    }

    /**
     * Preview a question as it will appear to students, including answers and explanation.
     */
    public function preview(Question $question): View
    {
        $question->load(['category.parent', 'options', 'tags']);

        return view('admin.questions.preview', [
            'question' => $question,
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $this->validatedQuestionData($request);

        DB::transaction(function () use ($request, $question, $data): void {
            $subcategory = $this->resolveSubcategory($data['category']);
            $questionData = $data['question'];
            $questionData['category_id'] = $subcategory->id;

            $question->update($questionData);
            $this->syncQuestionImage($request, $question);
            $this->syncExplanationImage($request, $question);
            $this->syncTags($question, $data['tags']);

            $this->syncOptions($request, $question, $data['options'], $data['correct_options']);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Question updated successfully.');
    }

    /**
     * Deactivate the specified question.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $question->update(['is_active' => false]);

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Question deactivated successfully.');
    }

    /**
     * Update whether the question is available for exams.
     */
    public function updateStatus(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $question->update([
            'is_active' => (bool) $validated['is_active'],
        ]);

        return back()->with('status', 'Question marked as '.($question->is_active ? 'active' : 'inactive').'.');
    }

    /**
     * Permanently delete a question if it has not been used in exam attempts.
     */
    public function permanentDestroy(Question $question): RedirectResponse
    {
        if ($question->attemptAnswers()->exists()) {
            return back()->withErrors([
                'question' => 'This question has exam history and cannot be permanently deleted. Deactivate it instead.',
            ]);
        }

        DB::transaction(function () use ($question): void {
            $this->deleteQuestionImage($question);
            $this->deleteExplanationImage($question);
            $this->deleteOptionImages($question);
            $question->tags()->detach();
            $question->delete();
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Question permanently deleted successfully.');
    }

    /**
     * Validate question and option form data.
     *
     * @return array{question: array<string, mixed>, options: array<int, array{text: string, match_text?: string}>, correct_options: array<int, int>}
     */
    private function validatedQuestionData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'parent_category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('parent_id')),
            ],
            'new_category_name' => ['nullable', 'string', 'max:255', 'unique:categories,name'],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('parent_id')),
            ],
            'new_subcategory_name' => ['nullable', 'string', 'max:255', 'unique:categories,name'],
            'question_text' => ['required', 'string'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'explanation_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_explanation_image' => ['sometimes', 'boolean'],
            'question_type' => ['required', Rule::in(array_keys(Question::TYPES))],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.text' => ['required', 'string'],
            'options.*.match_text' => ['nullable', 'string'],
            'options.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'options.*.remove_image' => ['sometimes', 'boolean'],
            'correct_option' => ['nullable', 'integer'],
            'correct_options' => ['nullable', 'array'],
            'correct_options.*' => ['integer'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $options = $request->input('options', []);
            $questionType = $request->input('question_type', Question::TYPE_SINGLE_CHOICE);
            $hasExistingCategory = filled($request->input('parent_category_id'));
            $hasNewCategory = filled($request->input('new_category_name'));
            $hasExistingSubcategory = filled($request->input('subcategory_id'));
            $hasNewSubcategory = filled($request->input('new_subcategory_name'));

            if (RichText::plainText($request->input('question_text')) === '') {
                $validator->errors()->add('question_text', 'Enter the question.');
            }

            foreach ($options as $index => $option) {
                if (RichText::plainText($option['text'] ?? '') === '') {
                    $validator->errors()->add("options.{$index}.text", 'Enter the option text.');
                }
            }

            if ($hasExistingCategory && $hasNewCategory) {
                $validator->errors()->add('new_category_name', 'Choose an existing category or add a new one, not both.');
            }

            if (! $hasExistingCategory && ! $hasNewCategory) {
                $validator->errors()->add('parent_category_id', 'Choose an existing category or add a new one.');
            }

            if ($hasExistingSubcategory && $hasNewSubcategory) {
                $validator->errors()->add('new_subcategory_name', 'Choose an existing subcategory or add a new one, not both.');
            }

            if (! $hasExistingSubcategory && ! $hasNewSubcategory) {
                $validator->errors()->add('subcategory_id', 'Every question must have a subcategory.');
            }

            if ($hasNewCategory && $hasExistingSubcategory) {
                $validator->errors()->add('subcategory_id', 'Add a new subcategory under the new category.');
            }

            if ($hasExistingCategory && $hasExistingSubcategory) {
                $subcategory = Category::query()->find($request->integer('subcategory_id'));

                if (! $subcategory || $subcategory->parent_id !== $request->integer('parent_category_id')) {
                    $validator->errors()->add('subcategory_id', 'Choose a subcategory under the selected category.');
                }
            }

            if ($questionType === Question::TYPE_MATCHING) {
                $matchTexts = [];

                foreach ($options as $index => $option) {
                    if (blank($option['match_text'] ?? null)) {
                        $validator->errors()->add("options.{$index}.match_text", 'Enter the matching answer.');
                    }

                    $matchTexts[] = strtolower(trim((string) ($option['match_text'] ?? '')));
                }

                if (count($matchTexts) !== count(array_unique($matchTexts))) {
                    $validator->errors()->add('options', 'Matching answers must be unique.');
                }

                return;
            }

            $correctOptions = $this->correctOptionIndexes($request);

            if ($correctOptions === []) {
                $validator->errors()->add('correct_option', $questionType === Question::TYPE_MULTIPLE_CHOICE
                    ? 'Choose at least one correct option.'
                    : 'Choose one correct option.');
            }

            foreach ($correctOptions as $correctOption) {
                if (! array_key_exists($correctOption, $options)) {
                    $validator->errors()->add('correct_option', 'Choose a valid correct option.');
                }
            }

            if (in_array($questionType, [Question::TYPE_SINGLE_CHOICE, Question::TYPE_TRUE_FALSE], true) && count($correctOptions) !== 1) {
                $validator->errors()->add('correct_option', 'Choose exactly one correct option.');
            }

            if ($questionType === Question::TYPE_TRUE_FALSE) {
                $optionTexts = collect($options)
                    ->pluck('text')
                    ->map(fn ($text) => strtolower(RichText::plainText((string) $text)))
                    ->sort()
                    ->values()
                    ->all();

                if ($optionTexts !== ['false', 'true']) {
                    $validator->errors()->add('options', 'True / False questions must have True and False options.');
                }
            }
        });

        $validated = $validator->validate();

        return [
            'category' => [
                'parent_category_id' => $validated['parent_category_id'] ?? null,
                'new_category_name' => $validated['new_category_name'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'new_subcategory_name' => $validated['new_subcategory_name'] ?? null,
            ],
            'question' => [
                'question_text' => RichText::clean($validated['question_text']),
                'question_type' => $validated['question_type'],
                'explanation' => filled($validated['explanation'] ?? null) ? RichText::clean($validated['explanation']) : null,
                'difficulty' => $validated['difficulty'],
                'is_active' => $request->boolean('is_active'),
            ],
            'options' => collect(array_values($validated['options']))
                ->map(fn (array $option) => [
                    'id' => $option['id'] ?? null,
                    'text' => RichText::clean($option['text']),
                    'match_text' => $option['match_text'] ?? null,
                ])
                ->all(),
            'correct_options' => $this->correctOptionIndexes($request),
            'tags' => $this->tagNames($validated['tags'] ?? ''),
        ];
    }

    /**
     * @param  array<int, string>  $tagNames
     */
    private function syncTags(Question $question, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->map(fn (string $name) => QuestionTag::firstOrCreate(['name' => $name])->id)
            ->all();

        $question->tags()->sync($tagIds);
    }

    /**
     * @return array<int, string>
     */
    private function tagNames(string $value): array
    {
        return collect(preg_split('/[,;|]/', $value) ?: [])
            ->map(fn ($tag) => str($tag)->trim()->lower()->toString())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function syncQuestionImage(Request $request, Question $question): void
    {
        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            $this->deleteQuestionImage($question);

            if ($request->boolean('remove_image') && ! $request->hasFile('image')) {
                $question->update(['image_path' => null]);
            }
        }

        if ($request->hasFile('image')) {
            $question->update([
                'image_path' => $request->file('image')->store('question-images', 'public'),
            ]);
        }
    }

    private function deleteQuestionImage(Question $question): void
    {
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }
    }

    private function syncExplanationImage(Request $request, Question $question): void
    {
        if ($request->boolean('remove_explanation_image') || $request->hasFile('explanation_image')) {
            $this->deleteExplanationImage($question);

            if ($request->boolean('remove_explanation_image') && ! $request->hasFile('explanation_image')) {
                $question->update(['explanation_image_path' => null]);
            }
        }

        if ($request->hasFile('explanation_image')) {
            $question->update([
                'explanation_image_path' => $request->file('explanation_image')->store('question-images', 'public'),
            ]);
        }
    }

    private function deleteExplanationImage(Question $question): void
    {
        if ($question->explanation_image_path) {
            Storage::disk('public')->delete($question->explanation_image_path);
        }
    }

    private function deleteOptionImage(Option $option): void
    {
        if ($option->image_path) {
            Storage::disk('public')->delete($option->image_path);
        }
    }

    private function deleteOptionImages(Question $question): void
    {
        $question->options()->whereNotNull('image_path')->get()->each(
            fn (Option $option) => $this->deleteOptionImage($option)
        );
    }

    /**
     * Synchronize a question's answer options.
     *
     * @param  array<int, array{id?: int|null, text: string, match_text?: string}>  $options
     * @param  array<int, int>  $correctOptions
     */
    private function syncOptions(?Request $request, Question $question, array $options, array $correctOptions): void
    {
        $existingOptions = $question->options()->get()->keyBy('id');
        $keptOptionIds = [];

        foreach ($options as $index => $option) {
            $optionModel = filled($option['id'] ?? null)
                ? $existingOptions->get((int) $option['id'])
                : null;

            $optionData = [
                'option_text' => $option['text'],
                'match_text' => $question->question_type === Question::TYPE_MATCHING ? ($option['match_text'] ?? null) : null,
                'is_correct' => $question->question_type === Question::TYPE_MATCHING || in_array($index, $correctOptions, true),
            ];

            if ($optionModel) {
                $optionModel->update($optionData);
            } else {
                $optionModel = $question->options()->create($optionData);
            }

            if ($request?->boolean("options.{$index}.remove_image") || $request?->hasFile("options.{$index}.image")) {
                $this->deleteOptionImage($optionModel);

                if ($request->boolean("options.{$index}.remove_image") && ! $request->hasFile("options.{$index}.image")) {
                    $optionModel->update(['image_path' => null]);
                }
            }

            if ($request?->hasFile("options.{$index}.image")) {
                $optionModel->update([
                    'image_path' => $request->file("options.{$index}.image")->store('question-images', 'public'),
                ]);
            }

            $keptOptionIds[] = $optionModel->id;
        }

        $existingOptions
            ->reject(fn (Option $option) => in_array($option->id, $keptOptionIds, true))
            ->each(function (Option $option): void {
                $this->deleteOptionImage($option);
                $option->delete();
            });
    }

    /**
     * @param  array{parent_category_id?: int|null, new_category_name?: string|null, subcategory_id?: int|null, new_subcategory_name?: string|null}  $data
     */
    private function resolveSubcategory(array $data): Category
    {
        if (filled($data['new_category_name'] ?? null)) {
            $parent = Category::create([
                'name' => trim($data['new_category_name']),
                'is_active' => true,
            ]);
        } else {
            $parent = Category::query()->findOrFail($data['parent_category_id']);
        }

        if (filled($data['new_subcategory_name'] ?? null)) {
            return Category::create([
                'parent_id' => $parent->id,
                'name' => trim($data['new_subcategory_name']),
                'is_active' => true,
            ]);
        }

        return Category::query()->where('parent_id', $parent->id)->findOrFail($data['subcategory_id']);
    }

    /**
     * @return array<int, int>
     */
    private function correctOptionIndexes(Request $request): array
    {
        $questionType = $request->input('question_type', Question::TYPE_SINGLE_CHOICE);

        if ($questionType === Question::TYPE_MATCHING) {
            return [];
        }

        if ($questionType === Question::TYPE_MULTIPLE_CHOICE) {
            return collect($request->input('correct_options', []))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all();
        }

        if (! is_numeric($request->input('correct_option'))) {
            return [];
        }

        return [(int) $request->input('correct_option')];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{errors: array<int, string>, question?: array{category: array{category_name: string, subcategory_name: string}, question: array<string, mixed>, options: array<int, array{text: string, match_text?: string|null}>, correct_options: array<int, int>}}
     */
    private function questionDataFromImportRow(array $row, int $rowNumber): array
    {
        $errors = [];
        $categoryName = trim($row['category'] ?? '');
        $subcategoryName = trim($row['subcategory'] ?? '');
        $questionType = $this->questionTypeFromImport($row['question_type'] ?? 'single_choice');
        $difficulty = strtolower($row['difficulty'] ?? 'easy');
        $options = $this->optionsFromImportRow($row);

        if ($categoryName === '') {
            $errors[] = "Row {$rowNumber}: category is required.";
        }

        if ($subcategoryName === '') {
            $errors[] = "Row {$rowNumber}: subcategory is required.";
        }

        if (blank($row['question'] ?? $row['question_text'] ?? null)) {
            $errors[] = "Row {$rowNumber}: question is required.";
        }

        if (! $questionType) {
            $errors[] = "Row {$rowNumber}: question_type must be single_choice, multiple_choice, true_false, or matching.";
        }

        if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $errors[] = "Row {$rowNumber}: difficulty must be easy, medium, or hard.";
        }

        if (count($options) < 2) {
            $errors[] = "Row {$rowNumber}: at least option_1 and option_2 are required.";
        }

        $correctOptions = [];

        if ($questionType === Question::TYPE_MATCHING) {
            $matchTexts = collect($options)
                ->pluck('match_text')
                ->map(fn ($text) => strtolower(trim((string) $text)))
                ->all();

            if (in_array('', $matchTexts, true)) {
                $errors[] = "Row {$rowNumber}: matching questions require match_1, match_2, and so on for each option.";
            }

            if (count($matchTexts) !== count(array_unique($matchTexts))) {
                $errors[] = "Row {$rowNumber}: matching answers must be unique.";
            }
        } else {
            $correctOptions = $this->correctOptionsFromImportRow($row['correct_answers'] ?? '', $options);

            if ($correctOptions === []) {
                $errors[] = "Row {$rowNumber}: correct_answers must identify the correct option(s).";
            }

            if (in_array($questionType, [Question::TYPE_SINGLE_CHOICE, Question::TYPE_TRUE_FALSE], true) && count($correctOptions) !== 1) {
                $errors[] = "Row {$rowNumber}: this question type needs exactly one correct answer.";
            }

            if ($questionType === Question::TYPE_TRUE_FALSE) {
                $optionTexts = collect($options)
                    ->pluck('text')
                    ->map(fn ($text) => strtolower(trim((string) $text)))
                    ->sort()
                    ->values()
                    ->all();

                if ($optionTexts !== ['false', 'true']) {
                    $errors[] = "Row {$rowNumber}: true_false questions must have True and False options.";
                }
            }
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        return [
            'errors' => [],
            'question' => [
                'question' => [
                    'question_text' => $row['question'] ?? $row['question_text'],
                    'question_type' => $questionType,
                    'explanation' => blank($row['explanation'] ?? null) ? null : $row['explanation'],
                    'difficulty' => $difficulty,
                    'is_active' => $this->booleanFromImport($row['is_active'] ?? 'yes'),
                ],
                'category' => [
                    'category_name' => $categoryName,
                    'subcategory_name' => $subcategoryName,
                ],
                'options' => $options,
                'correct_options' => $correctOptions,
                'tags' => $this->tagNames($row['tags'] ?? ''),
            ],
        ];
    }

    private function questionTypeFromImport(string $value): ?string
    {
        $normalized = str($value)
            ->lower()
            ->replace(['/', '-', ' '], '_')
            ->toString();

        return match ($normalized) {
            '', 'single', 'single_choice', 'multiple_choice_single' => Question::TYPE_SINGLE_CHOICE,
            'multiple', 'multiple_correct', 'multiple_choice' => Question::TYPE_MULTIPLE_CHOICE,
            'true_false', 'true_or_false', 'boolean' => Question::TYPE_TRUE_FALSE,
            'match', 'matching' => Question::TYPE_MATCHING,
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $row
     * @return array<int, array{text: string, match_text: string|null}>
     */
    private function optionsFromImportRow(array $row): array
    {
        $options = [];

        for ($index = 1; $index <= 10; $index++) {
            $optionText = trim($row["option_{$index}"] ?? '');

            if ($optionText === '') {
                continue;
            }

            $options[] = [
                'text' => $optionText,
                'match_text' => blank($row["match_{$index}"] ?? null) ? null : trim($row["match_{$index}"]),
            ];
        }

        return $options;
    }

    /**
     * @param  array<int, array{text: string, match_text?: string|null}>  $options
     * @return array<int, int>
     */
    private function correctOptionsFromImportRow(string $value, array $options): array
    {
        $parts = preg_split('/[,;|]/', $value) ?: [];
        $correctOptions = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (ctype_digit($part)) {
                $index = (int) $part - 1;

                if (array_key_exists($index, $options)) {
                    $correctOptions[] = $index;
                }

                continue;
            }

            foreach ($options as $index => $option) {
                if (strtolower($option['text']) === strtolower($part)) {
                    $correctOptions[] = $index;
                }
            }
        }

        return collect($correctOptions)
            ->unique()
            ->values()
            ->all();
    }

    private function booleanFromImport(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'active'], true);
    }

    /**
     * @return array{0: string, 1: string|null, 2: int, 3: View|null, 4: array<int, array{index: int, name: string, path: string}>}
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
                        'categories' => $this->parentCategories(),
                        'sheets' => $sheets,
                        'importPath' => $temporaryImportPath,
                        'importFileName' => basename($temporaryImportPath),
                        'sheetError' => 'Choose the worksheet to import.',
                    ]),
                    $sheets,
                ];
            }

            return [$path, $temporaryImportPath, $sheetIndex, null, $sheets];
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
                    'categories' => $this->parentCategories(),
                    'sheets' => $sheets,
                    'importPath' => $temporaryImportPath,
                    'importFileName' => $uploadedFile->getClientOriginalName(),
                    'sheetError' => null,
                ]),
                $sheets,
            ];
        }

        return [$path, null, (int) ($sheets[0]['index'] ?? 0), null, $sheets];
    }

    /**
     * @param  array<int, array{index: int, name: string, path: string}>  $sheets
     */
    private function importSheetErrorView(string $message, ?string $temporaryImportPath, array $sheets, string $view): RedirectResponse|View
    {
        if (! $temporaryImportPath) {
            return back()
                ->withInput()
                ->withErrors(['questions_file' => $message]);
        }

        return view($view, [
            'categories' => $this->parentCategories(),
            'sheets' => $sheets,
            'importPath' => $temporaryImportPath,
            'importFileName' => basename($temporaryImportPath),
            'sheetError' => $message,
        ]);
    }

    /**
     * @param  array{category_name: string, subcategory_name: string}  $data
     */
    private function resolveImportSubcategory(array $data): Category
    {
        $parent = Category::firstOrCreate(
            ['name' => $data['category_name']],
            ['is_active' => true],
        );

        if (! $parent->is_active) {
            $parent->update(['is_active' => true]);
        }

        $subcategory = Category::query()
            ->where('parent_id', $parent->id)
            ->where('name', $data['subcategory_name'])
            ->first();

        if ($subcategory) {
            if (! $subcategory->is_active) {
                $subcategory->update(['is_active' => true]);
            }

            return $subcategory;
        }

        return Category::create([
            'parent_id' => $parent->id,
            'name' => $data['subcategory_name'],
            'is_active' => true,
        ]);
    }

    /**
     * Get active categories for question forms.
     */
    private function parentCategories()
    {
        return Category::query()
            ->with(['subcategories' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name')])
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }
}
