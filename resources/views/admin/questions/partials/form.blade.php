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
                'id' => $option->id,
                'text' => $option->option_text,
                'match_text' => $option->match_text,
                'image_path' => $option->image_path,
                'image_url' => $option->imageUrl(),
            ])
            ->values()
            ->all();

        if (! $optionRows) {
            $optionRows = $questionType === \App\Models\Question::TYPE_TRUE_FALSE
                ? [['text' => 'True', 'match_text' => '', 'image_path' => null, 'image_url' => null], ['text' => 'False', 'match_text' => '', 'image_path' => null, 'image_url' => null]]
                : [
                    ['text' => '', 'match_text' => '', 'image_path' => null, 'image_url' => null],
                    ['text' => '', 'match_text' => '', 'image_path' => null, 'image_url' => null],
                    ['text' => '', 'match_text' => '', 'image_path' => null, 'image_url' => null],
                    ['text' => '', 'match_text' => '', 'image_path' => null, 'image_url' => null],
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

    $tagValue = old('tags', $question?->tags?->pluck('name')->implode(', '));
    $questionTextValue = old('question_text', $question?->question_text ?? '');
    $explanationValue = old('explanation', $question?->explanation ?? '');
@endphp

<style>
    .rich-editor-shell {
        overflow: hidden;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #ffffff;
    }

    .rich-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        border-bottom: 1px solid #d1d5db;
        background: #f8fafc;
        padding: 0.45rem;
    }

    .rich-editor-toolbar button {
        min-width: 2rem;
        border-radius: 4px;
        padding: 0.25rem 0.45rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #374151;
    }

    .rich-editor-toolbar button:hover {
        background: #e5e7eb;
    }

    .rich-editor-input {
        min-height: 8rem;
        padding: 0.75rem;
        outline: none;
    }

    .rich-editor-input:empty::before {
        color: #9ca3af;
        content: attr(data-placeholder);
    }

    .rich-editor-option .rich-editor-input {
        min-height: 5rem;
    }

    .rich-content :where(p, ul, ol, blockquote, pre) {
        margin-bottom: 0.65rem;
    }

    .rich-content :where(ul, ol) {
        padding-left: 1.25rem;
    }

    .rich-content ul {
        list-style: disc;
    }

    .rich-content ol {
        list-style: decimal;
    }
</style>

<div
    x-data="{
        questionType: @js($questionType),
        questionText: @js($questionTextValue),
        explanation: @js($explanationValue),
        explanationOpen: @js(filled($explanationValue) || $question?->explanation_image_path),
        activeEditor: null,
        categoryId: @js($selectedParentCategoryId ? (int) $selectedParentCategoryId : null),
        subcategoryId: @js($selectedSubcategoryId ? (int) $selectedSubcategoryId : null),
        categories: @js($categoryRows),
        options: @js($optionRows),
        correctOption: @js($correctOption === false ? null : $correctOption),
        correctOptions: @js($correctOptions),
        init() {
            this.$watch('questionType', (value) => {
                if (value === 'true_false') {
                    this.options = [
                        { text: 'True', match_text: '', image_path: null, image_url: null },
                        { text: 'False', match_text: '', image_path: null, image_url: null },
                    ];
                    this.correctOptions = [];
                    this.correctOption = null;
                }
            });
        },
        setActiveEditor(editor) {
            this.activeEditor = editor;
        },
        formatRichText(command, value = null) {
            if (! this.activeEditor) {
                return;
            }

            this.activeEditor.focus();
            document.execCommand(command, false, value);
            this.activeEditor.dispatchEvent(new InputEvent('input', { bubbles: true }));
        },
        subcategories() {
            return this.categories.find((category) => category.id === this.categoryId)?.subcategories ?? [];
        },
        addOption() {
            this.options.push({ text: '', match_text: '', image_path: null, image_url: null });
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
    <input type="hidden" id="question_text" name="question_text" x-model="questionText">
    <div class="mt-1 rich-editor-shell">
        <div class="rich-editor-toolbar" aria-label="Question formatting toolbar">
            <button type="button" title="Bold" @mousedown.prevent="formatRichText('bold')">B</button>
            <button type="button" title="Italic" @mousedown.prevent="formatRichText('italic')"><span class="italic">I</span></button>
            <button type="button" title="Underline" @mousedown.prevent="formatRichText('underline')"><span class="underline">U</span></button>
            <button type="button" title="Bulleted list" @mousedown.prevent="formatRichText('insertUnorderedList')">UL</button>
            <button type="button" title="Numbered list" @mousedown.prevent="formatRichText('insertOrderedList')">OL</button>
            <button type="button" title="Subscript" @mousedown.prevent="formatRichText('subscript')">X<sub>2</sub></button>
            <button type="button" title="Superscript" @mousedown.prevent="formatRichText('superscript')">X<sup>2</sup></button>
            <button type="button" title="Code" @mousedown.prevent="formatRichText('formatBlock', 'pre')">&lt;/&gt;</button>
            <button type="button" title="Clear formatting" @mousedown.prevent="formatRichText('removeFormat')">Tx</button>
        </div>
        <div
            contenteditable="true"
            class="rich-editor-input"
            data-placeholder="Type the question here"
            x-ref="questionEditor"
            x-init="$el.innerHTML = questionText"
            @focus="setActiveEditor($el)"
            @click="setActiveEditor($el)"
            @input="questionText = $el.innerHTML"
        ></div>
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('question_text')" />
</div>

<div>
    <x-input-label for="image" value="Question Image" />

    @if ($question?->image_path)
        <div class="mt-2 rounded-md border border-gray-200 p-3">
            <img
                src="{{ $question->imageUrl() }}"
                alt="Current question image"
                class="max-h-64 rounded-md object-contain"
            >

            <label class="mt-3 flex items-center text-sm text-gray-600">
                <input
                    id="remove_image"
                    name="remove_image"
                    type="checkbox"
                    value="1"
                    @checked(old('remove_image'))
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-2">Remove current image</span>
            </label>
        </div>
    @endif

    <input
        id="image"
        name="image"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        class="mt-2 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
    >
    <p class="mt-1 text-sm text-gray-500">Optional JPG, PNG, or WebP image. Maximum size: 2MB.</p>
    <x-input-error class="mt-2" :messages="$errors->get('image')" />
    <x-input-error class="mt-2" :messages="$errors->get('remove_image')" />
</div>

<div>
    <x-input-label for="tags" value="Tags" />
    <x-text-input
        id="tags"
        name="tags"
        type="text"
        class="mt-1 block w-full"
        :value="$tagValue"
        placeholder="agile, risk, calculations"
    />
    <p class="mt-1 text-sm text-gray-500">Optional. Separate tags with commas.</p>
    <x-input-error class="mt-2" :messages="$errors->get('tags')" />
</div>

<div class="rounded-md border border-gray-200 bg-gray-50 p-4">
    <button type="button" class="flex w-full items-center gap-2 text-left text-sm font-semibold text-gray-900" @click="explanationOpen = ! explanationOpen">
        <span x-text="explanationOpen ? '-' : '+'"></span>
        Add Explanation
    </button>

    <div class="mt-4 space-y-4" x-show="explanationOpen" x-cloak>
        <div>
            <input type="hidden" id="explanation" name="explanation" x-model="explanation">
            <div class="rich-editor-shell bg-white">
                <div class="rich-editor-toolbar" aria-label="Explanation formatting toolbar">
                    <button type="button" title="Bold" @mousedown.prevent="formatRichText('bold')">B</button>
                    <button type="button" title="Italic" @mousedown.prevent="formatRichText('italic')"><span class="italic">I</span></button>
                    <button type="button" title="Underline" @mousedown.prevent="formatRichText('underline')"><span class="underline">U</span></button>
                    <button type="button" title="Bulleted list" @mousedown.prevent="formatRichText('insertUnorderedList')">UL</button>
                    <button type="button" title="Numbered list" @mousedown.prevent="formatRichText('insertOrderedList')">OL</button>
                    <button type="button" title="Subscript" @mousedown.prevent="formatRichText('subscript')">X<sub>2</sub></button>
                    <button type="button" title="Superscript" @mousedown.prevent="formatRichText('superscript')">X<sup>2</sup></button>
                    <button type="button" title="Code" @mousedown.prevent="formatRichText('formatBlock', 'pre')">&lt;/&gt;</button>
                    <button type="button" title="Clear formatting" @mousedown.prevent="formatRichText('removeFormat')">Tx</button>
                </div>
                <div
                    contenteditable="true"
                    class="rich-editor-input"
                    data-placeholder="Add explanation here"
                    x-init="$el.innerHTML = explanation"
                    @focus="setActiveEditor($el)"
                    @click="setActiveEditor($el)"
                    @input="explanation = $el.innerHTML"
                ></div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('explanation')" />
        </div>

        <div>
            <x-input-label for="explanation_image" value="Explanation Image" />

    @if ($question?->explanation_image_path)
        <div class="mt-2 rounded-md border border-gray-200 bg-white p-3">
            <img
                src="{{ $question->explanationImageUrl() }}"
                alt="Current explanation image"
                class="max-h-64 rounded-md object-contain"
            >

            <label class="mt-3 flex items-center text-sm text-gray-600">
                <input
                    id="remove_explanation_image"
                    name="remove_explanation_image"
                    type="checkbox"
                    value="1"
                    @checked(old('remove_explanation_image'))
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-2">Remove current explanation image</span>
            </label>
        </div>
    @endif

    <input
        id="explanation_image"
        name="explanation_image"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        class="mt-2 block w-full text-sm text-gray-700 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
    >
    <p class="mt-1 text-sm text-gray-500">Optional JPG, PNG, or WebP image shown with the explanation. Maximum size: 2MB.</p>
    <x-input-error class="mt-2" :messages="$errors->get('explanation_image')" />
    <x-input-error class="mt-2" :messages="$errors->get('remove_explanation_image')" />
        </div>
    </div>
</div>

<div>
    <div class="flex items-center justify-between">
        <x-input-label value="Options" />
        <p class="text-sm text-gray-500" x-text="questionType === 'multiple_choice' ? 'Select every correct answer.' : (questionType === 'matching' ? 'Enter matching pairs.' : 'Select one correct answer.')"></p>
    </div>

    <div class="mt-3 grid gap-5 lg:grid-cols-2">
        <template x-for="(option, index) in options" :key="index">
            <div class="rich-editor-option">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <span x-text="String.fromCharCode(65 + index)"></span>
                        <input
                            x-show="questionType === 'single_choice' || questionType === 'true_false'"
                            type="radio"
                            name="correct_option"
                            :value="index"
                            x-model.number="correctOption"
                            class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >

                        <input
                            x-show="questionType === 'multiple_choice'"
                            type="checkbox"
                            name="correct_options[]"
                            :value="index"
                            x-model.number="correctOptions"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                    </label>

                    <button
                        type="button"
                        class="text-sm font-medium text-red-600 hover:text-red-900 disabled:cursor-not-allowed disabled:text-gray-300"
                        @click="removeOption(index)"
                        :disabled="options.length <= 2 || questionType === 'true_false'"
                    >
                        Remove
                    </button>
                </div>

                <div class="rich-editor-shell">
                    <div class="rich-editor-toolbar" aria-label="Answer formatting toolbar">
                        <button type="button" title="Subscript" @mousedown.prevent="formatRichText('subscript')">X<sub>2</sub></button>
                        <button type="button" title="Superscript" @mousedown.prevent="formatRichText('superscript')">X<sup>2</sup></button>
                        <button type="button" title="Bold" @mousedown.prevent="formatRichText('bold')">B</button>
                        <button type="button" title="Italic" @mousedown.prevent="formatRichText('italic')"><span class="italic">I</span></button>
                        <button type="button" title="Underline" @mousedown.prevent="formatRichText('underline')"><span class="underline">U</span></button>
                        <button type="button" title="Code" @mousedown.prevent="formatRichText('formatBlock', 'pre')">&lt;/&gt;</button>
                        <button type="button" title="Clear formatting" @mousedown.prevent="formatRichText('removeFormat')">Tx</button>
                    </div>
                    <template x-if="option.id">
                        <input type="hidden" :name="'options[' + index + '][id]'" :value="option.id">
                    </template>
                    <input type="hidden" :name="'options[' + index + '][text]'" :value="option.text">
                    <div
                        contenteditable="true"
                        class="rich-editor-input"
                        :data-placeholder="'Option ' + String.fromCharCode(65 + index)"
                        x-init="$el.innerHTML = option.text"
                        @focus="setActiveEditor($el)"
                        @click="setActiveEditor($el)"
                        @input="option.text = $el.innerHTML"
                    ></div>
                </div>

                    <input
                        x-show="questionType === 'matching'"
                        :name="'options[' + index + '][match_text]'"
                        type="text"
                        x-model="option.match_text"
                        :placeholder="'Match for item ' + (index + 1)"
                        class="mt-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    />

                    <div class="mt-3 rounded-md border border-gray-200 bg-gray-50 p-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Option Image</div>
                                <p class="mt-1 text-xs text-gray-500">Optional JPG, PNG, or WebP. Maximum size: 2MB.</p>
                            </div>

                            <template x-if="option.image_url">
                                <label class="flex items-center text-xs text-gray-600">
                                    <input
                                        type="checkbox"
                                        :name="'options[' + index + '][remove_image]'"
                                        value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    >
                                    <span class="ms-2">Remove image</span>
                                </label>
                            </template>
                        </div>

                        <template x-if="option.image_url">
                            <img
                                :src="option.image_url"
                                alt="Current option image"
                                class="mt-3 max-h-40 rounded-md border border-gray-200 bg-white object-contain"
                            >
                        </template>

                        <input
                            :id="'option_image_' + index"
                            :name="'options[' + index + '][image]'"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="mt-3 block w-full text-xs text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-gray-700"
                        >
                    </div>
            </div>
        </template>
    </div>

    <button type="button" class="mt-5 text-sm font-semibold text-indigo-600 hover:text-indigo-900" @click="addOption" x-show="questionType !== 'true_false'">
        + Add New Choice
    </button>

    <x-input-error class="mt-2" :messages="$errors->get('options')" />
    @foreach ($errors->get('options.*.text') as $messages)
        <x-input-error class="mt-2" :messages="$messages" />
    @endforeach
    @foreach ($errors->get('options.*.match_text') as $messages)
        <x-input-error class="mt-2" :messages="$messages" />
    @endforeach
    @foreach ($errors->get('options.*.image') as $messages)
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
