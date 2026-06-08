@php
    $questionType = old('question_type', $question?->question_type ?? \App\Models\Question::TYPE_SINGLE_CHOICE);
    $selectedParentCategoryId = old('parent_category_id', $question?->category?->parent_id);
    $selectedSubcategoryId = old('subcategory_id', $question?->category?->parent_id ? $question?->category_id : null);
    $categoryRows = $categories
        ->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'subcategories' => $category->subcategories
                ->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();
    $optionRows = old('options');

    if (! $optionRows) {
        $optionRows = $question?->options
            ->map(fn ($option) => [
                'text' => $option->option_text,
                'match_text' => $option->match_text,
            ])
            ->values()
            ->all();

        if (! $optionRows) {
            $optionRows = $questionType === \App\Models\Question::TYPE_TRUE_FALSE
                ? [['text' => 'True'], ['text' => 'False']]
                : [
                    ['text' => '', 'match_text' => ''],
                    ['text' => '', 'match_text' => ''],
                    ['text' => '', 'match_text' => ''],
                    ['text' => '', 'match_text' => ''],
                ];
        }
    }

    $correctOption = old('correct_option');
    $correctOptions = collect(old('correct_options', []))->map(fn ($value) => (int) $value)->all();

    if ($correctOption === null && $question) {
        $correctOption = $question->options->values()->search(fn ($option) => $option->is_correct);
    }

    if ($correctOptions === [] && $question) {
        $correctOptions = $question->options
            ->values()
            ->filter(fn ($option) => $option->is_correct)
            ->keys()
            ->all();
    }
@endphp

<div
    x-data="{
        questionType: @js($questionType),
        categoryId: @js($selectedParentCategoryId ? (int) $selectedParentCategoryId : null),
        subcategoryId: @js($selectedSubcategoryId ? (int) $selectedSubcategoryId : null),
        categories: @js($categoryRows),
        options: @js($optionRows),
        correctOption: @js($correctOption === false ? null : $correctOption),
        correctOptions: @js($correctOptions),
        init() {
            this.$watch('questionType', (value) => {
                if (value === 'true_false') {
                    this.options = [{ text: 'True', match_text: '' }, { text: 'False', match_text: '' }];
                    this.correctOptions = [];
                    this.correctOption = null;
                }
            });
        },
        subcategories() {
            return this.categories.find((category) => category.id === this.categoryId)?.subcategories ?? [];
        },
        addOption() {
            this.options.push({ text: '', match_text: '' });
        },
        removeOption(index) {
            if (this.options.length <= 2 || this.questionType === 'true_false') {
                return;
            }

            this.options.splice(index, 1);

            if (this.correctOption === index) {
                this.correctOption = null;
            } else if (this.correctOption > index) {
                this.correctOption--;
            }

            this.correctOptions = this.correctOptions
                .filter((value) => value !== index)
                .map((value) => value > index ? value - 1 : value);
        },
    }"
>
<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="parent_category_id" value="Category" />
        <select
            id="parent_category_id"
            name="parent_category_id"
            x-model.number="categoryId"
            @change="subcategoryId = null"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        >
            <option value="">Choose existing category</option>
            <template x-for="category in categories" :key="category.id">
                <option :value="category.id" x-text="category.name"></option>
            </template>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('parent_category_id')" />

        <div class="mt-3">
            <x-input-label for="new_category_name" value="Or Add New Category" />
            <x-text-input
                id="new_category_name"
                name="new_category_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('new_category_name')"
            />
            <x-input-error class="mt-2" :messages="$errors->get('new_category_name')" />
        </div>
    </div>

    <div>
        <x-input-label for="subcategory_id" value="Subcategory" />
        <select
            id="subcategory_id"
            name="subcategory_id"
            x-model.number="subcategoryId"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        >
            <option value="">Choose existing subcategory</option>
            <template x-for="subcategory in subcategories()" :key="subcategory.id">
                <option :value="subcategory.id" x-text="subcategory.name"></option>
            </template>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('subcategory_id')" />

        <div class="mt-3">
            <x-input-label for="new_subcategory_name" value="Or Add New Subcategory" />
            <x-text-input
                id="new_subcategory_name"
                name="new_subcategory_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('new_subcategory_name')"
            />
            <x-input-error class="mt-2" :messages="$errors->get('new_subcategory_name')" />
        </div>
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="question_type" value="Question Type" />
        <select id="question_type" name="question_type" x-model="questionType" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (\App\Models\Question::TYPES as $value => $label)
                <option value="{{ $value }}" @selected($questionType === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('question_type')" />
    </div>

    <div>
        <x-input-label for="difficulty" value="Difficulty" />
        <select id="difficulty" name="difficulty" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                <option value="{{ $value }}" @selected(old('difficulty', $question?->difficulty ?? 'easy') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('difficulty')" />
    </div>
</div>

<div>
    <x-input-label for="question_text" value="Question" />
    <textarea
        id="question_text"
        name="question_text"
        rows="5"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        required
    >{{ old('question_text', $question?->question_text) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('question_text')" />
</div>

<div>
    <x-input-label for="explanation" value="Explanation" />
    <textarea
        id="explanation"
        name="explanation"
        rows="4"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
    >{{ old('explanation', $question?->explanation) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('explanation')" />
</div>

<div>
    <div class="flex items-center justify-between">
        <x-input-label value="Options" />
        <p class="text-sm text-gray-500" x-text="questionType === 'multiple_choice' ? 'Select every correct answer.' : (questionType === 'matching' ? 'Enter matching pairs.' : 'Select one correct answer.')"></p>
    </div>

    <div class="mt-3 space-y-3">
        <template x-for="(option, index) in options" :key="index">
            <div class="flex items-start gap-3">
                <input
                    x-show="questionType === 'single_choice' || questionType === 'true_false'"
                    type="radio"
                    name="correct_option"
                    :value="index"
                    x-model.number="correctOption"
                    class="mt-3 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >

                <input
                    x-show="questionType === 'multiple_choice'"
                    type="checkbox"
                    name="correct_options[]"
                    :value="index"
                    x-model.number="correctOptions"
                    class="mt-3 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >

                <span x-show="questionType === 'matching'" class="mt-2 min-w-6 text-sm font-medium text-gray-500" x-text="index + 1"></span>

                <div class="flex-1">
                    <input
                        :name="'options[' + index + '][text]'"
                        type="text"
                        x-model="option.text"
                        :placeholder="'Option ' + (index + 1)"
                        required
                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    />

                    <input
                        x-show="questionType === 'matching'"
                        :name="'options[' + index + '][match_text]'"
                        type="text"
                        x-model="option.match_text"
                        :placeholder="'Match for item ' + (index + 1)"
                        class="mt-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    />
                </div>

                <button
                    type="button"
                    class="mt-2 text-sm font-medium text-red-600 hover:text-red-900 disabled:cursor-not-allowed disabled:text-gray-300"
                    @click="removeOption(index)"
                    :disabled="options.length <= 2 || questionType === 'true_false'"
                >
                    Remove
                </button>
            </div>
        </template>
    </div>

    <button type="button" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-900" @click="addOption" x-show="questionType !== 'true_false'">
        Add another option
    </button>

    <x-input-error class="mt-2" :messages="$errors->get('options')" />
    @foreach ($errors->get('options.*.text') as $messages)
        <x-input-error class="mt-2" :messages="$messages" />
    @endforeach
    @foreach ($errors->get('options.*.match_text') as $messages)
        <x-input-error class="mt-2" :messages="$messages" />
    @endforeach
    <x-input-error class="mt-2" :messages="$errors->get('correct_option')" />
    <x-input-error class="mt-2" :messages="$errors->get('correct_options')" />
</div>

</div>

<div class="flex items-center">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $question?->is_active ?? true))
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    <label for="is_active" class="ms-2 text-sm text-gray-600">Active</label>
    <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
</div>
