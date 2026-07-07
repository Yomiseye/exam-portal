@php
    $selectedExamIds = collect(old('exam_ids', $package?->exams->pluck('id')->all() ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Package Name" icon="clipboard-list" />
        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $package?->name)"
            required
            autofocus
            placeholder="Practice Essentials"
        />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="badge" value="Badge" icon="tag" />
        <x-text-input
            id="badge"
            name="badge"
            type="text"
            class="mt-1 block w-full"
            :value="old('badge', $package?->badge)"
            placeholder="Recommended, Best Value"
        />
        <x-input-error class="mt-2" :messages="$errors->get('badge')" />
    </div>
</div>

<div>
    <x-input-label for="description" value="Description" icon="file-text" />
    <textarea
        id="description"
        name="description"
        rows="4"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        placeholder="Describe who this package is designed for."
    >{{ old('description', $package?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="grid gap-6 md:grid-cols-3">
    <div>
        <x-input-label for="price" value="Price" icon="chart-bar" />
        <x-text-input
            id="price"
            name="price"
            type="number"
            min="0"
            step="0.01"
            class="mt-1 block w-full"
            :value="old('price', $package?->price)"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>

    <div>
        <x-input-label for="duration_days" value="Duration (days)" icon="calendar-days" />
        <x-text-input
            id="duration_days"
            name="duration_days"
            type="number"
            min="1"
            class="mt-1 block w-full"
            :value="old('duration_days', $package?->duration_days)"
            required
        />
        <x-input-error class="mt-2" :messages="$errors->get('duration_days')" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Sort Order" icon="filter" />
        <x-text-input
            id="sort_order"
            name="sort_order"
            type="number"
            min="0"
            class="mt-1 block w-full"
            :value="old('sort_order', $package?->sort_order ?? 0)"
        />
        <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
    </div>
</div>

<div>
    <div class="flex items-center justify-between">
        <x-input-label value="Included Exams" icon="clipboard-list" />
        <span class="text-sm text-gray-500">Select exams included in this package.</span>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2">
        @forelse ($exams as $exam)
            <label class="flex items-start rounded-md border border-gray-200 p-3">
                <input
                    type="checkbox"
                    name="exam_ids[]"
                    value="{{ $exam->id }}"
                    @checked(in_array((string) $exam->id, $selectedExamIds, true))
                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-3">
                    <span class="block text-sm font-medium text-gray-900">{{ $exam->title }}</span>
                    <span class="block text-xs text-gray-500">
                        {{ $exam->duration_minutes }} min &middot; {{ $exam->total_questions }} questions &middot; {{ $exam->pass_mark }}%
                    </span>
                </span>
            </label>
        @empty
            <x-empty-state
                class="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6 md:col-span-2"
                icon="clipboard-list"
                title="No active exams"
                message="Create and activate exams before assigning them to a package."
            />
        @endforelse
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('exam_ids')" />
    <x-input-error class="mt-2" :messages="$errors->get('exam_ids.*')" />
</div>

<div class="flex items-center">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $package?->is_active ?? true))
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    <label for="is_active" class="ms-2 inline-flex items-center gap-1.5 text-sm text-gray-600">
        <x-icon name="check-circle" class="h-3.5 w-3.5 text-gray-400" />
        Active
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
</div>
