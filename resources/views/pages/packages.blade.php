<x-filament-panels::page>
    @php
        $mods = $context['kind'] === 'mods';
        $sites = ['modrinth' => 'Modrinth', 'hangar' => 'Hangar', 'spigot' => 'SpigotMC', 'curseforge' => 'CurseForge'];
    @endphp

    @if ($unreachable)
        <p class="text-sm text-danger-600 dark:text-danger-400">{{ __('simplify-mods::packages.unreachable') }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <x-filament::section class="lg:col-span-3" :heading="__('simplify-mods::packages.find')" :description="$this->ready() ? __('simplify-mods::packages.for', ['loader' => ucfirst($context['loader']), 'version' => $context['minecraft']]) : null">
            @if (! $this->ready())
                <div class="flex flex-col items-start gap-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('simplify-mods::packages.unknown_software') }}</p>
                    {{ $this->chooseAction }}
                </div>
            @else
                <x-slot name="afterHeader">
                    @unless ($context['detected'])
                        {{ ($this->chooseAction)->color('gray')->size('sm') }}
                    @endunless
                </x-slot>
                <x-filament::input.wrapper prefix-icon="tabler-search">
                    <x-filament::input type="search" wire:model.live.debounce.400ms="query" :placeholder="__('simplify-mods::packages.search_placeholder')" />
                </x-filament::input.wrapper>

                @if ($offline)
                    <p class="mt-3 text-xs text-warning-600 dark:text-warning-400">{{ __('simplify-mods::packages.offline', ['sites' => implode(', ', $offline)]) }}</p>
                @endif

                <ul class="mt-4 divide-y divide-gray-200 dark:divide-white/10" wire:loading.class="opacity-60" wire:target="query">
                    @forelse ($results as $card)
                        <li class="flex items-start gap-3 py-3" wire:key="card-{{ $card['key'] }}">
                            @if ($card['icon'])
                                <img src="{{ $card['icon'] }}" alt="" loading="lazy" class="h-10 w-10 flex-none rounded-md object-cover">
                            @else
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-md bg-gray-100 dark:bg-white/5">
                                    <x-filament::icon icon="tabler-puzzle" class="h-5 w-5 text-gray-400" />
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <a href="{{ $card['page'] }}" target="_blank" rel="noopener" class="truncate font-medium text-gray-950 hover:underline dark:text-white">{{ $card['title'] }}</a>
                                    <x-filament::badge size="sm" color="gray">{{ $card['label'] }}</x-filament::badge>
                                    @unless ($card['fits'])
                                        <x-filament::badge size="sm" color="warning">{{ __('simplify-mods::packages.other_version') }}</x-filament::badge>
                                    @endunless
                                </div>
                                <p class="mt-0.5 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $card['description'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    @if ($card['author']){{ $card['author'] }} · @endif{{ __('simplify-mods::packages.downloads', ['count' => \Illuminate\Support\Number::abbreviate($card['downloads'])]) }}
                                    @foreach ($card['also'] as $also)
                                        · <a href="{{ $also['page'] }}" target="_blank" rel="noopener" class="hover:underline">{{ __('simplify-mods::packages.also_on', ['site' => $also['label']]) }}</a>
                                    @endforeach
                                </p>
                            </div>
                            <div class="flex-none">
                                @if ($this->isInstalled($card))
                                    <x-filament::badge color="success" icon="tabler-check">{{ __('simplify-mods::packages.is_installed') }}</x-filament::badge>
                                @else
                                    {{ ($this->installAction)(['key' => $card['key'], 'fits' => $card['fits']]) }}
                                @endif
                            </div>
                        </li>
                    @empty
                        @if ($searched)
                            <li class="py-6 text-center text-sm text-gray-500">{{ __('simplify-mods::packages.nothing_found') }}</li>
                        @endif
                    @endforelse
                </ul>
            @endif
        </x-filament::section>

        <div class="lg:col-span-2" @if ($watching) wire:poll.15s="checkStart" @endif>
            <x-filament::section :heading="__('simplify-mods::packages.'.($mods ? 'installed_mods' : 'installed_plugins'))" :description="__('simplify-mods::packages.restart_help')">
                <ul class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($installed as $item)
                        <li class="flex items-center gap-3 py-2" wire:key="file-{{ $item['file'] }}">
                            <div @class(['min-w-0 flex-1', 'opacity-50' => ! $item['enabled']])>
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                    @if ($item['page'])
                                        <a href="{{ $item['page'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $item['title'] }}</a>
                                    @else
                                        {{ $item['title'] }}
                                    @endif
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $item['version'] ?: $item['file'] }}
                                    · {{ $item['source'] ? ($sites[$item['source']] ?? $item['source']) : __('simplify-mods::packages.by_hand') }}
                                    @unless ($item['enabled'])
                                        · {{ __('simplify-mods::packages.off') }}
                                    @endunless
                                    @if ($item['watched'])
                                        · {{ __('simplify-mods::packages.watched') }}
                                    @endif
                                </p>
                                @if ($item['off_reason'])
                                    <p class="mt-0.5 line-clamp-2 text-xs text-warning-600 dark:text-warning-400">{{ __('simplify-mods::packages.off_after_start') }}: {{ $item['off_reason'] }}</p>
                                @endif
                            </div>
                            <div class="flex flex-none items-center">
                                @if ($item['off_reason'])
                                    {{ ($this->offLogAction)(['file' => $item['file']]) }}
                                @endif
                                {{ ($this->toggleAction)(['file' => $item['file'], 'enabled' => $item['enabled']]) }}
                                {{ ($this->removeAction)(['file' => $item['file']]) }}
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-gray-500">{{ __('simplify-mods::packages.'.($mods ? 'no_mods' : 'no_plugins')) }}</li>
                    @endforelse
                </ul>
            </x-filament::section>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
