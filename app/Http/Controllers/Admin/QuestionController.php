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

            $this->syncOptions($question, $data['options'], $data['correct_option']);
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

            $this->syncOptions($question, $data['options'], $data['correct_option']);
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
     * @return array{question: array<string, mixed>, options: array<int, array{text: string}>, correct_option: int}
     */
    private function validatedQuestionData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.text' => ['required', 'string'],
            'correct_option' => ['required', 'integer'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $options = $request->input('options', []);
            $correctOption = $request->input('correct_option');

            if (! is_numeric($correctOption) || ! array_key_exists((int) $correctOption, $options)) {
                $validator->errors()->add('correct_option', 'Choose one correct option.');
            }
        });

        $validated = $validator->validate();

        return [
            'question' => [
                'category_id' => $validated['category_id'],
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty' => $validated['difficulty'],
                'is_active' => $request->boolean('is_active'),
            ],
            'options' => array_values($validated['options']),
            'correct_option' => (int) $validated['correct_option'],
        ];
    }

    /**
     * Replace a question's answer options.
     *
     * @param  array<int, array{text: string}>  $options
     */
    private function syncOptions(Question $question, array $options, int $correctOption): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $index === $correctOption,
            ]);
        }
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
