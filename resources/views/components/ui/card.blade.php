@props(['title' => null, 'eyebrow' => null])

<section {{ $attributes->merge(['class' => 'rounded-md border border-border-1 bg-surface-0 shadow-1']) }}>
    @if ($title !== null || $eyebrow !== null || isset($header))
        <header class="flex items-center justify-between gap-3 border-b border-border-1 px-[18px] py-3.5">
            <div>
                @if ($eyebrow !== null)
                    <p class="mb-1 text-micro font-bold tracking-wide text-accent-600">{{ $eyebrow }}</p>
                @endif

                @if ($title !== null)
                    <h3 class="m-0 text-h3 font-semibold text-ink-900">{{ $title }}</h3>
                @endif
            </div>

            {{ $header ?? '' }}
        </header>
    @endif

    <div class="p-[18px]">
        {{ $slot }}
    </div>
</section>
