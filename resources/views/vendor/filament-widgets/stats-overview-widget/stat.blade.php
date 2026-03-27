@php
    use Filament\Support\Enums\IconPosition;

    $descriptionIcon     = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url      = $getUrl();
    $tag      = $url ? 'a' : 'div';
    $statColor = $getColor() ?? 'gray';
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat relative rounded-xl p-6 shadow-sm ring-1 ring-inset ring-white/10 overflow-hidden',
                $url ? 'transition hover:brightness-110 cursor-pointer' : '',
                match ($statColor) {
                    'gray' => 'bg-gray-500 dark:bg-gray-600',
                    default => 'fi-color-custom bg-custom-600 dark:bg-custom-600',
                },
                is_string($statColor) ? "fi-color-{$statColor}" : null,
            ])
            ->style([
                \Filament\Support\get_color_css_variables(
                    $statColor,
                    shades: [50, 400, 500, 600],
                    alias: 'widgets::stats-overview-widget.stat',
                ) => $statColor !== 'gray',
            ])
    }}
>
    {{-- Subtle decorative blob --}}
    <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-white opacity-[0.07]"></div>

    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="fi-wi-stats-overview-stat-label text-sm font-medium text-white/70 truncate">
                {{ $getLabel() }}
            </p>

            <p class="fi-wi-stats-overview-stat-value mt-2 text-3xl font-bold tracking-tight text-white">
                {{ $getValue() }}
            </p>

            @if ($description = $getDescription())
                <div class="mt-2 flex items-center gap-x-1">
                    @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                        <x-filament::icon
                            :icon="$descriptionIcon"
                            class="fi-wi-stats-overview-stat-description-icon h-4 w-4 shrink-0 text-white/60"
                        />
                    @endif

                    <p class="fi-wi-stats-overview-stat-description text-sm text-white/60 truncate">
                        {{ $description }}
                    </p>

                    @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                        <x-filament::icon
                            :icon="$descriptionIcon"
                            class="fi-wi-stats-overview-stat-description-icon h-4 w-4 shrink-0 text-white/60"
                        />
                    @endif
                </div>
            @endif
        </div>

        @if ($icon = $getIcon())
            <div class="shrink-0 rounded-lg bg-white/15 p-2.5">
                <x-filament::icon
                    :icon="$icon"
                    class="fi-wi-stats-overview-stat-icon h-6 w-6 text-white"
                />
            </div>
        @endif
    </div>
</{!! $tag !!}>
