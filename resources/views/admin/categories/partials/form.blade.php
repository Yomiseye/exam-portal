<div>
    <x-input-label for="name" value="Name" icon="tag" />
    <x-text-input
        id="name"
        name="name"
        type="text"
        class="mt-1 block w-full"
        :value="old('name', $category?->name)"
        required
        autofocus
    />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="certification_id" value="Certification" icon="award" />
    <select id="certification_id" name="certification_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">General question bank</option>
        @foreach ($certifications as $certification)
            <option value="{{ $certification->id }}" @selected((string) old('certification_id', $category?->certification_id) === (string) $certification->id)>
                {{ $certification->name }} ({{ $certification->classificationProfileLabel() }})
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-sm text-gray-500">Used only for a top-level category. Subcategories inherit their parent question bank's certification.</p>
    <x-input-error class="mt-2" :messages="$errors->get('certification_id')" />
</div>

<div>
    <x-input-label for="parent_id" value="Parent Category" icon="tag" />
    <select id="parent_id" name="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        <option value="">No parent category</option>
        @foreach ($parentCategories as $parentCategory)
            <option value="{{ $parentCategory->id }}" @selected((string) old('parent_id', $category?->parent_id) === (string) $parentCategory->id)>
                {{ $parentCategory->fullName() }}
            </option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
</div>

<div>
    <x-input-label for="description" value="Description" icon="file-text" />
    <textarea
        id="description"
        name="description"
        rows="4"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
    >{{ old('description', $category?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="flex items-center">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $category?->is_active ?? true))
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    <label for="is_active" class="ms-2 inline-flex items-center gap-1.5 text-sm text-gray-600">
        <x-icon name="check-circle" class="h-3.5 w-3.5 text-gray-400" />
        Active
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
</div>
