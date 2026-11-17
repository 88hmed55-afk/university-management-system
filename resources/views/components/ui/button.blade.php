@props([
    'variant' => 'secondary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'border-brand-800 bg-brand-800 text-white hover:bg-brand-700',
        'secondary' => 'border-border-2 bg-surface-0 text-ink-700 hover:bg-surface-2',
        'ghost' => 'border-transparent bg-transparent text-brand-700 hover:bg-surface-2',
        'danger' => 'border-danger bg-danger text-white hover:brightness-95',
    ];

    $sizes = [
        'sm' => 'px-2.5 py-1 text-small',
        'md' => 'px-3.5 py-2 text-small',
        'lg' => 'px-5 py-2.5 text-body',
    ];

    $variantClasses = $variants[$variant] ?? $variants['secondary'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 rounded-sm border font-semibold leading-relaxed transition-colors disabled:cursor-not-allowed disabled:opacity-50 '.$variantClasses.' '.$sizeClasses]) }}
>{{ $slot }}</button>
