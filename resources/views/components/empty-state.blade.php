@props([
    'icon' => 'circle-help',
    'title',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-10 text-center']) }}>
    <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>
    <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 max-w-md text-sm text-gray-500">{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
