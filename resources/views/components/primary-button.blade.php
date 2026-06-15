<button {{ $attributes->merge(['type' => 'submit', 'class' => 'portal-button-primary focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
