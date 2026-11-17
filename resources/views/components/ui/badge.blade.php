@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => 'border-[#CFE7DA] bg-success-bg text-success',
        'warning' => 'border-[#F0DFC0] bg-warning-bg text-warning',
        'danger' => 'border-[#F2D3CF] bg-danger-bg text-danger',
        'info' => 'border-[#D5E3F4] bg-info-bg text-info',
        'neutral' => 'border-border-1 bg-surface-2 text-ink-500',
    ];

    $toneClasses = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-micro font-semibold '.$toneClasses]) }}>
    <span class="size-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
    {{ $slot }}
</span>
