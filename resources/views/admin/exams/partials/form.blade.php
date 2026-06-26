@php
    $selectedCategories = collect(old('category_ids', $exam?->categories->pluck('id')->all() ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div>
    <x-input-label for="title" value="Title" icon="clipboard-list" />
    <x-text-input
        id="title"
        name="title"
        type="text"
        class="mt-1 block w-full"
        :value="old('title', $exam?->title)"
        required
        autofocus
    />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="description" value="Description" icon="file-text" />
    <textarea
        id="description"
        name="description"
        rows="4"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
    >{{ old('description', $exam?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="grid gap-6 md:grid-cols-3">
    <div>
        <x-input-label for="duration_minutes" value="Duration (minutes)" icon="timer" />
        <x-text-input
            id="duration_minutes"
            name="duration_minutes"
            type="number"
            min="1"
            class="mt-1 block w-full"
            :value="old('duration_minutes', $exam?->duration_minutes)"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('duration_minutes')" />
    </div>

    <div>
        <x-input-label for="total_questions" value="Total Questions" icon="circle-help" />
        <x-text-input
            id="total_questions"
            name="total_questions"
            type="number"
            min="1"
            class="mt-1 block w-full"
            :value="old('total_questions', $exam?->total_questions)"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('total_questions')" />
    </div>

    <div>
        <x-input-label for="pass_mark" value="Pass Mark (%)" icon="chart-bar" />
        <x-text-input
            id="pass_mark"
            name="pass_mark"
            type="number"
            min="0"
            max="100"
            class="mt-1 block w-full"
            :value="old('pass_mark', $exam?->pass_mark)"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('pass_mark')" />
    </div>
</div>

<div>
    <x-input-label value="Categories" icon="tag" />
    <div class="mt-3 grid gap-3 md:grid-cols-2">
        @forelse ($categories as $category)
            <label class="flex items-start rounded-md border border-gray-200 p-3">
                <input
                    type="checkbox"
                    name="category_ids[]"
                    value="{{ $category->id }}"
                    @checked(in_array((string) $category->id, $selectedCategories, true))
                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-3">
                    <span class="block text-sm font-medium text-gray-900">{{ $category->fullName() }}</span>
                    @if ($category->description)
                        <span class="block text-sm text-gray-500">{{ $category->description }}</span>
                    @endif
                </span>
            </label>
        @empty
            <x-empty-state
                class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6"
                icon="tag"
                title="No active categories"
                message="Create an active category before creating an exam."
            />
        @endforelse
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('category_ids')" />
    <x-input-error class="mt-2" :messages="$errors->get('category_ids.*')" />
</div>

<div class="grid gap-4 md:grid-cols-4">
    <label class="flex items-center">
        <input
            name="is_randomized"
            type="checkbox"
            value="1"
            @checked(old('is_randomized', $exam?->is_randomized ?? true))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <span class="ms-2 text-sm text-gray-600">Randomize questions</span>
    </label>

    <label class="flex items-center">
        <input
            name="show_corrections"
            type="checkbox"
            value="1"
            @checked(old('show_corrections', $exam?->show_corrections ?? false))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <span class="ms-2 text-sm text-gray-600">Show corrections after submission</span>
    </label>

    <label class="flex items-center">
        <input
            name="allow_pause"
            type="checkbox"
            value="1"
            @checked(old('allow_pause', $exam?->allow_pause ?? false))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <span class="ms-2 text-sm text-gray-600">Allow pause and resume</span>
    </label>

    <label class="flex items-center">
        <input
            name="is_active"
            type="checkbox"
            value="1"
            @checked(old('is_active', $exam?->is_active ?? true))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <span class="ms-2 text-sm text-gray-600">Active</span>
    </label>
</div>
