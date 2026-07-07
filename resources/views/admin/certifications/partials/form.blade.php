<div>
    <x-input-label for="name" value="Certification Name" icon="tag" />
    <x-text-input
        id="name"
        name="name"
        type="text"
        class="mt-1 block w-full"
        :value="old('name', $certification?->name)"
        required
        autofocus
        placeholder="PMP, PMI-ACP, CBAP"
    />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="description" value="Description" icon="file-text" />
    <textarea
        id="description"
        name="description"
        rows="5"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        placeholder="Describe who this certification track is for."
    >{{ old('description', $certification?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div>
    <x-input-label for="image" value="Certification Image" icon="image" />

    @if ($certification?->imageUrl())
        <div class="mt-2 rounded-md border border-gray-200 p-3">
            <img src="{{ $certification->imageUrl() }}" alt="Current certification image" class="max-h-48 rounded-md object-contain">

            <label class="mt-3 flex items-center text-sm text-gray-600">
                <input
                    id="remove_image"
                    name="remove_image"
                    type="checkbox"
                    value="1"
                    @checked(old('remove_image'))
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span class="ms-2 inline-flex items-center gap-1.5">
                    <x-icon name="trash" class="h-3.5 w-3.5 text-gray-400" />
                    Remove current image
                </span>
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

<div class="flex items-center">
    <input
        id="is_active"
        name="is_active"
        type="checkbox"
        value="1"
        @checked(old('is_active', $certification?->is_active ?? true))
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    >
    <label for="is_active" class="ms-2 inline-flex items-center gap-1.5 text-sm text-gray-600">
        <x-icon name="check-circle" class="h-3.5 w-3.5 text-gray-400" />
        Active
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
</div>
