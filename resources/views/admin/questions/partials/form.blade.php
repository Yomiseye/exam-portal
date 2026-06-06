@php
    $optionRows = old('options');

    if (! $optionRows) {
        $optionRows = $question?->options
            ->map(fn ($option) => ['text' => $option->option_text])
            ->values()
            ->all() ?? [
                ['text' => ''],
                ['text' => ''],
                ['text' => ''],
                ['text' => ''],
            ];
    }

    $correctOption = old('correct_option');

    if ($correctOption === null && $question) {
        $correctOption = $question->options->values()->search(fn ($option) => $option->is_correct);
    }
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="category_id" value="Category" />
        <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            <option value="">Choose category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $question?->category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
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

<div
    x-data="{
        options: @js($optionRows),
        correctOption: @js($correctOption === false ? null : $correctOption),
        addOption() {
            this.options.push({ text: '' });
        },
        removeOption(index) {
            if (this.options.length <= 2) {
                return;
            }

            this.options.splice(index, 1);

            if (this.correctOption === index) {
                this.correctOption = null;
            } else if (this.correctOption > index) {
                this.correctOption--;
            }
        },
    }"
>
    <div class="flex items-center justify-between">
        <x-input-label value="Options" />
        <p class="text-sm text-gray-500">Select one correct answer.</p>
    </div>

    <div class="mt-3 space-y-3">
        <template x-for="(option, index) in options" :key="index">
            <div class="flex items-start gap-3">
                <input
                    type="radio"
                    name="correct_option"
                    :value="index"
                    x-model.number="correctOption"
                    class="mt-3 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    required
                >

                <div class="flex-1">
                    <input
                        :name="'options[' + index + '][text]'"
                        type="text"
                        x-model="option.text"
                        :placeholder="'Option ' + (index + 1)"
                        required
                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    />
                </div>

                <button
                    type="button"
                    class="mt-2 text-sm font-medium text-red-600 hover:text-red-900 disabled:cursor-not-allowed disabled:text-gray-300"
                    @click="removeOption(index)"
                    :disabled="options.length <= 2"
                >
                    Remove
                </button>
            </div>
        </template>
    </div>

    <button type="button" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-900" @click="addOption">
        Add another option
    </button>

    <x-input-error class="mt-2" :messages="$errors->get('options')" />
    @foreach ($errors->get('options.*.text') as $messages)
        <x-input-error class="mt-2" :messages="$messages" />
    @endforeach
    <x-input-error class="mt-2" :messages="$errors->get('correct_option')" />
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
