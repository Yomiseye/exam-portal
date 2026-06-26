<div>
    <x-input-label for="name" value="Group Name" icon="users-round" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $group?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="description" value="Description" icon="file-text" />
    <textarea
        id="description"
        name="description"
        rows="4"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >{{ old('description', $group?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="flex items-center">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $group?->is_active ?? true))
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    <label for="is_active" class="ms-2 inline-flex items-center gap-1.5 text-sm text-gray-600">
        <x-icon name="check-circle" class="h-3.5 w-3.5 text-gray-400" />
        Active
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
</div>
