<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->with('category')
            ->withCount('options')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->string('difficulty')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()
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
            'categories' => $this->activeCategories(),
        ]);
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedQuestionData($request);

        DB::transaction(function () use ($data): void {
            $question = Question::create($data['question']);

            $this->syncOptions($question, $data['options'], $data['correct_options']);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'Question created successfully.');
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Question $question): View
    {
        $question->load('options');

        return view('admin.questions.edit', [
            'question' => $question,
            'categories' => $this->activeCategories(),
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $this->validatedQuestionData($request);

        DB::transaction(function () use ($question, $data): void {
            $question->update($data['question']);

            $this->syncOptions($question, $data['options'], $data['correct_options']);
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
     * Validate question and option form data.
     *
     * @return array{question: array<string, mixed>, options: array<int, array{text: string, match_text?: string}>, correct_options: array<int, int>}
     */
    private function validatedQuestionData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'question_text' => ['required', 'string'],
            'question_type' => ['required', Rule::in(array_keys(Question::TYPES))],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.text' => ['required', 'string'],
            'options.*.match_text' => ['nullable', 'string'],
            'correct_option' => ['nullable', 'integer'],
            'correct_options' => ['nullable', 'array'],
            'correct_options.*' => ['integer'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $options = $request->input('options', []);
            $questionType = $request->input('question_type', Question::TYPE_SINGLE_CHOICE);

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
                    ->map(fn ($text) => strtolower(trim((string) $text)))
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
            'question' => [
                'category_id' => $validated['category_id'],
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty' => $validated['difficulty'],
                'is_active' => $request->boolean('is_active'),
            ],
            'options' => array_values($validated['options']),
            'correct_options' => $this->correctOptionIndexes($request),
        ];
    }

    /**
     * Replace a question's answer options.
     *
     * @param  array<int, array{text: string, match_text?: string}>  $options
     * @param  array<int, int>  $correctOptions
     */
    private function syncOptions(Question $question, array $options, array $correctOptions): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'match_text' => $question->question_type === Question::TYPE_MATCHING ? ($option['match_text'] ?? null) : null,
                'is_correct' => $question->question_type === Question::TYPE_MATCHING || in_array($index, $correctOptions, true),
            ]);
        }
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
     * Get active categories for question forms.
     */
    private function activeCategories()
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
