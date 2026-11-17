@props([
    'label' => '',
    'for' => null,
    'required' => false,
    'help' => null,
    'error' => null,
])

<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    @if ($label !== '')
        <label @if ($for !== null) for="{{ $for }}" @endif class="mb-1.5 block text-small font-semibold text-ink-700">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($help !== null)
        <p class="mt-1.5 text-micro text-ink-500">{{ $help }}</p>
    @endif

    @if ($error !== null)
        <p class="mt-1.5 text-small font-semibold text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
