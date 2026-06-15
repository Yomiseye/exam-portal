<button {{ $attributes->merge(['type' => 'submit', 'class' => 'portal-button-danger focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
