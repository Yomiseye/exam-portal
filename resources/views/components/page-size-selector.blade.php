@props(['label' => 'Rows'])

<form method="GET" {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @foreach (request()->except(['page', 'per_page']) as $name => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <label for="per_page_{{ md5(request()->path()) }}" class="text-sm font-medium text-gray-600">{{ $label }}</label>
    <select
        id="per_page_{{ md5(request()->path()) }}"
        name="per_page"
        class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        onchange="this.form.submit()"
    >
        @foreach ([10, 25, 50, 100] as $size)
            <option value="{{ $size }}" @selected((int) request()->session()->get('admin_per_page', 10) === $size)>
                {{ $size }}
            </option>
        @endforeach
    </select>
</form>
