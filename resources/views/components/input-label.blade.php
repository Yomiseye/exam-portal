@props(['value', 'icon' => null])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    @if ($icon)
        <span class="inline-flex items-center gap-1.5">
            <x-icon :name="$icon" class="h-3.5 w-3.5 text-gray-400" />
            <span>{{ $value ?? $slot }}</span>
        </span>
    @else
        {{ $value ?? $slot }}
    @endif
</label>
