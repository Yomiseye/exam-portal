<button {{ $attributes->merge(['type' => 'button', 'class' => 'portal-button-secondary focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:opacity-40']) }}>
    {{ $slot }}
</button>
